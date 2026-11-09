/**
 * «اسم‌فامیل سریع» — the classic Iranian word game, made fast for one player:
 * a letter + a category appear; pick the ONE option that is in that category AND starts with that letter.
 * 75 s clock (+2 s per correct, −3 s per mistake). Embedded word lists (no network).
 */
import { CATALOG } from '../catalog.js';
import { fa, esc, listeners, shuffle, pick } from '../lib/util.js';

export const achievements = CATALOG['esm-famil'].achievements;
const CATS = {
	'اسم': 'علی آرش بهرام پریسا تینا ثمین جواد حسن خسرو داریوش رضا زهرا ژاله سارا شیرین صبا ضیا طاهره عباس غزاله فرهاد قاسم کوروش گلناز لیلا مریم نرگس وحید هادی یاسمن بابک پرویز مهسا نیما کاوه سیاوش فریبا امید الهام بهار سپیده تارا رویا',
	'شهر': 'آبادان اصفهان بابل بوشهر پاوه تبریز تهران جیرفت چالوس خرم‌آباد دزفول رشت زاهدان زنجان ساری سمنان شیراز طبس قزوین قم کاشان کرج کرمان گرگان لاهیجان مشهد مراغه نیشابور همدان یزد یاسوج ارومیه اهواز بجنورد بیرجند سنندج شهرکرد ایلام اراک کیش',
	'حیوان': 'آهو اسب الاغ ببر بز پلنگ پروانه تمساح جغد جوجه خرس خرگوش دلفین روباه زرافه زنبور سگ سنجاب شتر شیر طوطی عقاب غاز فیل قورباغه کبوتر کرگدن گاو گربه گرگ لاک‌پشت مار میمون مرغ نهنگ هدهد یوزپلنگ پنگوئن کفتار موش',
	'خوراکی': 'آلبالو انار انگور انجیر بادام به پرتقال پسته توت تمشک خرما خربزه زردآلو زیتون سیب شلیل طالبی گیلاس گلابی گردو لیمو موز نارنگی هلو هندوانه کیوی کشمش قیسی کدو فندق قارچ نان ماست کباب دوغ سمنو حلوا شیرینی برنج عدس',
	'اشیا': 'آینه انگشتر بالش بشقاب پنکه پیراهن تخت تلفن جارو چتر چراغ چاقو خودکار دفتر دستکش رادیو زنگ سماور ساعت شانه صندلی طناب عینک فرش فنجان قاشق قیچی کتاب کلید کیف گلدان لیوان لباس مداد میز نردبان ویولن هدفون یخچال',
	'کشور': 'آلمان ایران ایتالیا اسپانیا برزیل بلژیک پرتغال پاکستان ترکیه تایلند چین ژاپن سوئد سوئیس سوریه عراق عمان فرانسه فنلاند قطر کانادا کره کویت گرجستان لبنان مصر مکزیک نروژ هند هلند یونان یمن روسیه آرژانتین استرالیا اتریش ارمنستان دانمارک شیلی کوبا',
};
const LIST = Object.fromEntries(Object.entries(CATS).map(([k, v]) => [k, v.split(' ')]));
const norm = (ch) => (ch === 'آ' ? 'ا' : ch);
const first = (w) => norm(w[0]);
const START = 75;
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	stage.innerHTML = `<div class="ef">
		<div class="ef__top"><span class="ef__combo" data-ef-combo></span><span class="ef__time" data-ef-time>${fa(START)}</span></div>
		<div class="ef__bar"><i data-ef-bar></i></div>
		<div class="ef__card">
			<span class="ef__letter" data-ef-letter aria-live="polite">؟</span>
			<p class="ef__q" data-ef-q>آماده‌ای؟ حرف و دسته می‌آید؛ کلمه درست را بزن!</p>
		</div>
		<div class="ef__opts" data-ef-opts role="group" aria-label="گزینه‌ها"></div>
		<div class="ef__sheet" data-ef-sheet aria-label="برگه اسم‌فامیل"></div>
	</div>`;
	const $ = (q) => stage.querySelector(q);
	const el = { combo: $('[data-ef-combo]'), time: $('[data-ef-time]'), bar: $('[data-ef-bar]'), letter: $('[data-ef-letter]'), q: $('[data-ef-q]'), opts: $('[data-ef-opts]'), sheet: $('[data-ef-sheet]') };
	let s = null, raf = 0, last = 0;

	const letters = (() => {
		const count = {};
		Object.values(LIST).forEach((ws) => new Set(ws.map(first)).forEach((ch) => (count[ch] = (count[ch] || 0) + 1)));
		return Object.keys(count).filter((ch) => count[ch] >= 4);
	})();
	const newRound = () => {
		s.letter = pick(letters.filter((l) => l !== s.letter));
		s.queue = shuffle(Object.keys(LIST).filter((c) => LIST[c].some((w) => first(w) === s.letter))).slice(0, 5);
		s.sheet = [];
		el.letter.textContent = s.letter === 'ا' ? 'الف/آ' : s.letter;
		el.letter.classList.remove('is-pop'); void el.letter.offsetWidth; el.letter.classList.add('is-pop');
		ask();
	};
	const ask = () => {
		if (!s.queue.length) {
			s.rounds++;
			s.score += 300;
			api.setScore(s.score);
			api.toast(`برگه حرف «${s.letter}» کامل شد! +۳۰۰`);
			api.sfx('level');
			if (s.rounds === 3) api.unlock('three');
			return setTimeout(() => s && !s.over && newRound(), 500);
		}
		const cat = s.queue[0];
		const right = pick(LIST[cat].filter((w) => first(w) === s.letter));
		const sameLetterOther = Object.entries(LIST).filter(([c]) => c !== cat).flatMap(([, ws]) => ws.filter((w) => first(w) === s.letter && !LIST[cat].includes(w)));
		const sameCatOther = LIST[cat].filter((w) => first(w) !== s.letter);
		const wrong = new Set();
		shuffle(sameLetterOther).slice(0, 2).forEach((w) => wrong.add(w));
		shuffle(sameCatOther).forEach((w) => wrong.size < 3 && wrong.add(w));
		s.answer = right;
		s.locked = false;
		el.q.innerHTML = `یک <b>${esc(cat)}</b> با حرف «${esc(s.letter === 'ا' ? 'الف' : s.letter)}»`;
		el.opts.innerHTML = shuffle([right, ...wrong]).map((w, i) => `<button type="button" class="ef__opt" data-w="${esc(w)}"><kbd>${fa(i + 1)}</kbd>${esc(w)}</button>`).join('');
		el.sheet.innerHTML = Object.keys(LIST).map((c) => { const done = s.sheet.find((x) => x.c === c); return `<span class="${done ? 'is-done' : c === cat ? 'is-now' : ''}"><small>${esc(c)}</small>${done ? esc(done.w) : '—'}</span>`; }).join('');
	};
	const choose = (btn) => {
		if (!s || s.over || s.locked || !btn) return;
		s.locked = true;
		const ok = btn.dataset.w === s.answer;
		el.opts.querySelectorAll('.ef__opt').forEach((b) => b.dataset.w === s.answer && b.classList.add('is-ok'));
		if (ok) {
			s.combo++;
			s.n++;
			s.left = Math.min(START, s.left + 2);
			s.score += 100 + Math.min(10, s.combo) * 15;
			api.setScore(s.score);
			api.sfx('coin');
			s.sheet.push({ c: s.queue[0], w: s.answer });
			if (s.n === 1) api.unlock('first');
			if (s.combo === 12) api.unlock('combo12');
			if (s.score >= 6000) api.unlock('s6k');
		} else {
			btn.classList.add('is-bad');
			s.combo = 0;
			s.wrong++;
			s.left -= 3;
			api.sfx('wrong');
		}
		s.queue.shift();
		el.combo.textContent = s.combo > 1 ? `×${fa(Math.min(10, s.combo))}` : '';
		setTimeout(() => s && !s.over && ask(), ok ? 350 : 900);
	};
	const tick = (now) => {
		if (!s || s.over) return;
		s.left -= Math.min(0.1, (now - last) / 1000);
		last = now;
		el.time.textContent = fa(Math.max(0, Math.ceil(s.left)));
		el.bar.style.transform = `scaleX(${Math.max(0, s.left / START)})`;
		if (s.left <= 0) { s.over = true; return api.over({ score: s.score, meta: { summary: `${fa(s.n)} کلمه درست · ${fa(s.rounds)} برگه کامل` } }); }
		raf = requestAnimationFrame(tick);
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };
	L.on(el.opts, 'click', (e) => choose(e.target.closest('.ef__opt')));
	L.on(window, 'keydown', (e) => {
		if (!api.running || !s) return;
		const i = '1234۱۲۳۴'.indexOf(e.key);
		if (i >= 0) choose(el.opts.children[i % 4]);
	});
	const inst = {
		start() { s = { score: 0, n: 0, wrong: 0, combo: 0, rounds: 0, left: START, over: false, letter: '' }; api.setScore(0); el.combo.textContent = ''; newRound(); loop(); },
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); stage.innerHTML = ''; },
	};
	current = inst;
	// test hook: data integrity
	stage.kmprDebug = { LIST, letters, first };
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
