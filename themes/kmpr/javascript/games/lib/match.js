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

	const showLayer = (html, size = '') => {
		layer.innerHTML = `<div class="w-full ${size === 'wide' ? 'max-w-5xl' : 'max-w-md'} my-auto">${html}</div>`;
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
	/* ---------------- mode chooser (step-by-step cards) ---------------- */
	const ICONS = {
		ai: '<svg width="28" height="28" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="7" width="16" height="12" rx="3"/><path d="M12 3v4M9 12h.01M15 12h.01M9.5 16h5M2 12v3M22 12v3"/></svg>',
		local: '<svg width="28" height="28" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="16.5" cy="9" r="2.5"/><path d="M2.5 20a5.5 5.5 0 0 1 11 0M13.5 20a4.5 4.5 0 0 1 8.5-2"/></svg>',
		online: '<svg width="28" height="28" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>',
		chev: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.2 7.2a.75.75 0 0 1 1.06 0L10 10.94l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.26a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>',
		play: '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5.5v13a1 1 0 0 0 1.5.86l11-6.5a1 1 0 0 0 0-1.72l-11-6.5A1 1 0 0 0 7 5.5Z"/></svg>',
		dice: '<svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 8h.01M16 8h.01M12 12h.01M8 16h.01M16 16h.01"/></svg>',
		link: '<svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>',
	};
	const HINTS = { easy: 'برای گرم کردن', medium: 'چالشی ولی شکست‌پذیر', hard: 'تقریباً بی‌نقص', learn: 'الگوی شما را یاد می‌گیرد', slow: 'آرام و صبور', normal: 'سرعت معمولی', fast: 'خیلی سریع' };
	const LAST = `kmpr_mx_last_${api.engine}`;
	const lastRead = () => {
		try {
			return JSON.parse(localStorage.getItem(LAST) || '{}') || {};
		} catch (e) {
			return {};
		}
	};
	const lastWrite = (v) => {
		try {
			localStorage.setItem(LAST, JSON.stringify({ ...lastRead(), ...v }));
		} catch (e) {
			/* ignore */
		}
	};
	let pending = null; // action waiting for "replace current game?" confirmation

	const modeLabel = (m) => (m.type === 'ai' ? 'مقابل هوش مصنوعی' : m.type === 'local' ? 'دو نفره روی همین گوشی' : 'آنلاین با دوستان');
	const hasCurrent = () => !!mode;

	const card = (id, title, desc, color, body, extra = '') => `
		<section class="mxm-card" data-card="${id}" data-open="false" style="--c:${color}">
			<button type="button" class="mxm-card__head" data-mx-card="${id}" aria-expanded="false" aria-controls="mxm-${id}-${api.gameId}">
				<span class="mxm-card__icon">${ICONS[id]}</span>
				<span class="mxm-card__text"><span class="mxm-card__title">${title}</span><span class="mxm-card__desc">${desc}</span>${extra}</span>
				<span class="mxm-card__chev">${ICONS.chev}</span>
			</button>
			<div class="mxm-card__body" id="mxm-${id}-${api.gameId}" role="region" inert><div class="mxm-card__inner">${body}</div></div>
		</section>`;

	const chooser = () => {
		const p = store.player;
		const last = lastRead();
		const levels = cfg.ai || [];
		const lvl = levels.find((l) => l.id === last.level) ? last.level : levels[0] && levels[0].id;
		const cur = mode;
		const curSummary = cur ? [modeLabel(cur), cfg.summary ? cfg.summary() : (statusEl.textContent || '').trim()].filter(Boolean).join(' · ') : '';
		const cards = [];
		if (cfg.ai) {
			cards.push(
				card(
					'ai',
					'بازی با هوش مصنوعی',
					'مقابل ماشین؛ سختی را خودتان انتخاب کنید',
					'#38bdf8',
					`<div class="mxm-seg" role="radiogroup" aria-label="سختی هوش مصنوعی">
						${levels.map((l) => `<button type="button" role="radio" class="mxm-seg__opt" data-mx-level="${esc(l.id)}" aria-checked="${String(l.id === lvl)}">${esc(l.label)}</button>`).join('')}
					</div>
					<p class="mxm-hint" data-mx-hint>${esc((levels.find((l) => l.id === lvl) || {}).hint || HINTS[lvl] || '')}</p>
					<button type="button" class="mxm-go" data-mx="ai" data-level="${esc(lvl)}">${ICONS.play}<span>شروع بازی</span></button>`
				)
			);
		}
		if (cfg.local) {
			cards.push(card('local', 'دو نفره روی همین گوشی', 'نوبتی، روی یک دستگاه با دوستتان', '#f59e0b', `<button type="button" class="mxm-go" data-mx="local">${ICONS.play}<span>شروع بازی دو نفره</span></button>`));
		}
		if (cfg.online) {
			cards.push(
				card(
					'online',
					'آنلاین با دوستان',
					'حریف تصادفی یا دعوت دوست با لینک',
					'#34d399',
					`<label class="mxm-label" for="mx-nick-${api.gameId}">نام شما</label>
					<input id="mx-nick-${api.gameId}" data-mx-nick maxlength="24" value="${esc(p.nickname || rest.userName || '')}" placeholder="یک نام مستعار بنویسید" class="game-nick__input mxm-input" autocomplete="nickname">
					<div class="mxm-actions">
						<button type="button" class="mxm-action" data-mx="quick">${ICONS.dice}<span><b>پیدا کردن حریف تصادفی</b><small>با اولین بازیکن منتظر جفت می‌شوید</small></span></button>
						<button type="button" class="mxm-action mxm-action--alt" data-mx="create">${ICONS.link}<span><b>ساخت اتاق و دعوت دوست</b><small>یک لینک و کد اختصاصی می‌گیرید</small></span></button>
					</div>
					<details class="mxm-code">
						<summary>کد دعوت داری؟</summary>
						<form class="mxm-code__form" data-mx-joinform>
							<label class="sr-only" for="mx-code-${api.gameId}">کد اتاق</label>
							<input id="mx-code-${api.gameId}" name="code" dir="ltr" maxlength="6" placeholder="کد ۶ حرفی" autocomplete="off" autocapitalize="characters" spellcheck="false" class="game-nick__input mx-code">
							<button type="submit" class="mxm-small">ورود</button>
						</form>
					</details>
					<p class="mxm-err" data-mx-err aria-live="polite"></p>`,
					`<span class="mxm-live" data-mx-live><span class="mxm-live__dot"></span><span data-mx-live-text>بازیکن‌ها را بررسی می‌کنم…</span></span>`
				)
			);
		}
		showLayer(
			`<div class="mxm">
				${
					cur
						? `<div class="mxm-current">
						<button type="button" class="mxm-continue" data-mx="continue" data-autofocus>${ICONS.play}<span><b>ادامه بازی فعلی</b><small>${esc(curSummary)}</small></span></button>
						${cur.type === 'online' ? '<button type="button" class="mxm-small mxm-small--danger" data-mx="leave-room">ترک اتاق</button>' : ''}
					</div>`
						: ''
				}
				<div class="mxm-head">
					<p class="mxm-kicker">${cur ? 'یا یک بازی تازه' : 'انتخاب حالت بازی'}</p>
					<p class="mxm-title">چطور بازی می‌کنید؟</p>
				</div>
				${cfg.options && cfg.options.length ? `<div class="mxm-opts">${optionChips()}</div>` : ''}
				<div class="mxm-cards">${cards.join('')}</div>
				<div class="mxm-confirm" data-mx-confirm hidden role="alertdialog" aria-label="تأیید">
					<p>بازی فعلی تمام می‌شود. بازی جدید شروع شود؟</p>
					<div><button type="button" class="mxm-small" data-mx="confirm-no">برگشت</button><button type="button" class="mxm-small mxm-small--primary" data-mx="confirm-yes">بله، بازی جدید</button></div>
				</div>
			</div>`,
			'wide'
		);
		statusEl.dataset.keep = '1';
		// Preselect the last used mode (or the only one).
		const pre = last.card && layer.querySelector(`[data-card="${last.card}"]`) ? last.card : !cur && cards.length === 1 ? (cfg.ai ? 'ai' : cfg.local ? 'local' : 'online') : null;
		if (pre && !cur) openCard(pre, false);
		if (cfg.online) {
			online
				.lobby(api.gameId)
				.then((res) => {
					const t = layer.querySelector('[data-mx-live-text]');
					if (!t || !res || !res.ok) return;
					const n = (res.rooms || []).length;
					t.textContent = n ? `${fa(n)} نفر منتظر حریف` : 'اولین نفر باش؛ حریف خودکار پیدا می‌شود';
					layer.querySelector('[data-mx-live]')?.classList.toggle('is-on', n > 0);
				})
				.catch(() => {});
		}
	};

	const openCard = (id, focus = true) => {
		layer.querySelectorAll('.mxm-card').forEach((c) => {
			const on = c.dataset.card === id && c.dataset.open !== 'true';
			c.dataset.open = String(on);
			c.querySelector('.mxm-card__head').setAttribute('aria-expanded', String(on));
			const body = c.querySelector('.mxm-card__body');
			if (on) body.removeAttribute('inert');
			else body.setAttribute('inert', '');
		});
		const open = layer.querySelector('.mxm-card[data-open="true"]');
		if (open) {
			lastWrite({ card: id });
			if (focus && !api.touch) setTimeout(() => open.querySelector('.mxm-card__body button, .mxm-card__body input')?.focus({ preventScroll: true }), 260);
			setTimeout(() => open.scrollIntoView({ block: 'nearest', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }), 120);
		}
	};

	/** Run a mode-changing action, asking first when a game is in progress. */
	const guard = (fn) => {
		if (!hasCurrent()) return fn();
		pending = fn;
		const c = layer.querySelector('[data-mx-confirm]');
		if (c) {
			c.hidden = false;
			c.querySelector('[data-mx="confirm-yes"]').focus({ preventScroll: true });
			c.scrollIntoView({ block: 'nearest' });
		}
	};
	const resetCurrent = () => {
		if (room) {
			room.leave();
			room.destroy();
			room = null;
		}
		started = false;
		lastOver = false;
		mode = null;
		cfg.onMenu && cfg.onMenu();
		api.setMode && api.setMode(null);
	};

	/** Fresh menu: ends whatever was running (used at start, after a finished game, «ترک اتاق»). */
	const menu = () => {
		resetCurrent();
		menuBtn.hidden = true;
		chooser();
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
					<button type="button" class="${BTN_GHOST}" data-mx="leave">خروج از اتاق</button>
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
		// Quick «same level» / «harder» without going back through the chooser.
		const levels = cfg.ai || [];
		const li = mode && mode.type === 'ai' ? levels.findIndex((l) => l.id === mode.level) : -1;
		const lvl = li >= 0 ? levels[li] : null;
		const next = li >= 0 ? levels[li + 1] : null;
		const color = tone === 'win' ? 'text-emerald-400' : tone === 'loss' ? 'text-rose-400' : 'text-sky-300';
		showLayer(`<div class="rounded-3xl bg-slate-900/90 border border-white/10 p-5 sm:p-6 flex flex-col gap-3 text-center shadow-2xl">
			<p class="text-xs font-black ${color}">پایان دست</p>
			<p class="text-3xl font-black text-white">${esc(title)}</p>
			${text ? `<p class="text-sm text-slate-300">${esc(text)}</p>` : ''}
			<div class="grid grid-cols-2 gap-2 pt-2">
				<button type="button" class="${BTN_PRIMARY}" data-mx="r-primary" data-autofocus>${esc(primary)}${lvl ? ` (${esc(lvl.label)})` : ''}</button>
				<button type="button" class="${BTN_GHOST}" data-mx="r-secondary">${esc(secondary)}</button>
				${next ? `<button type="button" class="${BTN_GHOST} col-span-2" data-mx="r-harder" data-level="${esc(next.id)}">سخت‌تر: ${esc(next.label)}</button>` : ''}
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
		const cardBtn = e.target.closest('[data-mx-card]');
		if (cardBtn) {
			api.sfx('click');
			openCard(cardBtn.dataset.mxCard);
			return;
		}
		const lv = e.target.closest('[data-mx-level]');
		if (lv) {
			api.sfx('click');
			const id = lv.dataset.mxLevel;
			layer.querySelectorAll('[data-mx-level]').forEach((x) => x.setAttribute('aria-checked', String(x === lv)));
			const l = (cfg.ai || []).find((x) => x.id === id) || {};
			const hint = layer.querySelector('[data-mx-hint]');
			if (hint) hint.textContent = l.hint || HINTS[id] || '';
			const go = layer.querySelector('[data-mx="ai"]');
			if (go) go.dataset.level = id;
			lastWrite({ level: id });
			return;
		}
		if (e.target === layer && hasCurrent() && layer.querySelector('.mxm')) {
			hideLayer(); // tap outside = continue the current game
			return;
		}
		const b = e.target.closest('[data-mx]');
		if (!b) return;
		const act = b.dataset.mx;
		api.sfx('click');
		if (act === 'ai') {
			const level = b.dataset.level;
			guard(() => {
				hideLayer();
				menuBtn.hidden = false;
				enter({ type: 'ai', level, options: { ...options } });
			});
		} else if (act === 'local') {
			guard(() => {
				hideLayer();
				menuBtn.hidden = false;
				api.unlock('local');
				enter({ type: 'local', options: { ...options } });
			});
		} else if (act === 'quick' || act === 'create') {
			if (!saveNick()) return;
			guard(() => startOnline(act));
		} else if (act === 'continue') hideLayer();
		else if (act === 'confirm-yes') {
			const fn = pending;
			pending = null;
			const keepNick = layer.querySelector('[data-mx-nick]')?.value;
			resetCurrent();
			if (fn) fn(keepNick);
		} else if (act === 'confirm-no') {
			pending = null;
			const c = layer.querySelector('[data-mx-confirm]');
			if (c) c.hidden = true;
		} else if (act === 'leave-room') guard(() => menu());
		else if (act === 'menu') {
			// In-game «منوی بازی»: never destroys the running match.
			if (hasCurrent()) chooser();
			else menu();
		} else if (act === 'cancel' || act === 'leave') menu();
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
		} else if (act === 'r-harder' && mode) {
			lastWrite({ level: b.dataset.level });
			enter({ type: 'ai', level: b.dataset.level, options: { ...mode.options } });
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
		guard(() => startOnline('join', code));
	});
	// Esc on the chooser = continue the current game (before the shell's focus-exit handler).
	const onEsc = (e) => {
		if (e.key !== 'Escape' || layer.hidden || !hasCurrent() || !layer.querySelector('.mxm') || !root.isConnected) return;
		e.preventDefault();
		e.stopImmediatePropagation();
		hideLayer();
	};
	window.addEventListener('keydown', onEsc, true); // capture: runs before the shell's Esc = exit focus
	const onHide = () => {
		if (room && room.view && room.view.status === 'waiting') room.leave();
	};
	window.addEventListener('pagehide', onHide);
	api._matchUI = { open: () => (hasCurrent() ? chooser() : menu()), create: () => cfg.online && guard(() => startOnline('create')), levels: (cfg.ai || []).map((l) => ({ id: l.id, label: l.label })) };

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
			window.removeEventListener('keydown', onEsc, true);
			if (room) {
				room.leave();
				room.destroy();
			}
			root.remove();
		},
	};
}
