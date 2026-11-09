/**
 * «درست یا غلط» — mental-math sprint. An equation appears: is it right? Answer fast (← غلط · → درست, or tap).
 * Numbers and operators get harder; 60 s, +1 s per correct, −2 s per mistake, combo multiplier.
 */
import { CATALOG } from '../catalog.js';
import { fa, listeners, randInt, pick } from '../lib/util.js';

export const achievements = CATALOG['math-sprint'].achievements;
const START = 60;
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `<div class="ms">
		<div class="ms__top"><span class="ms__combo" data-ms-combo></span><span class="ms__lvl" data-ms-lvl>سطح ۱</span><span class="ms__time" data-ms-time>${fa(START)}</span></div>
		<div class="ms__bar"><i data-ms-bar></i></div>
		<div class="ms__card" data-ms-card><p class="ms__eq" data-ms-eq dir="ltr">۲ + ۲ = ۴</p></div>
		<div class="ms__btns"><button type="button" class="ms__btn ms__btn--no" data-a="0"><span>✗</span>غلط</button><button type="button" class="ms__btn ms__btn--yes" data-a="1"><span>✓</span>درست</button></div>
		<p class="ms__hint">کیبورد: ← غلط · → درست</p>
	</div>`;
	const $ = (q) => stage.querySelector(q);
	let s = null, raf = 0, last = 0;
	const make = () => {
		const lvl = s.lvl;
		const ops = lvl < 2 ? ['+', '-'] : lvl < 4 ? ['+', '-', '×'] : ['+', '-', '×', '÷'];
		const op = pick(ops), big = 5 + lvl * 6;
		let a = randInt(1, big), b = randInt(1, big), r;
		if (op === '×') { a = randInt(2, 4 + lvl * 2); b = randInt(2, 9 + lvl); r = a * b; }
		else if (op === '÷') { b = randInt(2, 9 + lvl); r = randInt(2, 9 + lvl); a = b * r; }
		else if (op === '-') { if (b > a) [a, b] = [b, a]; r = a - b; }
		else r = a + b;
		const truth = Math.random() < 0.5;
		const shown = truth ? r : r + pick([-10, -2, -1, 1, 2, 10].filter((d) => r + d >= 0));
		s.q = { truth, text: `${fa(a)} ${op} ${fa(b)} = ${fa(shown)}` };
		$('[data-ms-eq]').textContent = s.q.text;
		const c = $('[data-ms-card]');
		c.classList.remove('is-in'); void c.offsetWidth; c.classList.add('is-in');
	};
	const answer = (yes) => {
		if (!s || s.over || s.lock) return;
		const ok = yes === s.q.truth;
		const c = $('[data-ms-card]');
		c.classList.remove('is-ok', 'is-bad'); void c.offsetWidth; c.classList.add(ok ? 'is-ok' : 'is-bad');
		if (ok) {
			s.n++; s.combo++;
			s.left = Math.min(START, s.left + 1);
			s.score += 50 * (1 + Math.min(9, Math.floor(s.combo / 3))) + s.lvl * 10;
			api.sfx('coin');
			if (s.n % 8 === 0) { s.lvl++; $('[data-ms-lvl]').textContent = `سطح ${fa(s.lvl + 1)}`; }
			if (s.n === 1) api.unlock('first');
			if (s.combo === 15) api.unlock('combo15');
			if (s.lvl >= 4) api.unlock('divide');
		} else { s.combo = 0; s.left -= 2; s.wrong++; api.sfx('wrong'); }
		api.setScore(s.score);
		$('[data-ms-combo]').textContent = s.combo > 2 ? `×${fa(1 + Math.min(9, Math.floor(s.combo / 3)))}` : '';
		s.lock = true;
		setTimeout(() => { if (s && !s.over) { s.lock = false; make(); } }, ok ? 140 : 450);
	};
	const tick = (now) => {
		if (!s || s.over) return;
		s.left -= Math.min(0.1, (now - last) / 1000); last = now;
		$('[data-ms-time]').textContent = fa(Math.max(0, Math.ceil(s.left)));
		$('[data-ms-bar]').style.transform = `scaleX(${Math.max(0, s.left / START)})`;
		if (s.left <= 0) { s.over = true; if (s.n >= 40) api.unlock('n40'); return api.over({ score: s.score, meta: { summary: `${fa(s.n)} جواب درست · ${fa(s.wrong)} اشتباه` } }); }
		raf = requestAnimationFrame(tick);
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };
	L.on(stage, 'click', (e) => { const b = e.target.closest('[data-a]'); if (b) answer(b.dataset.a === '1'); });
	L.on(window, 'keydown', (e) => { if (!api.running) return; if (e.code === 'ArrowRight') answer(true); if (e.code === 'ArrowLeft') answer(false); });
	const inst = {
		start() { s = { score: 0, n: 0, wrong: 0, combo: 0, lvl: 0, left: START, over: false }; api.setScore(0); $('[data-ms-lvl]').textContent = 'سطح ۱'; make(); loop(); },
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); stage.innerHTML = ''; },
	};
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
