/**
 * «سودوکو ۶×۶» — rows, columns and 2×3 boxes hold ۱–۶ once. Puzzles get emptier each round; 3 mistakes end the run.
 */
import { CATALOG } from '../catalog.js';
import { fa, listeners, shuffle } from '../lib/util.js';

export const achievements = CATALOG.sudoku.achievements;
let current = null;

const gen = (holes) => {
	const pat = (r, c) => (3 * (r % 2) + Math.floor(r / 2) + c) % 6;
	const digits = shuffle([1, 2, 3, 4, 5, 6]);
	const rows = shuffle([0, 1, 2]).flatMap((b) => shuffle([0, 1]).map((r) => b * 2 + r));
	const cols = shuffle([0, 1]).flatMap((s) => shuffle([0, 1, 2]).map((c) => s * 3 + c));
	const sol = [];
	for (let r = 0; r < 6; r++) for (let c = 0; c < 6; c++) sol.push(digits[pat(rows[r], cols[c])]);
	const given = sol.map(() => true);
	shuffle([...Array(36).keys()]).slice(0, holes).forEach((i) => (given[i] = false));
	return { sol, given };
};
const box = (i) => Math.floor(Math.floor(i / 6) / 2) * 2 + Math.floor((i % 6) / 3);
const peers = (i, j) => i !== j && (Math.floor(i / 6) === Math.floor(j / 6) || i % 6 === j % 6 || box(i) === box(j));

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `<div class="pz sd" data-sd>
		<div class="pz__top"><span class="pz__chip" data-sd-lvl>جدول ۱</span><span class="pz__chip" data-sd-miss>❤️❤️❤️</span><span class="pz__time" data-sd-time>۰</span></div>
		<div class="sd__grid" data-sd-grid role="grid" aria-label="جدول سودوکو"></div>
		<div class="sd__pad" data-sd-pad>${[1, 2, 3, 4, 5, 6].map((d) => `<button type="button" data-d="${d}">${fa(d)}</button>`).join('')}<button type="button" data-d="0" aria-label="پاک کردن">⌫</button></div></div>`;
	const grid = stage.querySelector('[data-sd-grid]');
	const $ = (q) => stage.querySelector(q);
	let s = null;
	let raf = 0;
	let last = 0;

	const draw = () => {
		grid.innerHTML = s.v
			.map((v, i) => {
				const cls = ['sd__c', s.given[i] ? 'is-given' : '', i === s.sel ? 'is-sel' : '', s.sel >= 0 && peers(i, s.sel) ? 'is-peer' : '', v && s.sel >= 0 && v === s.v[s.sel] ? 'is-same' : '', s.bad === i ? 'is-bad' : '', i % 3 === 2 && i % 6 !== 5 ? 'b-r' : '', Math.floor(i / 6) % 2 === 1 && i < 30 ? 'b-b' : ''].filter(Boolean).join(' ');
				return `<button type="button" class="${cls}" data-i="${i}" aria-label="ردیف ${fa(Math.floor(i / 6) + 1)} ستون ${fa((i % 6) + 1)} ${v ? fa(v) : 'خالی'}">${v ? fa(v) : ''}</button>`;
			})
			.join('');
		$('[data-sd-miss]').textContent = '❤️'.repeat(3 - s.miss) + '🤍'.repeat(s.miss);
	};
	const newPuzzle = () => {
		const holes = Math.min(24, 12 + s.round * 3);
		const g = gen(holes);
		s.sol = g.sol;
		s.given = g.given;
		s.v = g.sol.map((v, i) => (g.given[i] ? v : 0));
		s.sel = s.v.indexOf(0);
		s.pt = 0;
		$('[data-sd-lvl]').textContent = `جدول ${fa(s.round)}`;
		draw();
	};
	const put = (d) => {
		if (!s || s.over || s.sel < 0 || s.given[s.sel]) return;
		const i = s.sel;
		if (d === 0) { s.v[i] = 0; draw(); return; }
		const clash = s.v.some((v, j) => v === d && peers(i, j));
		if (clash) {
			s.miss++;
			s.bad = i;
			api.sfx('wrong');
			draw();
			s.bad = -1;
			if (s.miss >= 3) {
				s.over = true;
				return setTimeout(() => api.over({ score: s.score, meta: { summary: `${fa(s.round - 1)} جدول حل شد` } }), 400);
			}
			return;
		}
		s.v[i] = d;
		api.sfx('type');
		if (s.v.every(Boolean)) {
			const pts = 800 + s.round * 250 + Math.max(0, Math.round(240 - s.pt)) * 5;
			s.score += pts;
			api.setScore(s.score);
			api.sfx('level');
			api.toast(`جدول حل شد! +${fa(pts)}`);
			api.unlock('first');
			if (s.miss === 0) api.unlock('clean');
			if (s.pt < 90) api.unlock('fast');
			if (s.round === 5) api.unlock('five');
			s.round++;
			return setTimeout(() => s && !s.over && newPuzzle(), 600);
		}
		const nx = s.v.findIndex((v, j) => !v && j > i);
		s.sel = nx >= 0 ? nx : s.v.indexOf(0);
		draw();
	};
	const tick = (now) => {
		if (!s || s.over) return;
		s.pt += Math.min(0.1, (now - last) / 1000);
		last = now;
		$('[data-sd-time]').textContent = fa(Math.floor(s.pt));
		raf = requestAnimationFrame(tick);
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };
	L.on(grid, 'click', (e) => {
		const c = e.target.closest('.sd__c');
		if (!c || !s) return;
		s.sel = +c.dataset.i;
		draw();
	});
	L.on($('[data-sd-pad]'), 'click', (e) => {
		const b = e.target.closest('[data-d]');
		if (b) put(+b.dataset.d);
	});
	L.on(window, 'keydown', (e) => {
		if (!api.running || !s) return;
		const d = '۰۱۲۳۴۵۶'.indexOf(e.key) >= 0 ? '۰۱۲۳۴۵۶'.indexOf(e.key) : '0123456'.indexOf(e.key);
		if (d >= 1) return put(d);
		if (e.key === 'Backspace' || e.key === 'Delete') return put(0);
		const mv = { ArrowUp: -6, ArrowDown: 6, ArrowLeft: 1, ArrowRight: -1 }[e.key];
		if (mv) {
			e.preventDefault();
			s.sel = Math.max(0, Math.min(35, s.sel + mv));
			draw();
		}
	});
	const inst = {
		start() {
			s = { score: 0, round: 1, miss: 0, bad: -1, over: false };
			api.setScore(0);
			newPuzzle();
			loop();
		},
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); stage.innerHTML = ''; },
	};
	s = { round: 1, miss: 0, bad: -1 };
	newPuzzle();
	s = null;
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
