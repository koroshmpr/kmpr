/**
 * «منچ» — the traditional cross-and-circle race board game (public domain), 2 or 4 players.
 * Modes via lib/match.js: vs AI (easy / medium / hard) or pass-and-play on one device.
 * Rules: a 6 brings a piece out and gives another roll; landing on an opponent (outside a ★/start
 * square) sends it home and gives another roll; exact roll to reach the centre; three 6s in a row
 * burn the turn. First player with all four pieces home wins.
 */
import { CATALOG } from '../catalog.js';
import { createMatchUI, playerAvatar } from '../lib/match.js';
import { fa, listeners, pick, esc } from '../lib/util.js';

export const achievements = CATALOG.mench.achievements;

/* ---------- geometry (15×15 cells) ---------- */
const TRACK = (() => {
	const t = [];
	const seg = (x, y, dx, dy, n) => {
		for (let i = 0; i < n; i++) t.push([x + dx * i, y + dy * i]);
	};
	seg(1, 6, 1, 0, 5);
	seg(6, 5, 0, -1, 6);
	seg(7, 0, 1, 0, 2);
	seg(8, 1, 0, 1, 5);
	seg(9, 6, 1, 0, 6);
	seg(14, 7, 0, 1, 2);
	seg(13, 8, -1, 0, 5);
	seg(8, 9, 0, 1, 6);
	seg(7, 14, -1, 0, 2);
	seg(6, 13, 0, -1, 5);
	seg(5, 8, -1, 0, 6);
	seg(0, 7, 0, -1, 2);
	return t; // 52 cells
})();
const START = [0, 13, 26, 39];
const SAFE = new Set([0, 8, 13, 21, 26, 34, 39, 47]);
const HOME_COL = [(j) => [1 + j, 7], (j) => [7, 1 + j], (j) => [13 - j, 7], (j) => [7, 13 - j]];
const YARD = [[0, 0], [9, 0], [9, 9], [0, 9]];
const YARD_SPOTS = [[1.9, 1.9], [4.1, 1.9], [1.9, 4.1], [4.1, 4.1]];
const FINISH = 56; // 0..50 track, 51..55 home column, 56 home
const COLORS = [
	{ name: 'قرمز', c: '#ef4444', l: '#fecaca', d: '#991b1b' },
	{ name: 'سبز', c: '#22c55e', l: '#bbf7d0', d: '#166534' },
	{ name: 'زرد', c: '#facc15', l: '#fef9c3', d: '#a16207' },
	{ name: 'آبی', c: '#3b82f6', l: '#bfdbfe', d: '#1e40af' },
];
const LEVEL_PTS = { easy: 400, medium: 900, hard: 1600 };
const PIPS = { 1: [4], 2: [0, 8], 3: [0, 4, 8], 4: [0, 2, 6, 8], 5: [0, 2, 4, 6, 8], 6: [0, 2, 3, 5, 6, 8] };

const abs = (seat, p) => (START[seat] + p) % 52;

function boardSvg(seats) {
	const C = 10;
	let s = `<svg class="mn-svg shrink-0" width="100%" height="100%" viewBox="0 0 150 150" aria-hidden="true">`;
	s += `<rect x="0.5" y="0.5" width="149" height="149" rx="6" fill="#fff7ed" stroke="#16120f" stroke-width="1.6"/>`;
	YARD.forEach(([qx, qy], seat) => {
		const col = COLORS[seat];
		const on = seats.includes(seat);
		s += `<rect x="${qx * C + 1}" y="${qy * C + 1}" width="${6 * C - 2}" height="${6 * C - 2}" rx="5" fill="${on ? col.c : '#cbd5e1'}" stroke="#16120f" stroke-width="1.4"/>`;
		s += `<rect x="${qx * C + 9}" y="${qy * C + 9}" width="${4 * C + 2}" height="${4 * C + 2}" rx="6" fill="#fff" stroke="#16120f" stroke-width="1.2"/>`;
		YARD_SPOTS.forEach(([sx, sy]) => (s += `<circle cx="${(qx + sx) * C}" cy="${(qy + sy) * C}" r="6.2" fill="${on ? col.l : '#e2e8f0'}" stroke="#16120f" stroke-width="1"/>`));
	});
	TRACK.forEach(([x, y], i) => {
		const owner = START.indexOf(i);
		const fill = owner >= 0 && seats.includes(owner) ? COLORS[owner].c : '#fff';
		s += `<rect x="${x * C}" y="${y * C}" width="${C}" height="${C}" fill="${fill}" stroke="#16120f" stroke-width="0.7"/>`;
		if (SAFE.has(i) && owner < 0) s += `<path transform="translate(${x * C + 5} ${y * C + 5}) scale(0.38)" d="M0-9 2.6-3 9-2.8 4-1.2 5.6 7 0 3.2-5.6 7-4-1.2-9-2.8-2.6-3z" fill="#94a3b8" stroke="#16120f" stroke-width="1.2"/>`;
		if (owner >= 0) s += `<path transform="translate(${x * C + 5} ${y * C + 5}) scale(0.38)" d="M0-9 2.6-3 9-2.8 4-1.2 5.6 7 0 3.2-5.6 7-4-1.2-9-2.8-2.6-3z" fill="#fff" opacity="0.85"/>`;
	});
	HOME_COL.forEach((f, seat) => {
		for (let j = 0; j < 5; j++) {
			const [x, y] = f(j);
			s += `<rect x="${x * C}" y="${y * C}" width="${C}" height="${C}" fill="${seats.includes(seat) ? COLORS[seat].c : '#e2e8f0'}" stroke="#16120f" stroke-width="0.7"/>`;
		}
	});
	const tri = [
		['60,60 60,90 75,75', 0],
		['60,60 90,60 75,75', 1],
		['90,60 90,90 75,75', 2],
		['60,90 90,90 75,75', 3],
	];
	tri.forEach(([pts, seat]) => (s += `<polygon points="${pts}" fill="${seats.includes(seat) ? COLORS[seat].c : '#e2e8f0'}" stroke="#16120f" stroke-width="0.8"/>`));
	s += `</svg>`;
	return s;
}

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	stage.classList.add('mn-stage');
	const L = listeners();
	let mode = null;
	let st = null; // game state
	let timers = [];
	let lastPlayers = 2;
	let paused = false;
	const later = (fn, ms) => {
		const run = () => {
			timers = timers.filter((x) => x !== t);
			if (paused) return later(fn, 200); // hold AI / animations while the shell is paused
			fn();
		};
		const t = setTimeout(run, ms);
		timers.push(t);
	};
	const clearTimers = () => {
		timers.forEach(clearTimeout);
		timers = [];
	};
	const STEP = api.reducedMotion ? 60 : 150;

	const ui = createMatchUI(stage, api, {
		ai: [
			{ id: 'easy', label: 'آسان' },
			{ id: 'medium', label: 'متوسط' },
			{ id: 'hard', label: 'زرنگ', hint: 'خطر را حساب می‌کند و فرصت زدن را از دست نمی‌دهد' },
		],
		local: true,
		online: false,
		options: [{ key: 'players', label: 'تعداد بازیکن', choices: [[2, '۲ نفر']], value: 2 } /* 4-player temporarily hidden: turn rotation failed QC (2026-11-25) */],
		summary: () => (st ? st.seats.map((k) => `${COLORS[k].name} ${fa(st.tok[k].filter((p) => p === FINISH).length)}`).join(' · ') : ''),
		onStart: (m) => {
			mode = m;
			newGame();
		},
		onMenu: () => {
			clearTimers();
			mode = null;
			st = null;
			idle();
		},
	});

	const wrap = document.createElement('div');
	wrap.className = 'mn';
	wrap.innerHTML = `<div class="mn-board" data-mn-board>
		<div class="mn-bg" data-mn-bg></div>
		<div class="mn-toks" data-mn-toks></div>
		<button type="button" class="mn-dice" data-mn-dice aria-label="ریختن تاس" disabled><span class="mn-dice__face" data-mn-face></span></button>
	</div>`;
	ui.board.appendChild(wrap);
	const boardEl = wrap.querySelector('[data-mn-board]');
	const bgEl = wrap.querySelector('[data-mn-bg]');
	const toksEl = wrap.querySelector('[data-mn-toks]');
	const diceEl = wrap.querySelector('[data-mn-dice]');
	const faceEl = wrap.querySelector('[data-mn-face]');
	const bar = ui.root.querySelector('[data-bar]');

	const face = (v) => {
		faceEl.innerHTML = Array.from({ length: 9 }, (_, i) => `<i class="${PIPS[v] && PIPS[v].includes(i) ? 'on' : ''}"></i>`).join('');
		diceEl.dataset.v = v || '';
	};

	/* ---------- state ---------- */
	const isAi = (seat) => mode && mode.type === 'ai' && seat !== 0;
	const newGame = () => {
		clearTimers();
		const n = Number((mode && mode.options && mode.options.players) || 2) === 4 ? 4 : 2;
		lastPlayers = n;
		const seats = n === 4 ? [0, 1, 2, 3] : [0, 2];
		st = { seats, tok: [0, 1, 2, 3].map(() => [-1, -1, -1, -1]), turn: 0, dice: 0, phase: 'roll', sixes: 0, caps: [0, 0, 0, 0], lost: [0, 0, 0, 0], rolls: 0, moves: [] };
		bgEl.innerHTML = boardSvg(seats);
		buildTokens();
		render();
		renderPlayers();
		turnStart();
	};
	const idle = () => {
		const seats = lastPlayers === 4 ? [0, 1, 2, 3] : [0, 2];
		bgEl.innerHTML = boardSvg(seats);
		toksEl.innerHTML = '';
		face(6);
		diceEl.disabled = true;
		diceEl.style.setProperty('--dc', '#64748b');
		ui.players([
			{ name: 'شما', avatar: playerAvatar('me'), sub: 'قرمز', seat: 0 },
			{ name: 'حریف', avatar: playerAvatar({ player: 'mench-bot', avatar: { style: 'bot', hue: 48 } }), sub: 'زرد', seat: 1 },
		]);
	};

	const names = (seat) => {
		if (!mode) return COLORS[seat].name;
		if (mode.type === 'ai') return seat === 0 ? 'شما' : `ربات ${COLORS[seat].name}`;
		return `بازیکن ${COLORS[seat].name}`;
	};

	const renderPlayers = () => {
		if (!st) return;
		const chip = (seat) => {
			const home = st.tok[seat].filter((p) => p === FINISH).length;
			const on = st.turn === seat && st.phase !== 'over';
			const av = mode.type === 'ai' && seat !== 0 ? playerAvatar({ player: 'mench-bot-' + seat, avatar: { style: 'bot', hue: [0, 140, 48, 220][seat] } }) : mode.type === 'ai' ? playerAvatar('me') : playerAvatar({ player: 'mench-local-' + seat, avatar: { style: 'blocks', hue: [0, 140, 48, 220][seat] } });
			return `<div class="mn-chip${on ? ' is-on' : ''}" style="--pc:${COLORS[seat].c}"><span class="mn-chip__av">${av}</span><span class="mn-chip__txt"><b>${esc(names(seat))}</b><small>${fa(home)} از ۴ رسیده</small></span></div>`;
		};
		bar.innerHTML = `<div class="mn-chips mn-chips--${st.seats.length}">${st.seats.map(chip).join('')}</div>`;
	};

	/* ---------- tokens ---------- */
	const cellOf = (seat, k, p) => {
		if (p < 0 || p === FINISH) {
			const [qx, qy] = YARD[seat];
			const [sx, sy] = YARD_SPOTS[k];
			return [qx + sx - 0.5, qy + sy - 0.5];
		}
		if (p <= 50) return TRACK[abs(seat, p)];
		return HOME_COL[seat](p - 51);
	};
	let tokEls = [];
	const buildTokens = () => {
		toksEl.innerHTML = '';
		tokEls = st.seats.map((seat) =>
			[0, 1, 2, 3].map((k) => {
				const b = document.createElement('span');
				b.className = 'mn-tok';
				b.style.setProperty('--tc', COLORS[seat].c);
				b.style.setProperty('--td', COLORS[seat].d);
				b.dataset.seat = seat;
				b.dataset.k = k;
				b.innerHTML = `<i class="mn-tok__eye"></i><i class="mn-tok__eye"></i><b class="mn-tok__n">${fa(k + 1)}</b>`;
				toksEl.appendChild(b);
				return b;
			})
		);
	};
	const tokEl = (seat, k) => tokEls[st.seats.indexOf(seat)][k];
	const render = () => {
		if (!st) return;
		// group tokens per cell to offset stacks
		const groups = new Map();
		st.seats.forEach((seat) =>
			st.tok[seat].forEach((p, k) => {
				const [x, y] = cellOf(seat, k, p);
				const key = p < 0 || p === FINISH ? `y${seat}${k}` : `${x},${y}`;
				if (!groups.has(key)) groups.set(key, []);
				groups.get(key).push([seat, k, x, y, p]);
			})
		);
		const movable = st.phase === 'move' ? legal(st.turn, st.dice) : [];
		groups.forEach((list) => {
			list.forEach(([seat, k, x, y, p], i) => {
				const el = tokEl(seat, k);
				const n = list.length;
				const off = n > 1 ? [[-0.18, -0.18], [0.18, 0.18], [0.18, -0.18], [-0.18, 0.18]][i % 4] : [0, 0];
				el.style.setProperty('--x', ((x + 0.5 + off[0]) / 15) * 100 + '%');
				el.style.setProperty('--y', ((y + 0.5 + off[1]) / 15) * 100 + '%');
				el.classList.toggle('is-small', n > 1);
				el.classList.toggle('is-home', p === FINISH);
				el.classList.toggle('is-can', seat === st.turn && movable.includes(k) && !isAi(seat));
				el.style.zIndex = String(10 + (p === FINISH ? 0 : y * 2) + i);
			});
		});
	};

	/* ---------- rules ---------- */
	const legal = (seat, v) => {
		const out = [];
		st.tok[seat].forEach((p, k) => {
			if (p === FINISH) return;
			if (p < 0) {
				if (v === 6) out.push(k);
				return;
			}
			if (p + v <= FINISH) out.push(k);
		});
		return out;
	};
	const occupants = (a, except) => {
		const out = [];
		st.seats.forEach((seat) => {
			if (seat === except) return;
			st.tok[seat].forEach((p, k) => p >= 0 && p <= 50 && abs(seat, p) === a && out.push([seat, k]));
		});
		return out;
	};
	/** Opponent pieces that could reach absolute square `a` with one roll. */
	const threats = (a, me) => {
		if (SAFE.has(a)) return 0;
		let n = 0;
		st.seats.forEach((seat) => {
			if (seat === me) return;
			st.tok[seat].forEach((q) => {
				if (q < 0 || q > 50) return;
				const d = (a - abs(seat, q) + 52) % 52;
				if (d >= 1 && d <= 6 && q + d <= 50) n++;
			});
		});
		return n;
	};
	const evalMove = (seat, k, v, level) => {
		const p = st.tok[seat][k];
		const np = p < 0 ? 0 : p + v;
		let sc = np * 0.25;
		if (np === FINISH) sc += 100;
		if (p < 0) sc += 55;
		if (np > 50 && p <= 50) sc += 40;
		if (np <= 50) {
			const a = abs(seat, np);
			const caps = SAFE.has(a) ? [] : occupants(a, seat);
			if (caps.length) sc += 85 + caps.reduce((m, [s2, k2]) => m + st.tok[s2][k2] * 0.6, 0);
			if (SAFE.has(a)) sc += 18;
			if (level === 'hard') sc -= threats(a, seat) * (22 + np * 0.4);
		}
		if (level === 'hard' && p >= 0 && p <= 50) sc += threats(abs(seat, p), seat) * (16 + p * 0.4);
		return sc + Math.random() * (level === 'hard' ? 2 : 14);
	};
	const aiChoose = (seat, v, opts) => {
		const lvl = mode.level || 'medium';
		if (lvl === 'easy' && Math.random() < 0.55) return pick(opts);
		let best = opts[0];
		let bs = -Infinity;
		for (const k of opts) {
			const sc = evalMove(seat, k, v, lvl);
			if (sc > bs) ((bs = sc), (best = k));
		}
		return best;
	};

	/* ---------- turn flow ---------- */
	const status = (t, tone = '') => ui.status(t, tone);
	const turnStart = () => {
		if (!st || st.phase === 'over') return;
		st.phase = 'roll';
		st.dice = 0;
		diceEl.style.setProperty('--dc', COLORS[st.turn].c);
		const ai = isAi(st.turn);
		diceEl.disabled = ai;
		diceEl.classList.toggle('is-ready', !ai);
		renderPlayers();
		render();
		if (ai) {
			status(`نوبت ${names(st.turn)}…`);
			later(roll, 650);
		} else status(mode.type === 'ai' ? 'نوبت شماست: تاس بریز' : `نوبت ${names(st.turn)}: تاس بریز`, 'turn');
	};

	const roll = () => {
		if (!st || st.phase !== 'roll') return;
		st.phase = 'rolling';
		diceEl.disabled = true;
		diceEl.classList.remove('is-ready');
		diceEl.classList.add('is-rolling');
		api.sfx('click');
		if (navigator.vibrate && api.touch && !isAi(st.turn)) navigator.vibrate(10);
		const v = 1 + Math.floor(Math.random() * 6);
		let n = 0;
		const spin = () => {
			face(1 + Math.floor(Math.random() * 6));
			if (++n < (api.reducedMotion ? 1 : 6)) later(spin, 60);
			else later(() => afterRoll(v), 60);
		};
		spin();
	};

	const afterRoll = (v) => {
		diceEl.classList.remove('is-rolling');
		face(v);
		st.dice = v;
		st.rolls++;
		const seat = st.turn;
		const me = !isAi(seat);
		if (v === 6) {
			st.sixes++;
			if (st.sixes === 3) {
				api.sfx('wrong');
				status(`سه شش پشت‌سرهم! نوبت ${names(seat)} سوخت`, 'loss');
				return later(nextTurn, 1100);
			}
		}
		const opts = legal(seat, v);
		if (!opts.length) {
			status(`${me ? (mode.type === 'ai' ? 'حرکتی نداری' : `${names(seat)} حرکتی ندارد`) : `${names(seat)} حرکتی ندارد`}${v === 6 ? ' — دوباره بریز' : ''}`);
			return later(v === 6 ? turnStart : nextTurn, 900);
		}
		const distinct = new Set(opts.map((k) => st.tok[seat][k]));
		if (!me) return later(() => move(seat, aiChoose(seat, v, opts), v), 420);
		if (distinct.size === 1) return later(() => move(seat, opts[0], v), 320);
		st.phase = 'move';
		render();
		status(api.touch ? 'یک مهره روشن را لمس کن' : 'یک مهره روشن را بزن (کلید ۱ تا ۴)', 'turn');
	};

	const move = (seat, k, v) => {
		if (!st || st.phase === 'anim' || st.phase === 'over') return;
		st.phase = 'anim';
		render();
		const from = st.tok[seat][k];
		const target = from < 0 ? 0 : from + v;
		const stepTo = (p) => {
			st.tok[seat][k] = p;
			api.sfx('flip');
			render();
			if (p < target) later(() => stepTo(p + 1), STEP);
			else later(() => landed(seat, k, from, target, v), STEP);
		};
		stepTo(from < 0 ? 0 : from + 1);
	};

	const landed = (seat, k, from, p, v) => {
		let bonus = false;
		const me = !isAi(seat);
		if (p <= 50) {
			const a = abs(seat, p);
			if (!SAFE.has(a)) {
				const caps = occupants(a, seat);
				if (caps.length) {
					caps.forEach(([s2, k2]) => {
						st.tok[s2][k2] = -1;
						st.lost[s2]++;
						const el = tokEl(s2, k2);
						el.classList.add('is-hit');
						later(() => el.classList.remove('is-hit'), 600);
					});
					st.caps[seat] += caps.length;
					bonus = true;
					api.sfx('hit');
					if (navigator.vibrate && api.touch) navigator.vibrate([20, 30, 20]);
					const what = caps.length > 1 ? `${fa(caps.length)} مهره` : 'یک مهره';
					api.toast(mode.type === 'ai' && seat === 0 ? `${what} زدی و به خانه فرستادی!` : `${names(seat)} ${what} زد و به خانه فرستاد!`);
					if (mode.type === 'ai' && seat === 0) {
						api.unlock('capture');
						if (st.caps[0] >= 3) api.unlock('hunter');
					}
				}
			}
		}
		if (p === FINISH) {
			bonus = true;
			api.sfx('coin');
			if (seat === 0 && mode.type === 'ai') api.unlock('home');
		}
		render();
		renderPlayers();
		api.save && api.save();
		if (st.tok[seat].every((q) => q === FINISH)) return win(seat);
		if (v === 6 || bonus) {
			if (v !== 6) st.sixes = 0;
			status(v === 6 ? `شش آوردی؛ ${me ? 'دوباره بریز' : 'دوباره می‌ریزد'}!` : `جایزه: ${me ? 'دوباره بریز' : 'دوباره می‌ریزد'}!`, 'turn');
			return later(turnStart, 450);
		}
		nextTurn();
	};

	const nextTurn = () => {
		if (!st) return;
		st.sixes = 0;
		const i = st.seats.indexOf(st.turn);
		st.turn = st.seats[(i + 1) % st.seats.length];
		turnStart();
	};

	const win = (seat) => {
		st.phase = 'over';
		diceEl.disabled = true;
		diceEl.classList.remove('is-ready');
		renderPlayers();
		render();
		const again = () => newGame();
		if (mode.type === 'local') {
			api.sfx('level');
			status(`${names(seat)} برد!`, 'win');
			return later(() => ui.result({ title: `${COLORS[seat].name} برد! 🎉`, text: `هر چهار مهره ${COLORS[seat].name} به خانه رسید. زده‌شده‌ها: ${fa(st.caps[seat])}`, tone: 'win', onPrimary: again }), 900);
		}
		if (seat === 0) {
			const lvl = mode.level || 'medium';
			api.unlock('win');
			if (lvl === 'hard') api.unlock('win_hard');
			if (!st.lost[0]) api.unlock('clean');
			const pts = Math.min(6000, Math.round(LEVEL_PTS[lvl] * (st.seats.length === 4 ? 1.5 : 1) + st.caps[0] * 60 + (st.lost[0] ? 0 : 300)));
			api.sfx('level');
			status('بردید! 🎉', 'win');
			return later(() => api.over({ score: pts, meta: { mode: lvl, result: 'win', players: st.seats.length, summary: `منچ ${fa(st.seats.length)} نفره را مقابل ربات «${lvl === 'easy' ? 'آسان' : lvl === 'medium' ? 'متوسط' : 'زرنگ'}» بردید؛ ${fa(st.caps[0])} مهره حریف را زدید` } }), 1000);
		}
		api.sfx('over');
		status(`${names(seat)} برد`, 'loss');
		const mine = st.tok[0].filter((q) => q === FINISH).length;
		later(() => ui.result({ title: 'باختید', text: `${names(seat)} زودتر رسید. شما ${fa(mine)} مهره به خانه رساندید؛ مهره‌ها را روی خانه‌های ستاره نگه دارید!`, tone: 'loss', onPrimary: again }), 900);
	};

	/* ---------- input ---------- */
	const humanTurn = () => st && mode && !isAi(st.turn) && !ui.layerOpen;
	const pickToken = (k) => {
		if (!humanTurn() || st.phase !== 'move') return;
		if (!legal(st.turn, st.dice).includes(k)) {
			api.sfx('wrong');
			return;
		}
		move(st.turn, k, st.dice);
	};
	L.on(boardEl, 'click', (e) => {
		if (!api.running || !humanTurn()) return;
		if (e.target.closest('[data-mn-dice]')) {
			if (st.phase === 'roll') roll();
			return;
		}
		if (st.phase === 'roll') {
			// tapping your own piece while you should roll = roll (fewer missed taps on phones)
			const t = e.target.closest('.mn-tok');
			if (t && Number(t.dataset.seat) === st.turn) roll();
			return;
		}
		if (st.phase !== 'move') return;
		// nearest movable piece to the tap point (big effective hit area)
		const r = boardEl.getBoundingClientRect();
		const cell = r.width / 15;
		const opts = legal(st.turn, st.dice);
		let best = -1;
		let bd = Infinity;
		opts.forEach((k) => {
			const b = tokEl(st.turn, k).getBoundingClientRect();
			const d = Math.hypot(b.left + b.width / 2 - e.clientX, b.top + b.height / 2 - e.clientY);
			if (d < bd) ((bd = d), (best = k));
		});
		if (best >= 0 && bd <= cell * 2.4) pickToken(best);
	});
	L.on(window, 'keydown', (e) => {
		if (!api.running || !humanTurn()) return;
		if (e.target.closest && e.target.closest('input, textarea')) return;
		if ((e.key === ' ' || e.key === 'Enter') && st.phase === 'roll') {
			e.preventDefault();
			roll();
			return;
		}
		const n = '1234'.indexOf(e.key) >= 0 ? Number(e.key) - 1 : '۱۲۳۴'.indexOf(e.key);
		if (n >= 0 && st.phase === 'move') {
			e.preventDefault();
			pickToken(n);
		}
	});

	idle();

	const inst = {
		noAutoPause: true,
		/** test/debug hook (read-only use) */
		get _s() {
			return st;
		},
		serialize() {
			if (!st || !mode || st.phase === 'over') return null;
			return { tok: st.tok, turn: st.turn, seats: st.seats, caps: st.caps, lost: st.lost };
		},
		restore(d) {
			if (!d || !mode || !Array.isArray(d.tok) || !Array.isArray(d.seats)) return;
			clearTimers();
			st = { seats: d.seats, tok: d.tok.map((a) => a.map(Number)), turn: d.turn | 0, dice: 0, phase: 'roll', sixes: 0, caps: d.caps || [0, 0, 0, 0], lost: d.lost || [0, 0, 0, 0], rolls: 0 };
			bgEl.innerHTML = boardSvg(st.seats);
			buildTokens();
			turnStart();
		},
		start(opts = {}) {
			clearTimers();
			ui.start(opts);
		},
		pause() {
			paused = true;
		},
		resume() {
			paused = false;
		},
		destroy() {
			clearTimers();
			L.off();
			ui.destroy();
		},
	};
	stage.kmprDebug = inst; // QC hook (tools/game-qc/new-games)
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
