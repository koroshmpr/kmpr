/**
 * «پازل کشویی» — sliding tiles, 3×3 → 5×5. One 180s clock for the whole run; solve as many boards as you can.
 */
import { CATALOG } from '../catalog.js';
import { fa, listeners, pick } from '../lib/util.js';

export const achievements = CATALOG['slide-puzzle'].achievements;
const SIZES = [3, 3, 4, 4, 5];
const TIME = 180;
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `<div class="pz" data-pz>
		<div class="pz__top"><span class="pz__chip" data-pz-lvl>تخته ۱</span><span class="pz__chip" data-pz-moves>۰ حرکت</span><span class="pz__chip pz__goalwrap" aria-label="هدف: اعداد به ترتیب از چپ به راست، ردیف به ردیف">هدف <span class="pz__goal" data-pz-goal aria-hidden="true"></span></span><span class="pz__time" data-pz-time>${fa(TIME)}</span></div>
		<div class="pz__board" data-pz-board role="grid" aria-label="پازل"></div>
		<p class="pz__hint">مثل تصویر «هدف» مرتب کن: از بالا چپ با عدد یک شروع کن و به راست برو؛ خانه خالی در گوشه پایین راست. روی خانه کنار جای خالی بزن.</p></div>`;
	const board = stage.querySelector('[data-pz-board]');
	const $ = (q) => stage.querySelector(q);
	let s = null;
	let raf = 0;
	let last = 0;

	const draw = () => {
		const n = s.n;
		board.style.setProperty('--n', n);
		const goal = stage.querySelector('[data-pz-goal]');
		goal.style.setProperty('--n', n);
		goal.innerHTML = [...Array(n * n).keys()].map((i) => (i < n * n - 1 ? `<i>${fa(i + 1)}</i>` : '<i class="is-gap"></i>')).join('');
		board.innerHTML = s.t
			.map((v, i) => (v ? `<button type="button" class="pz__tile${v === i + 1 ? ' is-ok' : ''}" data-i="${i}" aria-label="${fa(v)}">${fa(v)}</button>` : `<span class="pz__gap" aria-hidden="true"></span>`))
			.join('');
		$('[data-pz-moves]').textContent = `${fa(s.moves)} حرکت`;
	};
	const neighbors = (b, n) => {
		const r = Math.floor(b / n), c = b % n, out = [];
		if (r > 0) out.push(b - n);
		if (r < n - 1) out.push(b + n);
		if (c > 0) out.push(b - 1);
		if (c < n - 1) out.push(b + 1);
		return out;
	};
	const newBoard = () => {
		const n = SIZES[Math.min(s.level - 1, SIZES.length - 1)];
		s.n = n;
		s.t = [...Array(n * n - 1).keys()].map((x) => x + 1).concat(0);
		let b = n * n - 1, prev = -1;
		for (let k = 0; k < n * n * 25; k++) {
			const nb = pick(neighbors(b, n).filter((x) => x !== prev));
			[s.t[b], s.t[nb]] = [s.t[nb], s.t[b]];
			prev = b;
			b = nb;
		}
		if (s.t.every((v, i) => v === (i + 1) % (n * n))) return newBoard();
		s.moves = 0;
		s.boardT = 0;
		$('[data-pz-lvl]').textContent = `تخته ${fa(s.level)} · ${fa(n)}×${fa(n)}`;
		draw();
	};
	const solved = () => s.t.every((v, i) => v === (i + 1) % (s.n * s.n));
	const move = (i) => {
		if (!s || s.over) return;
		const n = s.n, b = s.t.indexOf(0);
		const sameRow = Math.floor(i / n) === Math.floor(b / n), sameCol = i % n === b % n;
		if (i === b || (!sameRow && !sameCol)) return;
		const step = sameRow ? (i > b ? 1 : -1) : i > b ? n : -n;
		for (let p = b; p !== i; p += step) {
			[s.t[p], s.t[p + step]] = [s.t[p + step], s.t[p]];
			s.moves++;
		}
		api.sfx('flip');
		draw();
		if (solved()) {
			const pts = Math.max(150, n * n * 140 - s.moves * 4) + Math.max(0, Math.round(60 - s.boardT)) * 10;
			s.score += pts;
			s.left = Math.min(TIME, s.left + 15);
			api.setScore(s.score);
			api.sfx('level');
			api.toast(`حل شد! +${fa(pts)}`);
			api.unlock('first');
			if (n === 4) api.unlock('four');
			if (n === 5) api.unlock('five');
			if (s.boardT < 20) api.unlock('fast');
			s.level++;
			setTimeout(() => s && !s.over && newBoard(), 500);
		}
	};
	const tick = (now) => {
		if (!s || s.over) return;
		const dt = Math.min(0.1, (now - last) / 1000);
		last = now;
		s.left -= dt;
		s.boardT += dt;
		$('[data-pz-time]').textContent = fa(Math.max(0, Math.ceil(s.left)));
		if (s.left <= 0) {
			s.over = true;
			return api.over({ score: s.score, meta: { summary: `${fa(s.level - 1)} تخته حل شد` } });
		}
		raf = requestAnimationFrame(tick);
	};
	const loop = () => {
		cancelAnimationFrame(raf);
		last = performance.now();
		raf = requestAnimationFrame(tick);
	};
	L.on(board, 'click', (e) => {
		const t = e.target.closest('.pz__tile');
		if (t) move(+t.dataset.i);
	});
	L.on(window, 'keydown', (e) => {
		if (!api.running || !s) return;
		const n = s.n, b = s.t.indexOf(0), r = Math.floor(b / n), c = b % n;
		// LTR board: an arrow moves the neighbouring tile in that direction into the gap.
		const map = { ArrowUp: r < n - 1 ? b + n : -1, ArrowDown: r > 0 ? b - n : -1, ArrowLeft: c < n - 1 ? b + 1 : -1, ArrowRight: c > 0 ? b - 1 : -1 };
		if (map[e.key] !== undefined) {
			e.preventDefault();
			if (map[e.key] >= 0) move(map[e.key]);
		}
	});
	const inst = {
		start() {
			s = { score: 0, level: 1, left: TIME, over: false };
			api.setScore(0);
			newBoard();
			loop();
		},
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); stage.innerHTML = ''; },
	};
	s = { level: 1, n: 3 };
	newBoard();
	s = null;
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
