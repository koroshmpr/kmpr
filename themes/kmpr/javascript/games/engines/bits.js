/**
 * «صفر و یک» — binary logic puzzle (a public-domain grid puzzle, dressed as compiling bits).
 * Fill every cell with 0 or 1: no three equal bits next to each other in a row/column,
 * every row and column has as many 0s as 1s, and no two rows (or columns) are identical.
 * Boards are generated on the fly with a unique solution (6×6 → 8×8); each board has its own clock.
 */
import { CATALOG } from '../catalog.js';
import { fa, listeners, shuffle } from '../lib/util.js';

export const achievements = CATALOG.bits.achievements;

const BOARDS = [
	{ n: 6, extra: 6, time: 150, base: 300 },
	{ n: 6, extra: 2, time: 150, base: 400 },
	{ n: 6, extra: 0, time: 150, base: 500 },
	{ n: 8, extra: 10, time: 300, base: 800 },
	{ n: 8, extra: 6, time: 300, base: 1000 },
	{ n: 8, extra: 3, time: 300, base: 1200 },
	{ n: 8, extra: 0, time: 300, base: 1500 },
	{ n: 8, extra: 0, time: 270, base: 1800 },
];
const HINT_COST = 20;

/* ---------- logic (grid = Int8Array, -1 empty) ---------- */
const lines = (n) => {
	const out = [];
	for (let r = 0; r < n; r++) out.push([...Array(n).keys()].map((c) => r * n + c));
	for (let c = 0; c < n; c++) out.push([...Array(n).keys()].map((r) => r * n + c));
	return out;
};

/** Apply forced deductions in place. Returns false on a contradiction. */
function propagate(g, n, L) {
	const half = n / 2;
	let changed = true;
	while (changed) {
		changed = false;
		for (const ln of L) {
			let z = 0;
			let o = 0;
			for (const i of ln) (g[i] === 0 ? z++ : g[i] === 1 ? o++ : 0);
			if (z > half || o > half) return false;
			for (let k = 0; k + 2 < n; k++) {
				const a = g[ln[k]];
				const b = g[ln[k + 1]];
				const c = g[ln[k + 2]];
				if (a >= 0 && a === b && b === c) return false;
				// xx_ / _xx / x_x
				if (a >= 0 && a === b && c < 0) ((g[ln[k + 2]] = 1 - a), (changed = true));
				else if (b >= 0 && b === c && a < 0) ((g[ln[k]] = 1 - b), (changed = true));
				else if (a >= 0 && a === c && b < 0) ((g[ln[k + 1]] = 1 - a), (changed = true));
			}
			if (z === half && o < half) for (const i of ln) if (g[i] < 0) ((g[i] = 1), (changed = true));
			if (o === half && z < half) for (const i of ln) if (g[i] < 0) ((g[i] = 0), (changed = true));
		}
	}
	return true;
}
/** Complete lines must be unique (rows among rows, columns among columns). */
function uniqueOk(g, n, L) {
	const seen = [new Set(), new Set()];
	for (let k = 0; k < L.length; k++) {
		const ln = L[k];
		if (ln.some((i) => g[i] < 0)) continue;
		const key = ln.map((i) => g[i]).join('');
		const set = seen[k < n ? 0 : 1];
		if (set.has(key)) return false;
		set.add(key);
	}
	return true;
}
/** Count solutions up to `limit`; `out` receives the first one. */
function solve(grid, n, L, limit = 2, out = null) {
	const g = Int8Array.from(grid);
	if (!propagate(g, n, L) || !uniqueOk(g, n, L)) return 0;
	const e = g.indexOf(-1);
	if (e < 0) {
		if (out && !out.sol) out.sol = g;
		return 1;
	}
	let count = 0;
	for (const v of Math.random() < 0.5 ? [0, 1] : [1, 0]) {
		g[e] = v;
		count += solve(g, n, L, limit - count, out);
		if (count >= limit) break;
	}
	return count;
}
function generate(n, extra) {
	const L = lines(n);
	const out = {};
	solve(new Int8Array(n * n).fill(-1), n, L, 1, out);
	const sol = out.sol;
	const puzzle = Int8Array.from(sol);
	const order = shuffle([...Array(n * n).keys()]);
	const kept = [];
	for (const i of order) {
		const v = puzzle[i];
		puzzle[i] = -1;
		if (solve(puzzle, n, L, 2) !== 1) {
			puzzle[i] = v;
			kept.push(i);
		}
	}
	// easier boards: give back a few cells
	shuffle(order.filter((i) => puzzle[i] < 0))
		.slice(0, extra)
		.forEach((i) => (puzzle[i] = sol[i]));
	return { sol, puzzle, L };
}
/** Indices that break a rule right now (for the red highlight). */
function errors(g, n, L) {
	const bad = new Set();
	const half = n / 2;
	for (const ln of L) {
		for (let k = 0; k + 2 < n; k++) {
			const a = g[ln[k]];
			if (a >= 0 && a === g[ln[k + 1]] && a === g[ln[k + 2]]) [ln[k], ln[k + 1], ln[k + 2]].forEach((i) => bad.add(i));
		}
		for (const v of [0, 1]) {
			const cells = ln.filter((i) => g[i] === v);
			if (cells.length > half) cells.forEach((i) => bad.add(i));
		}
	}
	const seen = new Map();
	L.forEach((ln, k) => {
		if (ln.some((i) => g[i] < 0)) return;
		const key = (k < n ? 'r' : 'c') + ln.map((i) => g[i]).join('');
		if (seen.has(key)) [...ln, ...seen.get(key)].forEach((i) => bad.add(i));
		else seen.set(key, ln);
	});
	return bad;
}

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.insertAdjacentHTML(
		'beforeend',
		`<div class="pz bt" data-bt>
		<div class="pz__top bt__top">
			<span class="pz__chip" data-bt-lvl></span>
			<span class="pz__chip bt__chip--ok" data-bt-left></span>
			<span class="pz__time" dir="ltr" data-bt-time aria-label="زمان باقی‌مانده"></span>
		</div>
		<div class="bt__board" data-bt-board role="grid" aria-label="جدول صفر و یک"></div>
		<div class="bt__bar">
			<button type="button" class="bt__btn" data-bt-hint><svg width="18" height="18" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.2 1 2V17h6v-.3c0-.8.4-1.5 1-2A7 7 0 0 0 12 2z"/></svg><span>راهنما (−${fa(HINT_COST)} ثانیه)</span></button>
			<p class="bt__rule">بدون سه رقم یکسان کنار هم · تعداد ۰ و ۱ هر ردیف برابر · ردیف تکراری ممنوع</p>
		</div>
	</div>`
	);
	const root = stage.querySelector('[data-bt]');
	const boardEl = root.querySelector('[data-bt-board]');
	const $ = (q) => root.querySelector(q);
	let s = null;
	let raf = 0;
	let last = 0;
	let cells = [];

	const buildBoard = () => {
		const n = s.n;
		boardEl.style.setProperty('--n', n);
		boardEl.innerHTML = Array.from({ length: n * n }, (_, i) => `<button type="button" class="bt__cell" data-i="${i}" role="gridcell"><span class="bt__v"></span></button>`).join('');
		cells = [...boardEl.children];
	};
	const render = () => {
		const bad = errors(s.g, s.n, s.L);
		cells.forEach((c, i) => {
			const v = s.g[i];
			const given = s.given[i];
			c.dataset.v = v < 0 ? '' : String(v);
			c.classList.toggle('is-given', given);
			c.classList.toggle('is-bad', bad.has(i));
			c.classList.toggle('is-cur', i === s.cur && !api.touch);
			c.querySelector('.bt__v').textContent = v < 0 ? '' : String(v);
			const r = Math.floor(i / s.n) + 1;
			const col = (i % s.n) + 1;
			c.setAttribute('aria-label', `ردیف ${fa(r)} ستون ${fa(col)}: ${v < 0 ? 'خالی' : fa(v)}${given ? ' (ثابت)' : ''}`);
		});
		const empty = s.g.reduce((k, v) => k + (v < 0 ? 1 : 0), 0);
		$('[data-bt-left]').textContent = empty ? `${fa(empty)} بیت خالی` : bad.size ? 'خطا دارد' : 'کامپایل شد ✓';
	};
	const startBoard = () => {
		const cfg = BOARDS[Math.min(BOARDS.length - 1, s.board)];
		const { sol, puzzle, L: lns } = generate(cfg.n, cfg.extra);
		Object.assign(s, { n: cfg.n, sol, L: lns, g: Int8Array.from(puzzle), given: [...puzzle].map((v) => v >= 0), left: cfg.time, time: cfg.time, cfg, hints: 0, solving: false, cur: 0, t0: performance.now() });
		$('[data-bt-lvl]').innerHTML = `بورد ${fa(s.board + 1)} از ${fa(BOARDS.length)} <bdi dir="ltr" class="bt__size">${s.n}×${s.n}</bdi>`;
		buildBoard();
		render();
	};
	const solved = () => {
		s.solving = true;
		const used = s.time - s.left;
		const pts = s.cfg.base + Math.max(0, Math.round(s.left)) * 4 - s.hints * 50;
		const gain = Math.max(100, pts);
		s.score += gain;
		api.setScore(s.score);
		api.sfx('level');
		api.toast(`Build پاس شد! +${fa(gain)}`);
		boardEl.classList.add('is-done');
		api.unlock('first');
		if (s.n === 6 && used <= 40) api.unlock('fast');
		if (s.n === 8 && !s.hints) api.unlock('nohint');
		if (s.n === 8) api.unlock('byte');
		if (s.score >= 8000) api.unlock('s8k');
		s.board++;
		api.hud({ level: s.board + 1 > BOARDS.length ? BOARDS.length : s.board + 1, levelLabel: 'بورد' });
		api.save && api.save();
		setTimeout(() => {
			if (!s || s.over) return;
			boardEl.classList.remove('is-done');
			if (s.board >= BOARDS.length) {
				s.over = true;
				api.unlock('all');
				api.over({ score: s.score, meta: { result: 'win', boards: s.board, summary: `هر ${fa(BOARDS.length)} بورد کامپایل شد؛ صفر خطا!` } });
				return;
			}
			startBoard();
		}, 900);
	};
	const check = () => {
		if (s.g.includes(-1)) return;
		const bad = errors(s.g, s.n, s.L);
		if (bad.size) {
			// warn only if the full-but-wrong grid stays that way (not while cycling the last cell 0 → 1)
			const ver = (s.ver = (s.ver || 0) + 1);
			setTimeout(() => {
				if (!s || s.over || s.solving || s.ver !== ver || s.g.includes(-1)) return;
				api.sfx('wrong');
				api.toast('جدول پر شد ولی خطا دارد؛ خانه‌های قرمز را درست کن');
			}, 900);
			return;
		}
		solved();
	};
	const set = (i, v) => {
		if (!s || s.over || s.solving || s.given[i]) return;
		s.g[i] = v;
		s.cur = i;
		s.ver = (s.ver || 0) + 1;
		api.sfx(v < 0 ? 'click' : 'flip');
		render();
		check();
	};
	const cycle = (i) => set(i, s.g[i] === -1 ? 0 : s.g[i] === 0 ? 1 : -1);
	const hint = () => {
		if (!s || s.over || s.solving) return;
		// prefer a wrong cell, else an empty one that logic forces, else any empty one
		let i = s.g.findIndex((v, k) => v >= 0 && !s.given[k] && v !== s.sol[k]);
		if (i < 0) {
			const g = Int8Array.from(s.g);
			propagate(g, s.n, s.L);
			i = s.g.findIndex((v, k) => v < 0 && g[k] >= 0);
		}
		if (i < 0) i = s.g.indexOf(-1);
		if (i < 0) return;
		s.hints++;
		s.left = Math.max(1, s.left - HINT_COST);
		s.g[i] = s.sol[i];
		s.cur = i;
		api.sfx('coin');
		render();
		cells[i].classList.add('is-hint');
		setTimeout(() => cells[i] && cells[i].classList.remove('is-hint'), 900);
		check();
	};

	const tick = (now) => {
		if (!s || s.over) return;
		const dt = Math.min(0.1, (now - last) / 1000);
		last = now;
		if (!s.solving) s.left -= dt;
		const t = Math.max(0, Math.ceil(s.left));
		const el = $('[data-bt-time]');
		el.textContent = `${fa(Math.floor(t / 60))}:${String(t % 60).padStart(2, '0').replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d])}`;
		el.classList.toggle('is-low', t <= 15);
		if (s.left <= 0) {
			s.over = true;
			api.sfx('over');
			api.over({ score: s.score, meta: { result: s.board ? 'win' : 'loss', boards: s.board, summary: s.board ? `${fa(s.board)} بورد کامپایل شد؛ وقت بورد ${fa(s.board + 1)} تمام شد.` : 'وقت تمام شد؛ دوباره امتحان کن!' } });
			return;
		}
		raf = requestAnimationFrame(tick);
	};

	L.on(boardEl, 'click', (e) => {
		const c = e.target.closest('[data-i]');
		if (c && api.running) cycle(Number(c.dataset.i));
	});
	L.on(boardEl, 'contextmenu', (e) => {
		const c = e.target.closest('[data-i]');
		if (!c) return;
		e.preventDefault();
		if (api.running) set(Number(c.dataset.i), -1);
	});
	L.on($('[data-bt-hint]'), 'click', () => api.running && hint());
	L.on(window, 'keydown', (e) => {
		if (!api.running || !s || s.over) return;
		if (e.target.closest && e.target.closest('input, textarea')) return;
		const n = s.n;
		const mv = { ArrowUp: -n, ArrowDown: n, ArrowLeft: -1, ArrowRight: 1 }[e.key];
		if (mv) {
			e.preventDefault();
			const r = Math.floor(s.cur / n);
			const c = s.cur % n;
			if ((mv === -1 && c === 0) || (mv === 1 && c === n - 1) || (mv === -n && r === 0) || (mv === n && r === n - 1)) return;
			s.cur += mv;
			render();
			return;
		}
		if (e.key === '0' || e.key === '۰') set(s.cur, 0);
		else if (e.key === '1' || e.key === '۱') set(s.cur, 1);
		else if (e.key === ' ' || e.key === 'Enter') {
			e.preventDefault();
			cycle(s.cur);
		} else if (e.key === 'Backspace' || e.key === 'Delete') set(s.cur, -1);
		else if (e.key === 'h' || e.key === 'H' || e.key === 'ا') hint();
	});

	const inst = {
		/** test/debug hook (read-only use) */
		get _s() {
			return s;
		},
		serialize() {
			if (!s || s.over || s.solving) return null;
			return { b: s.board, sc: s.score, n: s.n, sol: [...s.sol], g: [...s.g], gv: s.given.map(Number), left: s.left, h: s.hints };
		},
		restore(d) {
			if (!d || !Array.isArray(d.g) || !Array.isArray(d.sol) || d.g.length !== d.n * d.n) return;
			const cfg = BOARDS[Math.min(BOARDS.length - 1, d.b | 0)];
			s.board = d.b | 0;
			s.score = Number(d.sc) || 0;
			Object.assign(s, { n: d.n, L: lines(d.n), sol: Int8Array.from(d.sol), g: Int8Array.from(d.g), given: d.gv.map(Boolean), left: Number(d.left) || cfg.time, time: cfg.time, cfg, hints: d.h | 0, solving: false, cur: 0 });
			$('[data-bt-lvl]').innerHTML = `بورد ${fa(s.board + 1)} از ${fa(BOARDS.length)} <bdi dir="ltr" class="bt__size">${s.n}×${s.n}</bdi>`;
			api.hud({ level: s.board + 1, levelLabel: 'بورد' });
			buildBoard();
			render();
		},
		start() {
			cancelAnimationFrame(raf);
			s = { board: 0, score: 0, over: false };
			api.setScore(0);
			api.hud({ level: 1, levelLabel: 'بورد' });
			startBoard();
			last = performance.now();
			raf = requestAnimationFrame(tick);
		},
		pause() {
			cancelAnimationFrame(raf);
			root.classList.add('is-paused');
		},
		resume() {
			root.classList.remove('is-paused');
			last = performance.now();
			cancelAnimationFrame(raf);
			raf = requestAnimationFrame(tick);
		},
		destroy() {
			cancelAnimationFrame(raf);
			L.off();
			root.remove();
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
