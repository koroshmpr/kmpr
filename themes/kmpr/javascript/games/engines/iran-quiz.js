/**
 * «ایران‌گرد» — fast trivia about Iran: province → capital, city → souvenir, landmark → city.
 * 60s clock; correct = +2s and combo points, wrong = −3s. Run ends when the clock hits zero.
 */
import { CATALOG } from '../catalog.js';
import { fa, esc, listeners, shuffle, pick } from '../lib/util.js';

export const achievements = CATALOG['iran-quiz'].achievements;

const CAPITALS = [
	['آذربایجان شرقی', 'تبریز'], ['آذربایجان غربی', 'ارومیه'], ['اردبیل', 'اردبیل'], ['اصفهان', 'اصفهان'], ['البرز', 'کرج'],
	['ایلام', 'ایلام'], ['بوشهر', 'بوشهر'], ['تهران', 'تهران'], ['چهارمحال و بختیاری', 'شهرکرد'], ['خراسان جنوبی', 'بیرجند'],
	['خراسان رضوی', 'مشهد'], ['خراسان شمالی', 'بجنورد'], ['خوزستان', 'اهواز'], ['زنجان', 'زنجان'], ['سمنان', 'سمنان'],
	['سیستان و بلوچستان', 'زاهدان'], ['فارس', 'شیراز'], ['قزوین', 'قزوین'], ['قم', 'قم'], ['کردستان', 'سنندج'],
	['کرمان', 'کرمان'], ['کرمانشاه', 'کرمانشاه'], ['کهگیلویه و بویراحمد', 'یاسوج'], ['گلستان', 'گرگان'], ['گیلان', 'رشت'],
	['لرستان', 'خرم‌آباد'], ['مازندران', 'ساری'], ['مرکزی', 'اراک'], ['هرمزگان', 'بندرعباس'], ['همدان', 'همدان'], ['یزد', 'یزد'],
];
const SOUVENIRS = [
	['گز', 'اصفهان'], ['سوهان', 'قم'], ['کلوچه', 'رشت'], ['باقلوا', 'یزد'], ['قطاب', 'یزد'], ['نان برنجی', 'کرمانشاه'],
	['کلمپه', 'کرمان'], ['زعفران', 'مشهد'], ['قوتاب', 'یزد'], ['لوز', 'تبریز'], ['کاک', 'کرمانشاه'], ['حلوا ارده', 'اردکان'],
	['مسقطی', 'شیراز'], ['پشمک', 'یزد'], ['چاقو', 'زنجان'], ['ماهی دودی', 'بندر انزلی'], ['خرما', 'بم'], ['رنگینک', 'شیراز'],
];
const LANDMARKS = [
	['تخت جمشید', 'شیراز'], ['میدان نقش جهان', 'اصفهان'], ['برج آزادی', 'تهران'], ['ارگ بم', 'بم'], ['آرامگاه حافظ', 'شیراز'],
	['آرامگاه فردوسی', 'توس'], ['بیستون', 'کرمانشاه'], ['سی‌وسه‌پل', 'اصفهان'], ['ائل‌گلی', 'تبریز'], ['باغ ارم', 'شیراز'],
	['قلعه رودخان', 'فومن'], ['شوش دانیال', 'شوش'], ['زیگورات چغازنبیل', 'شوش'], ['گنبد قابوس', 'گنبد کاووس'], ['کاخ گلستان', 'تهران'],
	['دریاچه ارومیه', 'ارومیه'], ['ماسوله', 'ماسوله'], ['بازار وکیل', 'شیراز'], ['آتشکده یزد', 'یزد'], ['کندوان', 'اسکو'],
];
const TYPES = [
	{ data: CAPITALS, q: (a) => `مرکز استان «${a}» کجاست؟`, tag: 'مرکز استان', icon: '🏛️' },
	{ data: SOUVENIRS, q: (a) => `«${a}» سوغات کدام شهر است؟`, tag: 'سوغات', icon: '🍯' },
	{ data: LANDMARKS, q: (a) => `«${a}» در کدام شهر است؟`, tag: 'جاذبه', icon: '🗺️' },
];
const START = 60;

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `
	<div class="iq" data-iq>
		<div class="iq__top">
			<span class="iq__tag" data-iq-tag>ایران‌گرد</span>
			<span class="iq__combo" data-iq-combo></span>
			<span class="iq__time" data-iq-time aria-label="زمان باقی‌مانده">${fa(START)}</span>
		</div>
		<div class="iq__bar"><i data-iq-bar></i></div>
		<div class="iq__main">
			<div class="iq__card">
				<span class="iq__icon" data-iq-icon aria-hidden="true">🧭</span>
				<p class="iq__q" data-iq-q aria-live="polite">آماده‌ای دور ایران رو بگردی؟</p>
				<div class="iq__stats"><span>درست <b data-iq-ok>۰</b></span><span>اشتباه <b data-iq-bad>۰</b></span><span>بهترین زنجیره <b data-iq-best>۰</b></span></div>
				<div class="iq__hist" data-iq-hist aria-hidden="true"></div>
			</div>
			<div class="iq__opts" data-iq-opts role="group" aria-label="گزینه‌ها"></div>
		</div>
	</div>`;
	const $ = (q) => stage.querySelector(q);
	const el = { icon: $('[data-iq-icon]'), ok: $('[data-iq-ok]'), bad: $('[data-iq-bad]'), best: $('[data-iq-best]'), hist: $('[data-iq-hist]'), tag: $('[data-iq-tag]'), combo: $('[data-iq-combo]'), time: $('[data-iq-time]'), bar: $('[data-iq-bar]'), q: $('[data-iq-q]'), opts: $('[data-iq-opts]') };
	let s = null;
	let raf = 0;
	let last = 0;

	const ask = () => {
		const t = pick(TYPES.slice(0, s.n < 4 ? 1 : 3));
		const row = pick(t.data.filter((r) => r[0] !== s.lastQ));
		s.lastQ = row[0];
		const pool = [...new Set([...CAPITALS, ...SOUVENIRS, ...LANDMARKS].map((r) => r[1]))].filter((c) => c !== row[1]);
		const opts = shuffle([row[1], ...shuffle(pool).slice(0, 3)]);
		s.answer = row[1];
		s.locked = false;
		el.tag.textContent = t.tag;
		el.icon.textContent = t.icon;
		el.q.textContent = t.q(row[0]);
		el.opts.innerHTML = opts.map((o, i) => `<button type="button" class="iq__opt" data-o="${esc(o)}"><kbd>${fa(i + 1)}</kbd><span>${esc(o)}</span></button>`).join('');
	};

	const choose = (btn) => {
		if (!s || s.over || s.locked || !btn) return;
		s.locked = true;
		const ok = btn.dataset.o === s.answer;
		el.opts.querySelectorAll('.iq__opt').forEach((b) => b.dataset.o === s.answer && b.classList.add('is-ok'));
		if (ok) {
			s.n++;
			s.combo++;
			s.left = Math.min(START, s.left + 2);
			s.score += 100 + Math.min(s.combo, 10) * 20;
			api.setScore(s.score);
			api.sfx('coin');
			if (s.n === 1) api.unlock('first');
			if (s.combo === 10) api.unlock('combo10');
			if (s.n === 25) api.unlock('n25');
			if (s.score >= 5000) api.unlock('s5k');
		} else {
			btn.classList.add('is-bad');
			s.combo = 0;
			s.wrong++;
			s.left -= 3;
			api.sfx('hit');
		}
		s.best = Math.max(s.best, s.combo);
		s.hist = [...s.hist, ok].slice(-14);
		el.ok.textContent = fa(s.n);
		el.bad.textContent = fa(s.wrong);
		el.best.textContent = fa(s.best);
		el.hist.innerHTML = s.hist.map((x) => `<i class="${x ? 'is-ok' : 'is-bad'}"></i>`).join('');
		el.combo.textContent = s.combo > 1 ? `×${fa(Math.min(s.combo, 10))}` : '';
		setTimeout(() => s && !s.over && ask(), ok ? 380 : 900);
	};

	const tick = (now) => {
		if (!s || s.over) return;
		s.left -= Math.min(0.1, (now - last) / 1000);
		last = now;
		el.time.textContent = fa(Math.max(0, Math.ceil(s.left)));
		el.bar.style.transform = `scaleX(${Math.max(0, s.left / START)})`;
		if (s.left <= 0) {
			s.over = true;
			if (s.n >= 15 && !s.wrong) api.unlock('flawless');
			api.over({ score: s.score, meta: { summary: `${fa(s.n)} جواب درست، ${fa(s.wrong)} اشتباه` } });
			return;
		}
		raf = requestAnimationFrame(tick);
	};
	const loop = () => {
		cancelAnimationFrame(raf);
		last = performance.now();
		raf = requestAnimationFrame(tick);
	};

	L.on(el.opts, 'click', (e) => choose(e.target.closest('.iq__opt')));
	L.on(window, 'keydown', (e) => {
		if (!api.running) return;
		const i = '1234۱۲۳۴'.indexOf(e.key) % 4;
		if (i >= 0) choose(el.opts.children[i]);
	});

	const inst = {
		start() {
			s = { score: 0, n: 0, wrong: 0, combo: 0, best: 0, hist: [], left: START, over: false };
			api.setScore(0);
			el.ok.textContent = el.bad.textContent = el.best.textContent = fa(0);
			el.hist.innerHTML = '';
			el.combo.textContent = '';
			ask();
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
