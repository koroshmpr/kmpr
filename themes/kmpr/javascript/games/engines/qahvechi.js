/**
 * «قهوه‌چی» — Korosh's coffee bar. Customers order drinks; build the cup from ingredients and serve before
 * their patience runs out. Order of ingredients doesn't matter, the set must match exactly.
 * 3 lost customers = closing time. Faster serve = bigger tip; recipes unlock as the day goes on.
 */
import { CATALOG } from '../catalog.js';
import { fa, esc, listeners, pick } from '../lib/util.js';

export const achievements = CATALOG.qahvechi.achievements;
const ING = {
	shot: { label: 'اسپرسو', icon: '☕' },
	milk: { label: 'شیر', icon: '🥛' },
	foam: { label: 'فوم', icon: '☁️' },
	choc: { label: 'شکلات', icon: '🍫' },
	ice: { label: 'یخ', icon: '🧊' },
	cin: { label: 'دارچین', icon: '🌰' },
};
const RECIPES = [
	{ name: 'اسپرسو', items: ['shot'], lvl: 0 },
	{ name: 'دوپیو', items: ['shot', 'shot'], lvl: 0 },
	{ name: 'لاته', items: ['shot', 'milk'], lvl: 0 },
	{ name: 'کاپوچینو', items: ['shot', 'milk', 'foam'], lvl: 1 },
	{ name: 'موکا', items: ['shot', 'choc', 'milk'], lvl: 1 },
	{ name: 'آیس لاته', items: ['shot', 'milk', 'ice'], lvl: 2 },
	{ name: 'کاپوچینو دارچینی', items: ['shot', 'milk', 'foam', 'cin'], lvl: 2 },
	{ name: 'آیس موکا', items: ['shot', 'choc', 'milk', 'ice'], lvl: 3 },
];
const FACES = ['🧑‍💻', '👩‍🎨', '👨‍🔧', '👩‍💼', '🧔', '👵', '👨‍🎓', '👩‍🔬', '🧑‍🚀'];
const same = (a, b) => a.length === b.length && [...a].sort().join() === [...b].sort().join();
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `<div class="qc">
		<div class="qc__top"><span class="qc__chip" data-qc-day>روز ۱</span><span class="qc__chip" data-qc-lost aria-label="مشتری از دست رفته">💔 ۰ از ۳</span><span class="qc__chip qc__chip--tip" data-qc-tip>انعام ۰</span></div>
		<div class="qc__seats" data-qc-seats aria-live="polite"></div>
		<div class="qc__bar">
			<div class="qc__cup" data-qc-cup aria-label="فنجان"><span class="qc__empty">فنجان خالی</span></div>
			<div class="qc__ings">${Object.entries(ING).map(([k, v]) => `<button type="button" class="qc__ing" data-ing="${k}" data-lock-lvl="${k === 'ice' ? 2 : k === 'cin' ? 2 : k === 'foam' || k === 'choc' ? 1 : 0}"><span>${v.icon}</span>${v.label}</button>`).join('')}
				<button type="button" class="qc__ing qc__ing--trash" data-trash><span>🗑️</span>خالی کن</button></div>
		</div>
		<p class="qc__hint">مواد را بزن، بعد روی مشتری‌ای که همین سفارش را دارد ضربه بزن.</p>
	</div>`;
	const $ = (q) => stage.querySelector(q);
	const seatsEl = $('[data-qc-seats]');
	const cupEl = $('[data-qc-cup]');
	let s = null, raf = 0, last = 0;

	const unlocked = () => RECIPES.filter((r) => r.lvl <= s.lvl);
	const drawCup = () => {
		cupEl.innerHTML = s.cup.length ? s.cup.map((k) => `<span class="qc__layer qc__layer--${k}" title="${ING[k].label}">${ING[k].icon}</span>`).join('') : '<span class="qc__empty">فنجان خالی</span>';
	};
	const drawSeats = () => {
		seatsEl.innerHTML = s.seats.map((c, i) => (c ? `<button type="button" class="qc__cust${c.happy ? ' is-happy' : ''}" data-seat="${i}" aria-label="سفارش ${esc(c.r.name)}">
			<span class="qc__face" aria-hidden="true">${c.face}</span>
			<span class="qc__bubble"><b>${esc(c.r.name)}</b><span class="qc__need">${c.r.items.map((k) => ING[k].icon).join(' ')}</span></span>
			<span class="qc__pat"><i style="transform:scaleX(${Math.max(0, c.pat / c.max)})"></i></span>
		</button>` : `<span class="qc__seat" aria-hidden="true">🪑</span>`)).join('');
		s.dirty = false;
	};
	const lockIngs = () => stage.querySelectorAll('[data-lock-lvl]').forEach((b) => (b.disabled = Number(b.dataset.lockLvl) > s.lvl));
	const hud = () => {
		$('[data-qc-day]').textContent = `روز ${fa(s.lvl + 1)}`;
		$('[data-qc-lost]').textContent = `💔 ${fa(s.lost)} از ۳`;
		$('[data-qc-tip]').textContent = `انعام ${fa(s.tips)}`;
	};
	const spawn = () => {
		const free = s.seats.map((c, i) => (c ? -1 : i)).filter((i) => i >= 0);
		if (!free.length) return;
		const i = pick(free), r = pick(unlocked());
		const max = Math.max(9, 20 - s.lvl * 2.5) + r.items.length * 2;
		s.seats[i] = { r, face: pick(FACES), pat: max, max };
		s.dirty = true;
	};
	const serve = (i) => {
		const c = s.seats[i];
		if (!c || c.happy || !s.cup.length) return;
		if (same(s.cup, c.r.items)) {
			const tip = Math.round(20 * (c.pat / c.max));
			s.score += 100 + c.r.items.length * 40 + tip * 5;
			s.tips += tip;
			s.served++;
			c.happy = true;
			c.leave = 0.6;
			s.cup = [];
			api.sfx('coin');
			api.setScore(s.score);
			if (s.served === 1) api.unlock('first');
			if (c.pat / c.max > 0.8) api.unlock('fast');
			if (c.r.items.length >= 4) api.unlock('barista');
			if (s.served % 8 === 0 && s.lvl < 3) { s.lvl++; lockIngs(); api.toast(`روز ${fa(s.lvl + 1)}: نوشیدنی‌های جدید! ☕`); api.sfx('level'); }
			if (s.served === 30) api.unlock('s30');
		} else {
			api.sfx('wrong');
			const el = seatsEl.querySelector(`[data-seat="${i}"]`);
			if (el) { el.classList.remove('is-no'); void el.offsetWidth; el.classList.add('is-no'); }
			c.pat = Math.max(0.5, c.pat - 3);
		}
		drawCup();
		hud();
		s.dirty = true;
	};
	const tick = (now) => {
		if (!s || s.over) return;
		const dt = Math.min(0.1, (now - last) / 1000);
		last = now;
		s.t += dt;
		s.next -= dt;
		if (s.next <= 0) { spawn(); s.next = Math.max(1.6, 4.2 - s.lvl * 0.7) + Math.random() * 1.5; }
		s.seats.forEach((c, i) => {
			if (!c) return;
			if (c.happy) { c.leave -= dt; if (c.leave <= 0) { s.seats[i] = null; s.dirty = true; } return; }
			c.pat -= dt;
			if (c.pat <= 0) { s.seats[i] = null; s.lost++; api.sfx('hit'); hud(); s.dirty = true; }
		});
		if (s.lost >= 3) {
			s.over = true;
			return api.over({ score: s.score, meta: { summary: `${fa(s.served)} نوشیدنی سرو شد · ${fa(s.tips)} انعام` } });
		}
		if (s.dirty) drawSeats();
		else seatsEl.querySelectorAll('[data-seat]').forEach((b) => { const c = s.seats[b.dataset.seat]; const bar = b.querySelector('.qc__pat i'); if (c && bar) bar.style.transform = `scaleX(${Math.max(0, c.pat / c.max)})`; });
		raf = requestAnimationFrame(tick);
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };

	L.on(stage, 'click', (e) => {
		if (!s || s.over) return;
		const ing = e.target.closest('[data-ing]');
		if (ing && !ing.disabled) { if (s.cup.length < 5) { s.cup.push(ing.dataset.ing); api.sfx('click'); drawCup(); } return; }
		if (e.target.closest('[data-trash]')) { s.cup = []; drawCup(); return; }
		const seat = e.target.closest('[data-seat]');
		if (seat) serve(Number(seat.dataset.seat));
	});
	L.on(window, 'keydown', (e) => {
		if (!api.running || !s) return;
		const keys = { 1: 'shot', 2: 'milk', 3: 'foam', 4: 'choc', 5: 'ice', 6: 'cin' };
		if (keys[e.key]) stage.querySelector(`[data-ing="${keys[e.key]}"]`)?.click();
		if (e.key === 'Backspace') { s.cup = []; drawCup(); }
		const seatKey = { q: 0, w: 1, e: 2, ض: 0, ص: 1, ث: 2 }[e.key];
		if (seatKey !== undefined) serve(seatKey);
	});

	const inst = {
		start() {
			s = { t: 0, next: 0.4, seats: [null, null, null], cup: [], lvl: 0, lost: 0, served: 0, tips: 0, score: 0, over: false, dirty: true };
			api.setScore(0);
			lockIngs();
			hud();
			drawCup();
			drawSeats();
			loop();
		},
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); stage.innerHTML = ''; },
	};
	s = { seats: [null, null, null], cup: [], lvl: 0, lost: 0, tips: 0 };
	lockIngs(); drawSeats();
	s = null;
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
