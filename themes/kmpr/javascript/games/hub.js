/**
 * Game Center hub: player profile (avatar editor, XP/level, badges, recent),
 * "your best" on cards, continue playing, daily challenge, online lobby, top players.
 * Only network polling: lobby every 6s while the tab is visible and the lobby is on screen.
 */
import { store } from './lib/store.js';
import { api as rest, online } from './lib/api.js';
import { avatarSvg, AVATAR_STYLES, AVATAR_HUES } from './lib/avatar.js';
import { CATALOG } from './catalog.js';
import { fa, esc } from './lib/util.js';
import { drawHero } from './lib/hero.js';

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

let games = [];
let serverProfile = null;

const gameByEngine = (e) => games.find((g) => g.engine === e);

function bests() {
	$$('[data-game-best]').forEach((el) => {
		const g = store.game(el.dataset.gameBest);
		el.textContent = g.best > 0 ? fa(g.best) : '—';
		const chip = el.closest('.gc-chip[data-best-wrap]');
		if (chip) chip.hidden = !(g.best > 0);
	});
}

/* ---------- game header: player chip + global sound ---------- */
const SOUND_ON = '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5Z" fill="currentColor"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>';
const SOUND_OFF = '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5Z" fill="currentColor"/><path d="m22 9-6 6M16 9l6 6"/></svg>';

function headerBar() {
	const p = store.player;
	const av = $('[data-gh-avatar]');
	if (av) av.innerHTML = avatarSvg(p.hash || p.id, p.avatar || {}, 36);
	const n = $('[data-gh-name]');
	if (n) n.textContent = p.nickname || 'بازیکن مهمان';
	const l = $('[data-gh-level]');
	if (l) l.textContent = `سطح ${fa(serverProfile ? serverProfile.level : p.level || 1)}`;
	const b = $('[data-gh-sound]');
	if (b && !b.dataset.bound) {
		b.dataset.bound = '1';
		const paint = () => {
			const on = !!store.player.sound;
			b.setAttribute('aria-pressed', String(on));
			b.setAttribute('aria-label', on ? 'قطع صدای بازی‌ها' : 'روشن کردن صدای بازی‌ها');
			b.innerHTML = on ? SOUND_ON : SOUND_OFF;
		};
		paint();
		b.addEventListener('click', () => {
			store.set('sound', !store.player.sound);
			paint();
			window.dispatchEvent(new CustomEvent('kmpr:sound', { detail: !!store.player.sound }));
		});
		window.addEventListener('kmpr:sound-changed', paint);
	}
}

/* ---------- spotlight carousel ---------- */
function spotlight() {
	const root = $('[data-gc-spot]');
	if (!root) return;
	const slides = $$('[data-gc-slide]', root);
	const dots = $$('[data-gc-dot]', root);
	if (slides.length < 2) return;
	let i = 0;
	let timer = 0;
	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const show = (n) => {
		i = (n + slides.length) % slides.length;
		slides.forEach((s, k) => {
			const on = k === i;
			s.classList.toggle('is-active', on);
			s.toggleAttribute('aria-hidden', !on);
			const a = s.querySelector('.gc-play');
			if (a) a.tabIndex = on ? 0 : -1;
			if (on) s.querySelectorAll('img[loading="lazy"]').forEach((img) => (img.loading = 'eager'));
		});
		dots.forEach((d, k) => d.setAttribute('aria-selected', String(k === i)));
	};
	const play = () => {
		clearTimeout(timer);
		if (reduce || document.hidden) return;
		timer = setTimeout(() => {
			show(i + 1);
			play();
		}, 7000);
	};
	dots.forEach((d) =>
		d.addEventListener('click', () => {
			show(Number(d.dataset.gcDot));
			play();
		})
	);
	root.addEventListener('pointerenter', () => clearTimeout(timer));
	root.addEventListener('pointerleave', play);
	root.addEventListener('focusin', () => clearTimeout(timer));
	document.addEventListener('visibilitychange', play);
	new IntersectionObserver((en) => (en[0].isIntersecting ? play() : clearTimeout(timer))).observe(root);
}

/* ---------- rails: arrow buttons ---------- */
function rails() {
	$$('[data-gc-rail]').forEach((rail) => {
		const track = rail.querySelector('.gc-rail__track');
		const btns = $$('[data-rail-dir]', rail);
		const nav = rail.querySelector('.gc-rail__nav');
		const update = () => {
			const max = track.scrollWidth - track.clientWidth;
			const pos = Math.abs(track.scrollLeft);
			btns.forEach((b) => (b.disabled = Number(b.dataset.railDir) < 0 ? pos < 4 : pos > max - 4));
			if (nav) nav.style.visibility = max < 8 ? 'hidden' : '';
		};
		btns.forEach((b) =>
			b.addEventListener('click', () => {
				// RTL: "next" scrolls toward the inline end (negative scrollLeft).
				track.scrollBy({ left: -Number(b.dataset.railDir) * track.clientWidth * 0.85, behavior: 'smooth' });
			})
		);
		track.addEventListener('scroll', update, { passive: true });
		window.addEventListener('resize', update, { passive: true });
		update();
	});
}

/* ---------- starfield (cheap canvas; paused offscreen / hidden / reduced motion) ---------- */
function starfield() {
	const c = $('[data-gc-stars]');
	if (!c) return;
	const ctx = c.getContext('2d');
	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	let w = 0;
	let h = 0;
	let stars = [];
	let raf = 0;
	let visible = true;
	let last = 0;
	const draw = (dt) => {
		ctx.clearRect(0, 0, w, h);
		for (const s of stars) {
			s.y += s.z * 12 * dt;
			s.t += dt * 2;
			if (s.y > h) {
				s.y = 0;
				s.x = Math.random() * w;
			}
			ctx.globalAlpha = Math.max(0.1, 0.25 + s.z * 0.5 + Math.sin(s.t) * 0.15);
			ctx.fillStyle = s.z > 0.85 ? '#fde68a' : '#e2e8f0';
			const r = s.z * 1.6;
			ctx.fillRect(s.x, s.y, r, r);
		}
		ctx.globalAlpha = 1;
	};
	const fit = () => {
		const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
		w = c.clientWidth;
		h = c.clientHeight;
		c.width = Math.round(w * dpr);
		c.height = Math.round(h * dpr);
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		const n = Math.min(140, Math.round((w * h) / 9000));
		stars = Array.from({ length: n }, () => ({ x: Math.random() * w, y: Math.random() * h, z: 0.2 + Math.random() * 0.8, t: Math.random() * 6 }));
		draw(0);
	};
	const loop = (t) => {
		const dt = Math.min(0.05, (t - last) / 1000 || 0);
		last = t;
		draw(dt);
		raf = requestAnimationFrame(loop);
	};
	const start = () => {
		cancelAnimationFrame(raf);
		if (reduce || !visible || document.hidden) return;
		last = performance.now();
		raf = requestAnimationFrame(loop);
	};
	fit();
	new ResizeObserver(() => fit()).observe(c);
	new IntersectionObserver((en) => {
		visible = en[0].isIntersecting;
		if (visible) start();
		else cancelAnimationFrame(raf);
	}).observe(c);
	document.addEventListener('visibilitychange', start);
}


function profileCard() {
	const el = $('[data-arcade-profile]');
	if (!el) return;
	const p = store.player;
	const set = (k, v, html = false) => {
		const n = el.querySelector(`[data-profile="${k}"]`);
		if (n) n[html ? 'innerHTML' : 'textContent'] = v;
	};
	let plays = 0;
	let records = 0;
	let got = 0;
	Object.keys(CATALOG).forEach((slug) => {
		const g = p.games[slug];
		if (!g) return;
		plays += g.plays;
		got += g.ach.length;
		if (g.best > 0) records++;
	});
	const sp = serverProfile;
	const xp = sp ? sp.xp : p.xp || 0;
	const level = sp ? sp.level : p.level || 1;
	const floor = sp ? sp.level_floor : 40 * (level - 1) ** 2;
	const next = sp ? sp.level_next : 40 * level ** 2;
	const pct = Math.max(0, Math.min(100, ((xp - floor) / Math.max(1, next - floor)) * 100));
	set('avatar', avatarSvg(p.hash || p.id, p.avatar || {}, 64), true);
	set('name', p.nickname || 'بازیکن مهمان');
	set('level', fa(level));
	set('xp-text', `${fa(xp)} XP · ${fa(Math.max(0, next - xp))} تا سطح بعد`);
	set('plays', fa(sp ? Math.max(plays, sp.total_plays || 0) : plays));
	set('records', fa(records));
	set('ach', fa(got));
	set('wl', sp ? `${fa(sp.wins)}` : '۰');
	const bar = el.querySelector('[data-profile="xp-bar"]');
	if (bar) {
		bar.setAttribute('aria-valuenow', String(Math.round(pct)));
		bar.firstElementChild.style.width = pct + '%';
	}
}

function badges() {
	const el = $('[data-badges]');
	if (!el) return;
	const p = store.player;
	const remote = new Set();
	let total = 0;
	let got = 0;
	// SVG medals grouped by game: tier ring (bronze → silver → gold by order), the game's cartoon icon inside.
	const TIERS = [['#d97706', '#fcd34d'], ['#94a3b8', '#f1f5f9'], ['#eab308', '#fef08a']];
	const inner = (svg) => (/<svg[^>]*>([\s\S]*)<\/svg>/.exec(svg || '') || [])[1] || '';
	const medal = (g, i, on) => {
		const [ring, light] = TIERS[Math.min(2, Math.floor(i / 2))];
		const acc = (g && g.accent) || '#f59e0b';
		return `<svg viewBox="0 0 48 58" width="44" height="53" aria-hidden="true" class="ab-medal">
			<path d="M12 2h9l5 16h-9zM36 2h-9l-5 16h9z" fill="${acc}" stroke="#16120f" stroke-width="2" stroke-linejoin="round"/>
			<circle cx="24" cy="36" r="19" fill="${ring}" stroke="#16120f" stroke-width="2.5"/>
			<circle cx="24" cy="36" r="14.5" fill="${light}" stroke="#16120f" stroke-width="1.5"/>
			<g transform="translate(13.5 25.5) scale(.33)">${inner(g && g.icon)}</g>
			<path d="M16 26a11 11 0 0 1 8-5" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" opacity=".7"/>
			${on ? '' : '<g><circle cx="38" cy="48" r="7" fill="#1e293b" stroke="#16120f" stroke-width="1.5"/><path d="M35.5 47v-1.5a2.5 2.5 0 0 1 5 0V47" stroke="#94a3b8" stroke-width="1.5" fill="none"/><rect x="34.5" y="47" width="7" height="4.5" rx="1" fill="#94a3b8"/></g>'}
		</svg>`;
	};
	el.innerHTML = Object.entries(CATALOG)
		.map(([slug, c]) => {
			const g = gameByEngine(slug);
			let mine = 0;
			const items = c.achievements
				.map((a, i) => {
					total++;
					const on = (p.games[slug] && p.games[slug].ach.includes(a.id)) || remote.has(`${slug}:${a.id}`);
					if (on) { got++; mine++; }
					return `<li class="arcade-badge${on ? ' is-on' : ''}" title="${esc(a.title + ': ' + a.desc)}">${medal(g, i, on)}<span class="ab-name">${esc(a.title)}</span><span class="sr-only">${esc(a.desc)} ${on ? '(باز شده)' : '(قفل)'}</span></li>`;
				})
				.join('');
			return `<li class="ab-game"><p class="ab-game__t">${esc(c.title)} <span>${fa(mine)}/${fa(c.achievements.length)}</span></p><ul class="ab-row">${items}</ul></li>`;
		})
		.join('');
	const c = $('[data-badges-count]');
	if (c) c.textContent = `${fa(got)} از ${fa(total)}`;
}

function continuePlaying() {
	const el = $('[data-continue]');
	if (!el) return;
	const played = Object.entries(store.player.games)
		.filter(([slug, g]) => g.at && gameByEngine(slug))
		.sort((a, b) => b[1].at - a[1].at)
		.slice(0, 6);
	const rail = el.closest('[data-continue-rail]');
	if (!played.length) {
		if (rail) rail.hidden = true;
		return;
	}
	if (rail) rail.hidden = false;
	el.innerHTML = played
		.map(([slug, g]) => {
			const gm = gameByEngine(slug);
			return `<li class="gc-rail__item"><a href="${esc(gm.url)}" class="gc-card group" style="--accent:${esc(gm.accent)}">
				<span class="gc-card__chips">${g.best ? `<span class="gc-chip gc-chip--best">★ ${fa(g.best)}</span>` : ''}</span>
				<span class="gc-card__icon">${gm.icon || ''}</span>
				<span class="gc-card__title">${esc(gm.title)}</span>
				<span class="gc-card__meta">${fa(g.plays)} بار بازی کرده‌اید</span>
				<span class="gc-card__play"><svg width="14" height="14" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg>ادامه</span>
			</a></li>`;
		})
		.join('');
}


function daily() {
	const el = $('[data-daily]');
	if (!el) return;
	const today = new Date().toLocaleDateString('en-CA');
	const d = store.player.daily && store.player.daily.date === today ? store.player.daily : {};
	const best = d[el.dataset.engine] || 0;
	$('[data-daily-best]', el).textContent = best ? fa(best) : '—';
	$('[data-daily-done]', el).hidden = best < Number(el.dataset.target);
}

function recentList() {
	const el = $('[data-recent]');
	const list = (store.player.recent || []).filter((r) => gameByEngine(r.e));
	if (!el || !list.length) return;
	el.innerHTML = list
		.slice(0, 6)
		.map((r) => {
			const gm = gameByEngine(r.e);
			const badge =
				r.type === 'match'
					? `<span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-black ${r.r === 'win' ? 'bg-emerald-500/15 text-emerald-300' : r.r === 'draw' ? 'bg-sky-500/15 text-sky-300' : 'bg-rose-500/15 text-rose-300'}">${r.r === 'win' ? 'برد' : r.r === 'draw' ? 'مساوی' : 'باخت'}</span>`
					: `<span class="shrink-0 text-sm font-black text-amber-300">${fa(r.s)}</span>`;
			const when = new Date(r.t).toLocaleDateString('fa-IR', { month: 'long', day: 'numeric' });
			return `<li><a href="${esc(gm.url)}" class="flex items-center gap-3 p-3 min-h-11 rounded-2xl bg-white/[0.03] border border-white/10 hover:border-white/25 transition-colors">
				<span class="min-w-0 flex-1"><span class="block text-sm font-black text-white truncate">${esc(gm.title)}</span><span class="block text-xs text-slate-400">${r.type === 'match' ? 'آنلاین مقابل ' + esc(r.o || 'حریف') : 'تک‌نفره'} · ${when}</span></span>
				${badge}
			</a></li>`;
		})
		.join('');
}

async function sync() {
	try {
		const res = await rest.player(store.player.id);
		if (!res || !res.ok) return;
		if (res.player) store.set('hash', res.player);
		(res.games || []).forEach((g) => g.engine && store.merge(g.engine, g));
		if (res.profile) {
			serverProfile = { ...res.profile, total_plays: res.total_plays };
			if (!store.player.nickname && res.profile.nickname) store.set('nickname', res.profile.nickname);
			if (!store.player.avatar && res.profile.avatar) store.set('avatar', res.profile.avatar);
		}
		renderAll();
	} catch (e) {
		/* offline */
	}
}

function renderAll() {
	headerBar();
	bests();
	profileCard();
	badges();
	continuePlaying();
	daily();
	recentList();
}

/* ---------- editor ---------- */
function editor() {
	const dlg = $('[data-profile-dialog]');
	const btn = $('[data-profile-edit]');
	if (!dlg || !btn || !dlg.showModal) {
		if (btn) btn.hidden = true;
		return;
	}
	let draft = {};
	const preview = () => {
		$('[data-edit-preview]', dlg).innerHTML = avatarSvg(store.player.hash || store.player.id, draft, 64);
		$$('[data-style]', dlg).forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.style === draft.style)));
		$$('[data-hue]', dlg).forEach((b) => b.setAttribute('aria-pressed', String(Number(b.dataset.hue) === draft.hue)));
	};
	$('[data-edit-styles]', dlg).innerHTML = AVATAR_STYLES.map((s) => `<button type="button" class="arcade-choice" data-style="${s.id}" aria-pressed="false"><span data-style-prev="${s.id}"></span>${esc(s.label)}</button>`).join('');
	$('[data-edit-hues]', dlg).innerHTML = AVATAR_HUES.map((h) => `<button type="button" class="arcade-hue" data-hue="${h}" aria-pressed="false" aria-label="رنگ ${fa(h)}" style="background:hsl(${h} 85% 58%)"></button>`).join('');
	btn.addEventListener('click', () => {
		draft = { style: 'blocks', hue: 32, ...(store.player.avatar || {}) };
		dlg.querySelector('[name="nickname"]').value = store.player.nickname || rest.userName || '';
		$$('[data-style-prev]', dlg).forEach((s) => (s.innerHTML = avatarSvg(store.player.hash || store.player.id, { ...draft, style: s.dataset.stylePrev }, 32)));
		preview();
		dlg.showModal();
	});
	dlg.addEventListener('click', (e) => {
		const s = e.target.closest('[data-style]');
		const h = e.target.closest('[data-hue]');
		if (s) draft.style = s.dataset.style;
		if (h) {
			draft.hue = Number(h.dataset.hue);
			$$('[data-style-prev]', dlg).forEach((n) => (n.innerHTML = avatarSvg(store.player.hash || store.player.id, { ...draft, style: n.dataset.stylePrev }, 32)));
		}
		if (s || h) preview();
		if (e.target === dlg) dlg.close('cancel');
	});
	dlg.addEventListener('close', async () => {
		if (dlg.returnValue !== 'save') return;
		const nick = dlg.querySelector('[name="nickname"]').value.trim().slice(0, 24);
		if (nick) store.set('nickname', nick);
		store.set('avatar', draft);
		renderAll();
		const res = await online.profile({ player_id: store.player.id, nickname: nick || undefined, avatar: draft }).catch(() => null);
		if (res && res.ok && res.profile) {
			serverProfile = { ...serverProfile, ...res.profile };
			if (res.profile.player) store.set('hash', res.profile.player);
			renderAll();
		}
	});
}

/* ---------- lobby + top players ---------- */
function lobby() {
	const el = $('[data-lobby]');
	if (!el) return;
	let timer = 0;
	let visible = false;
	const load = async () => {
		clearTimeout(timer);
		if (document.hidden || !visible) return;
		try {
			const res = await online.lobby();
			if (res && res.ok) {
				el.innerHTML = res.rooms.length
					? res.rooms
							.map((r) => {
								const mine = r.player && r.player === store.player.hash;
								return `<li class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.03] border border-white/10">
								${avatarSvg(r.player || r.code, r.avatar || {}, 40)}
								<span class="min-w-0 flex-1"><span class="block text-sm font-black text-white truncate">${esc(r.host)}${mine ? ' (شما)' : ''}</span><span class="block text-xs text-slate-400 truncate">${esc(r.title)} · کد <span dir="ltr" class="font-mono">${esc(r.code)}</span></span></span>
								${mine ? '' : `<a href="${esc(r.url)}" class="shrink-0 inline-flex items-center min-h-11 px-5 rounded-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-sm font-black whitespace-nowrap transition-colors">ورود</a>`}
							</li>`;
							})
							.join('')
					: '<li class="text-sm text-slate-400 py-6 text-center rounded-2xl border border-dashed border-white/10">الان اتاق بازی منتظری نیست.</li>';
			}
		} catch (e) {
			/* ignore */
		}
		timer = setTimeout(load, 6000);
	};
	new IntersectionObserver((en) => {
		visible = en[0].isIntersecting;
		if (visible) load();
		else clearTimeout(timer);
	}).observe(el);
	document.addEventListener('visibilitychange', () => !document.hidden && visible && load());
}

async function topPlayers() {
	const el = $('[data-top-players]');
	if (!el) return;
	const io = new IntersectionObserver(async (en) => {
		if (!en[0].isIntersecting) return;
		io.disconnect();
		try {
			const res = await online.topPlayers();
			if (!res || !res.ok) return;
			el.innerHTML = res.rows.length
				? res.rows
						.map(
							(r, i) => `<li class="flex items-center gap-3 p-3 rounded-2xl ${r.player === store.player.hash ? 'bg-secondary/10 border-secondary/50' : 'bg-white/[0.03] border-white/10'} border">
							<span class="w-7 text-center font-black text-slate-200 shrink-0">${['🥇', '🥈', '🥉'][i] || fa(i + 1)}</span>
							${avatarSvg(r.player, r.avatar, 40)}
							<span class="min-w-0 flex-1"><span class="block text-sm font-black text-white truncate">${esc(r.nickname)}${(r.badges || []).map((b) => ` <span class="game-badge">${esc(b)}</span>`).join('')}</span><span class="block text-xs text-slate-400">سطح ${fa(r.level)} · ${fa(r.wins)} برد آنلاین</span></span>
							<span class="shrink-0 text-sm font-black text-amber-300">${fa(r.xp)} XP</span>
						</li>`
						)
						.join('')
				: '<li class="text-sm text-slate-400 py-6 text-center rounded-2xl border border-dashed border-white/10">هنوز کسی امتیاز تجربه نگرفته؛ اولین نفر باشید!</li>';
		} catch (e) {
			/* ignore */
		}
	});
	io.observe(el);
}


/* ---------------- tabs: games / lobby / ranks (hash-addressable, view-transition switch) ---------------- */
function tabs() {
	const bar = $('[data-gc-tabs]');
	if (!bar) return;
	const tabsEls = [...bar.querySelectorAll('[data-tab]')];
	const panes = [...document.querySelectorAll('[data-pane]')];
	const ink = bar.querySelector('.gc-tabs__ink');
	const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	let current = 'games';
	let busy = false;
	const moveInk = () => {
		const t = tabsEls.find((x) => x.dataset.tab === current);
		if (!t || !ink) return;
		ink.style.width = `${t.offsetWidth}px`;
		ink.style.transform = `translateX(${t.offsetLeft}px)`;
	};
	const show = (name, animate = true) => {
		if (!panes.some((p) => p.dataset.pane === name)) name = 'games';
		if (name === current && animate) return;
		const swap = () => {
			current = name;
			panes.forEach((p) => (p.hidden = p.dataset.pane !== name));
			tabsEls.forEach((t) => t.setAttribute('aria-selected', String(t.dataset.tab === name)));
			document.querySelectorAll('[data-gh-tab]').forEach((a) => (a.dataset.ghTab === name ? a.setAttribute('aria-current', 'page') : a.removeAttribute('aria-current')));
			moveInk();
			window.dispatchEvent(new CustomEvent('kmpr:hub-tab', { detail: { tab: name } }));
		};
		if (!animate || reduced) return swap();
		// Direction-aware, GPU-only transition (View Transitions API; CSS fallback elsewhere).
		const order = tabsEls.map((t) => t.dataset.tab);
		document.documentElement.dataset.gcDir = order.indexOf(name) > order.indexOf(current) ? 'next' : 'prev';
		// Keep the reader at the top of the panel area (just under the sticky header).
		const curPane = panes.find((p) => p.dataset.pane === current);
		const head = document.querySelector('.gh');
		const top = curPane ? curPane.getBoundingClientRect().top + window.scrollY - (head ? head.getBoundingClientRect().bottom : 70) - 12 : 0;
		const run = () => {
			swap();
			if (window.scrollY > top) window.scrollTo({ top, behavior: 'instant' });
		};
		if (document.startViewTransition) {
			busy = true;
			const vt = document.startViewTransition(run);
			vt.finished.finally(() => (busy = false));
		} else {
			run();
			const pane = panes.find((p) => p.dataset.pane === name);
			pane.classList.remove('is-enter');
			void pane.offsetWidth;
			pane.classList.add('is-enter');
		}
	};
	bar.addEventListener('click', (e) => {
		const t = e.target.closest('[data-tab]');
		if (!t) return;
		e.preventDefault();
		if (busy) return;
		history.replaceState(null, '', `#${t.dataset.tab}`);
		show(t.dataset.tab);
	});
	bar.addEventListener('keydown', (e) => {
		const i = tabsEls.findIndex((x) => x.dataset.tab === current);
		const d = { ArrowLeft: 1, ArrowRight: -1 }[e.key];
		if (d === undefined) return;
		const n = tabsEls[(i + d + tabsEls.length) % tabsEls.length];
		n.focus();
		n.click();
	});
	// In-page links to old anchors (#leaders, #lobby, #profile…) open the right tab.
	const fromHash = () => {
		const h = location.hash.slice(1);
		const pane = panes.find((p) => p.dataset.pane === h || (h && p.querySelector(`#${CSS.escape(h)}`)));
		if (pane) show(pane.dataset.pane, false);
	};
	window.addEventListener('hashchange', () => {
		const h = location.hash.slice(1);
		if (panes.some((p) => p.dataset.pane === h)) show(h);
		else fromHash();
	});
	// Header / dock shortcuts on the hub itself: animated switch, no jump.
	document.querySelectorAll('[data-gh-tab]').forEach((a) =>
		a.addEventListener('click', (e) => {
			const k = a.dataset.ghTab;
			if (!panes.some((p) => p.dataset.pane === k)) return;
			e.preventDefault();
			history.replaceState(null, '', `#${k}`);
			show(k);
		})
	);
	window.addEventListener('resize', moveInk);
	fromHash();
	moveInk();
}

/* ---------------- presence list (data comes from pwa.js heartbeats) ---------------- */
function presence() {
	const list = $('[data-online]');
	const counts = document.querySelectorAll('[data-online-count]');
	const label = $('[data-online-label]');
	window.addEventListener('kmpr:presence', (e) => {
		const d = e.detail || {};
		counts.forEach((c) => {
			c.textContent = fa(d.count || 0);
			c.hidden = false;
		});
		if (label) label.textContent = `${fa(d.count || 0)} نفر`;
		if (!list) return;
		const urlOf = (engine) => (games.find((g) => g.engine === engine) || {}).url;
		list.innerHTML = (d.online || []).length
			? d.online
					.map((u) => {
						const url = u.where !== 'hub' && urlOf(u.where);
						return `<li class="gc-online__row${u.me ? ' is-me' : ''}">
							<span class="gc-online__av">${avatarSvg(u.id, u.avatar || {}, 40)}<i></i></span>
							<span class="min-w-0 flex-1"><b>${esc(u.nick)}${u.me ? ' (شما)' : ''}</b><small>${u.where === 'hub' ? 'در لابی' : `در حال بازی «${esc(u.title)}»`}</small></span>
							${url && !u.me ? `<a href="${esc(url)}" class="gc-online__go">همین بازی</a>` : ''}
						</li>`;
					})
					.join('')
			: '<li class="text-sm text-slate-400 py-6 text-center rounded-2xl border border-dashed border-white/10">فعلاً فقط خودت اینجایی! یک اتاق بساز و لینکش را بفرست.</li>';
	});
}

/* spotlight: animated pixel Korosh (running in place) when the featured game has no cover */
function heroArt() {
	document.querySelectorAll('[data-hero-art]').forEach((c) => {
		const ctx = c.getContext('2d');
		ctx.scale(4, 4); // vector hero on a 4× canvas: crisp when CSS scales it up
		const t0 = performance.now();
		const paint = () => { ctx.clearRect(0, 0, 36, 56); drawHero(ctx, 18, 22, 'big', 'coffee', 'run', 1, 1, 1, (performance.now() - t0) / 450); };
		paint();
		if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) { const loop = () => { paint(); requestAnimationFrame(loop); }; requestAnimationFrame(loop); }
	});
}

export function initHub() {
	try {
		games = JSON.parse(($('[data-arcade-games]') || {}).textContent || '[]');
	} catch (e) {
		games = [];
	}
	renderAll();
	editor();
	spotlight();
	rails();
	starfield();
	lobby();
	topPlayers();
	tabs();
	presence();
	heroArt();
	window.addEventListener('kmpr:profile-changed', () => renderAll());
	sync();
}
