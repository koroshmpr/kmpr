/**
 * «سفره هفت‌سین» — items pop up on the sofreh; tap only the ones that start with «س».
 * Wrong tap = −1 life (3 lives). Collect all 7 classic sins for a «سفره کامل» bonus. Speeds up over time.
 */
import { CATALOG } from '../catalog.js';
import { fa, listeners, pick, rand } from '../lib/util.js';

export const achievements = CATALOG['haft-sin'].achievements;

const SIN = [['سیب', '🍎'], ['سیر', '🧄'], ['سکه', '🪙'], ['سبزه', '🌱'], ['سنجد', '🫒'], ['سماق', '🌶️'], ['سرکه', '🍶'], ['سنبل', '🪻'], ['ساعت', '⏰'], ['سمنو', '🥣']];
const CLASSIC = ['سیب', 'سیر', 'سکه', 'سبزه', 'سنجد', 'سماق', 'سرکه'];
const NOT = [['ماهی قرمز', '🐠'], ['آینه', '🪞'], ['شمع', '🕯️'], ['تخم‌مرغ', '🥚'], ['کتاب', '📖'], ['شیرینی', '🍪'], ['گلدان', '🏺'], ['انار', '🫐'], ['پرتقال', '🍊'], ['نان', '🍞']];
const CELLS = 9;

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `
	<div class="hs" data-hs>
		<div class="hs__top">
			<span class="hs__lives" data-hs-lives aria-label="جان"></span>
			<span class="hs__set" data-hs-set aria-label="سین‌های جمع‌شده"></span>
		</div>
		<div class="hs__board" data-hs-board role="grid" aria-label="سفره">
			${Array.from({ length: CELLS }, (_, i) => `<button type="button" class="hs__cell" data-i="${i}" aria-label="خالی" tabindex="-1"></button>`).join('')}
		</div>
		<p class="hs__hint">فقط چیزهایی که با «س» شروع می‌شوند را بردار!</p>
	</div>`;
	const cells = [...stage.querySelectorAll('.hs__cell')];
	const livesEl = stage.querySelector('[data-hs-lives]');
	const setEl = stage.querySelector('[data-hs-set]');
	let s = null;
	let raf = 0;
	let last = 0;

	const draw = () => {
		livesEl.textContent = '❤️'.repeat(Math.max(0, s.lives)) + '🤍'.repeat(Math.max(0, 3 - s.lives));
		setEl.innerHTML = CLASSIC.map((n) => `<i class="${s.got.has(n) ? 'is-on' : ''}">${n}</i>`).join('');
	};
	const clear = (c) => {
		c.item = null;
		c.className = 'hs__cell';
		c.innerHTML = '';
		c.setAttribute('aria-label', 'خالی');
		c.tabIndex = -1;
	};
	const spawn = () => {
		const free = cells.filter((c) => !c.item);
		if (!free.length) return;
		const c = pick(free);
		const good = Math.random() < 0.58;
		const [name, icon] = pick(good ? SIN : NOT);
		c.item = { name, good, ttl: Math.max(0.9, 2.2 - s.t / 40) };
		c.className = 'hs__cell is-up';
		c.innerHTML = `<span class="hs__ic" aria-hidden="true">${icon}</span><span class="hs__nm">${name}</span>`;
		c.setAttribute('aria-label', name);
		c.tabIndex = 0;
	};
	const hit = (c) => {
		if (!s || s.over || !c || !c.item) return;
		const it = c.item;
		if (it.good) {
			s.combo++;
			s.score += 50 + Math.min(s.combo, 8) * 10;
			if (CLASSIC.includes(it.name)) s.got.add(it.name);
			if (s.got.size === 7) {
				s.score += 700;
				s.sets++;
				s.got.clear();
				api.toast('سفره کامل شد! +۷۰۰');
				api.sfx('level');
				api.unlock('full');
				if (s.sets === 3) api.unlock('three');
			} else api.sfx('coin');
			if (s.combo === 15) api.unlock('combo15');
		} else {
			s.lives--;
			s.combo = 0;
			api.sfx('hit');
			c.classList.add('is-bad');
		}
		api.setScore(s.score);
		if (s.score >= 5000) api.unlock('s5k');
		clear(c);
		draw();
		if (s.lives <= 0) end();
	};
	const end = () => {
		s.over = true;
		cancelAnimationFrame(raf);
		api.over({ score: s.score, meta: { summary: `${fa(s.sets)} سفره کامل` } });
	};
	const tick = (now) => {
		if (!s || s.over) return;
		const dt = Math.min(0.1, (now - last) / 1000);
		last = now;
		s.t += dt;
		s.next -= dt;
		if (s.next <= 0) {
			spawn();
			s.next = Math.max(0.35, 1.1 - s.t / 60) * rand(0.7, 1.2);
		}
		cells.forEach((c) => {
			if (!c.item) return;
			c.item.ttl -= dt;
			if (c.item.ttl <= 0) {
				if (c.item.good) s.combo = 0;
				clear(c);
			}
		});
		if (s.t >= 30 && s.lives === 3) api.unlock('careful');
		raf = requestAnimationFrame(tick);
	};
	const loop = () => {
		cancelAnimationFrame(raf);
		last = performance.now();
		raf = requestAnimationFrame(tick);
	};

	L.on(stage, 'pointerdown', (e) => {
		const c = e.target.closest('.hs__cell');
		if (c) {
			e.preventDefault();
			hit(c);
		}
	});
	L.on(stage, 'keydown', (e) => {
		if ((e.key === 'Enter' || e.key === ' ') && e.target.closest('.hs__cell')) {
			e.preventDefault();
			hit(e.target.closest('.hs__cell'));
		}
	});
	L.on(window, 'keydown', (e) => {
		// Numpad layout: 7 8 9 / 4 5 6 / 1 2 3
		const map = { 7: 0, 8: 1, 9: 2, 4: 3, 5: 4, 6: 5, 1: 6, 2: 7, 3: 8 };
		if (api.running && map[e.key] !== undefined) hit(cells[map[e.key]]);
	});

	const inst = {
		start() {
			cells.forEach(clear);
			s = { score: 0, lives: 3, combo: 0, t: 0, next: 0.4, got: new Set(), sets: 0, over: false };
			api.setScore(0);
			draw();
			loop();
		},
		pause() {
			cancelAnimationFrame(raf);
		},
		resume() {
			if (s && !s.over) loop();
		},
		destroy() {
			cancelAnimationFrame(raf);
			L.off();
			stage.innerHTML = '';
		},
	};
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
