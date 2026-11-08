/**
 * «ایکس‌او» — tic-tac-toe, upgraded from my showcase repo (local 2P + localStorage scores)
 * to: vs AI (easy / medium / unbeatable minimax), local 2-player and ONLINE rooms
 * (server validates every move). Animated SVG marks, winning line, keyboard 1–9.
 */
import { CATALOG } from '../catalog.js';
import { createMatchUI, playerAvatar } from '../lib/match.js';
import { fa, listeners, pick } from '../lib/util.js';

export const achievements = CATALOG.xo.achievements;

const LINES = [[0, 1, 2], [3, 4, 5], [6, 7, 8], [0, 3, 6], [1, 4, 7], [2, 5, 8], [0, 4, 8], [2, 4, 6]];
const winnerOf = (b) => {
	for (const l of LINES) if (b[l[0]] !== '.' && b[l[0]] === b[l[1]] && b[l[1]] === b[l[2]]) return { mark: b[l[0]], line: l };
	return b.includes('.') ? null : { mark: 'draw', line: null };
};

/* ---------- AI ---------- */
function minimax(b, me, turn, depth = 0) {
	const w = winnerOf(b);
	if (w) return { score: w.mark === 'draw' ? 0 : w.mark === me ? 10 - depth : depth - 10 };
	let best = { score: turn === me ? -Infinity : Infinity, cell: -1 };
	for (let i = 0; i < 9; i++) {
		if (b[i] !== '.') continue;
		const nb = b.slice(0, i) + turn + b.slice(i + 1);
		const r = minimax(nb, me, turn === 'X' ? 'O' : 'X', depth + 1);
		if (turn === me ? r.score > best.score : r.score < best.score) best = { score: r.score, cell: i };
	}
	return best;
}
function aiMove(b, me, level) {
	const empty = [...b].map((c, i) => (c === '.' ? i : -1)).filter((i) => i >= 0);
	const opp = me === 'X' ? 'O' : 'X';
	const findWin = (mark) => empty.find((i) => winnerOf(b.slice(0, i) + mark + b.slice(i + 1))?.mark === mark);
	if (level === 'easy') {
		if (Math.random() < 0.35) {
			const w = findWin(me);
			if (w !== undefined) return w;
		}
		return pick(empty);
	}
	if (level === 'medium') {
		const w = findWin(me);
		if (w !== undefined) return w;
		const blk = findWin(opp);
		if (blk !== undefined && Math.random() < 0.85) return blk;
		if (b[4] === '.' && Math.random() < 0.6) return 4;
		return Math.random() < 0.45 ? minimax(b, me, me).cell : pick(empty);
	}
	if (empty.length === 9) return pick([0, 2, 4, 6, 8]);
	return minimax(b, me, me).cell;
}

const MARK = {
	X: '<svg width="100%" height="100%" class="shrink-0 xo-mark xo-x" viewBox="0 0 100 100" aria-hidden="true"><path d="M24 24 76 76" pathLength="1"/><path d="M76 24 24 76" pathLength="1"/></svg>',
	O: '<svg width="100%" height="100%" class="shrink-0 xo-mark xo-o" viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="27" pathLength="1"/></svg>',
};

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '4/5');
	const L = listeners();
	let board = '.........';
	let turn = 'X';
	let locked = false;
	let series = [0, 0, 0];
	let starter = 'X';
	let mode = null;
	let aiMark = 'O';
	let streak = 0;
	let aiTimer = 0;
	let lastShown = '';

	const ui = createMatchUI(stage, api, {
		ai: [
			{ id: 'easy', label: 'آسان' },
			{ id: 'medium', label: 'متوسط' },
			{ id: 'hard', label: 'شکست‌ناپذیر' },
		],
		local: true,
		online: true,
		seatLabel: (seat) => (seat === 0 ? 'ایکس (X)' : 'او (O)'),
		activeSeat: (view) => (view.state && !view.state.over ? view.state.turn : null),
		onStart: (m) => {
			mode = m;
			series = [0, 0, 0];
			streak = 0;
			starter = 'X';
			if (m.type !== 'online') newRound();
		},
		onRoomState: (view) => renderOnline(view),
		resultText: (view) => {
			const s = view.state;
			if (s.winner === 'draw') return { title: 'مساوی!', text: 'هیچ‌کس سه‌تایی نشد.' };
			return null;
		},
		onMenu: () => {
			mode = null;
			clearTimeout(aiTimer);
			board = '.........';
			render();
			ui.players([
				{ name: 'شما', avatar: playerAvatar('me'), sub: 'X' },
				{ name: 'حریف', avatar: playerAvatar({ player: 'xo-bot', avatar: { style: 'bot', hue: 200 } }), sub: 'O' },
			]);
		},
	});

	const grid = document.createElement('div');
	grid.className = 'xo-grid';
	grid.setAttribute('role', 'grid');
	grid.setAttribute('aria-label', 'صفحه ایکس‌او');
	grid.innerHTML = Array.from({ length: 9 }, (_, i) => `<button type="button" class="xo-cell" data-cell="${i}" aria-label="خانه ${fa(i + 1)}"><span class="xo-num" aria-hidden="true">${fa(i + 1)}</span></button>`).join('') + '<svg class="xo-line shrink-0" width="100%" height="100%" viewBox="0 0 300 300" aria-hidden="true"><line x1="0" y1="0" x2="0" y2="0" pathLength="1"/></svg>';
	ui.board.appendChild(grid);
	const cells = [...grid.querySelectorAll('[data-cell]')];
	const lineEl = grid.querySelector('.xo-line line');

	const names = () => {
		if (!mode) return ['شما', 'حریف'];
		if (mode.type === 'ai') return [aiMark === 'O' ? 'شما' : 'هوش مصنوعی', aiMark === 'O' ? 'هوش مصنوعی' : 'شما'];
		return ['بازیکن ایکس', 'بازیکن او'];
	};

	const renderPlayers = () => {
		if (!mode || mode.type === 'online') return;
		const n = names();
		const bot = { player: 'xo-bot-' + (mode.level || ''), avatar: { style: 'bot', hue: mode.level === 'hard' ? 340 : mode.level === 'medium' ? 265 : 200 } };
		const av = (mark) => (mode.type === 'ai' ? (mark === aiMark ? playerAvatar(bot) : playerAvatar('me')) : playerAvatar({ player: 'local-' + mark, avatar: { style: 'blocks', hue: mark === 'X' ? 32 : 200 } }));
		ui.players(
			[
				{ name: n[0], avatar: av('X'), sub: 'ایکس (X)', seat: 0 },
				{ name: n[1], avatar: av('O'), sub: 'او (O)', seat: 1 },
			],
			winnerOf(board) ? null : turn === 'X' ? 0 : 1,
			series
		);
	};

	const render = (line = null) => {
		cells.forEach((c, i) => {
			const m = board[i];
			if (c.dataset.mark !== m) {
				c.dataset.mark = m;
				c.querySelector('.xo-mark')?.remove();
				if (m !== '.') c.insertAdjacentHTML('beforeend', MARK[m]);
			}
			c.setAttribute('aria-label', `خانه ${fa(i + 1)}${m === '.' ? ' خالی' : m === 'X' ? ' ایکس' : ' او'}`);
			c.disabled = m !== '.' || locked || !mode;
			c.classList.toggle('is-win', !!line && line.includes(i));
		});
		if (line) {
			const pos = (i) => [50 + (i % 3) * 100, 50 + Math.floor(i / 3) * 100];
			const [a, b] = [pos(line[0]), pos(line[2])];
			const dx = (b[0] - a[0]) * 0.18;
			const dy = (b[1] - a[1]) * 0.18;
			lineEl.setAttribute('x1', a[0] - dx);
			lineEl.setAttribute('y1', a[1] - dy);
			lineEl.setAttribute('x2', b[0] + dx);
			lineEl.setAttribute('y2', b[1] + dy);
			grid.classList.add('has-line');
		} else grid.classList.remove('has-line');
	};

	const newRound = () => {
		board = '.........';
		locked = false;
		turn = starter;
		if (mode.type === 'ai') aiMark = 'O';
		render();
		renderPlayers();
		statusTurn();
		if (mode.type === 'ai' && turn === aiMark) queueAi();
	};

	const statusTurn = () => {
		if (!mode) return;
		if (mode.type === 'ai') ui.status(turn === aiMark ? 'هوش مصنوعی فکر می‌کند…' : 'نوبت شماست', turn === aiMark ? '' : 'turn');
		else ui.status(`نوبت ${turn === 'X' ? 'ایکس' : 'او'}`, 'turn');
	};

	const queueAi = () => {
		locked = true;
		render();
		aiTimer = setTimeout(() => {
			locked = false;
			place(aiMove(board, aiMark, mode.level), true);
		}, 380 + Math.random() * 350);
	};

	const place = (i, byAi = false) => {
		if (board[i] !== '.') return;
		board = board.slice(0, i) + turn + board.slice(i + 1);
		api.sfx(turn === 'X' ? 'flip' : 'click');
		if (navigator.vibrate && api.touch) navigator.vibrate(8);
		const w = winnerOf(board);
		if (w) return endRound(w);
		turn = turn === 'X' ? 'O' : 'X';
		render();
		renderPlayers();
		statusTurn();
		api.save && api.save();
		if (mode.type === 'ai' && turn === aiMark && !byAi) queueAi();
	};

	const endRound = (w) => {
		locked = true;
		render(w.line);
		if (w.mark === 'draw') series[2]++;
		else series[w.mark === 'X' ? 0 : 1]++;
		renderPlayers();
		starter = starter === 'X' ? 'O' : 'X';
		const again = () => newRound();
		if (mode.type === 'local') {
			ui.status(w.mark === 'draw' ? 'مساوی شد' : `${w.mark === 'X' ? 'ایکس' : 'او'} برد!`, 'win');
			setTimeout(() => ui.result({ title: w.mark === 'draw' ? 'مساوی!' : `${w.mark === 'X' ? 'ایکس' : 'او'} برد!`, text: `نتیجه: ایکس ${fa(series[0])} — ${fa(series[1])} او`, tone: 'win', onPrimary: again }), 700);
			return;
		}
		// vs AI
		const human = aiMark === 'O' ? 'X' : 'O';
		const lvl = mode.level;
		if (w.mark === human) {
			streak++;
			api.sfx('level');
			api.unlock(lvl === 'easy' ? 'win_easy' : 'win_medium');
			const pts = { easy: 100, medium: 300, hard: 1000 }[lvl] * Math.min(3, streak);
			ui.status('بردید!', 'win');
			setTimeout(() => api.over({ score: pts, meta: { mode: lvl, result: 'win', round: series[0] + series[1] + series[2], summary: `هوش مصنوعی «${lvl === 'easy' ? 'آسان' : lvl === 'medium' ? 'متوسط' : 'شکست‌ناپذیر'}» را بردید${streak > 1 ? ` (${fa(streak)} برد پیاپی)` : ''}.` } }), 800);
		} else if (w.mark === 'draw') {
			if (lvl === 'hard') api.unlock('draw_hard');
			api.sfx('coin');
			ui.status('مساوی شد');
			const pts = lvl === 'hard' ? 150 : 0;
			if (pts) setTimeout(() => api.over({ score: pts, meta: { mode: lvl, result: 'draw', summary: 'با هوش مصنوعی شکست‌ناپذیر مساوی کردید؛ این بهترین نتیجه ممکن است!' } }), 800);
			else setTimeout(() => ui.result({ title: 'مساوی!', text: `نتیجه: شما ${fa(series[0])} — ${fa(series[1])} هوش مصنوعی`, onPrimary: again }), 700);
		} else {
			streak = 0;
			api.sfx('over');
			ui.status('هوش مصنوعی برد', 'loss');
			setTimeout(() => ui.result({ title: 'باختید', text: 'یک بار دیگر امتحان کنید؛ گوشه‌ها را جدی بگیرید!', tone: 'loss', onPrimary: again }), 700);
		}
	};

	/* ---------- online ---------- */
	const renderOnline = (view) => {
		const s = view.state;
		if (!s || !s.board) return;
		board = s.board;
		turn = s.turn === 0 ? 'X' : 'O';
		const mine = view.seat === 0 ? 'X' : 'O';
		locked = s.over || s.turn !== view.seat;
		const key = board + s.over;
		if (key !== lastShown && s.last !== undefined && board[s.last] !== mine) api.sfx('flip');
		lastShown = key;
		render(s.line || null);
		if (s.over) ui.status(s.winner === 'draw' ? 'مساوی شد' : s.winner === view.seat ? 'بردید!' : 'باختید', s.winner === view.seat ? 'win' : s.winner === 'draw' ? '' : 'loss');
		else ui.status(s.turn === view.seat ? `نوبت شماست (${mine})` : 'نوبت حریف…', s.turn === view.seat ? 'turn' : '');
	};

	const clickCell = async (i) => {
		if (!mode || locked || board[i] !== '.') return;
		if (mode.type === 'online') {
			const room = ui.room;
			if (!room || !room.view) return;
			const mine = room.view.seat === 0 ? 'X' : 'O';
			// optimistic
			board = board.slice(0, i) + mine + board.slice(i + 1);
			locked = true;
			render();
			api.sfx('click');
			const res = await room.move({ cell: i });
			if (!res || !res.ok) {
				api.sfx('wrong');
				if (room.view) renderOnline(room.view);
			}
			return;
		}
		if (mode.type === 'ai' && turn === aiMark) return;
		place(i);
	};

	L.on(grid, 'click', (e) => {
		const c = e.target.closest('[data-cell]');
		if (c) clickCell(Number(c.dataset.cell));
	});
	L.on(window, 'keydown', (e) => {
		if (!api.running || !mode || ui.layerOpen) return;
		if (e.target.closest && e.target.closest('input, textarea')) return;
		const n = '123456789'.indexOf(e.key) >= 0 ? Number(e.key) - 1 : '۱۲۳۴۵۶۷۸۹'.indexOf(e.key);
		if (n >= 0) {
			e.preventDefault();
			clickCell(n);
		}
	});

	render();
	ui.players([
		{ name: 'شما', avatar: playerAvatar('me'), sub: 'X', seat: 0 },
		{ name: 'حریف', avatar: playerAvatar({ player: 'xo-bot', avatar: { style: 'bot', hue: 200 } }), sub: 'O', seat: 1 },
	]);

	const inst = {
		noAutoPause: true,
		/** Optional shell hook: AI / local game snapshot (online games resume by re-joining the room). */
		serialize() {
			if (!mode || mode.type === 'online') return null;
			return { b: board, t: turn, st: starter, se: series, ai: aiMark, sk: streak, lk: !!winnerOf(board) };
		},
		restore(d) {
			if (!d || !mode || mode.type === 'online' || typeof d.b !== 'string' || d.b.length !== 9) return;
			clearTimeout(aiTimer);
			board = d.b;
			turn = d.t === 'O' ? 'O' : 'X';
			starter = d.st === 'O' ? 'O' : 'X';
			series = Array.isArray(d.se) ? d.se.slice(0, 3).map(Number) : [0, 0, 0];
			aiMark = d.ai === 'X' ? 'X' : 'O';
			streak = Number(d.sk) || 0;
			const w = winnerOf(board);
			if (w) {
				newRound();
				return;
			}
			locked = false;
			render();
			renderPlayers();
			statusTurn();
			if (mode.type === 'ai' && turn === aiMark) queueAi();
		},
		start(opts = {}) {
			clearTimeout(aiTimer);
			ui.start(opts);
		},
		pause() {},
		resume() {},
		destroy() {
			clearTimeout(aiTimer);
			L.off();
			ui.destroy();
		},
	};
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
