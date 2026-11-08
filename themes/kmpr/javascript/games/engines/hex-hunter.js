/**
 * «معمای رنگ» — colour-code puzzle for web people.
 * Swatch → pick its code, or code → pick its swatch. Formats progress hex → rgb() → hsl().
 * Distractors get closer every level; per-question timer; 3 lives; streak multiplier.
 */
import { CATALOG } from '../catalog.js';
import { clamp, rand, shuffle, listeners, fa, esc } from '../lib/util.js';

export const achievements = CATALOG['hex-hunter'].achievements;

const PER_LEVEL = 5;
const FEEDBACK = 0.75; // seconds between questions
const FA_DIGITS = { '۱': 1, '۲': 2, '۳': 3, '۴': 4, '1': 1, '2': 2, '3': 3, '4': 4 };

/* ---------- colour maths ---------- */
const hsl2rgb = (h, s, l) => {
	s /= 100;
	l /= 100;
	const k = (n) => (n + h / 30) % 12;
	const a = s * Math.min(l, 1 - l);
	const f = (n) => l - a * Math.max(-1, Math.min(k(n) - 3, 9 - k(n), 1));
	return [Math.round(f(0) * 255), Math.round(f(8) * 255), Math.round(f(4) * 255)];
};
const rgb2hsl = ([r, g, b]) => {
	r /= 255;
	g /= 255;
	b /= 255;
	const max = Math.max(r, g, b);
	const min = Math.min(r, g, b);
	let h = 0;
	let s = 0;
	const l = (max + min) / 2;
	if (max !== min) {
		const d = max - min;
		s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
		if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
		else if (max === g) h = (b - r) / d + 2;
		else h = (r - g) / d + 4;
		h *= 60;
	}
	return [Math.round(h), Math.round(s * 100), Math.round(l * 100)];
};
const hex = (c) => '#' + c.map((v) => v.toString(16).padStart(2, '0')).join('');
const css = (c) => `rgb(${c[0]}, ${c[1]}, ${c[2]})`;
const fmt = (c, f) => {
	if (f === 'hex') return hex(c);
	if (f === 'rgb') return `rgb(${c[0]}, ${c[1]}, ${c[2]})`;
	const [h, s, l] = rgb2hsl(c);
	return `hsl(${h}, ${s}%, ${l}%)`;
};
const dist = (a, b) => Math.hypot(a[0] - b[0], a[1] - b[1], a[2] - b[2]);

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	const root = document.createElement('div');
	root.className = 'hexh';
	root.innerHTML = `
		<div class="hexh__glow" aria-hidden="true"></div>
		<div class="hexh__top">
			<div class="hexh__timer" aria-hidden="true"><span data-bar></span></div>
			<div class="hexh__meta">
				<span data-format class="hexh__chip">HEX</span>
				<span data-streak class="hexh__streak" aria-live="polite"></span>
			</div>
		</div>
		<div class="hexh__body">
			<p class="hexh__prompt" data-prompt>کد این رنگ کدام است؟</p>
			<div class="hexh__question" data-question></div>
			<div class="hexh__options" data-options role="group" aria-label="گزینه‌ها"></div>
		</div>`;
	stage.appendChild(root);
	const $ = (s) => root.querySelector(s);
	const bar = $('[data-bar]');
	const qEl = $('[data-question]');
	const optEl = $('[data-options]');
	const promptEl = $('[data-prompt]');
	const fmtEl = $('[data-format]');
	const streakEl = $('[data-streak]');

	let s = null;
	let raf = 0;
	let last = 0;
	let running = false;

	const formatFor = (level) => (level <= 3 ? 'hex' : level <= 6 ? 'rgb' : level <= 9 ? 'hsl' : ['hex', 'rgb', 'hsl'][Math.floor(Math.random() * 3)]);
	const timeFor = (level) => Math.max(3.6, 10 - (level - 1) * 0.6);
	const spreadFor = (level) => Math.max(16, 130 - (level - 1) * 12);
	const mult = () => Math.min(3, 1 + Math.floor(s.streak / 3));

	const newRun = () => ({ score: 0, lives: 3, level: 1, correct: 0, streak: 0, q: null, t: 0, wait: 0, done: false, answered: 0 });

	const makeQuestion = () => {
		const level = s.level;
		const target = hsl2rgb(rand(0, 360), rand(35, 95), rand(25, 75));
		const spread = spreadFor(level);
		const opts = [target];
		let guard = 0;
		while (opts.length < 4 && guard++ < 400) {
			const c = target.map((v) => clamp(Math.round(v + rand(-spread, spread)), 0, 255));
			const tooClose = opts.some((o) => dist(o, c) < Math.max(10, spread * 0.35));
			if (!tooClose) opts.push(c);
		}
		while (opts.length < 4) opts.push(target.map((v) => clamp(v + 40 * opts.length, 0, 255)));
		const format = formatFor(level);
		const reverse = Math.random() < 0.45;
		s.q = { target, options: shuffle(opts), format, reverse, time: timeFor(level), left: timeFor(level), answered: false };
		render();
	};

	const render = () => {
		const q = s.q;
		fmtEl.textContent = q.format.toUpperCase();
		promptEl.textContent = q.reverse ? 'کدام رنگ با این کد یکی است؟' : 'کد این رنگ کدام است؟';
		if (q.reverse) {
			qEl.innerHTML = `<code dir="ltr" class="hexh__code">${esc(fmt(q.target, q.format))}</code>`;
		} else {
			qEl.innerHTML = `<div class="hexh__swatch" style="background:${css(q.target)};color:${css(q.target)}"><span class="sr-only">رنگ هدف</span></div>`;
		}
		optEl.className = 'hexh__options' + (q.reverse ? ' is-swatches' : '');
		optEl.innerHTML = q.options
			.map((c, i) => {
				const n = fa(i + 1);
				return q.reverse
					? `<button type="button" class="hexh__opt hexh__opt--swatch" data-i="${i}" style="--c:${css(c)}" aria-label="گزینه ${n}"><span class="hexh__key">${n}</span></button>`
					: `<button type="button" class="hexh__opt" data-i="${i}"><span class="hexh__key">${n}</span><code dir="ltr">${esc(fmt(c, q.format))}</code></button>`;
			})
			.join('');
		root.classList.remove('is-right', 'is-wrong');
		root.classList.add('is-enter');
		requestAnimationFrame(() => root.classList.remove('is-enter'));
		updateStreak();
	};

	const updateStreak = () => {
		const m = mult();
		streakEl.textContent = s.streak >= 2 ? `${fa(s.streak)} پشت‌سرهم${m > 1 ? ' — ضریب ' + fa(m) : ''}` : '';
	};

	const answer = (i) => {
		if (!s || s.done || !running || !s.q || s.q.answered) return;
		const q = s.q;
		q.answered = true;
		const chosen = q.options[i];
		const ok = i >= 0 && chosen === q.target;
		const btns = [...optEl.querySelectorAll('.hexh__opt')];
		btns.forEach((b, k) => {
			b.disabled = true;
			if (q.options[k] === q.target) b.classList.add('is-correct');
			else if (k === i) b.classList.add('is-chosen-wrong');
		});
		const elapsed = q.time - q.left;
		if (ok) {
			s.streak++;
			s.correct++;
			const pts = Math.round((50 + 50 * (q.left / q.time)) * mult() * (1 + (s.level - 1) * 0.05));
			s.score += pts;
			api.setScore(s.score);
			api.sfx('coin');
			root.classList.add('is-right');
			flyText('+' + fa(pts), btns[i]);
			api.unlock('first');
			if (s.streak >= 10) api.unlock('streak10');
			if (elapsed < 1) api.unlock('fast');
			if (s.score >= 5000) api.unlock('s5k');
			if (s.correct % PER_LEVEL === 0) {
				s.level++;
				api.hud({ level: s.level });
				api.sfx('level');
				if (formatFor(s.level) === 'hsl' || s.level >= 7) api.unlock('hsl');
				if (s.level >= 10) api.unlock('level10');
				api.toast(`<span class="game-toast__icon" aria-hidden="true">🎨</span><span><b>مرحله ${fa(s.level)}</b> ${s.level === 4 ? 'حالا با rgb()' : s.level === 7 ? 'حالا با hsl()' : 'رنگ‌ها نزدیک‌تر شدند'}</span>`);
			}
		} else {
			s.streak = 0;
			s.lives--;
			api.hud({ lives: s.lives });
			api.sfx('wrong');
			root.classList.add('is-wrong');
			if (navigator.vibrate) navigator.vibrate(60);
		}
		updateStreak();
		s.wait = FEEDBACK + (ok ? 0 : 0.5);
		if (s.lives <= 0) {
			s.done = true;
			s.wait = 1.1;
		}
	};

	const flyText = (txt, anchor) => {
		if (!anchor) return;
		const f = document.createElement('span');
		f.className = 'hexh__fly';
		f.textContent = txt;
		const r = anchor.getBoundingClientRect();
		const rr = root.getBoundingClientRect();
		f.style.left = r.left - rr.left + r.width / 2 + 'px';
		f.style.top = r.top - rr.top + 'px';
		root.appendChild(f);
		setTimeout(() => f.remove(), 900);
	};

	const finish = () => {
		running = false;
		cancelAnimationFrame(raf);
		api.over({
			score: s.score,
			meta: { level: s.level, combo: s.streak, summary: `به مرحله ${fa(s.level)} رسیدید و ${fa(s.correct)} رنگ را درست تشخیص دادید.` },
		});
	};

	const frame = (t) => {
		if (!running) return;
		const dt = Math.min(0.05, (t - last) / 1000);
		last = t;
		if (s.wait > 0) {
			s.wait -= dt;
			if (s.wait <= 0) {
				if (s.done) return finish();
				makeQuestion();
			}
		} else if (s.q && !s.q.answered) {
			s.q.left -= dt;
			if (s.q.left <= 0) {
				s.q.left = 0;
				answer(-1);
			}
		}
		if (s.q) {
			const f = s.q.left / s.q.time;
			bar.style.transform = `scaleX(${f})`;
			bar.dataset.low = f < 0.3 ? '1' : '0';
		}
		raf = requestAnimationFrame(frame);
	};

	L.on(optEl, 'click', (e) => {
		const b = e.target.closest('.hexh__opt');
		if (b) answer(Number(b.dataset.i));
	});
	L.on(window, 'keydown', (e) => {
		if (!api.running || !running) return;
		const n = FA_DIGITS[e.key];
		if (n) {
			e.preventDefault();
			answer(n - 1);
		}
	});

	// attract preview
	s = newRun();
	s.q = { target: [217, 119, 6], options: [[217, 119, 6], [56, 189, 248], [52, 211, 153], [167, 139, 250]], format: 'hex', reverse: false, time: 1, left: 1, answered: true };
	render();
	optEl.querySelectorAll('button').forEach((b) => (b.disabled = true));

	const go = () => {
		running = true;
		last = performance.now();
		raf = requestAnimationFrame(frame);
	};

	const inst = {
		/** test/debug hook (read-only use) */
		get _s() {
			return s;
		},
		start() {
			cancelAnimationFrame(raf);
			s = newRun();
			api.hud({ level: 1, levelLabel: 'مرحله', lives: 3 });
			api.setScore(0);
			makeQuestion();
			go();
		},
		pause() {
			running = false;
			cancelAnimationFrame(raf);
		},
		resume() {
			if (!s || (s.done && s.wait <= 0)) return;
			go();
		},
		destroy() {
			running = false;
			cancelAnimationFrame(raf);
			L.off();
			root.remove();
		},
	};
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
