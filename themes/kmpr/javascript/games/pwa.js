/**
 * KMPR Arcade app layer: service worker (scope /games/), update toast, install encouragement
 * (beforeinstallprompt + iOS sheet), app-bar back button, standalone tweaks, hover/touch prefetch.
 */
import { esc } from './lib/util.js';

const BASE = (() => {
	const m = location.pathname.match(/^(.*?\/games\/)/);
	return m ? m[1] : '/games/';
})();
const REST = ((window.kmprGames || {}).rest || '/wp-json/kmpr/v1/games').replace(/\/$/, '');
const KEY = 'kmpr_pwa';
const SNOOZE_DAYS = 7;
const html = document.documentElement;

const mm = (q) => window.matchMedia && window.matchMedia(q).matches;
export const isStandalone = () => mm('(display-mode: standalone)') || mm('(display-mode: window-controls-overlay)') || mm('(display-mode: fullscreen)') || window.navigator.standalone === true;
const isIOS = () => /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
const isIOSSafari = () => isIOS() && /safari/i.test(navigator.userAgent) && !/crios|fxios|edgios|opios/i.test(navigator.userAgent);

const read = () => {
	try {
		return JSON.parse(localStorage.getItem(KEY) || '{}') || {};
	} catch (e) {
		return {};
	}
};
const write = (d) => {
	try {
		localStorage.setItem(KEY, JSON.stringify(d));
	} catch (e) {
		/* ignore */
	}
};
const stat = (event) => {
	try {
		fetch(`${REST}/pwa-stat`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ event }), keepalive: true }).catch(() => {});
	} catch (e) {
		/* ignore */
	}
};

const ICON = (window.kmprGames || {}).appIcon || '';
const SHARE_SVG = '<svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M8 7l4-4 4 4"/><path d="M5 11v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8"/></svg>';
const PLUS_SVG = '<svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M12 8v8M8 12h8"/></svg>';

const onHub = () => !!document.querySelector('[data-arcade-hub]');
let deferred = null;
let shownThisSession = false;

/* ---------------- install sheet ---------------- */
function sheet(kind) {
	document.querySelector('[data-pwa-sheet]')?.remove();
	const perks = ['بازی سریع‌تر؛ بدون نوار مرورگر', 'تمام‌صفحه، درست مثل یک اپ', 'آفلاین برای بازی‌های تک‌نفره'];
	const body =
		kind === 'ios'
			? `<ol class="pwa-steps">
				<li><span class="pwa-steps__n">۱</span><span>در نوار پایین سافاری روی دکمه <b>اشتراک‌گذاری</b> بزنید</span><span class="pwa-steps__i">${SHARE_SVG}</span></li>
				<li><span class="pwa-steps__n">۲</span><span>گزینه <b>Add to Home Screen</b> (افزودن به صفحه اصلی) را انتخاب کنید</span><span class="pwa-steps__i">${PLUS_SVG}</span></li>
				<li><span class="pwa-steps__n">۳</span><span>بالای صفحه روی <b>Add</b> بزنید؛ آیکون KMPR Arcade کنار بقیه اپ‌ها می‌نشیند</span><span class="pwa-steps__i">✓</span></li>
			</ol>
			<div class="pwa-actions"><button type="button" class="pwa-btn pwa-btn--ghost" data-pwa="later">بعداً</button><button type="button" class="pwa-btn" data-pwa="close">متوجه شدم</button></div>`
			: kind === 'manual'
				? `<p class="pwa-text">مرورگر شما دکمه نصب مستقیم ندارد؛ از منوی مرورگر گزینه «Install app» یا «Add to Home screen» را بزنید.</p>
			<div class="pwa-actions"><button type="button" class="pwa-btn" data-pwa="close">باشه</button></div>`
				: `<ul class="pwa-perks">${perks.map((p) => `<li><span aria-hidden="true">✦</span>${esc(p)}</li>`).join('')}</ul>
			<div class="pwa-actions"><button type="button" class="pwa-btn pwa-btn--ghost" data-pwa="later">بعداً</button><button type="button" class="pwa-btn" data-pwa="install">نصب اپ</button></div>`;
	const el = document.createElement('div');
	el.className = 'pwa-sheet';
	el.setAttribute('data-pwa-sheet', kind);
	el.innerHTML = `<div class="pwa-sheet__backdrop" data-pwa="later"></div>
		<section class="pwa-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="pwa-title">
			<span class="pwa-sheet__grip" aria-hidden="true"></span>
			<div class="pwa-head">
				${ICON ? `<img src="${esc(ICON)}" width="56" height="56" alt="" class="pwa-head__icon">` : ''}
				<div><p id="pwa-title" class="pwa-head__title">KMPR Arcade را نصب کنید</p><p class="pwa-head__sub">${kind === 'ios' ? 'روی آیفون و آیپد در سه قدم' : 'گیم‌سنتر روی صفحه اصلی گوشی یا دسکتاپ'}</p></div>
			</div>
			${body}
		</section>`;
	document.body.appendChild(el);
	requestAnimationFrame(() => el.classList.add('is-open'));
	const close = () => {
		el.classList.remove('is-open');
		setTimeout(() => el.remove(), 300);
	};
	el.addEventListener('click', async (e) => {
		const b = e.target.closest('[data-pwa]');
		if (!b) return;
		const act = b.dataset.pwa;
		if (act === 'later') {
			const d = read();
			d.snooze = Date.now() + SNOOZE_DAYS * 86400000;
			write(d);
			stat('dismissed');
			close();
		} else if (act === 'close') close();
		else if (act === 'install' && deferred) {
			close();
			deferred.prompt();
			const choice = await deferred.userChoice.catch(() => ({ outcome: 'dismissed' }));
			stat(choice.outcome === 'accepted' ? 'accepted' : 'dismissed');
			if (choice.outcome !== 'accepted') {
				const d = read();
				d.snooze = Date.now() + SNOOZE_DAYS * 86400000;
				write(d);
			}
			deferred = null;
			syncButtons();
		}
	});
	el.addEventListener('keydown', (e) => e.key === 'Escape' && close());
	el.querySelector('.pwa-btn:last-child')?.focus({ preventScroll: true });
	if (kind === 'ios') stat('ios_sheet');
}

const installable = () => !isStandalone() && !read().installed && (deferred || isIOSSafari());

function syncButtons() {
	const can = installable();
	const hub = !!document.querySelector('[data-arcade-hub]');
	document.querySelectorAll('[data-gh-install]').forEach((b) => (b.hidden = !(can && hub)));
	document.querySelectorAll('[data-gh-install-menu]').forEach((b) => (b.hidden = !can));
}

function openInstall() {
	if (deferred) sheet('prompt');
	else if (isIOSSafari()) sheet('ios');
	else sheet('manual');
}

function maybeEncourage() {
	if (shownThisSession || !installable()) return;
	const d = read();
	if (d.snooze && Date.now() < d.snooze) return;
	if ((d.games || 0) < 2 && (d.visits || 0) < 2) return;
	// Never interrupt an active game.
	if (html.classList.contains('game-focus')) return;
	shownThisSession = true;
	setTimeout(() => sheet(deferred ? 'prompt' : 'ios'), 1200);
}

/* ---------------- service worker ---------------- */
function updateToast(worker) {
	if (document.querySelector('[data-pwa-update]')) return;
	const t = document.createElement('div');
	t.className = 'pwa-update';
	t.setAttribute('data-pwa-update', '');
	t.setAttribute('role', 'status');
	t.innerHTML = '<span>نسخه جدید آماده است</span><button type="button" class="pwa-btn pwa-btn--sm">بروزرسانی</button>';
	t.querySelector('button').addEventListener('click', () => {
		window.__kmprReload = true;
		worker.postMessage({ type: 'SKIP_WAITING' });
		t.remove();
	});
	document.body.appendChild(t);
}

function registerSW() {
	if (!('serviceWorker' in navigator) || !window.isSecureContext) return;
	navigator.serviceWorker
		.register(`${BASE}sw.js`, { scope: BASE })
		.then((reg) => {
			if (reg.waiting && navigator.serviceWorker.controller) updateToast(reg.waiting);
			reg.addEventListener('updatefound', () => {
				const w = reg.installing;
				if (!w) return;
				w.addEventListener('statechange', () => {
					if (w.state === 'installed' && navigator.serviceWorker.controller) updateToast(w);
				});
			});
		})
		.catch(() => {});
	navigator.serviceWorker.addEventListener('controllerchange', () => {
		if (window.__kmprReload) {
			window.__kmprReload = false;
			location.reload();
		}
	});
}

/* ---------------- app navigation ---------------- */
function appNav() {
	// In-app history depth (game-center pages only).
	let depth = 0;
	try {
		const ref = document.referrer ? new URL(document.referrer) : null;
		const inApp = ref && ref.origin === location.origin && ref.pathname.startsWith(BASE);
		depth = inApp ? Number(sessionStorage.getItem('kmpr_app_depth') || 0) + 1 : 0;
		if (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]?.type === 'back_forward') depth = Math.max(0, depth - 2);
		sessionStorage.setItem('kmpr_app_depth', String(depth));
	} catch (e) {
		/* ignore */
	}
	document.querySelectorAll('[data-gh-appback]').forEach((b) =>
		b.addEventListener('click', () => {
			if (depth > 0 && history.length > 1) history.back();
			else location.href = b.dataset.home || BASE;
		})
	);
	// Overflow menu: close on outside click / Esc.
	const more = document.querySelector('[data-gh-more]');
	if (more) {
		document.addEventListener('click', (e) => more.open && !more.contains(e.target) && (more.open = false));
		document.addEventListener('keydown', (e) => e.key === 'Escape' && (more.open = false));
	}
	document.querySelectorAll('[data-gh-install], [data-gh-install-menu]').forEach((b) =>
		b.addEventListener('click', () => {
			if (more) more.open = false;
			openInstall();
		})
	);
	// Installed app: links leaving the game center open in the browser instead of the app window.
	document.addEventListener('click', (e) => {
		if (!isStandalone() || e.defaultPrevented) return;
		const a = e.target.closest('a[href]');
		if (!a || a.target === '_blank') return;
		const u = new URL(a.href, location.href);
		if (u.origin === location.origin && u.pathname.startsWith(BASE)) return;
		if (!/^https?:$/.test(u.protocol)) return;
		e.preventDefault();
		window.open(u.href, '_blank', 'noopener');
	});
}

/* ---------------- prefetch ---------------- */
function prefetch() {
	const done = new Set([location.pathname]);
	const go = (e) => {
		const a = e.target.closest && e.target.closest('a[href]');
		if (!a) return;
		const u = new URL(a.href, location.href);
		if (u.origin !== location.origin || !u.pathname.startsWith(BASE) || done.has(u.pathname) || u.pathname.endsWith('.js')) return;
		done.add(u.pathname);
		const l = document.createElement('link');
		l.rel = 'prefetch';
		l.href = u.pathname;
		l.as = 'document';
		document.head.appendChild(l);
	};
	document.addEventListener('pointerover', go, { passive: true });
	document.addEventListener('touchstart', go, { passive: true });
}

/* ---------------- page loader: only when a navigation is slow (> 150ms) ---------------- */
function pageLoader() {
	let t = 0;
	const show = () => {
		if (document.querySelector('.kl-page')) return;
		import('./lib/loader.js').then(({ loaderHTML }) => {
			const d = document.createElement('div');
			d.className = 'kl-page';
			d.innerHTML = loaderHTML();
			document.body.appendChild(d);
		});
	};
	document.addEventListener('click', (e) => {
		const a = e.target.closest && e.target.closest('a[href]');
		if (!a || e.defaultPrevented || e.button || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || a.target === '_blank' || a.hasAttribute('download')) return;
		const u = new URL(a.href, location.href);
		if (u.origin !== location.origin || (u.pathname === location.pathname && u.hash) || u.pathname.startsWith('/wp-')) return;
		clearTimeout(t);
		t = setTimeout(show, 150);
	});
	// Back/forward cache restore: never leave a loader on screen.
	window.addEventListener('pageshow', () => {
		clearTimeout(t);
		document.querySelectorAll('.kl-page').forEach((n) => n.remove());
	});
}


/* ---------------- device player: ask a nickname once, then keep it on this device ---------------- */
async function devicePlayer() {
	const [{ store }, { online }, { avatarSvg, AVATAR_STYLES, AVATAR_HUES }] = await Promise.all([import('./lib/store.js'), import('./lib/api.js'), import('./lib/avatar.js')]);
	const startPresence = () => {
		const shell = document.querySelector('[data-game-shell]');
		const where = shell ? shell.dataset.engine : 'hub';
		let t = 0;
		const beat = async () => {
			clearTimeout(t);
			if (document.hidden) return;
			const p = store.player;
			try {
				const res = await online.presence({ player_id: p.id, nickname: p.nickname, avatar: p.avatar || {}, where });
				if (res && res.ok) {
					window.kmprPresence = res;
					window.dispatchEvent(new CustomEvent('kmpr:presence', { detail: res }));
					document.querySelectorAll('.gh__nav [data-online-count], .gdock [data-online-count]').forEach((el) => {
						el.textContent = Number(res.count || 0).toLocaleString('fa-IR');
						el.hidden = false;
					});
					if (where !== 'hub') {
						const n = (res.online || []).filter((u) => u.where === where).length;
						document.querySelectorAll('[data-here]').forEach((el) => (el.textContent = n.toLocaleString('fa-IR')));
						document.querySelectorAll('[data-here-wrap]').forEach((el) => (el.hidden = n < 1));
					}
				}
			} catch (e) {
				/* offline: ignore */
			}
			t = setTimeout(beat, 25000);
		};
		document.addEventListener('visibilitychange', () => !document.hidden && beat());
		beat();
	};
	if (store.player.nickname) return startPresence();

	const styles = AVATAR_STYLES.map((x) => (typeof x === 'string' ? x : x.id));
	let av = { style: styles[0], hue: AVATAR_HUES[Math.floor(Math.random() * AVATAR_HUES.length)] };
	const d = document.createElement('dialog');
	d.className = 'gc-hello';
	d.setAttribute('aria-labelledby', 'gc-hello-t');
	const avatars = () => styles.map((st) => `<button type="button" class="gc-hello__av" data-st="${esc(st)}" aria-pressed="${String(st === av.style)}" aria-label="آواتار ${esc(st)}">${avatarSvg(store.player.id, { style: st, hue: av.hue }, 52)}</button>`).join('');
	d.innerHTML = `<form method="dialog" class="gc-hello__card">
		<p class="gc-hello__k">🎮 به گیم‌سنتر خوش آمدی</p>
		<h2 id="gc-hello-t" class="gc-hello__t">اسمت در بازی‌ها چی باشد؟</h2>
		<p class="gc-hello__x">فقط یک بار می‌پرسیم؛ روی همین دستگاه می‌ماند و در جدول برترین‌ها و لابی دیده می‌شود. ثبت‌نام لازم نیست.</p>
		<div class="gc-hello__avs" data-avs>${avatars()}</div>
		<input class="gc-hello__in" name="nick" maxlength="24" required autocomplete="nickname" placeholder="مثلاً: شاهین شب">
		<p class="gc-hello__err" data-err aria-live="polite"></p>
		<button type="submit" class="gc-hello__go">بزن بریم ▶</button>
	</form>`;
	document.body.appendChild(d);
	d.addEventListener('cancel', (e) => e.preventDefault()); // nickname is required once
	d.querySelector('[data-avs]').addEventListener('click', (e) => {
		const b = e.target.closest('[data-st]');
		if (!b) return;
		av = { ...av, style: b.dataset.st };
		d.querySelectorAll('[data-st]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
	});
	d.querySelector('form').addEventListener('submit', (e) => {
		const nick = d.querySelector('input').value.trim().replace(/\s+/g, ' ').slice(0, 24);
		if (nick.length < 2) {
			e.preventDefault();
			d.querySelector('[data-err]').textContent = 'حداقل دو حرف بنویس.';
			return;
		}
		store.set('nickname', nick);
		store.set('avatar', av);
		online.profile({ player_id: store.player.id, nickname: nick, avatar: av }).catch(() => {});
		window.dispatchEvent(new CustomEvent('kmpr:profile-changed'));
		setTimeout(() => d.remove(), 300);
		startPresence();
	});
	d.showModal();
	setTimeout(() => d.querySelector('input').focus(), 50);
}

export function initPwa() {
	if (html.dataset.pwaReady) return;
	html.dataset.pwaReady = '1';
	html.classList.toggle('is-standalone', isStandalone());
	// Refresh (installed app has no browser reload button): check for a new SW version, then reload.
	document.querySelectorAll('[data-gh-reload]').forEach((btn) => btn.addEventListener('click', async () => {
		btn.classList.add('is-spinning');
		btn.disabled = true;
		try {
			const reg = 'serviceWorker' in navigator ? await navigator.serviceWorker.getRegistration('/games/') : null;
			if (reg) await Promise.race([reg.update(), new Promise((r) => setTimeout(r, 1500))]);
		} catch (e) { /* offline or no SW: just reload */ }
		location.reload();
	}));
	const d = read();
	if (isStandalone() && !d.installed) {
		d.installed = true;
	}
	if (!sessionStorage.getItem('kmpr_pwa_visit')) {
		try {
			sessionStorage.setItem('kmpr_pwa_visit', '1');
		} catch (e) {
			/* ignore */
		}
		d.visits = (d.visits || 0) + 1;
	}
	write(d);

	window.addEventListener('beforeinstallprompt', (e) => {
		e.preventDefault();
		deferred = e;
		if (!sessionStorage.getItem('kmpr_pwa_prompted')) {
			sessionStorage.setItem('kmpr_pwa_prompted', '1');
			stat('prompt');
		}
		syncButtons();
		if (onHub()) maybeEncourage();
	});
	window.addEventListener('appinstalled', () => {
		const x = read();
		x.installed = true;
		write(x);
		deferred = null;
		stat('installed');
		syncButtons();
		document.querySelector('[data-pwa-sheet]')?.remove();
	});
	window.addEventListener('kmpr:game-over', () => {
		const x = read();
		x.games = (x.games || 0) + 1;
		write(x);
		// Show after the player leaves the game screen (focus exit) or on the next page.
	});
	window.addEventListener('kmpr:focus-change', (e) => !e.detail.active && (read().games || 0) >= 2 && maybeEncourage());

	registerSW();
	appNav();
	prefetch();
	pageLoader();
	// Not while speculatively prerendered: wait until the visitor actually sees the page.
	if (document.prerendering) document.addEventListener('prerenderingchange', () => devicePlayer(), { once: true });
	else devicePlayer();
	syncButtons();
	// Auto-suggest only on the hub (game pages: right after leaving a finished game, never over the start screen).
	if (onHub()) maybeEncourage();
	// Test hook (puppeteer): window.__kmprPwa.open('ios'|'prompt')
	window.__kmprPwa = { open: sheet, state: read };
}
