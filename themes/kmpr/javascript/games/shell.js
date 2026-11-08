/**
 * Game shell — wraps any engine module ({ mount(stage, api) → { start, pause, resume, destroy } }).
 * Owns: HUD, overlay screens (start / pause / game over), fullscreen, sound toggle,
 * pause on tab switch or scroll-away, nickname prompt, score submission,
 * live leaderboard panel and personal stats (localStorage + /player sync).
 */
import { store } from './lib/store.js';
import { api as rest, online } from './lib/api.js';
import { avatarSvg } from './lib/avatar.js';
import { createSound } from './lib/sound.js';
import { fa, esc, h, reducedMotion, isTouch } from './lib/util.js';
import { CATALOG, ENGINE_LOADERS } from './catalog.js';

const LOCAL_ONLY = (window.kmprGames || {}).storage === 'local';

/* ---------- unfinished-game snapshots (localStorage only) ---------- */
const SNAP = 'kmpr_snap_';
const ttlMs = (online) => {
	if (online) return 30 * 60 * 1000;
	const h = Number((window.kmprGames || {}).snapTtl ?? 12);
	return h > 0 ? h * 3600 * 1000 : Infinity;
};
const snapRead = (engine) => {
	try {
		const s = JSON.parse(localStorage.getItem(SNAP + engine) || 'null');
		if (!s || !s.t) return null;
		if (Date.now() - s.t > ttlMs(s.mode && s.mode.type === 'online')) {
			localStorage.removeItem(SNAP + engine);
			return null;
		}
		return s;
	} catch (e) {
		return null;
	}
};
const snapWrite = (engine, s) => {
	try {
		if (s) localStorage.setItem(SNAP + engine, JSON.stringify(s));
		else localStorage.removeItem(SNAP + engine);
	} catch (e) {
		/* quota / private mode — resume is best-effort */
	}
};
// Purge expired snapshots of every game on load.
try {
	Object.keys(localStorage)
		.filter((k) => k.startsWith(SNAP))
		.forEach((k) => snapRead(k.slice(SNAP.length)));
} catch (e) {
	/* ignore */
}

const ICONS = {
	pause: '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>',
	play: '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg>',
	soundOn: '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5Z" fill="currentColor"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>',
	soundOff: '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4V5Z" fill="currentColor"/><path d="m22 9-6 6M16 9l6 6"/></svg>',
	expand: '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>',
	shrink: '<svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/></svg>',
	trophy: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 3h10v2h3v3a4 4 0 0 1-4 4h-.35A5 5 0 0 1 13 14.9V18h3v3H8v-3h3v-3.1A5 5 0 0 1 8.35 12H8a4 4 0 0 1-4-4V5h3V3Zm0 4H6v1a2 2 0 0 0 1 1.73V7Zm10 0v2.73A2 2 0 0 0 18 8V7h-1Z"/></svg>',
};

/* ------------------------------------------------------------------ *
 * Leaderboard panel
 * ------------------------------------------------------------------ */
const leaderboards = new Map();

function bindLeaderboard(el) {
	const gameId = Number(el.dataset.gameId);
	const list = el.querySelector('[data-lb-list]');
	const tabs = [...el.querySelectorAll('[data-period]')];
	let period = 'all';

	const render = (rows) => {
		const me = store.player.hash;
		if (!rows || !rows.length) {
			list.innerHTML = `<li class="game-lb__empty">هنوز کسی رکورد نزده؛ اولین نفر باشید!</li>`;
			return;
		}
		list.innerHTML = rows
			.map((r, i) => {
				const medal = ['🥇', '🥈', '🥉'][i] || fa(i + 1);
				const mine = me && r.player === me;
				return `<li class="game-lb__row${mine ? ' is-me' : ''}">
					<span class="game-lb__rank">${medal}</span>
					<span class="game-lb__name">${esc(r.nickname)}${(r.badges || []).map((b) => ` <span class="game-badge">${esc(b)}</span>`).join('')}${mine ? ' <em>(شما)</em>' : ''}</span>
					<span class="game-lb__score">${fa(r.score)}</span>
				</li>`;
			})
			.join('');
	};

	const load = async (p = period) => {
		period = p;
		tabs.forEach((t) => t.setAttribute('aria-pressed', String(t.dataset.period === p)));
		el.setAttribute('aria-busy', 'true');
		try {
			const res = await rest.leaderboard(gameId, p);
			if (res && res.ok) render(res.rows);
		} catch (e) {
			/* keep the server-rendered list */
		}
		el.removeAttribute('aria-busy');
	};

	tabs.forEach((t) => t.addEventListener('click', () => load(t.dataset.period)));
	const ctrl = {
		load,
		set(rows) {
			if (period === 'all') render(rows);
			else load('week');
		},
	};
	leaderboards.set(gameId, ctrl);
	// Re-render with "you" highlighting once hydrated.
	load('all');
	return ctrl;
}

/* ------------------------------------------------------------------ *
 * Personal stats panel
 * ------------------------------------------------------------------ */
function renderStats(engine, achievements) {
	document.querySelectorAll(`[data-player-stats][data-engine="${engine}"]`).forEach((el) => {
		const g = store.game(engine);
		const p = store.player;
		const got = achievements.filter((a) => g.ach.includes(a.id)).length;
		const mins = Math.round(g.time / 60);
		el.innerHTML = `
			<div class="game-stats__head">
				<span class="game-stats__avatar" aria-hidden="true">${avatarSvg(p.hash || p.id, p.avatar || {}, 44)}</span>
				<div class="min-w-0">
					<p class="game-stats__name">${p.nickname ? esc(p.nickname) : 'بازیکن مهمان'}</p>
					<p class="game-stats__sub">${p.nickname ? 'پروفایل روی همین مرورگر ذخیره شده است' : 'با اولین رکورد، نام مستعار انتخاب می‌کنید'}</p>
				</div>
			</div>
			<dl class="game-stats__grid">
				<div><dt>بهترین امتیاز</dt><dd>${fa(g.best)}</dd></div>
				<div><dt>دفعات بازی</dt><dd>${fa(g.plays)}</dd></div>
				<div><dt>زمان بازی</dt><dd>${mins ? fa(mins) + ' دقیقه' : fa(g.time) + ' ثانیه'}</dd></div>
				<div><dt>دستاوردها</dt><dd>${fa(got)}/${fa(achievements.length)}</dd></div>
			</dl>
			<ul class="game-ach" aria-label="دستاوردها">
				${achievements
					.map((a) => {
						const on = g.ach.includes(a.id);
						return `<li class="game-ach__item${on ? ' is-on' : ''}" title="${esc(a.desc)}">
							<span class="game-ach__icon" aria-hidden="true">${on ? a.icon || '★' : '🔒'}</span>
							<span class="min-w-0"><b>${esc(a.title)}</b><small>${esc(a.desc)}</small></span>
							<span class="sr-only">${on ? 'باز شده' : 'قفل'}</span>
						</li>`;
					})
					.join('')}
			</ul>`;
	});
}

/* ------------------------------------------------------------------ *
 * Shell
 * ------------------------------------------------------------------ */
export async function createShell(root) {
	const gameId = Number(root.dataset.gameId);
	const engine = root.dataset.engine;
	if (!ENGINE_LOADERS[engine] || root.dataset.shellReady) return null;
	root.dataset.shellReady = '1';
	root.dataset.state = 'loading';
	let mod;
	try {
		mod = await ENGINE_LOADERS[engine]();
	} catch (e) {
		root.dataset.state = 'error';
		const o = root.querySelector('[data-overlay]');
		if (o) o.insertAdjacentHTML('beforeend', '<p class="game-screen__status" data-tone="warn">بارگذاری بازی ناموفق بود؛ صفحه را دوباره باز کنید.</p>');
		return null;
	}
	root.dataset.state = 'idle';

	const $ = (s) => root.querySelector(s);
	const frame = $('[data-frame]');
	const stage = $('[data-stage]');
	const overlay = $('[data-overlay]');
	const toasts = $('[data-toasts]');
	const btn = (a) => root.querySelector(`[data-act="${a}"]`);
	const hudEl = (k) => root.querySelector(`[data-hud="${k}"]`);
	const achievements = (CATALOG[engine] && CATALOG[engine].achievements) || mod.achievements || [];
	const sound = createSound(!!store.player.sound);
	const reduced = reducedMotion();
	const accent = getComputedStyle(root).getPropertyValue('--accent').trim() || '#f59e0b';
	const startScreen = overlay.innerHTML;

	let state = 'idle';
	let score = 0;
	let activeMs = 0;
	let resumedAt = 0;
	let tokenP = null;
	let runAch = [];
	let inst = null;

	/* --- HUD --- */
	const setText = (k, v) => {
		const el = hudEl(k);
		if (el) el.textContent = v;
	};
	const showWrap = (k, on) => {
		const w = root.querySelector(`[data-hud-wrap="${k}"]`);
		if (w) w.hidden = !on;
	};
	const updateBest = () => setText('best', fa(Math.max(store.game(engine).best, score)));

	const toast = (html, tone = 'accent') => {
		const t = h(`<div class="game-toast game-toast--${tone}" role="status">${html}</div>`);
		toasts.appendChild(t);
		setTimeout(() => t.classList.add('is-out'), 2600);
		setTimeout(() => t.remove(), 3100);
	};

	const api = {
		stage,
		accent,
		reducedMotion: reduced,
		touch: isTouch(),
		sfx: (n) => sound.play(n),
		toast,
		gameId,
		engine,
		gameUrl: root.dataset.url || window.location.href.split('?')[0].split('#')[0],
		get player() {
			return store.player;
		},
		/** Match engines report the active mode ({type:'ai'|'local'|'online', level?, code?}) for resume. */
		setMode(m) {
			currentMode = m || null;
			saveSnap(true);
		},
		/** Engines may call this after a meaningful state change (move, level) to autosave sooner. */
		save() {
			saveSnap();
		},
		/** Stage aspect ratio, e.g. setRatio('16/10', '3/4') — desktop / mobile. */
		setRatio(desktop, mobile = desktop) {
			frame.style.setProperty('--game-ratio', desktop);
			frame.style.setProperty('--game-ratio-m', mobile);
		},
		/** Tell the shell a run (or match) has begun — used by match engines after their own menu.
		 *  begin({ online: true }) → token only; online matches are counted server-side when they finish. */
		begin(opts = {}) {
			if (state !== 'running') return;
			activeMs = 0;
			resumedAt = performance.now();
			tokenP = startRun(!!opts.online);
		},
		get running() {
			return state === 'running';
		},
		get best() {
			return store.game(engine).best;
		},
		setScore(n) {
			score = Math.max(0, Math.floor(n));
			setText('score', fa(score));
			updateBest();
		},
		hud({ level, lives, levelLabel, extra, extraLabel } = {}) {
			if (level !== undefined) {
				showWrap('level', true);
				setText('level', typeof level === 'number' ? fa(level) : level);
			}
			if (levelLabel) setText('level-label', levelLabel);
			if (lives !== undefined) {
				showWrap('lives', true);
				const el = hudEl('lives');
				if (el) {
					el.innerHTML = lives > 5 ? `♥ ${fa(lives)}` : '<span class="text-rose-400">' + '♥'.repeat(Math.max(0, lives)) + '</span><span class="text-slate-600">' + '♥'.repeat(Math.max(0, 3 - lives)) + '</span>';
					el.setAttribute('aria-label', `${fa(lives)} جان`);
				}
			}
			if (extra !== undefined) {
				showWrap('extra', true);
				setText('extra', extra);
			}
			if (extraLabel) setText('extra-label', extraLabel);
		},
		unlock(id) {
			const a = achievements.find((x) => x.id === id);
			if (!a || !store.unlock(engine, id)) return;
			runAch.push(id);
			syncAch(`${engine}:${id}`);
			sound.play('ach');
			toast(`<span class="game-toast__icon" aria-hidden="true">${a.icon || '★'}</span><span><b>دستاورد جدید</b> ${esc(a.title)}</span>`);
			renderStats(engine, achievements);
		},
		over(result) {
			finish(result || {});
		},
	};

	// Achievements live only in localStorage; the server only receives the total count (admin stats).
	let achTimer = 0;
	const syncAch = () => {
		clearTimeout(achTimer);
		achTimer = setTimeout(() => {
			const total = Object.values(store.player.games).reduce((n, g) => n + (g.ach ? g.ach.length : 0), 0);
			online.profile({ player_id: store.player.id, ach_count: total }).catch(() => {});
		}, 2000);
	};
	// Start a run: token for score submission + play counter (both storage modes).
	const startRun = (isOnline = false) =>
		rest
			.start(gameId, { player_id: store.player.id, nickname: store.player.nickname || '', online: isOnline ? 1 : 0 })
			.then((r) => {
				runToken = r && r.ok ? r : null;
				return r;
			})
			.catch(() => null);


	/* --- Screens --- */
	const screen = (html) => {
		overlay.innerHTML = html;
		overlay.hidden = false;
		root.dataset.state = state;
		const focusable = overlay.querySelector('[data-autofocus]') || overlay.querySelector('button');
		if (focusable && !api.touch) focusable.focus({ preventScroll: true });
	};
	const hideOverlay = () => {
		overlay.hidden = true;
		overlay.innerHTML = '';
		root.dataset.state = state;
	};

	const pauseScreen = (restored = false) =>
		screen(`<div class="game-screen">
			<p class="game-screen__kicker">${restored ? 'بازی نیمه‌تمام شما' : 'مکث'}</p>
			<p class="game-screen__title">${restored ? 'از همان‌جا ادامه دهید' : 'بازی متوقف شد'}</p>
			<p class="game-screen__text">${restored ? 'بازی قبلی را بازیابی کردم؛ برای ادامه بزنید.' : 'هر وقت آماده بودید ادامه دهید. (کلید P)'}</p>
			<div class="game-screen__actions">
				<button type="button" class="game-btn game-btn--primary" data-act="resume" data-autofocus>${ICONS.play} ادامه</button>
				<button type="button" class="game-btn" data-act="play">شروع دوباره</button>
			</div>
		</div>`);

	/* --- Lifecycle --- */
	const ensureInst = () => {
		if (!inst) inst = mod.mount(stage, api);
		return inst;
	};

	const play = (opts = {}) => {
		ensureInst();
		if (!opts.restoring) {
			snapWrite(engine, null);
			if (!opts.mode) currentMode = null;
		}
		enterFocus(!opts.restoring);
		store.game(engine).at = Date.now();
		store.save();
		state = 'running';
		score = 0;
		activeMs = 0;
		runAch = [];
		resumedAt = performance.now();
		api.setScore(0);
		// Match engines request their token in api.begin() once a mode is chosen.
		tokenP = inst.noAutoPause ? null : startRun();
		hideOverlay();
		setPauseBtn();
		stage.focus({ preventScroll: true });
		inst.start(opts);
		setModeBtn();
		emit('kmpr:game-start', { slug: engine, mode: modeName() });
	};

	/* --- Public events for add-on modules (window CustomEvents) --- */
	const emit = (name, detail) => {
		try {
			window.dispatchEvent(new CustomEvent(name, { detail }));
		} catch (e) {
			/* ignore */
		}
	};
	const modeName = () => (currentMode && currentMode.type) || (CATALOG[engine] && CATALOG[engine].kind === 'match' ? 'menu' : 'arcade');
	api.emit = emit;

	/* --- Focus (immersive) mode --- */
	let focused = false;
	let pushed = false;
	const enterFocus = (gesture = true) => {
		if (!focused) {
			focused = true;
			document.documentElement.classList.add('game-focus');
			root.classList.add('is-focus');
			if (!pushed) {
				try {
					history.pushState({ kmprFocus: engine }, '');
					pushed = true;
				} catch (e) {
					/* ignore */
				}
			}
			window.scrollTo(0, 0);
			emit('kmpr:focus-change', { active: true });
		}
		// Phones: real fullscreen + optional orientation lock (needs a user gesture; ignored when unsupported).
		if (gesture && api.touch && !fsElement() && (frame.requestFullscreen || frame.webkitRequestFullscreen)) {
			Promise.resolve((frame.requestFullscreen || frame.webkitRequestFullscreen).call(frame, { navigationUI: 'hide' }))
				.then(() => inst && inst.orientation && screen.orientation && screen.orientation.lock && screen.orientation.lock(inst.orientation).catch(() => {}))
				.catch(() => {});
		}
		setFsBtn();
	};
	const exitFocus = () => {
		if (!focused) return;
		focused = false;
		if (state === 'running') pause();
		saveSnap(true);
		document.documentElement.classList.remove('game-focus');
		root.classList.remove('is-focus');
		emit('kmpr:focus-change', { active: false });
		if (fsElement()) (document.exitFullscreen || document.webkitExitFullscreen).call(document).catch?.(() => {});
		setFsBtn();
		frame.scrollIntoView({ block: 'center' });
	};
	const leaveFocus = () => {
		// Prefer history.back() so the browser history stays clean; popstate does the work.
		if (pushed && history.state && history.state.kmprFocus) history.back();
		else exitFocus();
	};
	window.addEventListener('popstate', () => {
		pushed = false;
		if (focused) exitFocus();
	});

	/* --- Snapshots (autosave / resume) --- */
	let currentMode = null;
	let runToken = null;
	let lastSave = 0;
	const saveSnap = (force = false) => {
		if (!inst || (state !== 'running' && state !== 'paused')) return;
		if (!force && Date.now() - lastSave < 1500) return;
		lastSave = Date.now();
		let data = null;
		try {
			data = inst.serialize ? inst.serialize() : null;
		} catch (e) {
			data = null;
		}
		if (!data && !currentMode && !CATALOG[engine]) return;
		const ms = activeMs + (state === 'running' ? performance.now() - resumedAt : 0);
		snapWrite(engine, { t: Date.now(), mode: currentMode, data, score, ms: Math.round(ms), run: runToken });
	};
	setInterval(() => state === 'running' && saveSnap(), 3000);
	document.addEventListener('visibilitychange', () => document.hidden && saveSnap(true));
	window.addEventListener('pagehide', () => saveSnap(true));

	const restoreSnap = (snap) => {
		ensureInst();
		currentMode = snap.mode || null;
		if (currentMode && currentMode.type === 'online') {
			// Re-join the same room (server re-seats the same player id); no pause overlay.
			play({ restoring: true, join: currentMode.code });
			return;
		}
		play({ restoring: true, mode: currentMode || undefined });
		if (snap.data && inst.restore) {
			try {
				inst.restore(snap.data);
				score = snap.score || 0;
				api.setScore(score);
				if (snap.run) {
					// Keep the original signed run token so the total duration still validates.
					runToken = snap.run;
					tokenP = Promise.resolve(snap.run);
					activeMs = snap.ms || 0;
				}
			} catch (e) {
				snapWrite(engine, null);
			}
		}
		if (!inst.noAutoPause) {
			pause();
			pauseScreen(true);
		}
	};

	const setModeBtn = () => {
		const b = btn('mode');
		if (b) b.hidden = !(inst && inst.noAutoPause);
	};

	const pause = () => {
		if (state !== 'running') return;
		state = 'paused';
		activeMs += performance.now() - resumedAt;
		inst && inst.pause();
		setPauseBtn();
		pauseScreen();
	};

	const resume = () => {
		if (state !== 'paused') return;
		state = 'running';
		resumedAt = performance.now();
		hideOverlay();
		setPauseBtn();
		stage.focus({ preventScroll: true });
		inst && inst.resume();
	};

	const finish = async ({ score: final = score, meta = {} }) => {
		if (state !== 'running' && state !== 'paused') return;
		if (state === 'running') activeMs += performance.now() - resumedAt;
		state = 'over';
		snapWrite(engine, null);
		inst && inst.pause();
		setPauseBtn();
		final = Math.max(0, Math.floor(final));
		api.setScore(final);
		const before = store.game(engine).best;
		const { best, isBest } = store.record(engine, final, activeMs);
		if (final > 0) store.pushRecent({ type: 'run', e: engine, s: final });
		emit('kmpr:game-over', { slug: engine, mode: modeName(), score: final, won: meta.result ? meta.result === 'win' : final > 0, durationMs: Math.round(activeMs) });
		const today = new Date().toLocaleDateString('en-CA');
		const daily = store.player.daily && store.player.daily.date === today ? store.player.daily : { date: today };
		daily[engine] = Math.max(daily[engine] || 0, final);
		store.set('daily', daily);
		renderStats(engine, achievements);
		updateBest();
		sound.play(isBest && final > 0 ? 'level' : 'over');

		const p = store.player;
		const canSubmit = final > 0;
		const needsNick = canSubmit && !p.nickname;
		const achHtml = runAch.length
			? `<ul class="game-screen__ach">${runAch
					.map((id) => achievements.find((a) => a.id === id))
					.filter(Boolean)
					.map((a) => `<li>${a.icon || '★'} ${esc(a.title)}</li>`)
					.join('')}</ul>`
			: '';
		const metaHtml = meta.summary ? `<p class="game-screen__text">${esc(meta.summary)}</p>` : '';

		screen(`<div class="game-screen">
			<p class="game-screen__kicker">${isBest && final > 0 ? (before > 0 ? 'رکورد جدید!' : 'اولین رکورد شما') : 'پایان بازی'}</p>
			<p class="game-screen__score">${fa(final)}</p>
			<p class="game-screen__text">بهترین امتیاز شما: <b>${fa(best)}</b></p>
			${metaHtml}
			${achHtml}
			${
				needsNick
					? `<form class="game-nick" data-nick-form novalidate>
						<label for="game-nick-${gameId}" class="game-nick__label">برای ثبت در جدول برترین‌ها یک نام مستعار انتخاب کنید</label>
						<div class="game-nick__row">
							<input id="game-nick-${gameId}" name="nickname" maxlength="24" autocomplete="nickname" required value="${esc(rest.userName)}" placeholder="مثلاً: کدنویس شب" class="game-nick__input" data-autofocus>
							<input type="text" name="website" tabindex="-1" autocomplete="off" class="game-hp" aria-hidden="true">
							<button type="submit" class="game-btn game-btn--primary">ثبت امتیاز</button>
						</div>
					</form>`
					: ''
			}
			<p class="game-screen__status" data-submit-status aria-live="polite"></p>
			<div data-kmpr-slot="game-over"></div>
			<div class="game-screen__actions">
				<button type="button" class="game-btn game-btn--primary" data-act="play"${needsNick ? '' : ' data-autofocus'}>${ICONS.play} دوباره بازی کن</button>
			</div>
		</div>`);

		if (!canSubmit) return;
		const payload = {
			game: gameId,
			engine,
			player_id: p.id,
			score: final,
			duration_ms: Math.round(activeMs),
			meta: { ...meta, summary: undefined, achievements: runAch },
		};
		if (needsNick) {
			const form = overlay.querySelector('[data-nick-form]');
			form.addEventListener('submit', (e) => {
				e.preventDefault();
				const nick = form.nickname.value.trim().slice(0, 24);
				if (!nick) {
					form.nickname.focus();
					return;
				}
				store.set('nickname', nick);
				online.profile({ player_id: store.player.id, nickname: nick }).catch(() => {});
				renderStats(engine, achievements);
				form.remove();
				submit({ ...payload, nickname: nick, website: form.website.value });
			});
		} else {
			submit({ ...payload, nickname: p.nickname });
		}
	};

	const submit = async (payload) => {
		const status = () => overlay.querySelector('[data-submit-status]');
		const say = (t, tone = '') => {
			const s = status();
			if (s) {
				s.textContent = t;
				s.dataset.tone = tone;
			}
		};
		if (LOCAL_ONLY) {
			say('رکورد شما روی همین دستگاه ذخیره شد.', 'ok');
			return;
		}
		say('در حال ثبت امتیاز…');
		const run = await tokenP;
		if (!run || !run.ok) {
			say('ارتباط با سرور برقرار نشد؛ امتیاز فقط روی همین دستگاه ذخیره شد.', 'warn');
			return;
		}
		try {
			const res = await rest.submit({ ...payload, token: run.token });
			if (res && res.ok) {
				if (res.stored === false) {
					if (res.local) say('رکورد شما روی همین دستگاه ذخیره شد.', 'ok');
					return;
				}
				say(res.rank ? `ثبت شد! رتبه شما در جدول: ${fa(res.rank)}` : 'امتیاز ثبت شد.', 'ok');
				const lb = leaderboards.get(gameId);
				lb && lb.set(res.rows);
				if (res.best) store.merge(engine, { best: res.best });
				sync();
			} else {
				say((res && res.message) || 'ثبت امتیاز ناموفق بود.', 'warn');
			}
		} catch (e) {
			say('ارتباط با سرور برقرار نشد.', 'warn');
		}
	};

	/* --- Server sync of personal stats --- */
	const sync = async () => {
		try {
			const res = await rest.player(store.player.id);
			if (!res || !res.ok) return;
			if (res.player) store.set('hash', res.player);
			if (!store.player.nickname && res.nickname) store.set('nickname', res.nickname);
			if (res.profile) {
				if (!store.player.avatar && res.profile.avatar) store.set('avatar', res.profile.avatar);
				store.set('xp', res.profile.xp);
				store.set('level', res.profile.level);
			}
			(res.games || []).forEach((g) => g.engine && store.merge(g.engine, g));
			renderStats(engine, achievements);
			updateBest();
		} catch (e) {
			/* offline is fine */
		}
	};

	/* --- Controls --- */
	const setPauseBtn = () => {
		const b = btn('pause');
		if (!b) return;
		const running = state === 'running';
		b.hidden = !!(inst && inst.noAutoPause);
		b.disabled = !(state === 'running' || state === 'paused');
		b.innerHTML = running ? ICONS.pause : ICONS.play;
		b.setAttribute('aria-label', running ? 'توقف بازی' : 'ادامه بازی');
	};
	const setSoundBtn = () => {
		const b = btn('sound');
		if (!b) return;
		b.innerHTML = sound.on ? ICONS.soundOn : ICONS.soundOff;
		b.setAttribute('aria-pressed', String(sound.on));
		b.setAttribute('aria-label', sound.on ? 'قطع صدا' : 'روشن کردن صدا');
	};

	const fsElement = () => document.fullscreenElement || document.webkitFullscreenElement;
	const isFs = () => fsElement() === frame || frame.classList.contains('is-pseudo-fs');
	const setFsBtn = () => {
		const b = btn('fullscreen');
		const x = btn('exit');
		if (x) x.hidden = !document.documentElement.classList.contains('game-focus');
		if (!b) return;
		b.innerHTML = isFs() ? ICONS.shrink : ICONS.expand;
		b.setAttribute('aria-label', isFs() ? 'خروج از تمام صفحه' : 'تمام صفحه');
		root.classList.toggle('is-fullscreen', isFs());
	};
	const toggleFs = async () => {
		if (isFs()) {
			if (fsElement()) await (document.exitFullscreen || document.webkitExitFullscreen).call(document);
			frame.classList.remove('is-pseudo-fs');
			document.documentElement.classList.remove('game-lock');
		} else if (frame.requestFullscreen || frame.webkitRequestFullscreen) {
			try {
				await (frame.requestFullscreen || frame.webkitRequestFullscreen).call(frame, { navigationUI: 'hide' });
			} catch (e) {
				frame.classList.add('is-pseudo-fs');
				document.documentElement.classList.add('game-lock');
			}
		} else {
			frame.classList.add('is-pseudo-fs');
			document.documentElement.classList.add('game-lock');
		}
		setFsBtn();
	};

	root.addEventListener('click', (e) => {
		const b = e.target.closest('[data-act]');
		if (!b || !root.contains(b)) return;
		const act = b.dataset.act;
		if (act === 'play') play();
		else if (act === 'menu') state === 'over' ? play() : null;
		else if (act === 'resume') resume();
		else if (act === 'pause') state === 'running' ? pause() : resume();
		else if (act === 'restart') {
			// Fresh game in the same mode (match engines) — snapshot cleared.
			snapWrite(engine, null);
			if (inst && inst.noAutoPause && currentMode && currentMode.type !== 'online') play({ mode: currentMode });
			else play();
		} else if (act === 'mode') {
			snapWrite(engine, null);
			currentMode = null;
			play();
		} else if (act === 'exit') leaveFocus(); else if (act === 'sound') {
			sound.set(!sound.on);
			store.set('sound', sound.on);
			setSoundBtn();
			sound.play('click');
			window.dispatchEvent(new CustomEvent('kmpr:sound-changed'));
		} else if (act === 'fullscreen') toggleFs();
	});

	document.addEventListener('keydown', (e) => {
		if (e.target.closest && e.target.closest('input, textarea, select, [contenteditable]') && e.key !== 'Escape') return;
		if (!root.contains(document.activeElement) && document.activeElement !== document.body) return;
		if (e.key === 'Escape' && focused && !document.querySelector('dialog[open]')) {
			e.preventDefault();
			leaveFocus();
			return;
		}
		if (inst && inst.noAutoPause) return;
		if (e.key === 'Escape' || e.key === 'p' || e.key === 'P' || e.key === 'پ') {
			if (state === 'running') {
				e.preventDefault();
				pause();
			} else if (state === 'paused' && e.key !== 'Escape') resume();
			else if (e.key === 'Escape' && frame.classList.contains('is-pseudo-fs')) toggleFs();
		}
	});

	document.addEventListener('fullscreenchange', setFsBtn);
	document.addEventListener('webkitfullscreenchange', setFsBtn);
	// Auto-pause (turn-based/online engines opt out with inst.noAutoPause).
	const autoPause = () => !(inst && inst.noAutoPause) && pause();
	document.addEventListener('visibilitychange', () => document.hidden && autoPause());
	window.addEventListener('blur', autoPause);
	if ('IntersectionObserver' in window) {
		new IntersectionObserver(
			(entries) => entries.forEach((en) => !en.isIntersecting && !isFs() && autoPause()),
			{ threshold: 0.25 }
		).observe(frame);
	}

	// Global sound toggle in the Game Center header.
	window.addEventListener('kmpr:sound', (e) => {
		sound.set(!!e.detail);
		setSoundBtn();
	});

	// Initial UI
	setPauseBtn();
	setSoundBtn();
	setFsBtn();
	updateBest();
	renderStats(engine, achievements);
	document.querySelectorAll(`[data-leaderboard][data-game-id="${gameId}"]`).forEach(bindLeaderboard);
	sync();
	// Engines may draw an attract-mode frame behind the start screen.
	ensureInst();
	// Invite links: ?room=CODE → jump straight into the online room.
	const roomCode = new URLSearchParams(window.location.search).get('room');
	const snap = snapRead(engine);
	if (roomCode && CATALOG[engine] && CATALOG[engine].online) {
		play({ join: roomCode.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8) });
	} else if (snap && (snap.data || snap.mode)) {
		restoreSnap(snap);
	}

	return {
		play,
		pause,
		resume,
		get state() {
			return state;
		},
		destroy() {
			inst && inst.destroy();
			if (mod.destroy) mod.destroy();
		},
		_startScreen: startScreen,
	};
}

