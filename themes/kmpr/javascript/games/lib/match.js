/**
 * Match UI kit for duel games (AI / local 2-player / online rooms).
 * Renders inside the engine stage: players bar, board slot, status line and
 * an in-stage layer for the mode menu, online waiting room + share, and results.
 *
 * const ui = createMatchUI(stage, api, {
 *   ai: [{ id, label }],               // AI levels (omit → no AI mode)
 *   local: true, online: true,
 *   options: [{ key, label, choices: [[value, label]], value }],   // e.g. best_of
 *   onStart(mode)        // { type:'ai', level, options } | { type:'local', options } | { type:'online', room, view }
 *   onRoomState(view)    // every online state change (view.state = engine state, view.seat = my seat)
 *   resultText(view)     // optional → { title, text } for an online result
 *   onMenu()             // back to menu (engine should reset its board)
 * });
 */
import { Room } from './rooms.js';
import { store } from './store.js';
import { online, api as rest } from './api.js';
import { avatarSvg } from './avatar.js';
import { esc, fa, h } from './util.js';

const BTN = 'inline-flex items-center justify-center gap-2 min-h-11 px-5 py-2.5 rounded-2xl font-black text-sm whitespace-nowrap transition-colors disabled:opacity-50 disabled:cursor-not-allowed';
export const BTN_PRIMARY = `${BTN} bg-secondary hover:bg-amber-500 text-slate-950`;
export const BTN_GHOST = `${BTN} bg-white/[0.05] hover:bg-white/[0.1] border border-white/15 text-slate-100`;

export function playerAvatar(seedOrPlayer, size = 36) {
	const p = store.player;
	if (!seedOrPlayer || seedOrPlayer === 'me') return avatarSvg(p.hash || p.id, p.avatar || {}, size);
	return avatarSvg(seedOrPlayer.player || seedOrPlayer.seed || 'x', seedOrPlayer.avatar || {}, size);
}

export function createMatchUI(stage, api, cfg) {
	stage.classList.add('mx-stage');
	const root = h(`<div class="mx relative flex flex-col h-full w-full text-slate-100 select-none">
		<div class="mx-bar flex items-center justify-between gap-2 px-3 sm:px-4 pt-3" data-bar></div>
		<div class="mx-board relative flex-1 min-h-0 flex items-center justify-center p-3 sm:p-4" data-board></div>
		<div class="mx-foot flex items-center justify-between gap-2 px-3 sm:px-4 pb-3">
			<p class="text-sm sm:text-base font-bold text-slate-200 min-h-6 truncate" data-status aria-live="polite"></p>
			<button type="button" class="${BTN_GHOST} !min-h-10 !px-4 !py-2 !text-xs shrink-0" data-mx="menu" hidden>منوی بازی</button>
		</div>
		<div class="mx-layer absolute inset-0 z-10 flex items-center justify-center p-3 sm:p-6 bg-slate-950/85 backdrop-blur-md overflow-y-auto" data-layer hidden></div>
	</div>`);
	stage.appendChild(root);
	const $ = (s) => root.querySelector(s);
	const bar = $('[data-bar]');
	const board = $('[data-board]');
	const statusEl = $('[data-status]');
	const layer = $('[data-layer]');
	const menuBtn = $('[data-mx="menu"]');
	const options = {};
	(cfg.options || []).forEach((o) => (options[o.key] = o.value));

	let room = null;
	let started = false;
	let lastOver = false;
	let mode = null;

	const showLayer = (html) => {
		layer.innerHTML = `<div class="w-full max-w-md my-auto">${html}</div>`;
		layer.hidden = false;
		const f = layer.querySelector('[data-autofocus]');
		if (f && !api.touch) f.focus({ preventScroll: true });
	};
	const hideLayer = () => {
		layer.hidden = true;
		layer.innerHTML = '';
	};

	const optionChips = () =>
		(cfg.options || [])
			.map(
				(o) => `<div class="flex items-center justify-between gap-3 flex-wrap">
				<span class="text-sm font-bold text-slate-300">${esc(o.label)}</span>
				<div class="flex gap-1.5" role="group" aria-label="${esc(o.label)}">
					${o.choices
						.map(
							([v, l]) =>
								`<button type="button" data-opt="${esc(o.key)}" data-val="${esc(v)}" aria-pressed="${String(options[o.key] == v)}" class="min-h-10 min-w-10 px-3 rounded-xl border text-sm font-black transition-colors ${options[o.key] == v ? 'bg-secondary/20 border-secondary/60 text-amber-300' : 'bg-white/[0.04] border-white/10 text-slate-300 hover:border-white/30'}">${esc(l)}</button>`
						)
						.join('')}
				</div>
			</div>`
			)
			.join('');

	/* ---------------- menu ---------------- */
	const menu = () => {
		if (room) {
			room.leave();
			room.destroy();
			room = null;
		}
		started = false;
		mode = null;
		menuBtn.hidden = true;
		cfg.onMenu && cfg.onMenu();
		api.setMode && api.setMode(null);
		const p = store.player;
		showLayer(`<div class="rounded-3xl bg-slate-900/90 border border-white/10 p-5 sm:p-6 flex flex-col gap-5 shadow-2xl">
			<div class="text-center">
				<p class="text-xs font-black text-secondary mb-1">انتخاب حالت بازی</p>
				<p class="text-xl sm:text-2xl font-black text-white">با چه کسی بازی می‌کنید؟</p>
			</div>
			${optionChips()}
			${
				cfg.ai
					? `<div class="flex flex-col gap-2">
					<p class="text-sm font-bold text-slate-300">مقابل هوش مصنوعی</p>
					<div class="grid grid-cols-${Math.min(3, cfg.ai.length)} gap-2">
						${cfg.ai.map((l, i) => `<button type="button" class="${i === 0 ? BTN_PRIMARY : BTN_GHOST} !px-3" data-mx="ai" data-level="${esc(l.id)}"${i === 0 ? ' data-autofocus' : ''}>${esc(l.label)}</button>`).join('')}
					</div>
				</div>`
					: ''
			}
			${cfg.local ? `<button type="button" class="${BTN_GHOST} w-full" data-mx="local"><svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M2 20a6 6 0 0 1 12 0M10 20a6 6 0 0 1 12 0"/></svg> دو نفره روی همین دستگاه</button>` : ''}
			${
				cfg.online
					? `<div class="flex flex-col gap-2 pt-4 border-t border-white/10">
					<p class="flex items-center gap-2 text-sm font-bold text-slate-300"><span class="size-2 rounded-full bg-emerald-400 shrink-0"></span> بازی آنلاین با دوستان</p>
					<label class="sr-only" for="mx-nick-${api.gameId}">نام مستعار</label>
					<input id="mx-nick-${api.gameId}" data-mx-nick maxlength="24" value="${esc(p.nickname || rest.userName || '')}" placeholder="نام مستعار شما" class="game-nick__input w-full" autocomplete="nickname">
					<div class="grid grid-cols-2 gap-2">
						<button type="button" class="${BTN_PRIMARY}" data-mx="quick">حریف تصادفی</button>
						<button type="button" class="${BTN_GHOST}" data-mx="create">ساخت اتاق خصوصی</button>
					</div>
					<form class="flex gap-2" data-mx-joinform>
						<label class="sr-only" for="mx-code-${api.gameId}">کد اتاق</label>
						<input id="mx-code-${api.gameId}" name="code" dir="ltr" maxlength="6" placeholder="کد اتاق" autocomplete="off" autocapitalize="characters" class="game-nick__input flex-1 min-w-0 text-center font-mono tracking-[0.3em] uppercase">
						<button type="submit" class="${BTN_GHOST}">ورود</button>
					</form>
					<p class="text-xs text-slate-400 min-h-4" data-mx-err aria-live="polite"></p>
				</div>`
					: ''
			}
		</div>`);
		statusEl.textContent = '';
	};

	const setErr = (t) => {
		const e = layer.querySelector('[data-mx-err]');
		if (e) e.textContent = t || '';
		else if (t) api.toast(esc(t), 'warn');
	};

	const saveNick = () => {
		const inp = layer.querySelector('[data-mx-nick]');
		const nick = inp ? inp.value.trim().slice(0, 24) : store.player.nickname;
		if (!nick) {
			setErr('برای بازی آنلاین یک نام مستعار بنویسید.');
			inp && inp.focus();
			return false;
		}
		if (nick !== store.player.nickname) {
			store.set('nickname', nick);
			online.profile({ player_id: store.player.id, nickname: nick, avatar: store.player.avatar }).catch(() => {});
		}
		return true;
	};

	/* ---------------- online ---------------- */
	const newRoom = () => {
		if (room) room.destroy();
		room = new Room({
			gameId: api.gameId,
			onState: onRoom,
			onPresence: (v) => renderOnlinePlayers(v),
			onError: (res) => {
				if (!res) return;
				if (res.code === 'no_room') {
					api.toast('اتاق بسته شد.', 'warn');
					menu();
				} else if (res.message && res.code !== 'stale') statusEl.textContent = res.message;
			},
		});
		return room;
	};

	const shareUrl = (view) => view.share || `${api.gameUrl}?room=${view.code}`;

	const waiting = (view) => {
		const url = shareUrl(view);
		const text = `بیا با هم «${document.title.split('|')[0].trim()}» بازی کنیم! کد اتاق: ${view.code}`;
		showLayer(`<div class="rounded-3xl bg-slate-900/90 border border-white/10 p-5 sm:p-6 flex flex-col gap-4 text-center shadow-2xl">
			<p class="text-xs font-black text-secondary">${view.public ? 'در انتظار حریف تصادفی…' : 'اتاق خصوصی ساخته شد'}</p>
			<p class="text-sm text-slate-300">${view.public ? 'اتاق شما در لابی عمومی است؛ هر کس وارد شود بازی شروع می‌شود. می‌توانید لینک را هم بفرستید.' : 'این کد یا لینک را برای دوستتان بفرستید؛ با ورود او بازی شروع می‌شود.'}</p>
			<p class="text-4xl sm:text-5xl font-black font-mono tracking-[0.25em] text-white" dir="ltr" data-code>${esc(view.code)}</p>
			<div class="flex items-center justify-center gap-2 text-slate-300 text-sm"><span class="mx-dots" aria-hidden="true"><i></i><i></i><i></i></span> منتظر ورود حریف</div>
			<div class="grid grid-cols-2 gap-2">
				<button type="button" class="${BTN_PRIMARY}" data-mx="copy" data-url="${esc(url)}" data-autofocus>کپی لینک دعوت</button>
				${navigator.share ? `<button type="button" class="${BTN_GHOST}" data-mx="share" data-url="${esc(url)}" data-text="${esc(text)}">اشتراک‌گذاری</button>` : `<a class="${BTN_GHOST}" href="https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}" target="_blank" rel="noopener">واتس‌اپ</a>`}
				<a class="${BTN_GHOST}" href="https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}" target="_blank" rel="noopener">تلگرام</a>
				<a class="${BTN_GHOST}" href="https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}" target="_blank" rel="noopener">واتس‌اپ</a>
			</div>
			<button type="button" class="${BTN_GHOST} w-full" data-mx="cancel">لغو و بازگشت</button>
		</div>`);
		statusEl.textContent = 'در انتظار حریف…';
	};

	const renderOnlinePlayers = (view) => {
		const me = view.seat;
		const ps = [0, 1].map((seat) => {
			const p = view.players.find((x) => x.seat === seat);
			if (!p) return { name: 'در انتظار…', avatar: '<span class="size-9 rounded-xl border border-dashed border-white/20 shrink-0"></span>', sub: '', online: false, seat };
			return { name: p.nick + (seat === me ? ' (شما)' : ''), avatar: avatarSvg(p.player, p.avatar, 36), online: p.online && !p.left, seat, sub: cfg.seatLabel ? cfg.seatLabel(seat) : '' };
		});
		players(ps, cfg.activeSeat ? cfg.activeSeat(view) : null, view.state && view.state.series);
	};

	const onRoom = (view) => {
		if (view.status === 'waiting') {
			waiting(view);
			renderOnlinePlayers(view);
			return;
		}
		if (view.status === 'expired' || view.status === 'closed') {
			api.toast('اتاق بسته شد.', 'warn');
			menu();
			return;
		}
		if (!started) {
			started = true;
			mode = { type: 'online', room, view };
			hideLayer();
			menuBtn.hidden = false;
			api.begin({ online: true });
			api.setMode && api.setMode({ type: 'online', code: view.code });
			cfg.onStart({ type: 'online', room, view });
		}
		renderOnlinePlayers(view);
		const over = !!(view.state && view.state.over);
		if (!over && lastOver) hideLayer();
		cfg.onRoomState(view);
		if (over) onlineResult(view, !lastOver);
		lastOver = over;
	};

	const onlineResult = (view, fresh) => {
		const s = view.state;
		const me = view.seat;
		const opp = view.players.find((p) => p.seat !== me);
		const mine = view.players.find((p) => p.seat === me);
		const won = s.winner === me;
		const draw = s.winner === 'draw';
		if (fresh) store.pushRecent({ type: 'match', e: api.engine, r: draw ? 'draw' : won ? 'win' : 'loss', o: opp ? opp.nick : '' });
		if (fresh && api.emit) api.emit('kmpr:game-over', { slug: api.engine, mode: 'online', score: 0, won, durationMs: 0 });
		if (fresh && won) {
			api.unlock('online_win');
			const g = store.game(api.engine);
			g.ow = (g.ow || 0) + 1;
			store.save();
			if (g.ow >= 5) api.unlock('online5');
			api.sfx('level');
		} else if (fresh) api.sfx(draw ? 'coin' : 'over');
		const custom = cfg.resultText ? cfg.resultText(view) : null;
		const title = custom?.title || (draw ? 'مساوی!' : won ? 'بردید! 🎉' : 'باختید');
		const text = custom?.text || (s.forfeit !== undefined && s.forfeit !== null ? (s.forfeit === me ? 'شما بازی را ترک کردید.' : 'حریف بازی را ترک کرد.') : '');
		const series = s.series ? `<p class="text-sm text-slate-300">نتیجه کل: شما ${fa(s.series[me] || 0)} — ${fa(s.series[1 - me] || 0)} حریف${s.series[2] ? ` · مساوی ${fa(s.series[2])}` : ''}</p>` : '';
		const oppLeft = opp && (opp.left || !opp.online);
		const waitingRematch = mine && mine.rematch;
		const oppWants = opp && opp.rematch;
		// Optional engine hooks so the result screen never covers the final animation:
		//   cfg.resultDelay (ms) and/or cfg.onRoundResolved(view) → Promise (awaited, capped at 4s).
		const delay = fresh ? Math.min(4000, Math.max(900, Number(cfg.resultDelay) || 0)) : 0;
		const seq = (onlineResult.seq = (onlineResult.seq || 0) + 1);
		const hold = fresh && typeof cfg.onRoundResolved === 'function' ? Promise.race([Promise.resolve(cfg.onRoundResolved(view)).catch(() => {}), new Promise((r) => setTimeout(r, 4000))]) : null;
		clearTimeout(onlineResult.t);
		const schedule = (fn) => {
			if (hold) hold.then(() => seq === onlineResult.seq && (onlineResult.t = setTimeout(fn, 250)));
			else onlineResult.t = setTimeout(fn, delay);
		};
		schedule(() => {
			if (!view.state.over || (room && room.view && !room.view.state.over)) return;
			showLayer(`<div class="rounded-3xl bg-slate-900/90 border border-white/10 p-5 sm:p-6 flex flex-col gap-3 text-center shadow-2xl">
				<p class="text-xs font-black ${won ? 'text-emerald-400' : draw ? 'text-sky-300' : 'text-rose-400'}">پایان بازی آنلاین</p>
				<p class="text-3xl font-black text-white">${esc(title)}</p>
				${text ? `<p class="text-sm text-slate-300">${esc(text)}</p>` : ''}
				${series}
				${won ? '<p class="text-xs text-amber-300 font-bold">+۶۰ امتیاز تجربه (XP)</p>' : ''}
				${oppWants && !waitingRematch ? '<p class="text-sm font-bold text-emerald-300">حریف درخواست بازی دوباره دارد!</p>' : ''}
				<div class="grid grid-cols-2 gap-2 pt-2">
					<button type="button" class="${BTN_PRIMARY}" data-mx="rematch" ${waitingRematch || (opp && opp.left) ? 'disabled' : ''} data-autofocus>${waitingRematch ? 'منتظر حریف…' : 'بازی دوباره'}</button>
					<button type="button" class="${BTN_GHOST}" data-mx="menu">خروج از اتاق</button>
				</div>
				${oppLeft ? '<p class="text-xs text-slate-400">حریف آفلاین است.</p>' : ''}
			</div>`);
		});
	};

	const startOnline = async (kind, code) => {
		if (!saveNick()) return;
		const buttons = layer.querySelectorAll('button');
		buttons.forEach((b) => (b.disabled = true));
		setErr('');
		statusEl.textContent = 'در حال اتصال…';
		const r = newRoom();
		let res;
		try {
			if (kind === 'quick') res = await r.quick();
			else if (kind === 'create') res = await r.create(false, { ...options });
			else res = await r.join(code);
		} catch (e) {
			res = { ok: false, message: 'ارتباط با سرور برقرار نشد.' };
		}
		if (!res || !res.ok) {
			buttons.forEach((b) => (b.disabled = false));
			statusEl.textContent = '';
			if (layer.hidden || !layer.querySelector('[data-mx-err]')) {
				menu();
			}
			setErr((res && res.message) || 'اتصال ناموفق بود.');
			r.destroy();
			room = null;
		}
	};

	/* ---------------- players bar ---------------- */
	const players = (list, active = null, series = null) => {
		bar.innerHTML = list
			.map((p, i) => {
				const on = active === p.seat;
				return `<div class="flex items-center gap-2 min-w-0 ${i === 1 ? 'flex-row-reverse text-end' : ''} px-2.5 py-1.5 rounded-2xl border transition-colors ${on ? 'border-secondary/60 bg-secondary/10' : 'border-white/10 bg-white/[0.03]'}" style="max-width:46%">
					<span class="relative shrink-0">${p.avatar}${p.online !== undefined ? `<span class="absolute -bottom-0.5 -end-0.5 size-2.5 rounded-full ring-2 ring-slate-950 ${p.online ? 'bg-emerald-400' : 'bg-slate-600'}"></span>` : ''}</span>
					<span class="min-w-0">
						<span class="block text-sm font-black text-white truncate">${esc(p.name)}</span>
						${p.sub ? `<span class="block text-xs text-slate-400 truncate">${p.sub}</span>` : ''}
					</span>
				</div>`;
			})
			.join(series ? `<span class="text-base sm:text-lg font-black text-slate-200 tabular-nums shrink-0">${fa(series[0] || 0)} <span class="text-slate-500">:</span> ${fa(series[1] || 0)}</span>` : '<span class="text-xs font-black text-slate-500 shrink-0">VS</span>');
	};

	/* ---------------- result (AI / local) ---------------- */
	const result = ({ title, text = '', tone = 'accent', primary = 'بازی دوباره', onPrimary, secondary = 'منوی بازی', onSecondary }) => {
		const color = tone === 'win' ? 'text-emerald-400' : tone === 'loss' ? 'text-rose-400' : 'text-sky-300';
		showLayer(`<div class="rounded-3xl bg-slate-900/90 border border-white/10 p-5 sm:p-6 flex flex-col gap-3 text-center shadow-2xl">
			<p class="text-xs font-black ${color}">پایان دست</p>
			<p class="text-3xl font-black text-white">${esc(title)}</p>
			${text ? `<p class="text-sm text-slate-300">${esc(text)}</p>` : ''}
			<div class="grid grid-cols-2 gap-2 pt-2">
				<button type="button" class="${BTN_PRIMARY}" data-mx="r-primary" data-autofocus>${esc(primary)}</button>
				<button type="button" class="${BTN_GHOST}" data-mx="r-secondary">${esc(secondary)}</button>
			</div>
		</div>`);
		result.primary = onPrimary;
		result.secondary = onSecondary || menu;
	};

	/** Start a non-online mode directly (menu choice, restart or resume after refresh). */
	const enter = (m) => {
		if (room) {
			room.leave();
			room.destroy();
			room = null;
		}
		hideLayer();
		menuBtn.hidden = false;
		mode = { type: m.type, level: m.level, options: { ...options, ...(m.options || {}) } };
		if (mode.type === 'ai') api.begin();
		api.setMode && api.setMode({ type: mode.type, level: mode.level, options: mode.options });
		cfg.onStart(mode);
	};

	/* ---------------- events ---------------- */
	root.addEventListener('click', async (e) => {
		const opt = e.target.closest('[data-opt]');
		if (opt) {
			const o = (cfg.options || []).find((x) => x.key === opt.dataset.opt);
			const raw = opt.dataset.val;
			options[o.key] = typeof o.value === 'number' ? Number(raw) : raw;
			layer.querySelectorAll(`[data-opt="${o.key}"]`).forEach((b) => {
				const on = b.dataset.val === raw;
				b.setAttribute('aria-pressed', String(on));
				b.className = b.className.replace(/bg-secondary\/20 border-secondary\/60 text-amber-300|bg-white\/\[0\.04\] border-white\/10 text-slate-300 hover:border-white\/30/, on ? 'bg-secondary/20 border-secondary/60 text-amber-300' : 'bg-white/[0.04] border-white/10 text-slate-300 hover:border-white/30');
			});
			api.sfx('click');
			return;
		}
		const b = e.target.closest('[data-mx]');
		if (!b) return;
		const act = b.dataset.mx;
		api.sfx('click');
		if (act === 'ai') {
			hideLayer();
			menuBtn.hidden = false;
			enter({ type: 'ai', level: b.dataset.level, options: { ...options } });
		} else if (act === 'local') {
			hideLayer();
			menuBtn.hidden = false;
			api.unlock('local');
			enter({ type: 'local', options: { ...options } });
		} else if (act === 'quick' || act === 'create') startOnline(act);
		else if (act === 'cancel' || act === 'menu') menu();
		else if (act === 'copy') {
			try {
				await navigator.clipboard.writeText(b.dataset.url);
				b.textContent = 'کپی شد ✓';
			} catch (err) {
				window.prompt('لینک دعوت:', b.dataset.url);
			}
		} else if (act === 'share') {
			navigator.share({ title: document.title, text: b.dataset.text, url: b.dataset.url }).catch(() => {});
		} else if (act === 'rematch' && room) {
			b.disabled = true;
			b.textContent = 'منتظر حریف…';
			await room.rematch();
		} else if (act === 'r-primary') {
			hideLayer();
			result.primary && result.primary();
		} else if (act === 'r-secondary') {
			hideLayer();
			result.secondary && result.secondary();
		}
	});
	root.addEventListener('submit', (e) => {
		const f = e.target.closest('[data-mx-joinform]');
		if (!f) return;
		e.preventDefault();
		const code = f.code.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
		if (code.length !== 6) {
			setErr('کد اتاق ۶ حرفی است.');
			return;
		}
		startOnline('join', code);
	});
	const onHide = () => {
		if (room && room.view && room.view.status === 'waiting') room.leave();
	};
	window.addEventListener('pagehide', onHide);

	return {
		root,
		board,
		menu,
		result,
		hideLayer,
		players,
		get mode() {
			return mode;
		},
		get room() {
			return room;
		},
		get layerOpen() {
			return !layer.hidden;
		},
		status(text, tone = '') {
			statusEl.textContent = text;
			statusEl.className = `text-sm sm:text-base font-bold min-h-6 truncate ${tone === 'win' ? 'text-emerald-300' : tone === 'loss' ? 'text-rose-300' : tone === 'turn' ? 'text-amber-300' : 'text-slate-200'}`;
		},
		enter,
		/** Engine start(opts) helper: { join } → room, { mode } → that mode directly, else the menu. */
		start(opts = {}) {
			if (opts.join) return this.join(opts.join);
			if (opts.mode && opts.mode.type && opts.mode.type !== 'online') {
				if (opts.mode.options) Object.assign(options, opts.mode.options);
				return enter(opts.mode);
			}
			return menu();
		},
		/** Join from an invite link. */
		join(code) {
			menu();
			if (!store.player.nickname) {
				setErr('برای ورود به اتاق ' + code + ' نام مستعار را بنویسید و «ورود» را بزنید.');
				const ci = layer.querySelector('[name="code"]');
				if (ci) ci.value = code;
				return;
			}
			startOnline('join', code);
		},
		refreshPlayers() {
			if (room && room.view) renderOnlinePlayers(room.view);
		},
		destroy() {
			window.removeEventListener('pagehide', onHide);
			if (room) {
				room.leave();
				room.destroy();
			}
			root.remove();
		},
	};
}
