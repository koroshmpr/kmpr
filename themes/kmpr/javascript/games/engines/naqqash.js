/**
 * «نقاش‌باشی» — online draw-and-guess for 2–8 players (server: inc/games-draw.php).
 *
 * Sync = plain HTTP polling (no websockets on shared hosting):
 *  - every response carries new events after a since-id cursor (`cur`) and the room view only when the
 *    room version changed (`v`), so an idle poll is ~80 bytes;
 *  - the drawer sends compact stroke batches (quantised 800×600 points, delta-encoded, min-distance
 *    simplified) at most every 400 ms while drawing — the POST answer doubles as the drawer's poll;
 *  - guessers poll every 700 ms while strokes flow, 1.1 s otherwise, 1.6 s in lobby/reveal, 6 s hidden.
 * The secret word never reaches a guesser before they guess it or the turn ends (server-side view).
 *
 * Stroke ops (joined by ';'): p<id>,<col>,<size>,x,y,dx,dy… (new) · a<id>,dx,dy… (append) · u<id> (undo) · x (clear)
 */
import { CATALOG } from '../catalog.js';
import { store } from '../lib/store.js';
import { avatarSvg } from '../lib/avatar.js';
import { esc, fa, h, listeners } from '../lib/util.js';

export const achievements = CATALOG.naqqash.achievements;

const W = 800;
const H = 600;
const COLORS = ['#fffdf7', '#16120f', '#e11d48', '#f97316', '#facc15', '#16a34a', '#0ea5e9', '#2563eb', '#7c3aed', '#ec4899', '#8b5a2b', '#94a3b8'];
const COLOR_NAMES = ['پاک‌کن', 'مشکی', 'قرمز', 'نارنجی', 'زرد', 'سبز', 'فیروزه‌ای', 'آبی', 'بنفش', 'صورتی', 'قهوه‌ای', 'خاکستری'];
const SIZES = [4, 9, 18, 30]; // index 3 = eraser
const SEND_MS = 400;
const MIN_DIST = 2.6;
const DIFF = { easy: 'آسان', medium: 'متوسط', hard: 'سخت' };

const ICON = {
	send: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 4 3 11l7 2 2 7 8-16Z"/><path d="m10 13 4-4"/></svg>',
	undo: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/></svg>',
	clear: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg>',
	eraser: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7 21-4-4a2 2 0 0 1 0-3l10-10a2 2 0 0 1 3 0l5 5a2 2 0 0 1 0 3l-8 9H7Z"/><path d="M21 21H7M6 11l7 7"/></svg>',
	link: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>',
	dice: '<svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 8h.01M16 8h.01M12 12h.01M8 16h.01M16 16h.01"/></svg>',
	brush: '<svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18.4 2.6a2 2 0 0 1 2.9 2.9L12 14.8 9.2 12Z"/><path d="M7 14c-2 0-3 1.5-3 3.5 0 1-.5 2-1.5 2.5 1 .7 2.6 1 4 1 2.8 0 4.5-1.8 4.5-4"/></svg>',
	users: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a5.5 5.5 0 0 1 3.5 5.5"/></svg>',
};

const dcfg = () => ({ on: true, pub: true, time: 75, rounds: 3, max: 8, cats: { mix: 'همه' }, ...(window.kmprDraw || {}) });
const base = () => ((window.kmprGames && window.kmprGames.rest) || '/wp-json/kmpr/v1/games').replace(/\/$/, '') + '/draw';

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	const stats = { req: 0, up: 0, down: 0, t0: Date.now() };
	const cfg = dcfg();

	const root = h(`<div class="nq" data-nq>
		<header class="nq-top">
			<span class="nq-round" data-round></span>
			<div class="nq-word" data-word aria-live="polite"></div>
			<span class="nq-timer" data-timer hidden><span data-timer-n></span></span>
		</header>
		<div class="nq-main">
			<div class="nq-stagecol">
				<div class="nq-board" data-board>
					<canvas class="nq-canvas" data-canvas aria-label="بوم نقاشی"></canvas>
					<div class="nq-bover" data-bover hidden></div>
					<div class="nq-pal" data-pal hidden role="group" aria-label="رنگ‌ها">${COLORS.slice(1)
						.map((c, i) => `<button type="button" class="nq-sw" data-col="${i + 1}" style="--c:${c}" aria-label="${COLOR_NAMES[i + 1]}"></button>`)
						.join('')}</div>
				</div>
				<div class="nq-tools" data-tools hidden>
					<button type="button" class="nq-tool" data-t="pal" aria-label="انتخاب رنگ"><span class="nq-dot" data-curcol></span></button>
					<button type="button" class="nq-tool" data-t="size" aria-label="ضخامت قلم"><span class="nq-size" data-cursize></span></button>
					<button type="button" class="nq-tool" data-t="eraser" aria-label="پاک‌کن" aria-pressed="false">${ICON.eraser}</button>
					<button type="button" class="nq-tool" data-t="undo" aria-label="برگرداندن">${ICON.undo}</button>
					<button type="button" class="nq-tool nq-tool--warn" data-t="clear" aria-label="پاک کردن همه">${ICON.clear}</button>
				</div>
			</div>
			<aside class="nq-side">
				<ul class="nq-players" data-players aria-label="بازیکن‌ها"></ul>
				<ol class="nq-chat" data-chat aria-live="polite"></ol>
				<form class="nq-form" data-form autocomplete="off">
					<label class="sr-only" for="nq-in-${api.gameId}">حدس یا پیام</label>
					<input id="nq-in-${api.gameId}" class="nq-input" data-input maxlength="60" enterkeyhint="send" placeholder="حدست را بنویس…" autocomplete="off" autocorrect="off" spellcheck="false">
					<button type="submit" class="nq-send" aria-label="ارسال">${ICON.send}</button>
				</form>
			</aside>
		</div>
		<div class="nq-layer" data-layer hidden></div>
	</div>`);
	stage.appendChild(root);
	const $ = (s) => root.querySelector(s);
	const canvas = $('[data-canvas]');
	const ctx = canvas.getContext('2d');
	const board = $('[data-board]');
	const bover = $('[data-bover]');
	const pal = $('[data-pal]');
	const tools = $('[data-tools]');
	const layer = $('[data-layer]');
	const chatEl = $('[data-chat]');
	const input = $('[data-input]');
	const form = $('[data-form]');
	const wordEl = $('[data-word]');
	const timerEl = $('[data-timer]');

	/* ---------------- network ---------------- */
	let room = null; // { code, token, seat, v, cur, st, off }
	let pollT = 0;
	let qBusy = 0;
	let chain = Promise.resolve();
	let lastHadStrokes = false;
	let alive = true;
	let started = false;

	const call = async (method, path, { body, query } = {}) => {
		const url = new URL(base() + path, window.location.origin);
		if (query) Object.entries(query).forEach(([k, v]) => url.searchParams.set(k, v));
		const headers = { Accept: 'application/json' };
		if (body) headers['Content-Type'] = 'application/json';
		if (window.kmprGames && window.kmprGames.nonce) headers['X-WP-Nonce'] = window.kmprGames.nonce;
		if (room && room.token) headers['X-Room-Token'] = room.token;
		const payload = body ? JSON.stringify(body) : undefined;
		stats.req++;
		stats.up += (payload ? payload.length : 0) + url.pathname.length + url.search.length;
		let data;
		try {
			const res = await fetch(url, { method, headers, body: payload, credentials: 'same-origin', cache: 'no-store' });
			const txt = await res.text();
			stats.down += txt.length;
			data = JSON.parse(txt);
			if (!res.ok && data) data.ok = false;
		} catch (e) {
			data = { ok: false, code: 'net', message: 'اتصال برقرار نشد.' };
		}
		return data || { ok: false };
	};
	const queued = (fn) => {
		qBusy++;
		const p = chain.then(fn).finally(() => qBusy--);
		chain = p.catch(() => {});
		return p;
	};
	const me = () => {
		const p = store.player;
		return { player_id: p.id, nickname: p.nickname || '', avatar: p.avatar || { style: 'blocks', hue: 32 } };
	};

	const schedule = () => {
		clearTimeout(pollT);
		if (!room || !alive) return;
		const st = room.st || {};
		let ms = 1100;
		if (document.hidden) ms = 6000;
		else if (st.ph === 'draw' && st.dr !== room.seat) ms = lastHadStrokes ? 700 : 1000;
		else if (st.ph === 'draw') ms = 1100;
		else if (st.ph === 'pick') ms = 900;
		else ms = 1600;
		ms += Math.min(8000, fails * 1500);
		pollT = setTimeout(poll, ms);
	};
	let fails = 0;
	const poll = () => {
		if (!room) return;
		if (qBusy) return schedule();
		queued(async () => {
			const res = await call('GET', `/${room.code}`, { query: { since: room.cur, v: room.v } });
			handle(res);
		}).finally(schedule);
	};
	const act = (a, extra = {}) =>
		queued(async () => {
			if (!room) return null;
			const res = await call('POST', `/${room.code}`, { body: { a, since: room.cur, v: room.v, ...extra } });
			handle(res);
			return res;
		});

	const handle = (res) => {
		if (!res || !res.ok) {
			fails++;
			if (!res) return;
			if (res.code === 'no_room') {
				api.toast('اتاق بسته شد یا منقضی شده است.');
				exitRoom(false);
			} else if (res.code === 'auth' && room) {
				rejoin(room.code);
			} else if (res.code === 'off') {
				exitRoom(false);
				offScreen(res.message);
			} else if (res.message && res.code !== 'net') sysLine(res.message, 'err');
			return;
		}
		fails = 0;
		if (!room) return;
		if (typeof res.now === 'number') room.off = res.now - Date.now() / 1000;
		if (res.st) applyState(res.st);
		room.v = res.v;
		if (Array.isArray(res.ev)) applyEvents(res.ev);
		room.cur = Math.max(room.cur, res.cur || 0);
		if (res.hit === 'near') {
			sysLine(`«${lastGuess}» — نزدیکه!`, 'near');
			api.sfx('flip');
		}
		if (res.hit === 'hit') {
			api.sfx('coin');
			api.unlock('first_hit');
			if (res.st && res.st.gs && res.st.gs[0] === room.seat) api.unlock('fastest');
		}
	};

	/* ---------------- rooms ---------------- */
	const adopt = (res) => {
		if (!res || !res.ok) return false;
		room = { code: res.st.code, token: res.token, seat: res.seat, v: 0, cur: 0, st: null, off: 0 };
		strokes = new Map();
		canvasTurn = -1;
		chatEl.innerHTML = '';
		prevPh = '';
		prevTurn = -1;
		hideLayer();
		handle(res);
		if (!started) {
			started = true;
			api.begin({ online: true });
		}
		api.setMode && api.setMode({ type: 'online', code: room.code });
		try {
			history.replaceState(null, '', `${location.pathname}?room=${room.code}`);
		} catch (e) {
			/* ignore */
		}
		schedule();
		return true;
	};
	const saveNick = () => {
		const inp = layer.querySelector('[data-nick]');
		if (!inp) return true;
		const nick = inp.value.trim().slice(0, 24);
		if (!nick) {
			inp.focus();
			menuErr('یک نام مستعار بنویسید.');
			return false;
		}
		if (nick !== store.player.nickname) store.set('nickname', nick);
		return true;
	};
	const busyBtns = (on) => layer.querySelectorAll('button').forEach((b) => (b.disabled = on));
	const create = async (quick) => {
		if (!saveNick()) return;
		busyBtns(true);
		const res = await call('POST', quick ? '/quick' : '/create', { body: { game: api.gameId, public: false, opts: { rounds: cfg.rounds, time: cfg.time, cat: 'mix' }, ...me() } });
		busyBtns(false);
		if (!adopt(res)) menuErr(res.message || 'ساخت اتاق ممکن نشد.');
		else if (quick) sysLine(res.matched ? 'به یک اتاق عمومی وصل شدید.' : 'اتاق عمومی ساخته شد؛ تا آمدن بقیه، لینک را هم بفرستید.', 'sys');
	};
	const join = async (code) => {
		code = String(code || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
		if (code.length !== 6) return menuErr('کد اتاق ۶ حرف است.');
		if (layer.querySelector('[data-nick]') && !saveNick()) return;
		busyBtns(true);
		room = null;
		const res = await call('POST', `/${code}/join`, { body: me() });
		busyBtns(false);
		if (!adopt(res)) {
			showMenu();
			menuErr(res.message || 'ورود به اتاق ممکن نشد.');
		}
	};
	let rejoining = false;
	const rejoin = async (code) => {
		if (rejoining) return;
		rejoining = true;
		const res = await call('POST', `/${code}/join`, { body: me() });
		rejoining = false;
		if (res && res.ok && room) {
			room.token = res.token;
			room.seat = res.seat;
			handle(res);
		}
	};
	const exitRoom = (sendLeave = true) => {
		const r = room;
		clearTimeout(pollT);
		room = null;
		if (sendLeave && r) {
			const headers = { 'Content-Type': 'application/json', 'X-Room-Token': r.token };
			if (window.kmprGames && window.kmprGames.nonce) headers['X-WP-Nonce'] = window.kmprGames.nonce;
			fetch(`${base()}/${r.code}`, { method: 'POST', headers, body: JSON.stringify({ a: 'leave' }), credentials: 'same-origin', keepalive: true }).catch(() => {});
		}
		api.setMode && api.setMode(null);
		try {
			history.replaceState(null, '', location.pathname);
		} catch (e) {
			/* ignore */
		}
		showMenu();
	};

	/* ---------------- layer screens ---------------- */
	const showLayer = (html) => {
		layer.innerHTML = `<div class="nq-card">${html}</div>`;
		layer.hidden = false;
	};
	const hideLayer = () => {
		layer.hidden = true;
		layer.innerHTML = '';
	};
	const menuErr = (t) => {
		const e = layer.querySelector('[data-err]');
		if (e) e.textContent = t;
	};
	const offScreen = (msg) =>
		showLayer(`<p class="nq-kicker">نقاش‌باشی</p><p class="nq-title">${esc(msg || 'حالت آنلاین این بازی موقتاً خاموش است.')}</p><p class="nq-muted">کمی بعد دوباره سر بزنید؛ بقیه بازی‌های گیم‌سنتر در دسترس‌اند.</p>`);
	const showMenu = () => {
		if (!cfg.on) return offScreen();
		const p = store.player;
		const cur = room;
		showLayer(`<p class="nq-kicker">${ICON.brush}<span>نقاش‌باشی · دورهمی آنلاین</span></p>
			<p class="nq-title">بکش، حدس بزن، برنده شو!</p>
			<p class="nq-muted">۲ تا ۸ نفر · هر نوبت یک نفر نقاشی می‌کشد و بقیه حدس می‌زنند.</p>
			${cur ? `<div class="nq-row"><button type="button" class="nq-btn nq-btn--pri" data-l="back">ادامه همین اتاق</button><button type="button" class="nq-btn nq-btn--ghost" data-l="leave">ترک اتاق</button></div>` : ''}
			<label class="nq-label" for="nq-nick-${api.gameId}">نام شما</label>
			<input id="nq-nick-${api.gameId}" class="nq-input nq-input--card" data-nick maxlength="24" value="${esc(p.nickname || '')}" placeholder="یک نام مستعار بنویسید" autocomplete="nickname">
			<div class="nq-actions">
				<button type="button" class="nq-action" data-l="create">${ICON.link}<span><b>ساخت اتاق و دعوت دوستان</b><small>کد و لینک اختصاصی؛ میزبان بازی را شروع می‌کند</small></span></button>
				${cfg.pub ? `<button type="button" class="nq-action nq-action--alt" data-l="quick">${ICON.dice}<span><b>بازی سریع</b><small>به یک اتاق عمومی بپیوند یا یکی بساز</small></span></button>` : ''}
			</div>
			<form class="nq-codeform" data-l-join>
				<label class="sr-only" for="nq-code-${api.gameId}">کد اتاق</label>
				<input id="nq-code-${api.gameId}" name="code" class="nq-input nq-input--card nq-code" dir="ltr" maxlength="6" placeholder="کد اتاق" autocomplete="off" autocapitalize="characters" spellcheck="false">
				<button type="submit" class="nq-btn nq-btn--ghost">ورود با کد</button>
			</form>
			<p class="nq-err" data-err aria-live="polite"></p>`);
	};

	const inviteBlock = (st) => {
		const url = st.share || `${api.gameUrl}?room=${st.code}`;
		const text = `بیا «نقاش‌باشی» بازی کنیم! کد اتاق: ${st.code}`;
		const friends = window.kmprFriends && window.kmprFriends.available && typeof window.kmprFriends.invite === 'function';
		return `<p class="nq-code-big" dir="ltr">${esc(st.code)}</p>
			<div class="nq-grid2">
				<button type="button" class="nq-btn nq-btn--pri" data-l="copy" data-url="${esc(url)}">کپی لینک دعوت</button>
				${friends ? `<button type="button" class="nq-btn nq-btn--ghost" data-l="friends">${ICON.users}<span>دعوت دوستان</span></button>` : navigator.share ? `<button type="button" class="nq-btn nq-btn--ghost" data-l="share" data-url="${esc(url)}" data-text="${esc(text)}">اشتراک‌گذاری</button>` : `<a class="nq-btn nq-btn--ghost" href="https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}" target="_blank" rel="noopener">واتس‌اپ</a>`}
				<a class="nq-btn nq-btn--ghost" href="https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}" target="_blank" rel="noopener">تلگرام</a>
				<a class="nq-btn nq-btn--ghost" href="https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}" target="_blank" rel="noopener">واتس‌اپ</a>
			</div>`;
	};

	const lobbyScreen = (st) => {
		const isHost = st.host === room.seat;
		const on = st.pl.filter((p) => p.on);
		const opt = (key, label, choices, val) =>
			`<div class="nq-opt"><span>${label}</span><div class="nq-seg" role="group" aria-label="${label}">${choices
				.map(([v, l]) => `<button type="button" class="nq-segbtn" data-opt="${key}" data-val="${esc(v)}" aria-pressed="${String(String(val) === String(v))}" ${isHost ? '' : 'disabled'}>${esc(l)}</button>`)
				.join('')}</div></div>`;
		const cats = Object.entries(cfg.cats || { mix: 'همه' });
		showLayer(`<p class="nq-kicker">${st.pub ? 'اتاق عمومی' : 'اتاق خصوصی'} · ${fa(on.length)} از ${fa(st.max)} نفر</p>
			<p class="nq-title">${isHost ? 'دوستانت را دعوت کن' : 'منتظر شروع میزبان…'}</p>
			${inviteBlock(st)}
			<ul class="nq-lobbyl">${st.pl
				.filter((p) => !p.l)
				.map((p) => `<li class="${p.on ? '' : 'is-off'}">${avatarSvg(p.p, p.a, 28)}<span>${esc(p.n)}${p.s === room.seat ? ' (شما)' : ''}</span>${p.s === st.host ? '<em>میزبان</em>' : ''}</li>`)
				.join('')}</ul>
			${opt('rounds', 'دور', [[2, '۲'], [3, '۳'], [4, '۴']], st.rounds)}
			${opt('time', 'زمان', [[60, '۶۰ث'], [75, '۷۵ث'], [90, '۹۰ث']], st.time)}
			${opt('cat', 'کلمه‌ها', cats, st.cat)}
			${isHost ? `<button type="button" class="nq-btn nq-btn--pri nq-btn--wide" data-l="start" ${on.length < 2 ? 'disabled' : ''}>${on.length < 2 ? 'حداقل ۲ نفر لازم است' : 'شروع بازی'}</button>` : '<p class="nq-muted nq-wait"><span class="nq-dots" aria-hidden="true"><i></i><i></i><i></i></span> میزبان بازی را شروع می‌کند</p>'}
			<button type="button" class="nq-btn nq-btn--ghost nq-btn--wide" data-l="leave">ترک اتاق</button>`);
	};

	const podium = (st) => {
		const rows = st.pl.filter((p) => st.sc[p.s] !== undefined).sort((a, b) => (st.sc[b.s] || 0) - (st.sc[a.s] || 0));
		const top = rows.slice(0, 3);
		const order = [1, 0, 2].filter((i) => top[i]);
		const isHost = st.host === room.seat;
		const mine = st.sc[room.seat] || 0;
		const best = rows.length ? st.sc[rows[0].s] || 0 : 0;
		showLayer(`<p class="nq-kicker">پایان بازی</p>
			<p class="nq-title">${mine > 0 && mine === best ? 'تو نقاش‌باشی شدی! 🎉' : 'سکوی برنده‌ها'}</p>
			<div class="nq-podium">${order
				.map((i) => {
					const p = top[i];
					return `<div class="nq-pod nq-pod--${i + 1}"><div class="nq-pod__av">${avatarSvg(p.p, p.a, 44)}</div><b>${esc(p.n)}</b><span>${fa(st.sc[p.s] || 0)}</span><div class="nq-pod__bar">${fa(i + 1)}</div></div>`;
				})
				.join('')}</div>
			${rows.length > 3 ? `<ol class="nq-rest" start="4">${rows.slice(3).map((p) => `<li><span>${esc(p.n)}</span><b>${fa(st.sc[p.s] || 0)}</b></li>`).join('')}</ol>` : ''}
			<div class="nq-grid2">
				${isHost ? '<button type="button" class="nq-btn nq-btn--pri" data-l="again">دوباره با همین جمع</button>' : '<p class="nq-muted">میزبان می‌تواند دوباره شروع کند.</p>'}
				<button type="button" class="nq-btn nq-btn--ghost" data-l="leave">خروج از اتاق</button>
			</div>`);
	};

	/* ---------------- state → UI ---------------- */
	let lastGuess = '';
	let prevPh = '';
	let prevTurn = -1;
	const isDrawer = () => room && room.st && room.st.ph === 'draw' && room.st.dr === room.seat;
	const nick = (seat) => {
		const p = room && room.st ? room.st.pl.find((x) => x.s === seat) : null;
		return p ? p.n : 'بازیکن';
	};

	const applyState = (st) => {
		const prev = room.st;
		room.st = st;
		room.seat = st.me;
		if (st.turn !== canvasTurn) {
			strokes = new Map();
			local = null;
			canvasTurn = st.turn;
			redraw();
		}
		const meDr = st.dr === room.seat;
		// Transitions
		if (prevPh !== st.ph || prevTurn !== st.turn) {
			if (st.ph === 'pick' && meDr) api.sfx('level');
			if (st.ph === 'draw' && prevPh === 'pick') sysLine(meDr ? 'نوبت توست؛ بکش!' : `${nick(st.dr)} شروع به کشیدن کرد.`, 'sys');
			if (st.ph === 'reveal' && st.last) {
				const why = { time: 'وقت تمام شد', all: 'همه حدس زدند', left: 'نقاش رفت' }[st.last.why] || '';
				sysLine(`کلمه «${st.last.w || '—'}» بود${why ? ` · ${why}` : ''}`, 'reveal');
				if (st.last.why === 'all' && st.last.dr === room.seat) api.unlock('artist');
			}
			if (st.ph === 'over' && prevPh !== 'over') {
				const sc = st.sc[room.seat] || 0;
				const best = Math.max(0, ...Object.values(st.sc).map(Number));
				if (sc > 0 && sc === best) {
					api.unlock('podium');
					api.sfx('level');
				} else api.sfx('over');
				store.pushRecent({ type: 'match', e: api.engine, r: sc > 0 && sc === best ? 'win' : 'loss', o: '' });
				if (api.emit) api.emit('kmpr:game-over', { slug: api.engine, mode: 'online', score: sc, won: sc > 0 && sc === best, durationMs: 0 });
			}
			if (st.ph === 'pick' && prevPh === 'lobby' && st.host === room.seat && st.pl.filter((p) => p.on).length >= 3) api.unlock('host');
		}
		prevPh = st.ph;
		prevTurn = st.turn;
		api.setScore(st.sc[room.seat] || 0);
		api.hud({ level: st.round || 0, levelLabel: 'دور' });

		// Layer screens
		if (st.ph === 'lobby') lobbyScreen(st);
		else if (st.ph === 'over') podium(st);
		else if (!layer.hidden && (!prev || prev.ph === 'lobby' || prev.ph === 'over')) hideLayer();

		renderTop();
		renderPlayers();
		renderBoardOverlay();
		const drawing = st.ph === 'draw' && meDr;
		tools.hidden = !drawing;
		root.classList.toggle('is-drawer', drawing);
		if (!drawing) pal.hidden = true;
		const guessed = st.gs.includes(room.seat);
		input.disabled = drawing;
		input.placeholder = drawing ? 'تو نقاشی؛ فقط بکش!' : st.ph === 'draw' && guessed ? 'درست گفتی! با حدس‌زده‌ها گپ بزن' : st.ph === 'draw' ? 'حدست را بنویس…' : 'پیام بنویس…';
	};

	const renderTop = () => {
		const st = room && room.st;
		if (!st) {
			wordEl.innerHTML = '';
			$('[data-round]').textContent = '';
			timerEl.hidden = true;
			return;
		}
		$('[data-round]').textContent = st.round ? `دور ${fa(st.round)} از ${fa(st.rounds)}` : 'لابی';
		if (st.ph === 'draw' && st.word) {
			wordEl.innerHTML = `<span class="nq-word__lbl">${st.dr === room.seat ? 'بکش:' : 'کلمه:'}</span><b class="nq-word__w">${esc(st.word)}</b>`;
		} else if (st.ph === 'draw' && st.mask) {
			const cells = [...st.mask].map((c) => (c === '_' ? '<span class="nq-cell"></span>' : c === ' ' ? '<span class="nq-gap"></span>' : c === '|' ? '<span class="nq-zw"></span>' : `<span class="nq-cell is-hint">${esc(c)}</span>`)).join('');
			const n = [...st.mask].filter((c) => c !== ' ' && c !== '|').length;
			wordEl.innerHTML = `<span class="nq-mask" dir="rtl" aria-label="${fa(n)} حرف">${cells}</span><small class="nq-word__n">${fa(n)}</small>`;
		} else if (st.ph === 'pick') {
			wordEl.innerHTML = `<span class="nq-word__lbl">${st.dr === room.seat ? 'یک کلمه انتخاب کن' : `${esc(nick(st.dr))} دارد انتخاب می‌کند…`}</span>`;
		} else if (st.ph === 'reveal' && st.last) {
			wordEl.innerHTML = `<span class="nq-word__lbl">کلمه:</span><b class="nq-word__w">${esc(st.last.w || '—')}</b>`;
		} else wordEl.innerHTML = '';
		timerEl.hidden = !(st.ph === 'draw' || st.ph === 'pick' || st.ph === 'reveal');
		tickTimer();
	};
	const tickTimer = () => {
		const st = room && room.st;
		if (!st || timerEl.hidden) return;
		const left = Math.max(0, Math.ceil(st.end - (Date.now() / 1000 + room.off)));
		timerEl.querySelector('[data-timer-n]').textContent = fa(left);
		const total = st.ph === 'draw' ? st.time : st.ph === 'pick' ? 15 : 6;
		timerEl.style.setProperty('--p', String(Math.min(1, left / total)));
		timerEl.classList.toggle('is-low', st.ph === 'draw' && left <= 10);
	};

	const renderPlayers = () => {
		const st = room.st;
		const list = st.pl.filter((p) => !p.l).sort((a, b) => (st.sc[b.s] || 0) - (st.sc[a.s] || 0));
		$('[data-players]').innerHTML = list
			.map((p) => {
				const tags = [p.s === st.dr && (st.ph === 'draw' || st.ph === 'pick') ? '<i class="nq-tag nq-tag--dr" title="نقاش">🖌</i>' : '', st.gs.includes(p.s) ? '<i class="nq-tag nq-tag--ok" title="حدس زد">✓</i>' : '', p.s === st.host ? '<i class="nq-tag" title="میزبان">★</i>' : ''].join('');
				return `<li class="nq-pl ${p.on ? '' : 'is-off'} ${p.s === room.seat ? 'is-me' : ''} ${st.gs.includes(p.s) ? 'is-ok' : ''}">${avatarSvg(p.p, p.a, 26)}<span class="nq-pl__n">${esc(p.n)}</span>${tags}<b class="nq-pl__s">${fa(st.sc[p.s] || 0)}</b></li>`;
			})
			.join('');
	};

	const renderBoardOverlay = () => {
		const st = room.st;
		let html = '';
		if (st.ph === 'pick' && st.dr === room.seat && st.ch) {
			html = `<div class="nq-pick"><p>یکی را برای کشیدن انتخاب کن</p><div class="nq-pick__row">${st.ch
				.map((c, i) => `<button type="button" class="nq-choice nq-choice--${c.df}" data-pick="${i}"><b>${esc(c.w)}</b><small>${DIFF[c.df] || ''}</small></button>`)
				.join('')}</div></div>`;
		} else if (st.ph === 'pick') {
			const p = st.pl.find((x) => x.s === st.dr);
			html = `<div class="nq-wait-card">${p ? avatarSvg(p.p, p.a, 48) : ''}<p><b>${esc(nick(st.dr))}</b> دارد کلمه انتخاب می‌کند…</p></div>`;
		} else if (st.ph === 'reveal' && st.last) {
			const gains = Object.entries(st.last.gains || {})
				.map(([s, g]) => [Number(s), g])
				.sort((a, b) => b[1] - a[1]);
			html = `<div class="nq-reveal"><p class="nq-reveal__k">کلمه</p><p class="nq-reveal__w">${esc(st.last.w || '—')}</p><ul>${gains
				.map(([s, g]) => `<li class="${g > 0 ? 'is-up' : ''}"><span>${esc(nick(s))}${s === st.last.dr ? ' 🖌' : ''}</span><b>+${fa(g)}</b></li>`)
				.join('') || '<li><span>کسی حدس نزد</span></li>'}</ul></div>`;
		}
		bover.innerHTML = html;
		bover.hidden = !html;
	};

	/* ---------------- chat ---------------- */
	const pushLine = (html, cls = '') => {
		const li = document.createElement('li');
		li.className = `nq-msg ${cls}`;
		li.innerHTML = html;
		chatEl.appendChild(li);
		while (chatEl.children.length > 80) chatEl.firstElementChild.remove();
		chatEl.scrollTop = chatEl.scrollHeight;
	};
	const sysLine = (text, cls = 'sys') => pushLine(esc(text), `nq-msg--${cls}`);

	/* ---------------- drawing ---------------- */
	let strokes = new Map(); // id → { col, sz, pts: [[x,y]…] }
	let canvasTurn = -1;
	let local = null; // { turn } → I am drawing in this page session for this turn (skip echoes of my own ops)
	let nextId = 1;
	let col = 1;
	let size = 1;
	let eraser = false;
	let cur = null; // stroke being drawn
	let pending = []; // ops not yet sent
	let sendT = 0;
	let lastSend = 0;
	let dpr = 1;
	let scale = 1;

	const fit = () => {
		const r = canvas.getBoundingClientRect();
		if (!r.width) return;
		dpr = Math.min(2, window.devicePixelRatio || 1);
		canvas.width = Math.round(r.width * dpr);
		canvas.height = Math.round(r.height * dpr);
		scale = (r.width * dpr) / W;
		redraw();
	};
	const paintStroke = (s, from = 0) => {
		const p = s.pts;
		if (!p.length) return;
		ctx.strokeStyle = COLORS[s.col] || '#16120f';
		ctx.fillStyle = ctx.strokeStyle;
		ctx.lineWidth = SIZES[s.sz] || 9;
		ctx.lineCap = 'round';
		ctx.lineJoin = 'round';
		if (p.length === 1) {
			ctx.beginPath();
			ctx.arc(p[0][0], p[0][1], ctx.lineWidth / 2, 0, Math.PI * 2);
			ctx.fill();
			return;
		}
		// Quadratic smoothing through segment midpoints (incremental from index `from`).
		ctx.beginPath();
		const start = Math.max(1, from);
		const m0 = start === 1 ? p[0] : [(p[start - 2][0] + p[start - 1][0]) / 2, (p[start - 2][1] + p[start - 1][1]) / 2];
		ctx.moveTo(m0[0], m0[1]);
		for (let i = start; i < p.length; i++) {
			const mid = [(p[i - 1][0] + p[i][0]) / 2, (p[i - 1][1] + p[i][1]) / 2];
			ctx.quadraticCurveTo(p[i - 1][0], p[i - 1][1], mid[0], mid[1]);
		}
		ctx.lineTo(p[p.length - 1][0], p[p.length - 1][1]);
		ctx.stroke();
	};
	const withT = (fn) => {
		ctx.setTransform(scale, 0, 0, scale, 0, 0);
		fn();
	};
	const redraw = () => {
		ctx.setTransform(1, 0, 0, 1, 0, 0);
		ctx.fillStyle = COLORS[0];
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		withT(() => strokes.forEach((s) => paintStroke(s)));
	};

	const applyOps = (str) => {
		let full = false;
		const touched = [];
		for (const op of String(str).split(';')) {
			if (!op) continue;
			const t = op[0];
			if (t === 'x') {
				strokes.clear();
				full = true;
				continue;
			}
			const nums = op.slice(1).split(',').map(Number);
			const id = nums[0];
			if (t === 'u') {
				strokes.delete(id);
				full = true;
			} else if (t === 'p') {
				const s = { col: nums[1] | 0, sz: nums[2] | 0, pts: [[nums[3], nums[4]]] };
				for (let i = 5; i + 1 < nums.length; i += 2) {
					const l = s.pts[s.pts.length - 1];
					s.pts.push([l[0] + nums[i], l[1] + nums[i + 1]]);
				}
				strokes.set(id, s);
				touched.push([s, 0]);
			} else if (t === 'a') {
				const s = strokes.get(id);
				if (!s) continue;
				const from = s.pts.length;
				for (let i = 1; i + 1 < nums.length; i += 2) {
					const l = s.pts[s.pts.length - 1];
					s.pts.push([l[0] + nums[i], l[1] + nums[i + 1]]);
				}
				touched.push([s, from]);
			}
			nextId = Math.max(nextId, id + 1);
		}
		if (full) redraw();
		else withT(() => touched.forEach(([s, from]) => paintStroke(s, from)));
	};

	const applyEvents = (ev) => {
		let sawStroke = false;
		for (const e of ev) {
			const [, k, seat, d, vis] = e;
			if (k === 's') {
				sawStroke = true;
				if (seat === room.seat && local && local.turn === canvasTurn) continue; // my own echo
				applyOps(d);
			} else if (k === 'c') pushLine(`<b>${esc(nick(seat))}:</b> ${esc(d)}`, vis ? 'nq-msg--private' : seat === room.seat ? 'nq-msg--me' : '');
			else if (k === 'g') pushLine(`✓ <b>${esc(nick(seat))}</b> درست حدس زد!${seat === room.seat ? ` +${fa(d)}` : ''}`, 'nq-msg--ok');
			else if (k === 'j') pushLine(`<b>${esc(d || nick(seat))}</b> وارد اتاق شد`, 'nq-msg--sys');
			else if (k === 'l') pushLine(`<b>${esc(nick(seat))}</b> از اتاق رفت`, 'nq-msg--sys');
		}
		lastHadStrokes = sawStroke;
	};

	const toLogical = (e) => {
		const r = canvas.getBoundingClientRect();
		return [Math.round(((e.clientX - r.left) / r.width) * W), Math.round(((e.clientY - r.top) / r.height) * H)].map((v, i) => Math.max(0, Math.min(i ? H : W, v)));
	};
	const queueOp = (op) => {
		pending.push(op);
		flushSoon();
	};
	const flushSoon = () => {
		if (sendT) return;
		const wait = Math.max(0, SEND_MS - (Date.now() - lastSend));
		sendT = setTimeout(flush, wait);
	};
	// Build the wire string: consecutive points of the same stroke collapse into one op.
	const flush = () => {
		sendT = 0;
		if (!pending.length || !room) return;
		if (qBusy) return flushSoon();
		const ops = [];
		for (const o of pending) {
			if (o.k === 'p') ops.push(`p${o.id},${o.col},${o.sz},${o.pts[0][0]},${o.pts[0][1]}${o.pts.slice(1).map((p, i) => `,${p[0] - o.pts[i][0]},${p[1] - o.pts[i][1]}`).join('')}`);
			else if (o.k === 'a') ops.push(`a${o.id}${o.pts.map((p, i) => `,${p[0] - (i ? o.pts[i - 1][0] : o.from[0])},${p[1] - (i ? o.pts[i - 1][1] : o.from[1])}`).join('')}`);
			else ops.push(o.s);
		}
		pending = [];
		lastSend = Date.now();
		const turn = room.st ? room.st.turn : 0;
		act('draw', { ops: ops.join(';'), turn });
	};
	const addPoint = (pt) => {
		const s = cur;
		const last = s.pts[s.pts.length - 1];
		if (Math.hypot(pt[0] - last[0], pt[1] - last[1]) < MIN_DIST) return false;
		s.pts.push(pt);
		// append to the pending op for this stroke, or open an 'a' op continuing from the last sent point
		const op = pending.length && pending[pending.length - 1].id === s.id && pending[pending.length - 1].k !== 'u' ? pending[pending.length - 1] : null;
		if (op) op.pts.push(pt);
		else queueOp({ k: 'a', id: s.id, from: last, pts: [pt] });
		flushSoon();
		return true;
	};
	let ptrId = null;
	const down = (e) => {
		if (!isDrawer() || ptrId !== null) return;
		e.preventDefault();
		pal.hidden = true;
		ptrId = e.pointerId;
		try {
			canvas.setPointerCapture(e.pointerId);
		} catch (err) {
			/* ignore */
		}
		local = { turn: canvasTurn };
		const pt = toLogical(e);
		cur = { id: nextId++, col: eraser ? 0 : col, sz: eraser ? 3 : size, pts: [pt] };
		strokes.set(cur.id, cur);
		queueOp({ k: 'p', id: cur.id, col: cur.col, sz: cur.sz, pts: [pt] });
		withT(() => paintStroke(cur));
	};
	const move = (e) => {
		if (e.pointerId !== ptrId || !cur) return;
		e.preventDefault();
		const list = e.getCoalescedEvents ? e.getCoalescedEvents() : [e];
		const from = cur.pts.length;
		for (const ce of list.length ? list : [e]) addPoint(toLogical(ce));
		if (cur.pts.length > from) withT(() => paintStroke(cur, from));
	};
	const up = (e) => {
		if (e.pointerId !== ptrId) return;
		ptrId = null;
		if (cur) {
			const pt = toLogical(e);
			const last = cur.pts[cur.pts.length - 1];
			if (pt[0] !== last[0] || pt[1] !== last[1]) {
				cur.pts.push(pt);
				const op = pending.length && pending[pending.length - 1].id === cur.id ? pending[pending.length - 1] : null;
				if (op) op.pts.push(pt);
				else queueOp({ k: 'a', id: cur.id, from: last, pts: [pt] });
				withT(() => paintStroke(cur, cur.pts.length - 1));
			}
		}
		cur = null;
		flushSoon();
	};
	const undo = () => {
		if (!isDrawer()) return;
		const ids = [...strokes.keys()];
		const id = ids[ids.length - 1];
		if (id === undefined) return;
		strokes.delete(id);
		local = { turn: canvasTurn };
		queueOp({ k: 'u', id: -1, s: `u${id}` });
		redraw();
	};
	const clearAll = () => {
		if (!isDrawer() || !strokes.size) return;
		strokes.clear();
		local = { turn: canvasTurn };
		queueOp({ k: 'x', id: -1, s: 'x' });
		redraw();
	};
	const syncTools = () => {
		$('[data-curcol]').style.background = COLORS[col];
		const sz = $('[data-cursize]');
		sz.style.width = sz.style.height = `${[8, 13, 20][size]}px`;
		tools.querySelector('[data-t="eraser"]').setAttribute('aria-pressed', String(eraser));
		pal.querySelectorAll('[data-col]').forEach((b) => b.setAttribute('aria-pressed', String(Number(b.dataset.col) === col && !eraser)));
	};
	syncTools();

	/* ---------------- events ---------------- */
	L.on(canvas, 'pointerdown', down);
	L.on(canvas, 'pointermove', move);
	L.on(canvas, 'pointerup', up);
	L.on(canvas, 'pointercancel', up);
	L.on(canvas, 'touchstart', (e) => e.preventDefault(), { passive: false });
	L.on(canvas, 'touchmove', (e) => e.preventDefault(), { passive: false });
	L.on(canvas, 'contextmenu', (e) => e.preventDefault());

	L.on(tools, 'click', (e) => {
		const b = e.target.closest('[data-t]');
		if (!b) return;
		const t = b.dataset.t;
		if (t === 'pal') pal.hidden = !pal.hidden;
		else if (t === 'size') {
			size = (size + 1) % 3;
			eraser = false;
		} else if (t === 'eraser') eraser = !eraser;
		else if (t === 'undo') undo();
		else if (t === 'clear') clearAll();
		if (t !== 'pal') pal.hidden = true;
		syncTools();
		api.sfx('click');
	});
	L.on(pal, 'click', (e) => {
		const b = e.target.closest('[data-col]');
		if (!b) return;
		col = Number(b.dataset.col);
		eraser = false;
		pal.hidden = true;
		syncTools();
	});
	L.on(bover, 'click', (e) => {
		const b = e.target.closest('[data-pick]');
		if (!b) return;
		bover.querySelectorAll('button').forEach((x) => (x.disabled = true));
		act('pick', { i: Number(b.dataset.pick) });
	});
	L.on(form, 'submit', (e) => {
		e.preventDefault();
		const text = input.value.trim();
		if (!text || !room) return;
		input.value = '';
		lastGuess = text.slice(0, 60);
		act('guess', { text: lastGuess });
	});
	L.on(document, 'keydown', (e) => {
		if ((e.ctrlKey || e.metaKey) && (e.key === 'z' || e.key === 'Z') && isDrawer() && !e.target.closest('input')) {
			e.preventDefault();
			undo();
		}
	});
	L.on(layer, 'click', async (e) => {
		const b = e.target.closest('[data-l],[data-opt]');
		if (!b) return;
		if (b.dataset.opt) {
			act('opts', { [b.dataset.opt]: b.dataset.val });
			return;
		}
		const l = b.dataset.l;
		if (l === 'create') create(false);
		else if (l === 'quick') create(true);
		else if (l === 'back') {
			hideLayer();
			if (room && room.st) applyState(room.st);
		} else if (l === 'leave') exitRoom(true);
		else if (l === 'start') act('start');
		else if (l === 'again') act('again');
		else if (l === 'copy') {
			try {
				await navigator.clipboard.writeText(b.dataset.url);
				b.textContent = 'کپی شد ✓';
			} catch (err) {
				window.prompt('لینک دعوت:', b.dataset.url);
			}
		} else if (l === 'share') {
			navigator.share({ title: 'نقاش‌باشی', text: b.dataset.text, url: b.dataset.url }).catch(() => {});
		} else if (l === 'friends') {
			try {
				window.kmprFriends.invite(api.engine, room.code);
			} catch (err) {
				/* ignore */
			}
		}
	});
	L.on(layer, 'submit', (e) => {
		if (!e.target.matches('[data-l-join]')) return;
		e.preventDefault();
		join(e.target.code.value);
	});
	L.on(document, 'visibilitychange', () => {
		if (!document.hidden && room) poll();
	});

	// Keep the guess box above the on-screen keyboard (iOS keeps the layout viewport; pad by the difference).
	const vv = window.visualViewport;
	const onVV = () => {
		const kb = vv ? Math.max(0, window.innerHeight - vv.height - vv.offsetTop) : 0;
		root.style.setProperty('--nq-kb', `${kb > 60 ? kb : 0}px`);
		root.classList.toggle('is-kb', kb > 60 || (document.activeElement === input && window.innerHeight < 560));
	};
	if (vv) L.on(vv, 'resize', onVV);
	L.on(input, 'focus', () => setTimeout(onVV, 250));
	L.on(input, 'blur', () => setTimeout(onVV, 250));

	const ro = new ResizeObserver(() => fit());
	ro.observe(canvas);
	const clock = setInterval(tickTimer, 250);

	// Test/debug hook (read-only snapshot).
	stage.kmprDebug = {
		get st() {
			return room && room.st;
		},
		get room() {
			return room && { code: room.code, seat: room.seat, cur: room.cur, v: room.v };
		},
		get strokes() {
			return strokes.size;
		},
		get points() {
			let n = 0;
			strokes.forEach((s) => (n += s.pts.length));
			return n;
		},
		stats,
	};

	api._matchUI = {
		levels: [],
		open: () => showMenu(),
		create: () => create(false),
	};

	return {
		noAutoPause: true,
		start(opts = {}) {
			if (!cfg.on) return offScreen();
			if (opts.join) {
				if (room && room.code === opts.join) return;
				showMenu();
				join(opts.join);
				return;
			}
			if (room) return;
			showMenu();
		},
		pause() {},
		resume() {},
		destroy() {
			alive = false;
			clearTimeout(pollT);
			clearTimeout(sendT);
			clearInterval(clock);
			ro.disconnect();
			L.off();
			root.remove();
			delete stage.kmprDebug;
		},
	};
}
