/**
 * «دیباگر» — rotate code-flow tiles so data runs from main() to return. Classic rotate-the-pipes logic,
 * code-themed: lit path = running code, the build passes when the flow reaches the end.
 * Boards grow 5×5 → 7×7; every board is generated from a guaranteed solvable path.
 */
import { CATALOG } from '../catalog.js';
import { fa, listeners, shuffle, pick } from '../lib/util.js';

export const achievements = CATALOG.debugger.achievements;
// directions: 0 up, 1 right, 2 down, 3 left (bitmask 1<<d)
const DX = [0, 1, 0, -1], DY = [-1, 0, 1, 0];
const rot = (m, k) => { let r = m; for (let i = 0; i < (k % 4); i++) r = ((r << 1) | (r >> 3)) & 15; return r; };
let current = null;

function makeBoard(n, r) {
	// random self-avoiding path from left-middle → right side (DFS with backtracking)
	const sy = Math.floor(r() * n), ey = Math.floor(r() * n);
	const seen = new Set(), path = [];
	const dfs = (x, y) => {
		path.push([x, y]); seen.add(`${x},${y}`);
		if (x === n - 1 && y === ey) return true;
		const dirs = shuffle([0, 1, 2, 3]).sort((a, b) => (b === 1) - (a === 1) + (r() - 0.5) * 1.2);
		for (const d of dirs) {
			const nx = x + DX[d], ny = y + DY[d];
			if (nx < 0 || ny < 0 || nx >= n || ny >= n || seen.has(`${nx},${ny}`)) continue;
			if (dfs(nx, ny)) return true;
		}
		path.pop();
		return false;
	};
	dfs(0, sy);
	const mask = Array.from({ length: n * n }, () => 0);
	path.forEach(([x, y], i) => {
		const idx = y * n + x;
		if (i === 0) mask[idx] |= 1 << 3; // entry from the left edge
		if (i === path.length - 1) mask[idx] |= 1 << 1; // exit to the right edge
		const nb = [path[i - 1], path[i + 1]].filter(Boolean);
		nb.forEach(([px, py]) => { const d = px > x ? 1 : px < x ? 3 : py > y ? 2 : 0; mask[idx] |= 1 << d; });
	});
	// decoys on empty cells: random straight / corner / T pieces
	for (let i = 0; i < mask.length; i++) if (!mask[i]) mask[i] = pick([5, 10, 3, 6, 12, 9, 7, 11, 13, 14]);
	const turns = mask.map(() => Math.floor(r() * 4));
	const tiles = mask.map((m, i) => rot(m, turns[i]));
	return { n, sy, ey, tiles, solution: mask, onPath: new Set(path.map(([x, y]) => y * n + x)) };
}

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `<div class="pz dbg">
		<div class="pz__top"><span class="pz__chip" data-dbg-lvl></span><span class="pz__chip" data-dbg-moves></span><span class="pz__time" data-dbg-time></span></div>
		<div class="dbg__wrap"><span class="dbg__io dbg__io--in" data-in>main()</span><div class="dbg__board" data-dbg-board role="grid" aria-label="تخته کد"></div><span class="dbg__io dbg__io--out" data-out>return ✓</span></div>
		<p class="pz__hint">روی هر خانه بزن تا بچرخد؛ مسیر روشن را از <b>main()</b> به <b>return</b> برسان.</p></div>`;
	const boardEl = stage.querySelector('[data-dbg-board]');
	const $ = (q) => stage.querySelector(q);
	let s = null, raf = 0, last = 0;
	let seed = 1;
	const r = () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);

	const flow = () => {
		const { n, tiles, sy } = s.b;
		const lit = new Set();
		const q = [];
		const start = sy * n;
		if (tiles[start] & 8) { lit.add(start); q.push(start); }
		while (q.length) {
			const i = q.shift(), x = i % n, y = Math.floor(i / n);
			for (let d = 0; d < 4; d++) {
				if (!(tiles[i] & (1 << d))) continue;
				const nx = x + DX[d], ny = y + DY[d];
				if (nx < 0 || ny < 0 || nx >= n || ny >= n) continue;
				const j = ny * n + nx;
				if (tiles[j] & (1 << ((d + 2) % 4)) && !lit.has(j)) { lit.add(j); q.push(j); }
			}
		}
		const end = s.b.ey * n + (n - 1);
		return { lit, done: lit.has(end) && (tiles[end] & 2) };
	};
	const svg = (m, lit) => {
		const c = lit ? '#22d3ee' : '#475569';
		const seg = [[50, 0, 50, 50], [50, 50, 100, 50], [50, 50, 50, 100], [0, 50, 50, 50]];
		return `<svg viewBox="0 0 100 100" width="100%" height="100%" aria-hidden="true">${seg.map((sg, d) => (m & (1 << d) ? `<line x1="${sg[0]}" y1="${sg[1]}" x2="${sg[2]}" y2="${sg[3]}" stroke="${c}" stroke-width="16" stroke-linecap="round"/>` : '')).join('')}<circle cx="50" cy="50" r="11" fill="${lit ? '#a5f3fc' : '#334155'}"/></svg>`;
	};
	const draw = () => {
		const { n, tiles } = s.b;
		const { lit, done } = flow();
		boardEl.style.setProperty('--n', n);
		boardEl.innerHTML = tiles.map((m, i) => `<button type="button" class="dbg__t${lit.has(i) ? ' is-lit' : ''}" data-i="${i}" aria-label="خانه ${fa(i + 1)}${lit.has(i) ? ' (متصل)' : ''}">${svg(m, lit.has(i))}</button>`).join('');
		$('[data-in]').style.setProperty('--row', s.b.sy);
		$('[data-out]').style.setProperty('--row', s.b.ey);
		$('[data-in]').style.top = `calc(${(s.b.sy + 0.5) / n} * 100%)`;
		$('[data-out]').style.top = `calc(${(s.b.ey + 0.5) / n} * 100%)`;
		$('[data-out]').classList.toggle('is-on', !!done);
		$('[data-dbg-moves]').textContent = `${fa(s.moves)} چرخش`;
		return done;
	};
	const newBoard = () => {
		const n = Math.min(7, 5 + Math.floor((s.level - 1) / 2));
		seed = (Date.now() + s.level * 977) >>> 0;
		let b;
		do { b = makeBoard(n, r); } while (b.tiles.every((m, i) => m === b.solution[i]));
		s.b = b;
		s.moves = 0;
		s.bt = 0;
		$('[data-dbg-lvl]').textContent = `باگ ${fa(s.level)} · ${fa(n)}×${fa(n)}`;
		if (draw()) newBoard();
	};
	const tap = (i) => {
		if (!s || s.over || s.solving) return;
		s.b.tiles[i] = rot(s.b.tiles[i], 1);
		s.moves++;
		api.sfx('flip');
		if (draw()) {
			s.solving = true;
			const pts = 600 + s.b.n * 150 + Math.max(0, Math.round(60 - s.bt)) * 12;
			s.score += pts;
			s.left = Math.min(240, s.left + 25);
			api.setScore(s.score);
			api.sfx('level');
			api.toast(`Build passed ✓ +${fa(pts)}`);
			api.unlock('first');
			if (s.bt < 15) api.unlock('fast');
			if (s.b.n === 7) api.unlock('big');
			if (s.level === 6) api.unlock('six');
			s.level++;
			setTimeout(() => { if (s && !s.over) { s.solving = false; newBoard(); } }, 700);
		}
	};
	const tick = (now) => {
		if (!s || s.over) return;
		const dt = Math.min(0.1, (now - last) / 1000);
		last = now;
		s.left -= dt;
		s.bt += dt;
		$('[data-dbg-time]').textContent = fa(Math.max(0, Math.ceil(s.left)));
		if (s.left <= 0) { s.over = true; return api.over({ score: s.score, meta: { summary: `${fa(s.level - 1)} باگ رفع شد` } }); }
		raf = requestAnimationFrame(tick);
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };
	L.on(boardEl, 'click', (e) => { const t = e.target.closest('.dbg__t'); if (t) tap(Number(t.dataset.i)); });

	const inst = {
		start() { s = { score: 0, level: 1, left: 120, over: false }; api.setScore(0); newBoard(); loop(); },
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); stage.innerHTML = ''; },
	};
	s = { level: 1, left: 120 };
	newBoard();
	s = null;
	current = inst;
	// test hook: solve the current board
	stage.kmprDebug = { solve: () => { if (!s) return; const k = [...s.b.onPath][0]; s.b.tiles = [...s.b.solution]; s.b.tiles[k] = rot(s.b.solution[k], 3); tap(k); } };
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
