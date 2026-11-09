/**
 * «برج‌های هانوی» — move the whole tower to the last peg; a bigger disk never sits on a smaller one.
 * 3 → 7 disks, a clock per level; fewer moves = more points.
 */
import { CATALOG } from '../catalog.js';
import { fa, listeners } from '../lib/util.js';

export const achievements = CATALOG.hanoi.achievements;
const COLORS = ['#f59e0b', '#ef4444', '#a855f7', '#3b82f6', '#14b8a6', '#22c55e', '#ec4899'];
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `<div class="pz hn" data-hn>
		<div class="pz__top"><span class="pz__chip" data-hn-lvl></span><span class="pz__chip" data-hn-moves></span><span class="pz__time" data-hn-time></span></div>
		<div class="hn__pegs" data-hn-pegs></div>
		<p class="pz__hint">یک میله را بزن، بعد میله مقصد را (کلیدهای ۱، ۲، ۳)</p></div>`;
	const pegsEl = stage.querySelector('[data-hn-pegs]');
	const $ = (q) => stage.querySelector(q);
	let s = null;
	let raf = 0;
	let last = 0;

	const draw = () => {
		pegsEl.innerHTML = s.pegs
			.map((p, i) => `<button type="button" class="hn__peg${s.from === i ? ' is-from' : ''}${i === 2 ? ' is-goal' : ''}" data-p="${i}" aria-label="میله ${fa(i + 1)}، ${fa(p.length)} دیسک"><i class="hn__rod"></i>${p
				.map((d, k) => `<span class="hn__disk${s.from === i && k === p.length - 1 ? ' is-lift' : ''}" style="--w:${30 + (d / s.n) * 65}%;--c:${COLORS[(d - 1) % COLORS.length]}"></span>`)
				.join('')}</button>`)
			.join('');
		$('[data-hn-moves]').textContent = `${fa(s.moves)} / ${fa(2 ** s.n - 1)} حرکت`;
	};
	const level = () => {
		s.n = Math.min(7, 2 + s.level);
		s.pegs = [[...Array(s.n).keys()].map((k) => s.n - k), [], []];
		s.moves = 0;
		s.from = -1;
		s.left = (2 ** s.n - 1) * 2.5 + 12;
		$('[data-hn-lvl]').textContent = `مرحله ${fa(s.level)} · ${fa(s.n)} دیسک`;
		draw();
	};
	const tap = (i) => {
		if (!s || s.over) return;
		if (s.from < 0) {
			if (s.pegs[i].length) { s.from = i; api.sfx('click'); draw(); }
			return;
		}
		const a = s.pegs[s.from], b = s.pegs[i];
		if (i !== s.from) {
			if (b.length && b[b.length - 1] < a[a.length - 1]) { api.sfx('wrong'); s.from = -1; draw(); return; }
			b.push(a.pop());
			s.moves++;
			api.sfx('flip');
		}
		s.from = -1;
		draw();
		if (s.pegs[2].length === s.n) {
			const min = 2 ** s.n - 1;
			const pts = Math.max(100, min * 40 - (s.moves - min) * 15) + Math.round(s.left) * 5;
			s.score += pts;
			api.setScore(s.score);
			api.sfx('level');
			api.toast(`برج منتقل شد! +${fa(pts)}`);
			api.unlock('first');
			if (s.moves === min) api.unlock('perfect');
			if (s.n === 5) api.unlock('five');
			if (s.n === 7) api.unlock('seven');
			s.level++;
			if (s.n === 7) { s.over = true; return setTimeout(() => api.over({ score: s.score, meta: { result: 'win', summary: 'همه مراحل را تمام کردی!' } }), 500); }
			setTimeout(() => s && !s.over && level(), 500);
		}
	};
	const tick = (now) => {
		if (!s || s.over) return;
		s.left -= Math.min(0.1, (now - last) / 1000);
		last = now;
		$('[data-hn-time]').textContent = fa(Math.max(0, Math.ceil(s.left)));
		if (s.left <= 0) { s.over = true; return api.over({ score: s.score, meta: { summary: `${fa(s.level - 1)} مرحله تمام شد` } }); }
		raf = requestAnimationFrame(tick);
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };
	L.on(pegsEl, 'click', (e) => { const p = e.target.closest('.hn__peg'); if (p) tap(+p.dataset.p); });
	L.on(window, 'keydown', (e) => {
		if (!api.running) return;
		const i = '123۱۲۳'.indexOf(e.key) % 3;
		if (i >= 0) tap(i);
	});
	const inst = {
		start() { s = { score: 0, level: 1, over: false }; api.setScore(0); level(); loop(); },
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); stage.innerHTML = ''; },
	};
	s = { level: 1 };
	level();
	s = null;
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
