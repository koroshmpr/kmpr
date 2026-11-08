/**
 * «شطرنج» — grown from the drag-and-drop board in my showcase repo (which had no rules)
 * into a full game: legal-move generator (castling, en passant, promotion, check / mate /
 * stalemate, 50-move rule, threefold repetition, insufficient material), drag + tap-to-move,
 * SAN move list, captured pieces, alpha-beta AI in a Web Worker, local 2P and ONLINE rooms
 * (the server re-validates every move with a PHP mirror of these rules).
 */
import { CATALOG } from '../catalog.js';
import { createMatchUI, playerAvatar, BTN_GHOST } from '../lib/match.js';
import { fa, listeners } from '../lib/util.js';

export const achievements = CATALOG.chess.achievements;

/* =====================================================================
 * Rules + AI core — self-contained (it is stringified into a Worker).
 * Board: array[64], index = row*8 + file, row 0 = rank 8. White = uppercase.
 * ================================================================== */
function chessCore() {
	const START = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
	const colorOf = (p) => (p === '.' ? '' : p === p.toUpperCase() ? 'w' : 'b');
	const opp = (c) => (c === 'w' ? 'b' : 'w');
	const sqIdx = (n) => (8 - Number(n[1])) * 8 + (n.charCodeAt(0) - 97);
	const name = (i) => 'abcdefgh'[i & 7] + (8 - (i >> 3));
	const KN = [[-2, -1], [-2, 1], [-1, -2], [-1, 2], [1, -2], [1, 2], [2, -1], [2, 1]];
	const KG = [[-1, -1], [-1, 0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1, 1]];
	const DIAG = [[-1, -1], [-1, 1], [1, -1], [1, 1]];
	const ORTH = [[-1, 0], [1, 0], [0, -1], [0, 1]];

	function parse(fen) {
		const [pl, t, c, e, h, f] = fen.trim().split(/\s+/);
		const b = [];
		for (const ch of pl) {
			if (ch === '/') continue;
			if (ch >= '1' && ch <= '8') for (let i = 0; i < Number(ch); i++) b.push('.');
			else b.push(ch);
		}
		return { b, turn: t || 'w', castle: !c || c === '-' ? '' : c, ep: !e || e === '-' ? -1 : sqIdx(e), half: Number(h) || 0, full: Number(f) || 1 };
	}
	function toFen(s) {
		let out = '';
		for (let r = 0; r < 8; r++) {
			let n = 0;
			for (let f = 0; f < 8; f++) {
				const p = s.b[r * 8 + f];
				if (p === '.') n++;
				else {
					if (n) out += n;
					n = 0;
					out += p;
				}
			}
			if (n) out += n;
			if (r < 7) out += '/';
		}
		return `${out} ${s.turn} ${s.castle || '-'} ${s.ep >= 0 ? name(s.ep) : '-'} ${s.half} ${s.full}`;
	}
	function attacked(b, sq, by) {
		if (sq < 0) return false;
		const r = sq >> 3;
		const f = sq & 7;
		const at = (rr, ff) => (rr >= 0 && rr < 8 && ff >= 0 && ff < 8 ? b[rr * 8 + ff] : null);
		const W = by === 'w';
		const pr = W ? r + 1 : r - 1;
		if (at(pr, f - 1) === (W ? 'P' : 'p') || at(pr, f + 1) === (W ? 'P' : 'p')) return true;
		for (const [dr, df] of KN) if (at(r + dr, f + df) === (W ? 'N' : 'n')) return true;
		for (const [dr, df] of KG) if (at(r + dr, f + df) === (W ? 'K' : 'k')) return true;
		const slide = (dirs, a, q) => {
			for (const [dr, df] of dirs) {
				let rr = r + dr;
				let ff = f + df;
				while (rr >= 0 && rr < 8 && ff >= 0 && ff < 8) {
					const p = b[rr * 8 + ff];
					if (p !== '.') {
						if (p === a || p === q) return true;
						break;
					}
					rr += dr;
					ff += df;
				}
			}
			return false;
		};
		return slide(DIAG, W ? 'B' : 'b', W ? 'Q' : 'q') || slide(ORTH, W ? 'R' : 'r', W ? 'Q' : 'q');
	}
	const kingSq = (b, c) => b.indexOf(c === 'w' ? 'K' : 'k');
	const inCheck = (s, c = s.turn) => attacked(s.b, kingSq(s.b, c), opp(c));

	function pseudo(s) {
		const { b, turn } = s;
		const W = turn === 'w';
		const out = [];
		const add = (from, to, flag = '', promo = '') => out.push({ from, to, flag, promo });
		for (let sq = 0; sq < 64; sq++) {
			const p = b[sq];
			if (p === '.' || colorOf(p) !== turn) continue;
			const r = sq >> 3;
			const f = sq & 7;
			const P = p.toUpperCase();
			const enemy = (t) => t !== '.' && colorOf(t) !== turn;
			if (P === 'P') {
				const dr = W ? -1 : 1;
				const r1 = r + dr;
				const last = W ? 0 : 7;
				const push = (to, flag) => {
					if (r1 === last) ['q', 'r', 'b', 'n'].forEach((pp) => add(sq, to, flag, pp));
					else add(sq, to, flag);
				};
				if (r1 >= 0 && r1 < 8 && b[r1 * 8 + f] === '.') {
					push(r1 * 8 + f, '');
					const start = W ? 6 : 1;
					if (r === start && b[(r + 2 * dr) * 8 + f] === '.') add(sq, (r + 2 * dr) * 8 + f, 'd');
				}
				for (const df of [-1, 1]) {
					const ff = f + df;
					if (ff < 0 || ff > 7 || r1 < 0 || r1 > 7) continue;
					const to = r1 * 8 + ff;
					if (enemy(b[to])) push(to, 'c');
					else if (to === s.ep) add(sq, to, 'e');
				}
			} else if (P === 'N' || P === 'K') {
				for (const [dr, df] of P === 'N' ? KN : KG) {
					const rr = r + dr;
					const ff = f + df;
					if (rr < 0 || rr > 7 || ff < 0 || ff > 7) continue;
					const t = b[rr * 8 + ff];
					if (t === '.') add(sq, rr * 8 + ff);
					else if (enemy(t)) add(sq, rr * 8 + ff, 'c');
				}
				if (P === 'K') {
					const home = W ? 60 : 4;
					const o = W ? 'b' : 'w';
					if (sq === home && !attacked(b, home, o)) {
						if (s.castle.includes(W ? 'K' : 'k') && b[home + 1] === '.' && b[home + 2] === '.' && b[home + 3] === (W ? 'R' : 'r') && !attacked(b, home + 1, o) && !attacked(b, home + 2, o)) add(sq, home + 2, 'k');
						if (s.castle.includes(W ? 'Q' : 'q') && b[home - 1] === '.' && b[home - 2] === '.' && b[home - 3] === '.' && b[home - 4] === (W ? 'R' : 'r') && !attacked(b, home - 1, o) && !attacked(b, home - 2, o)) add(sq, home - 2, 'q');
					}
				}
			} else {
				const dirs = P === 'B' ? DIAG : P === 'R' ? ORTH : DIAG.concat(ORTH);
				for (const [dr, df] of dirs) {
					let rr = r + dr;
					let ff = f + df;
					while (rr >= 0 && rr < 8 && ff >= 0 && ff < 8) {
						const t = b[rr * 8 + ff];
						if (t === '.') add(sq, rr * 8 + ff);
						else {
							if (enemy(t)) add(sq, rr * 8 + ff, 'c');
							break;
						}
						rr += dr;
						ff += df;
					}
				}
			}
		}
		return out;
	}
	function make(s, m) {
		const b = s.b.slice();
		const p = b[m.from];
		const W = s.turn === 'w';
		const captured = m.flag === 'e' ? b[m.to + (W ? 8 : -8)] : b[m.to];
		b[m.to] = m.promo ? (W ? m.promo.toUpperCase() : m.promo) : p;
		b[m.from] = '.';
		if (m.flag === 'e') b[m.to + (W ? 8 : -8)] = '.';
		if (m.flag === 'k') {
			b[m.from + 1] = b[m.from + 3];
			b[m.from + 3] = '.';
		}
		if (m.flag === 'q') {
			b[m.from - 1] = b[m.from - 4];
			b[m.from - 4] = '.';
		}
		let castle = s.castle;
		if (p === 'K') castle = castle.replace(/[KQ]/g, '');
		if (p === 'k') castle = castle.replace(/[kq]/g, '');
		const rights = { 63: 'K', 56: 'Q', 7: 'k', 0: 'q' };
		if (rights[m.from]) castle = castle.replace(rights[m.from], '');
		if (rights[m.to]) castle = castle.replace(rights[m.to], '');
		return {
			b,
			turn: opp(s.turn),
			castle,
			ep: m.flag === 'd' ? (m.from + m.to) / 2 : -1,
			half: p.toUpperCase() === 'P' || captured !== '.' ? 0 : s.half + 1,
			full: s.full + (s.turn === 'b' ? 1 : 0),
			captured,
		};
	}
	function legal(s) {
		return pseudo(s).filter((m) => {
			const n = make(s, m);
			return !attacked(n.b, kingSq(n.b, s.turn), n.turn);
		});
	}
	const key = (s) => s.b.join('') + s.turn + s.castle + s.ep;
	function insufficient(b) {
		const rest = b.filter((p) => p !== '.' && p.toUpperCase() !== 'K');
		if (!rest.length) return true;
		if (rest.length === 1 && 'NBnb'.includes(rest[0])) return true;
		if (rest.every((p) => p.toUpperCase() === 'B')) {
			const cols = new Set();
			b.forEach((p, i) => p.toUpperCase() === 'B' && cols.add(((i >> 3) + (i & 7)) % 2));
			return cols.size === 1;
		}
		return false;
	}
	function san(s, m, all) {
		if (m.flag === 'k') return 'O-O' + suffix(s, m);
		if (m.flag === 'q') return 'O-O-O' + suffix(s, m);
		const P = s.b[m.from].toUpperCase();
		const cap = m.flag === 'c' || m.flag === 'e';
		let out = '';
		if (P === 'P') {
			out = (cap ? name(m.from)[0] + 'x' : '') + name(m.to) + (m.promo ? '=' + m.promo.toUpperCase() : '');
		} else {
			const twins = (all || legal(s)).filter((o) => o.to === m.to && o.from !== m.from && s.b[o.from] === s.b[m.from]);
			let dis = '';
			if (twins.length) {
				const sameFile = twins.some((o) => (o.from & 7) === (m.from & 7));
				const sameRank = twins.some((o) => o.from >> 3 === m.from >> 3);
				dis = !sameFile ? name(m.from)[0] : !sameRank ? name(m.from)[1] : name(m.from);
			}
			out = P + dis + (cap ? 'x' : '') + name(m.to);
		}
		return out + suffix(s, m);
	}
	function suffix(s, m) {
		const n = make(s, m);
		if (!inCheck(n)) return '';
		return legal(n).length ? '+' : '#';
	}
	/** '' | mate | stalemate | fifty | repetition | material */
	function status(s, keys = []) {
		const moves = legal(s);
		if (!moves.length) return inCheck(s) ? 'mate' : 'stalemate';
		if (s.half >= 100) return 'fifty';
		if (insufficient(s.b)) return 'material';
		const k = key(s);
		if (keys.filter((x) => x === k).length >= 3) return 'repetition';
		return '';
	}

	/* ---------- AI ---------- */
	const VAL = { P: 100, N: 320, B: 330, R: 500, Q: 900, K: 0 };
	const PST = {
		P: [0, 0, 0, 0, 0, 0, 0, 0, 50, 50, 50, 50, 50, 50, 50, 50, 10, 10, 20, 30, 30, 20, 10, 10, 5, 5, 10, 25, 25, 10, 5, 5, 0, 0, 0, 20, 20, 0, 0, 0, 5, -5, -10, 0, 0, -10, -5, 5, 5, 10, 10, -20, -20, 10, 10, 5, 0, 0, 0, 0, 0, 0, 0, 0],
		N: [-50, -40, -30, -30, -30, -30, -40, -50, -40, -20, 0, 0, 0, 0, -20, -40, -30, 0, 10, 15, 15, 10, 0, -30, -30, 5, 15, 20, 20, 15, 5, -30, -30, 0, 15, 20, 20, 15, 0, -30, -30, 5, 10, 15, 15, 10, 5, -30, -40, -20, 0, 5, 5, 0, -20, -40, -50, -40, -30, -30, -30, -30, -40, -50],
		B: [-20, -10, -10, -10, -10, -10, -10, -20, -10, 0, 0, 0, 0, 0, 0, -10, -10, 0, 5, 10, 10, 5, 0, -10, -10, 5, 5, 10, 10, 5, 5, -10, -10, 0, 10, 10, 10, 10, 0, -10, -10, 10, 10, 10, 10, 10, 10, -10, -10, 5, 0, 0, 0, 0, 5, -10, -20, -10, -10, -10, -10, -10, -10, -20],
		R: [0, 0, 0, 0, 0, 0, 0, 0, 5, 10, 10, 10, 10, 10, 10, 5, -5, 0, 0, 0, 0, 0, 0, -5, -5, 0, 0, 0, 0, 0, 0, -5, -5, 0, 0, 0, 0, 0, 0, -5, -5, 0, 0, 0, 0, 0, 0, -5, -5, 0, 0, 0, 0, 0, 0, -5, 0, 0, 0, 5, 5, 0, 0, 0],
		Q: [-20, -10, -10, -5, -5, -10, -10, -20, -10, 0, 0, 0, 0, 0, 0, -10, -10, 0, 5, 5, 5, 5, 0, -10, -5, 0, 5, 5, 5, 5, 0, -5, 0, 0, 5, 5, 5, 5, 0, -5, -10, 5, 5, 5, 5, 5, 0, -10, -10, 0, 5, 0, 0, 0, 0, -10, -20, -10, -10, -5, -5, -10, -10, -20],
		K: [-30, -40, -40, -50, -50, -40, -40, -30, -30, -40, -40, -50, -50, -40, -40, -30, -30, -40, -40, -50, -50, -40, -40, -30, -30, -40, -40, -50, -50, -40, -40, -30, -20, -30, -30, -40, -40, -30, -30, -20, -10, -20, -20, -20, -20, -20, -20, -10, 20, 20, 0, 0, 0, 0, 20, 20, 20, 30, 10, 0, 0, 10, 30, 20],
	};
	function evaluate(s) {
		let sc = 0;
		for (let i = 0; i < 64; i++) {
			const p = s.b[i];
			if (p === '.') continue;
			const P = p.toUpperCase();
			if (p === P) sc += VAL[P] + PST[P][i];
			else sc -= VAL[P] + PST[P][i ^ 56];
		}
		return s.turn === 'w' ? sc : -sc;
	}
	const order = (s, moves) =>
		moves
			.map((m) => ({ m, k: (m.flag === 'c' ? 10 * VAL[s.b[m.to].toUpperCase()] - VAL[s.b[m.from].toUpperCase()] : 0) + (m.promo ? 800 : 0) }))
			.sort((a, b) => b.k - a.k)
			.map((x) => x.m);
	let nodes = 0;
	function quiesce(s, alpha, beta, depth) {
		nodes++;
		const stand = evaluate(s);
		if (stand >= beta) return beta;
		if (alpha < stand) alpha = stand;
		if (depth <= 0) return alpha;
		for (const m of order(s, pseudo(s).filter((x) => x.flag === 'c' || x.promo))) {
			const n = make(s, m);
			if (attacked(n.b, kingSq(n.b, s.turn), n.turn)) continue;
			const sc = -quiesce(n, -beta, -alpha, depth - 1);
			if (sc >= beta) return beta;
			if (sc > alpha) alpha = sc;
		}
		return alpha;
	}
	function search(s, depth, alpha, beta, ply) {
		if (depth <= 0) return quiesce(s, alpha, beta, 3);
		nodes++;
		let any = false;
		for (const m of order(s, pseudo(s))) {
			const n = make(s, m);
			if (attacked(n.b, kingSq(n.b, s.turn), n.turn)) continue;
			any = true;
			const sc = -search(n, depth - 1, -beta, -alpha, ply + 1);
			if (sc >= beta) return beta;
			if (sc > alpha) alpha = sc;
		}
		if (!any) return inCheck(s) ? -100000 + ply : 0;
		return alpha;
	}
	function bestMove(s, depth, noise = 0) {
		nodes = 0;
		let best = null;
		let bestSc = -Infinity;
		for (const m of order(s, legal(s))) {
			const sc = -search(make(s, m), depth - 1, -Infinity, -bestSc + noise, 1) + (noise ? Math.random() * noise : 0);
			if (sc > bestSc) {
				bestSc = sc;
				best = m;
			}
		}
		return best;
	}
	return { START, parse, toFen, legal, make, san, status, key, inCheck, bestMove, name, sqIdx, colorOf, VAL };
}

const C = chessCore();

/* ---------- AI runner: Worker from a Blob (falls back to main thread) ---------- */
let worker = null;
let workerFailed = false;
function think(fen, depth, noise) {
	return new Promise((resolve) => {
		if (!workerFailed && typeof Worker !== 'undefined') {
			try {
				if (!worker) {
					const src = `const C=(${chessCore.toString()})();onmessage=(e)=>{const d=e.data;postMessage(C.bestMove(C.parse(d.fen),d.depth,d.noise));};`;
					worker = new Worker(URL.createObjectURL(new Blob([src], { type: 'text/javascript' })));
				}
				const onMsg = (e) => {
					worker.removeEventListener('message', onMsg);
					resolve(e.data);
				};
				worker.addEventListener('message', onMsg);
				worker.postMessage({ fen, depth, noise });
				return;
			} catch (e) {
				workerFailed = true;
			}
		}
		setTimeout(() => resolve(C.bestMove(C.parse(fen), Math.min(depth, 2), noise)), 30);
	});
}

/* ---------- original SVG piece set ---------- */
const PIECE_PATHS = {
	P: '<circle cx="22.5" cy="13" r="5.2"/><path d="M17.5 19.5h10l2 9.5c3.2 1.8 5 4 5.5 7.5H10c.5-3.5 2.3-5.7 5.5-7.5Z"/>',
	R: '<path d="M11 9h4.5v3.5h3V9h8v3.5h3V9H34v7l-3 2.5V30l3.5 3.5V37h-24v-3.5L14 30V18.5L11 16Z"/><path d="M14 18.5h17M14 30h17" fill="none"/>',
	N: '<path d="M14 37h21l-1.5-6c-1-6 .5-12-2.5-17.5C28.5 9 24 7.5 20 8l-1.2-2.5-2 3.4C13 11 10 15.5 9.3 20.5L12.5 23l3.5-2.5 3 1c-3.5 4-6 8-5 15.5Z"/><circle cx="17.5" cy="14.5" r="1.4" class="pc-eye"/>',
	B: '<path d="M22.5 6.5a2.4 2.4 0 1 1 0 4.8 2.4 2.4 0 0 1 0-4.8Z"/><path d="M22.5 11.5c5 3.5 8 7.5 8 12 0 3-1.5 5-3.2 6.2H17.7C16 28.5 14.5 26.5 14.5 23.5c0-4.5 3-8.5 8-12Z"/><path d="M25 16.5l-4 5" fill="none"/><path d="M12 37c1.5-3.5 4.5-6.3 10.5-6.3S31.5 33.5 33 37Z"/>',
	Q: '<circle cx="8.5" cy="12.5" r="2.4"/><circle cx="15.5" cy="9" r="2.4"/><circle cx="22.5" cy="7.5" r="2.4"/><circle cx="29.5" cy="9" r="2.4"/><circle cx="36.5" cy="12.5" r="2.4"/><path d="M9.5 15l4.5 15h17l4.5-15-6.5 8-3.5-11-2.5 10.5-2.5-10.5-3.5 11Z"/><path d="M12 37c.5-3 1.5-5.5 2-7h17c.5 1.5 1.5 4 2 7Z"/>',
	K: '<path d="M22.5 4.5v8M18.5 8.5h8" fill="none"/><path d="M22.5 14c4 0 6.5 2.5 6.5 5.5 0 2.5-2 4-2 4h-9s-2-1.5-2-4c0-3 2.5-5.5 6.5-5.5Z"/><path d="M11 22.5c3.5-2 8-1.5 11.5 1.5 3.5-3 8-3.5 11.5-1.5 3.5 2.5 3 7-1 9.5H12c-4-2.5-4.5-7-1-9.5Z"/><path d="M12 37c.5-2.5 0-3.5 0-5h21c0 1.5-.5 2.5 0 5Z"/>',
};
const pieceSvg = (p, size = '100%') => {
	const P = p.toUpperCase();
	const white = p === P;
	return `<svg width="${size}" height="${size}" class="shrink-0 chess-pc ${white ? 'chess-pc--w' : 'chess-pc--b'}" viewBox="0 0 45 45" aria-hidden="true">${PIECE_PATHS[P]}</svg>`;
};
const PIECE_FA = { P: 'پیاده', N: 'اسب', B: 'فیل', R: 'رخ', Q: 'وزیر', K: 'شاه' };
const REASON_FA = { mate: 'کیش و مات', stalemate: 'پات (بن‌بست)', fifty: 'قانون ۵۰ حرکت', repetition: 'تکرار سه‌باره', material: 'مهره کافی برای مات نیست', resign: 'ترک بازی' };

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	let mode = null;
	let s = C.parse(C.START);
	let keys = [C.key(s)];
	let sans = [];
	let ucis = [];
	let last = null;
	let sel = -1;
	let targets = [];
	let flipped = false;
	let thinking = false;
	let over = false;
	let myColor = 'w';
	let series = [0, 0, 0];
	let pendingOnline = false;
	let drag = null;
	let aiToken = 0;

	const ui = createMatchUI(stage, api, {
		ai: [
			{ id: 'easy', label: 'آسان' },
			{ id: 'medium', label: 'متوسط' },
		],
		local: true,
		online: true,
		seatLabel: (seat) => {
			const v = ui && ui.room && ui.room.view;
			const white = v && v.state && v.state.white !== undefined ? v.state.white : 0;
			return seat === white ? 'سفید' : 'سیاه';
		},
		activeSeat: (view) => {
			const st = view.state;
			if (!st || st.over || !st.turn) return null;
			return st.turn === 'w' ? st.white : 1 - st.white;
		},
		resultText: (view) => {
			const st = view.state;
			if (!st || !st.reason) return null;
			const r = REASON_FA[st.reason] || '';
			if (st.winner === 'draw') return { title: 'مساوی!', text: r };
			return { title: st.winner === view.seat ? 'بردید! 🎉' : 'باختید', text: r };
		},
		onStart: (m) => {
			mode = m;
			series = [0, 0, 0];
			if (m.type === 'online') {
				resetGame();
				onlineState(m.view);
			} else {
				myColor = 'w';
				flipped = false;
				resetGame();
				renderPlayers();
				statusLine();
			}
		},
		onRoomState: (view) => onlineState(view),
		onMenu: () => {
			mode = null;
			aiToken++;
			thinking = false;
			resetGame();
			ui.players(defaultPlayers());
		},
	});

	const defaultPlayers = () => [
		{ name: 'شما', avatar: playerAvatar('me'), sub: 'سفید', seat: 0 },
		{ name: 'حریف', avatar: playerAvatar({ player: 'chess-bot', avatar: { style: 'gem', hue: 265 } }), sub: 'سیاه', seat: 1 },
	];

	/* ---------- DOM ---------- */
	const wrap = document.createElement('div');
	wrap.className = 'chess';
	wrap.innerHTML = `
		<div class="chess__main">
			<div class="chess__cap" data-cap="top"></div>
			<div class="chess__board" data-board role="grid" aria-label="صفحه شطرنج">
				${Array.from({ length: 64 }, (_, d) => `<div class="chess__sq ${((d >> 3) + (d & 7)) % 2 ? 'is-dark' : 'is-light'}" data-d="${d}" role="gridcell"><span class="chess__pc"></span></div>`).join('')}
				<div class="chess__promo" data-promo hidden></div>
			</div>
			<div class="chess__cap" data-cap="bottom"></div>
		</div>
		<aside class="chess__side" aria-label="حرکت‌ها">
			<div class="flex items-center justify-between gap-2">
				<p class="text-sm font-black text-white">حرکت‌ها</p>
				<button type="button" class="${BTN_GHOST} !min-h-10 !px-3 !py-2 !text-xs" data-flip>چرخاندن صفحه</button>
			</div>
			<ol class="chess__moves" dir="ltr" data-moves></ol>
		</aside>`;
	ui.board.appendChild(wrap);
	const boardEl = wrap.querySelector('[data-board]');
	const sqEls = [...boardEl.querySelectorAll('.chess__sq')];
	const promoEl = wrap.querySelector('[data-promo]');
	const movesEl = wrap.querySelector('[data-moves]');
	const capTop = wrap.querySelector('[data-cap="top"]');
	const capBottom = wrap.querySelector('[data-cap="bottom"]');

	// coordinates
	const coords = () => {
		sqEls.forEach((el, d) => {
			const sq = flipped ? 63 - d : d;
			el.querySelectorAll('.chess__coord').forEach((c) => c.remove());
			if ((d & 7) === 0) el.insertAdjacentHTML('beforeend', `<span class="chess__coord chess__coord--r">${8 - (sq >> 3)}</span>`);
			if (d >> 3 === 7) el.insertAdjacentHTML('beforeend', `<span class="chess__coord chess__coord--f">${'abcdefgh'[sq & 7]}</span>`);
		});
	};

	/* ---------- sizing: square board that fits the stage ---------- */
	const fit = () => {
		const r = ui.board.getBoundingClientRect();
		if (!r.width || !r.height) return;
		const side = r.width > r.height * 1.35 && r.width > 560;
		wrap.classList.toggle('is-side', side);
		const availW = side ? r.width - 230 : r.width - 8;
		const availH = r.height - 8 - 2 * 26;
		const size = Math.max(200, Math.floor(Math.min(availW, availH) / 8) * 8);
		boardEl.style.width = size + 'px';
		boardEl.style.height = size + 'px';
		wrap.style.setProperty('--board', size + 'px');
	};
	const ro = new ResizeObserver(fit);
	ro.observe(ui.board);

	/* ---------- render ---------- */
	const sqOf = (d) => (flipped ? 63 - d : d);
	const dOf = (sq) => (flipped ? 63 - sq : sq);
	const render = () => {
		const checkSq = C.inCheck(s) ? s.b.indexOf(s.turn === 'w' ? 'K' : 'k') : -1;
		sqEls.forEach((el, d) => {
			const sq = sqOf(d);
			const p = s.b[sq];
			const pc = el.querySelector('.chess__pc');
			if (pc.dataset.p !== p) {
				pc.dataset.p = p;
				pc.innerHTML = p === '.' ? '' : pieceSvg(p);
			}
			el.classList.toggle('is-last', !!last && (last.from === sq || last.to === sq));
			el.classList.toggle('is-sel', sq === sel);
			el.classList.toggle('is-check', sq === checkSq);
			const t = targets.find((m) => m.to === sq);
			el.classList.toggle('has-dot', !!t && p === '.' && t.flag !== 'e');
			el.classList.toggle('has-ring', !!t && (p !== '.' || t.flag === 'e'));
			el.setAttribute('aria-label', `${C.name(sq)} ${p === '.' ? 'خالی' : (p === p.toUpperCase() ? 'سفید ' : 'سیاه ') + PIECE_FA[p.toUpperCase()]}`);
		});
		// captured
		const count = (p) => s.b.filter((x) => x === p).length;
		const START_N = { P: 8, N: 2, B: 2, R: 2, Q: 1 };
		const lost = (white) =>
			Object.keys(START_N)
				.flatMap((P) => Array(Math.max(0, START_N[P] - count(white ? P : P.toLowerCase()))).fill(white ? P : P.toLowerCase()))
				.sort((a, b) => C.VAL[b.toUpperCase()] - C.VAL[a.toUpperCase()]);
		const lw = lost(true);
		const lb = lost(false);
		const mat = (arr) => arr.reduce((a, p) => a + C.VAL[p.toUpperCase()], 0);
		const diff = Math.round((mat(lb) - mat(lw)) / 100);
		const strip = (arr, adv) => arr.map((p) => pieceSvg(p, 18)).join('') + (adv > 0 ? `<span class="chess__adv">+${fa(adv)}</span>` : '');
		// bottom player captured the top colour's pieces
		const bottomWhite = !flipped;
		capBottom.innerHTML = strip(bottomWhite ? lb : lw, bottomWhite ? diff : -diff);
		capTop.innerHTML = strip(bottomWhite ? lw : lb, bottomWhite ? -diff : diff);
		// moves
		let html = '';
		for (let i = 0; i < sans.length; i += 2) html += `<li><span class="chess__mn">${i / 2 + 1}.</span> <span>${sans[i]}</span> <span>${sans[i + 1] || ''}</span></li>`;
		movesEl.innerHTML = html;
		movesEl.scrollTop = movesEl.scrollHeight;
	};

	const resetGame = () => {
		s = C.parse(C.START);
		keys = [C.key(s)];
		sans = [];
		ucis = [];
		last = null;
		sel = -1;
		targets = [];
		over = false;
		thinking = false;
		promoEl.hidden = true;
		coords();
		render();
	};

	const renderPlayers = () => {
		if (!mode || mode.type === 'online') return;
		const turnSeat = over ? null : s.turn === 'w' ? 0 : 1;
		if (mode.type === 'ai')
			ui.players(
				[
					{ name: 'شما', avatar: playerAvatar('me'), sub: 'سفید', seat: 0 },
					{ name: mode.level === 'medium' ? 'ربات متوسط' : 'ربات آسان', avatar: playerAvatar({ player: 'chess-bot-' + mode.level, avatar: { style: 'bot', hue: mode.level === 'medium' ? 340 : 200 } }), sub: 'سیاه', seat: 1 },
				],
				turnSeat,
				series
			);
		else
			ui.players(
				[
					{ name: 'سفید', avatar: playerAvatar({ player: 'local-w', avatar: { style: 'gem', hue: 40 } }), sub: 'بازیکن ۱', seat: 0 },
					{ name: 'سیاه', avatar: playerAvatar({ player: 'local-b', avatar: { style: 'gem', hue: 220 } }), sub: 'بازیکن ۲', seat: 1 },
				],
				turnSeat,
				series
			);
	};

	const statusLine = () => {
		if (!mode) return;
		const check = C.inCheck(s);
		if (mode.type === 'ai') ui.status(thinking ? 'هوش مصنوعی فکر می‌کند…' : check ? 'کیش! شاه را نجات دهید' : 'نوبت شما (سفید)', check ? 'loss' : thinking ? '' : 'turn');
		else if (mode.type === 'local') ui.status(`${check ? 'کیش! ' : ''}نوبت ${s.turn === 'w' ? 'سفید' : 'سیاه'}`, check ? 'loss' : 'turn');
		else {
			const mine = s.turn === myColor;
			ui.status(`${check ? 'کیش! ' : ''}${mine ? 'نوبت شماست' : 'نوبت حریف…'}`, check ? 'loss' : mine ? 'turn' : '');
		}
	};

	const canMove = () => {
		if (!mode || over || !api.running) return false;
		if (mode.type === 'ai') return s.turn === 'w' && !thinking;
		if (mode.type === 'local') return true;
		return s.turn === myColor && !pendingOnline;
	};

	/* ---------- applying moves ---------- */
	const applyMove = (m, { human = false } = {}) => {
		const all = C.legal(s);
		const san = C.san(s, m, all);
		const n = C.make(s, m);
		if (human) {
			if (m.flag === 'k' || m.flag === 'q') api.unlock('castle');
			if (m.promo) api.unlock('promote');
			if (m.flag === 'e') api.unlock('en_passant');
		}
		api.sfx(n.captured && n.captured !== '.' ? 'hit' : 'click');
		if (navigator.vibrate && api.touch && human) navigator.vibrate(10);
		s = n;
		sans.push(san);
		ucis.push(C.name(m.from) + C.name(m.to) + (m.promo || ''));
		keys.push(C.key(s));
		last = { from: m.from, to: m.to };
		sel = -1;
		targets = [];
		render();
		return san;
	};

	const checkEnd = () => {
		const st = C.status(s, keys);
		if (!st) return false;
		over = true;
		const winnerWhite = st === 'mate' ? s.turn === 'b' : null;
		render();
		if (mode.type === 'ai') {
			if (winnerWhite === true) {
				api.unlock('first_win');
				if (mode.level === 'medium') api.unlock('win_medium');
				const full = s.full;
				const pts = (mode.level === 'medium' ? 2500 : 800) + Math.max(0, 40 - full) * 25;
				series[0]++;
				ui.status('کیش و مات! بردید', 'win');
				api.sfx('level');
				setTimeout(() => api.over({ score: pts, meta: { mode: mode.level, result: 'win', moves: sans.length, summary: `هوش مصنوعی ${mode.level === 'medium' ? 'متوسط' : 'آسان'} را در ${fa(full)} حرکت مات کردید.` } }), 1000);
			} else if (winnerWhite === false) {
				series[1]++;
				api.sfx('over');
				ui.status('مات شدید', 'loss');
				setTimeout(() => ui.result({ title: 'مات شدید', text: 'هوش مصنوعی برد. یک دست دیگر؟', tone: 'loss', onPrimary: () => startSame() }), 1000);
			} else {
				series[2]++;
				ui.status('مساوی — ' + REASON_FA[st]);
				setTimeout(() => ui.result({ title: 'مساوی!', text: REASON_FA[st], onPrimary: () => startSame() }), 1000);
			}
		} else if (mode.type === 'local') {
			if (winnerWhite === null) series[2]++;
			else series[winnerWhite ? 0 : 1]++;
			const title = winnerWhite === null ? 'مساوی!' : winnerWhite ? 'سفید برد!' : 'سیاه برد!';
			ui.status(title, 'win');
			setTimeout(() => ui.result({ title, text: REASON_FA[st], tone: 'win', onPrimary: () => startSame() }), 1000);
		}
		renderPlayers();
		return true;
	};

	const startSame = () => {
		resetGame();
		renderPlayers();
		statusLine();
	};

	const aiTurn = async () => {
		thinking = true;
		statusLine();
		renderPlayers();
		const token = ++aiToken;
		const t0 = performance.now();
		const depth = mode.level === 'medium' ? 3 : 2;
		const noise = mode.level === 'medium' ? 8 : 90;
		const m = await think(C.toFen(s), depth, noise);
		const wait = Math.max(0, 450 - (performance.now() - t0));
		await new Promise((r) => setTimeout(r, wait));
		if (token !== aiToken || !mode || mode.type !== 'ai' || over) return;
		thinking = false;
		if (m) {
			const legalM = C.legal(s).find((x) => x.from === m.from && x.to === m.to && (x.promo || '') === (m.promo || ''));
			if (legalM) applyMove(legalM);
		}
		if (!checkEnd()) {
			statusLine();
			renderPlayers();
		}
	};

	const commit = async (m) => {
		if (mode.type === 'online') {
			const room = ui.room;
			if (!room || !room.view) return;
			applyMove(m, { human: true });
			pendingOnline = true;
			statusLine();
			const res = await room.move({ from: C.name(m.from), to: C.name(m.to), promo: m.promo || '' });
			pendingOnline = false;
			if (!res || !res.ok) {
				api.sfx('wrong');
				if (room.view) onlineState(room.view, true);
			}
			return;
		}
		applyMove(m, { human: true });
		if (checkEnd()) return;
		renderPlayers();
		statusLine();
		if (mode.type === 'ai') aiTurn();
	};

	const tryMove = (from, to) => {
		const cands = targets.filter((m) => m.from === from && m.to === to);
		if (!cands.length) return false;
		if (cands.length > 1 || cands[0].promo) {
			const white = s.turn === 'w';
			promoEl.innerHTML = `<p class="text-sm font-black text-white mb-2">ارتقا به:</p><div class="flex gap-2">${['q', 'r', 'b', 'n']
				.map((pp) => `<button type="button" class="chess__promo-btn" data-pp="${pp}" aria-label="${PIECE_FA[pp.toUpperCase()]}">${pieceSvg(white ? pp.toUpperCase() : pp, 44)}</button>`)
				.join('')}</div>`;
			promoEl.hidden = false;
			promoEl.onclick = (e) => {
				const b = e.target.closest('[data-pp]');
				if (!b) return;
				promoEl.hidden = true;
				commit(cands.find((m) => m.promo === b.dataset.pp));
			};
			return true;
		}
		commit(cands[0]);
		return true;
	};

	const select = (sq) => {
		sel = sq;
		targets = C.legal(s).filter((m) => m.from === sq);
		render();
	};

	/* ---------- online ---------- */
	const onlineState = (view, force = false) => {
		const st = view.state;
		if (!st || !st.fen) return;
		if (pendingOnline && !force) return;
		const white = st.white ?? 0;
		myColor = view.seat === white ? 'w' : 'b';
		if (flipped !== (myColor === 'b')) {
			flipped = myColor === 'b';
			coords();
		}
		const srv = st.uci || [];
		const same = srv.length === ucis.length && srv.every((u, i) => u === ucis[i]);
		if (!same) {
			const prefix = srv.length > ucis.length && ucis.every((u, i) => u === srv[i]);
			if (!prefix) {
				s = C.parse(C.START);
				keys = [C.key(s)];
				sans = [];
				ucis = [];
				last = null;
			}
			for (let i = ucis.length; i < srv.length; i++) {
				const u = srv[i];
				const m = C.legal(s).find((x) => C.name(x.from) === u.slice(0, 2) && C.name(x.to) === u.slice(2, 4) && (x.promo || '') === (u[4] || ''));
				if (!m) break;
				applyMove(m);
			}
			sel = -1;
			targets = [];
		}
		over = !!st.over;
		render();
		if (over) {
			const r = REASON_FA[st.reason] || '';
			ui.status(st.winner === 'draw' ? `مساوی — ${r}` : st.winner === view.seat ? `بردید — ${r}` : `باختید — ${r}`, st.winner === view.seat ? 'win' : st.winner === 'draw' ? '' : 'loss');
			if (st.winner === view.seat) api.unlock('first_win');
		} else statusLine();
		ui.refreshPlayers && ui.refreshPlayers();
	};

	/* ---------- input: tap + drag ---------- */
	const squareAt = (x, y) => {
		const r = boardEl.getBoundingClientRect();
		if (x < r.left || y < r.top || x >= r.right || y >= r.bottom) return -1;
		const d = Math.floor(((y - r.top) / r.height) * 8) * 8 + Math.floor(((x - r.left) / r.width) * 8);
		return sqOf(d);
	};
	const own = (sq) => {
		const p = s.b[sq];
		return p !== '.' && C.colorOf(p) === s.turn;
	};
	L.on(boardEl, 'pointerdown', (e) => {
		if (!promoEl.hidden || !canMove()) return;
		const sq = squareAt(e.clientX, e.clientY);
		if (sq < 0) return;
		if (sel >= 0 && targets.some((m) => m.to === sq)) {
			tryMove(sel, sq);
			return;
		}
		if (own(sq)) {
			select(sq);
			drag = { sq, x: e.clientX, y: e.clientY, active: false, id: e.pointerId, ghost: null };
			boardEl.setPointerCapture(e.pointerId);
			e.preventDefault();
		} else {
			sel = -1;
			targets = [];
			render();
		}
	});
	L.on(boardEl, 'pointermove', (e) => {
		if (!drag || e.pointerId !== drag.id) return;
		if (!drag.active && Math.hypot(e.clientX - drag.x, e.clientY - drag.y) > 6) {
			drag.active = true;
			const el = sqEls[dOf(drag.sq)].querySelector('.chess__pc');
			el.classList.add('is-dragging');
			drag.ghost = document.createElement('div');
			drag.ghost.className = 'chess__ghost';
			drag.ghost.innerHTML = pieceSvg(s.b[drag.sq]);
			boardEl.appendChild(drag.ghost);
		}
		if (drag.active) {
			const r = boardEl.getBoundingClientRect();
			drag.ghost.style.transform = `translate(${e.clientX - r.left - r.width / 16}px, ${e.clientY - r.top - r.height / 16}px)`;
		}
	});
	const endDrag = (e) => {
		if (!drag || e.pointerId !== drag.id) return;
		const d = drag;
		drag = null;
		if (d.ghost) d.ghost.remove();
		sqEls.forEach((el) => el.querySelector('.chess__pc').classList.remove('is-dragging'));
		if (!d.active) return;
		const to = squareAt(e.clientX, e.clientY);
		if (to >= 0 && to !== d.sq && !tryMove(d.sq, to)) api.sfx('wrong');
	};
	L.on(boardEl, 'pointerup', endDrag);
	L.on(boardEl, 'pointercancel', endDrag);
	L.on(wrap, 'click', (e) => {
		if (e.target.closest('[data-flip]')) {
			flipped = !flipped;
			coords();
			render();
		}
	});

	coords();
	render();
	ui.players(defaultPlayers());
	requestAnimationFrame(fit);

	const inst = {
		noAutoPause: true,
		start(opts = {}) {
			aiToken++;
			ui.start(opts);
		},
		pause() {},
		resume() {},
		destroy() {
			aiToken++;
			ro.disconnect();
			L.off();
			ui.destroy();
			if (worker) {
				worker.terminate();
				worker = null;
			}
		},
		_core: C,
	};
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
export { chessCore };
