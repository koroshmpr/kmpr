/**
 * Chapter «گیلدا» (main stages 6–8) — the owner's real story: during the war, jobless, he coded the Gilda bag shop
 * alone (internet cut, no AI), bought 20 bags in the wholesale bazaar below Baharestan, turned bed-photos into
 * catalogue shots with Gemini, made teasers and articles — then the payment gateway broke.
 *  showBazaar   stage 6  haggle and buy 20 bags (variety, budget, closing time)
 *  showStudio   stage 7  bed photo → Gemini: pick light + prompt for each brief, order the teaser, pick the SEO title
 *  showGateway  stage 8  (boss) rotate pipe tiles so payment flows cart → bank; three rounds of real gateway errors
 * Each returns close() (pause/resume) and calls done(ok, points, stats).
 */
import { drawPerson } from './korosh-story.js';
import { drawHero } from '../lib/hero.js';
import { shell, txt, rr, endScreen, fa, clamp, OL, font } from './korosh-minis.js';

const toman = (v) => `${fa(Math.round(v).toLocaleString('en-US'))} ت`;
function loop(update, draw) {
	let raf = 0, last = performance.now(), paused = false, closed = false;
	const tick = (now) => { if (closed) return; const dt = Math.min(0.05, (now - last) / 1000); last = now; if (!paused) update(dt); draw(dt); raf = requestAnimationFrame(tick); };
	raf = requestAnimationFrame(tick);
	return { stop() { closed = true; cancelAnimationFrame(raf); }, pause() { paused = true; }, resume() { paused = false; last = performance.now(); } };
}
function wire(el, lp, extra = () => {}) {
	const onResize = () => el.__fit && el.__fit();
	window.addEventListener('resize', onResize);
	const close = () => { lp.stop(); extra(); window.removeEventListener('resize', onResize); el.remove(); };
	close.pause = () => lp.pause(); close.resume = () => lp.resume();
	return close;
}
// a simple vector handbag (kind: tote | shoulder | backpack | clutch)
export function drawBag(c, x, y, k, kind, color) {
	c.save(); c.translate(x, y); c.scale(k, k); c.lineJoin = 'round'; c.strokeStyle = OL; c.lineWidth = 1.4;
	if (kind === 'backpack') { c.fillStyle = color; c.beginPath(); c.roundRect(-9, -14, 18, 22, 6); c.fill(); c.stroke(); c.beginPath(); c.roundRect(-6, -2, 12, 8, 3); c.fill(); c.stroke(); c.beginPath(); c.arc(0, -14, 4, Math.PI, 0); c.stroke(); }
	else if (kind === 'clutch') { c.fillStyle = color; c.beginPath(); c.roundRect(-12, -6, 24, 12, 3); c.fill(); c.stroke(); c.beginPath(); c.moveTo(-12, -6); c.lineTo(0, 1); c.lineTo(12, -6); c.stroke(); c.fillStyle = '#fde68a'; c.beginPath(); c.arc(0, 1, 1.6, 0, 7); c.fill(); }
	else {
		const sh = kind === 'shoulder';
		c.beginPath(); if (sh) { c.moveTo(-8, -8); c.quadraticCurveTo(0, -26, 8, -8); } else { c.moveTo(-5, -8); c.quadraticCurveTo(0, -17, 5, -8); } c.stroke();
		c.fillStyle = color; c.beginPath(); c.moveTo(-11, -8); c.lineTo(11, -8); c.lineTo(13, 8); c.lineTo(-13, 8); c.closePath(); c.fill(); c.stroke();
		c.fillStyle = 'rgba(255,255,255,.25)'; c.fillRect(-9, -6, 18, 2); c.fillStyle = '#fde68a'; c.fillRect(-2, -4, 4, 3);
	}
	c.restore();
}

/* ------------------------------------------------------------------ 6) the bazaar below Baharestan */
const BAG_TYPES = [['tote', 'دستی'], ['shoulder', 'دوشی'], ['backpack', 'کوله'], ['clutch', 'کیف پول']];
const SHOPS = [
	{ name: 'حاج‌آقا رضایی', mood: 'smile', items: [[0, '#1f2937', 420000], [1, '#be185d', 380000]] },
	{ name: 'پاساژ ستاره', mood: 'meh', items: [[2, '#334155', 450000], [0, '#92400e', 360000]] },
	{ name: 'عمده‌فروشی مهر', mood: 'smile', items: [[3, '#7c2d12', 190000], [1, '#0f766e', 410000]] },
	{ name: 'بنکداری پارسا', mood: 'angry', items: [[1, '#111827', 350000], [2, '#7c3aed', 480000]] },
	{ name: 'کیف آرمان', mood: 'smile', items: [[0, '#f1f5f9', 400000], [3, '#b91c1c', 210000]] },
];
export function showBazaar(host, cfg) {
	const el = document.createElement('div');
	el.className = 'kr-mini kr-bazaar';
	el.innerHTML = `<canvas aria-hidden="true"></canvas>
		<div class="kr-bz__hud"><span data-bags></span><span data-money></span><span data-clock></span></div>
		<div class="kr-bz__shop" data-shop></div>
		<div class="kr-mini__end" hidden></div>`;
	host.appendChild(el);
	const cv = el.querySelector('canvas'), c = cv.getContext('2d'), $ = (q) => el.querySelector(q);
	el.__fit = () => { const r = cv.getBoundingClientRect(), d = Math.min(2, window.devicePixelRatio || 1); cv.width = Math.round(r.width * d); cv.height = Math.round(r.height * d); };
	el.__fit();
	const BUDGET = cfg.budget || 6200000, NEED = 20, PER_TYPE = 8; // a shop needs variety: at most 8 of one type
	let st;
	const reset = () => { st = { shop: 0, bags: 0, spent: 0, types: new Set(), perType: {}, clock: 13 * 60, haggle: null, deal: {}, angry: {}, over: false, t: 0, walk: 0, bought: [] }; renderShop(); };
	const price = (si, ii) => Math.round(SHOPS[si].items[ii][2] * (1 - (st.deal[si] || 0)));
	const renderShop = () => {
		const sh = SHOPS[st.shop], d = st.deal[st.shop] || 0, mad = st.angry[st.shop];
		$('[data-shop]').innerHTML = `<p class="kr-bz__name">🏪 ${sh.name}${d > 0 ? ` <b>تخفیف ${fa(Math.round(d * 100))}٪</b>` : ''}${mad ? ' <b class="is-mad">قهر کرد 😠</b>' : ''}</p>
			<div class="kr-bz__items">${sh.items.map(([ti, col, p], ii) => `<div class="kr-bz__item"><canvas width="120" height="90" data-bag="${ii}"></canvas><p>${BAG_TYPES[ti][1]}<br><b>${toman(price(st.shop, ii))}</b></p>
				<div class="kr-bz__buy"><button type="button" data-buy="${ii}" data-q="1" ${mad ? 'disabled' : ''}>+۱</button><button type="button" data-buy="${ii}" data-q="5" ${mad ? 'disabled' : ''}>+۵</button></div></div>`).join('')}</div>
			<div class="kr-bz__acts"><button type="button" data-haggle ${d > 0 || mad ? 'disabled' : ''}>🤝 چونه بزن</button><button type="button" data-prev>◀ مغازه قبلی</button><button type="button" data-next>مغازه بعدی ▶</button></div>
			<div class="kr-bz__meter" data-meter hidden><i data-needle></i><span>وقتی عقربه در سبز است بزن!</span></div>`;
		el.querySelectorAll('[data-bag]').forEach((cvb) => { const x = cvb.getContext('2d'), [ti, col] = sh.items[Number(cvb.dataset.bag)]; x.fillStyle = '#fef3c7'; x.fillRect(0, 0, 120, 90); drawBag(x, 60, 55, 2.4, BAG_TYPES[ti][0], col); });
		hud();
	};
	const hud = () => { $('[data-bags]').textContent = `👜 ${fa(st.bags)} از ${fa(NEED)} · ${fa(st.types.size)} مدل`; $('[data-money]').textContent = `💰 ${toman(BUDGET - st.spent)}`; const m = Math.floor(st.clock); $('[data-clock]').textContent = `⏰ ${fa(Math.floor(m / 60))}:${fa(String(m % 60).padStart(2, '0'))}`; };
	el.addEventListener('click', (e) => {
		if (st.over) return;
		const b = e.target.closest('button'); if (!b || b.disabled) return;
		if (b.dataset.buy !== undefined) {
			const ii = Number(b.dataset.buy), q = Math.min(Number(b.dataset.q), NEED - st.bags), p = price(st.shop, ii) * q;
			if (q <= 0) return;
			const ti = SHOPS[st.shop].items[ii][0];
			if ((st.perType[ti] || 0) + q > PER_TYPE) { cfg.sound && cfg.sound('wrong'); $('[data-shop] .kr-bz__name').insertAdjacentHTML('beforeend', ' <b class="is-mad">از این مدل بس است؛ تنوع لازم است!</b>'); return; }
			if (st.spent + p > BUDGET) { cfg.sound && cfg.sound('wrong'); b.classList.add('is-no'); setTimeout(() => b.classList.remove('is-no'), 400); return; }
			st.perType[ti] = (st.perType[ti] || 0) + q;
			st.spent += p; st.bags += q; st.types.add(SHOPS[st.shop].items[ii][0]); st.bought.push(SHOPS[st.shop].items[ii]); cfg.sound && cfg.sound('coin'); hud();
			if (st.bags >= NEED) finish(true);
		} else if (b.hasAttribute('data-haggle')) { st.haggle = { p: 0, dir: 1, sp: 1.3 + st.shop * 0.25 }; $('[data-meter]').hidden = false; b.disabled = true; }
		else if (b.hasAttribute('data-next')) { st.shop = (st.shop + 1) % SHOPS.length; st.clock += 6; st.walk = 1; renderShop(); }
		else if (b.hasAttribute('data-prev')) { st.shop = (st.shop + SHOPS.length - 1) % SHOPS.length; st.clock += 6; st.walk = -1; renderShop(); }
	});
	const stopHaggle = () => {
		if (!st.haggle) return;
		const v = st.haggle.p; st.haggle = null;
		// sweet spot 0.62..0.8 → 18–25% off; too low (>0.9) offends; elsewhere a small discount
		if (v > 0.9) { st.angry[st.shop] = true; cfg.sound && cfg.sound('wrong'); }
		else if (v >= 0.62 && v <= 0.8) { st.deal[st.shop] = 0.18 + (0.8 - Math.abs(v - 0.71) * 3) * 0.08; cfg.sound && cfg.sound('level'); }
		else { st.deal[st.shop] = 0.05; cfg.sound && cfg.sound('click'); }
		renderShop();
	};
	el.addEventListener('pointerdown', (e) => { if (st.haggle && !e.target.closest('button')) { e.preventDefault(); stopHaggle(); } });
	const kd = (e) => { if (st.over) return; if (e.code === 'Space' && st.haggle) { e.preventDefault(); e.stopPropagation(); stopHaggle(); } else if (e.code === 'ArrowLeft') { e.stopPropagation(); el.querySelector('[data-next]').click(); } else if (e.code === 'ArrowRight') { e.stopPropagation(); el.querySelector('[data-prev]').click(); } };
	window.addEventListener('keydown', kd, true);
	el.addEventListener('click', (e) => { if (st.haggle && e.target.closest('[data-meter]')) stopHaggle(); });
	const update = (dt) => {
		if (st.over) return;
		st.t += dt; st.clock += dt * 2.2; if (st.walk) st.walk *= 0.85;
		if (st.haggle) { const h = st.haggle; h.p += h.dir * h.sp * dt; if (h.p > 1) { h.p = 1; h.dir = -1; } if (h.p < 0) { h.p = 0; h.dir = 1; } const n = el.querySelector('[data-needle]'); if (n) n.style.insetInlineStart = `${h.p * 100}%`; }
		if (st.clock >= 18 * 60) finish(false, 'بازار بسته شد… 🌆');
		if (Math.floor(st.t * 2) % 2 === 0) hud();
	};
	const draw = () => {
		const w = cv.width, h = cv.height, k = h / 100;
		c.setTransform(1, 0, 0, 1, 0, 0);
		const g = c.createLinearGradient(0, 0, 0, h); g.addColorStop(0, '#fcd34d'); g.addColorStop(1, '#fde68a'); c.fillStyle = g; c.fillRect(0, 0, w, h);
		const off = (st.shop * 160 + st.walk * 40) * k;
		for (let i = -1; i < 8; i++) { const x = i * 160 * k - (off % (160 * k)); c.fillStyle = ['#b45309', '#92400e', '#78350f'][(i + 9) % 3]; c.fillRect(x + 6 * k, 10 * k, 150 * k, 60 * k); c.fillStyle = '#fef3c7'; c.fillRect(x + 14 * k, 26 * k, 134 * k, 40 * k); for (let j = 0; j < 5; j++) drawBag(c, x + (30 + j * 25) * k, 52 * k, k * 0.9, BAG_TYPES[(i + j + 8) % 4][0], ['#1f2937', '#be185d', '#0f766e', '#7c2d12', '#334155'][(i + j + 9) % 5]); c.fillStyle = '#7f1d1d'; c.fillRect(x + 6 * k, 10 * k, 150 * k, 10 * k); }
		c.fillStyle = '#a8a29e'; c.fillRect(0, 70 * k, w, 30 * k);
		drawPerson(c, w * 0.62, 96 * k, k * 1.4, 'mh', { mood: st.angry[st.shop] ? 'angry' : SHOPS[st.shop].mood, name: SHOPS[st.shop].name, hair: '#57534e', style: 'bald', stache: true, top: '#78350f' }, st.t);
		c.save(); c.translate(w * 0.42, 96 * k); c.scale(k * 1.15, k * 1.15); drawHero(c, 0, -32, 'big', 'none', Math.abs(st.walk) > 0.1 ? 'run1' : 'stand', st.walk < 0 ? -1 : 1, 1, 1, (st.t * 2) % 1, 0, 0, 'bag'); c.restore();
	};
	const finish = (ok, why) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		st.typesN = st.types.size;
		const pts = ok ? Math.round((BUDGET - st.spent) / 1000) + st.types.size * 400 + 800 : 0;
		const stats = { spent: st.spent, budget: BUDGET, types: st.types.size };
		endScreen(el, ok, ok ? '۲۰ کیف خریدی! ✓ برگشت به خانه' : why || 'پول تمام شد…', ok ? [`خرج ${toman(st.spent)} از ${toman(BUDGET)} · ${fa(st.types.size)} مدل`, `امتیاز ${fa(pts)}`] : ['چونه بزن و مدل‌های ارزان‌تر را هم ببین.'], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, pts, stats); });
	};
	reset();
	const lp = loop(update, draw);
	const close = wire(el, lp, () => window.removeEventListener('keydown', kd, true));
	close.state = () => st; close.el = el;
	return close;
}

/* ------------------------------------------------------------------ 7) bed photos → Gemini, teaser, article */
const BRIEFS = [
	{ bag: ['tote', '#111827'], text: 'کیف دستی مشکی · حس لوکس برای صفحه اول', light: 1, prompt: 1 },
	{ bag: ['shoulder', '#be185d'], text: 'کیف دوشی صورتی · شاد و جوان برای اینستاگرام', light: 0, prompt: 2 },
	{ bag: ['backpack', '#334155'], text: 'کوله طوسی · ساده و کاربردی برای دانشجوها', light: 0, prompt: 0 },
];
const LIGHTS = ['☀️ نور پنجره', '💡 نور استودیو', '🔦 فلاش گوشی'];
const PROMPTS = ['استودیو سفید، سایه نرم', 'سنگ مرمر و نور طلایی', 'پس‌زمینه رنگی پاستلی و گل'];
const TEASER = ['کیف روی تخت (قبل)', 'جادوی جمینای ✨', 'کیف در دست مدل', 'لوگوی گیلدا: «جایی که سادگی، زیبا می‌شود»'];
const TITLES = [['خرید کیف زنانه شیک و ارزان | راهنمای انتخاب کیف دستی و دوشی', true], ['کیف!!! بهترین!!! بخرید!!!', false], ['درباره ما', false]];
export function showStudio(host, cfg) {
	const el = document.createElement('div');
	el.className = 'kr-mini kr-studio';
	el.innerHTML = `<div class="kr-st__photo"><canvas aria-hidden="true"></canvas><p class="kr-st__brief" data-brief></p></div><div class="kr-st__panel" data-panel></div><div class="kr-mini__end" hidden></div>`;
	host.appendChild(el);
	const cv = el.querySelector('canvas'), c = cv.getContext('2d'), $ = (q) => el.querySelector(q);
	el.__fit = () => { const r = cv.getBoundingClientRect(), d = Math.min(2, window.devicePixelRatio || 1); cv.width = Math.round(r.width * d); cv.height = Math.round(r.height * d); };
	el.__fit();
	let st;
	const reset = () => { st = { step: 0, bi: 0, light: null, prompt: null, score: 0, max: BRIEFS.length * 2 + 2, reveal: 0, t: 0, order: [], over: false }; render(); };
	const render = () => {
		const P = $('[data-panel]');
		if (st.step === 0) {
			const b = BRIEFS[st.bi];
			$('[data-brief]').textContent = `📋 بریف ${fa(st.bi + 1)} از ${fa(BRIEFS.length)}: ${b.text}`;
			P.innerHTML = `<p class="kr-st__h">۱) نور</p><div class="kr-st__chips">${LIGHTS.map((l, i) => `<button type="button" data-light="${i}" class="${st.light === i ? 'is-on' : ''}">${l}</button>`).join('')}</div>
				<p class="kr-st__h">۲) پرامپت جمینای برای پس‌زمینه</p><div class="kr-st__chips">${PROMPTS.map((l, i) => `<button type="button" data-prompt="${i}" class="${st.prompt === i ? 'is-on' : ''}">${l}</button>`).join('')}</div>
				<button type="button" class="kr-st__go" data-gen ${st.light === null || st.prompt === null ? 'disabled' : ''}>✨ ساخت عکس با جمینای</button>`;
		} else if (st.step === 1) {
			$('[data-brief]').textContent = '🎬 تیزر: صحنه‌ها را به ترتیب بزن';
			P.innerHTML = `<div class="kr-st__chips is-col">${TEASER.map((t, i) => [t, i]).sort(() => Math.random() - 0.5).map(([t, i]) => `<button type="button" data-scene="${i}">${t}<em></em></button>`).join('')}</div>`;
		} else {
			$('[data-brief]').textContent = '📝 مقاله اول سایت: کدام تیتر برای سئو بهتر است؟';
			P.innerHTML = `<div class="kr-st__chips is-col">${TITLES.map((t, i) => [t, i]).sort(() => Math.random() - 0.5).map(([t, i]) => `<button type="button" data-title="${i}">${t[0]}</button>`).join('')}</div>`;
		}
	};
	el.addEventListener('click', (e) => {
		if (st.over) return;
		const b = e.target.closest('button'); if (!b || b.disabled) return;
		if (b.dataset.light) { st.light = Number(b.dataset.light); render(); cfg.sound && cfg.sound('click'); }
		else if (b.dataset.prompt) { st.prompt = Number(b.dataset.prompt); render(); cfg.sound && cfg.sound('click'); }
		else if (b.hasAttribute('data-gen')) {
			const br = BRIEFS[st.bi]; const pts = (st.light === br.light ? 1 : 0) + (st.prompt === br.prompt ? 1 : 0);
			st.score += pts; st.reveal = 0.001; st.last = pts; cfg.sound && cfg.sound(pts === 2 ? 'level' : pts ? 'coin' : 'wrong');
			$('[data-panel]').innerHTML = `<p class="kr-st__res">${pts === 2 ? 'عالی! دقیقاً مطابق بریف ✓' : pts ? 'بد نیست؛ یک انتخاب با بریف نمی‌خواند' : 'با بریف جور نیست… 😕'}</p><button type="button" class="kr-st__go" data-nextbag>ادامه ◂</button>`;
		} else if (b.hasAttribute('data-nextbag')) { st.bi++; st.light = st.prompt = null; st.reveal = 0; if (st.bi >= BRIEFS.length) st.step = 1; render(); }
		else if (b.dataset.scene !== undefined) {
			st.order.push(Number(b.dataset.scene)); b.disabled = true; b.querySelector('em').textContent = fa(st.order.length); cfg.sound && cfg.sound('click');
			if (st.order.length === TEASER.length) { const okk = st.order.every((v, i) => v === i); if (okk) st.score++; cfg.sound && cfg.sound(okk ? 'level' : 'wrong'); setTimeout(() => { st.step = 2; render(); }, 700); }
		} else if (b.dataset.title !== undefined) { const okk = TITLES[Number(b.dataset.title)][1]; if (okk) st.score++; cfg.sound && cfg.sound(okk ? 'level' : 'wrong'); finish(); }
	});
	const update = (dt) => { st.t += dt; if (st.reveal > 0 && st.reveal < 1) st.reveal = Math.min(1, st.reveal + dt * 0.8); };
	const draw = () => {
		const w = cv.width, h = cv.height, k = h / 100;
		c.setTransform(1, 0, 0, 1, 0, 0);
		const br = BRIEFS[Math.min(st.bi, BRIEFS.length - 1)], cut = w * (st.step === 0 ? st.reveal : 1);
		// before: on the bed (crumpled sheet, phone-flash glare)
		c.fillStyle = '#e0e7ff'; c.fillRect(0, 0, w, h); c.strokeStyle = '#c7d2fe'; c.lineWidth = 3 * k; for (let i = 0; i < 6; i++) { c.beginPath(); c.moveTo(0, (12 + i * 16) * k); c.quadraticCurveTo(w / 2, (18 + i * 16 + Math.sin(i) * 6) * k, w, (10 + i * 16) * k); c.stroke(); }
		c.fillStyle = 'rgba(255,255,255,.55)'; c.beginPath(); c.arc(w * 0.62, h * 0.32, 14 * k, 0, 7); c.fill();
		drawBag(c, w / 2 + 6 * k, h * 0.62, k * 2.2, br.bag[0], br.bag[1]);
		txt(c, 'قبل · روی تخت 🛏', w * 0.82, 22 * k, 4.5 * k, '#475569', 'center', false);
		// after: Gemini catalogue shot, revealed with a sweeping line
		if (cut > 0) {
			c.save(); c.beginPath(); c.rect(0, 0, cut, h); c.clip();
			const pr = st.step === 0 ? (st.prompt ?? 0) : br.prompt;
			const bg = [['#ffffff', '#e5e7eb'], ['#f5f5f4', '#d6b98c'], ['#fbcfe8', '#c4b5fd']][pr];
			const g = c.createRadialGradient(w / 2, h * 0.5, 4 * k, w / 2, h * 0.5, w * 0.7); g.addColorStop(0, bg[0]); g.addColorStop(1, bg[1]); c.fillStyle = g; c.fillRect(0, 0, w, h);
			c.fillStyle = 'rgba(0,0,0,.18)'; c.beginPath(); c.ellipse(w / 2, h * 0.8, 30 * k, 5 * k, 0, 0, 7); c.fill();
			drawBag(c, w / 2, h * 0.62, k * 2.6, br.bag[0], br.bag[1]);
			for (let i = 0; i < 5; i++) txt(c, '✨', w / 2 + Math.cos(st.t * 2 + i * 1.3) * 34 * k, h * 0.45 + Math.sin(st.t * 2 + i) * 22 * k, 4 * k, '#fff', 'center', false);
			txt(c, 'بعد · جمینای ✨', w * 0.18, 22 * k, 4.5 * k, '#1f2937', 'center', false);
			c.restore();
			if (st.step === 0 && st.reveal < 1) { c.fillStyle = '#fde047'; c.fillRect(cut - 1.5 * k, 0, 3 * k, h); }
		}
	};
	const finish = () => {
		if (st.over) return; st.over = true;
		const ok = st.score >= Math.ceil(st.max * 0.5), pts = st.score * 450 + (ok ? 600 : 0);
		cfg.sound && cfg.sound(ok ? 'level' : 'over');
		endScreen(el, ok, ok ? 'سایت گیلدا کامل سرپا شد! 👜✨' : 'عکس‌ها با برند جور نیست…', [`انتخاب درست ${fa(st.score)} از ${fa(st.max)}`, `امتیاز ${fa(pts)}`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, pts, { score: st.score, max: st.max }); });
	};
	reset();
	const lp = loop(update, draw);
	const close = wire(el, lp);
	close.state = () => st; close.el = el;
	return close;
}

/* ------------------------------------------------------------------ 8) payment gateway (boss): pipe puzzle */
const N = 1, E = 2, S = 4, Wd = 8;
const rot = (m) => ((m << 1) | (m >> 3)) & 15;
const ROUNDS = [
	{ cols: 5, rows: 3, why: 'درگاه قطع است! مسیر پرداخت را از سبد خرید تا بانک وصل کن.' },
	{ cols: 6, rows: 4, why: 'وصل شد… ولی Callback URL اشتباه است؛ مسیر را دوباره بساز.', locks: 2 },
	{ cols: 7, rows: 4, why: 'آخرین گره: IP سرور در لیست سفید بانک نیست. همه‌چیز را درست وصل کن!', locks: 3 },
];
export function showGateway(host, cfg) {
	const { el, cv, c, fit } = shell(host, 'kr-gate');
	el.__fit = fit;
	const VW = 320, VH = 180;
	let st;
	const build = (r) => {
		const R = ROUNDS[r], cols = R.cols, rows = R.rows, grid = [...Array(rows)].map(() => Array(cols).fill(0));
		// random monotone path from the cart (left) to the bank (right)
		let y = Math.floor(Math.random() * rows), from = Wd, lastDy = 0, vMoves = 0; const start = y; let x = 0;
		while (x < cols) {
			const dy = vMoves < 2 && Math.random() < 0.45 ? (lastDy || (Math.random() < 0.5 ? -1 : 1)) : 0; // never turn back on itself
			if (dy && y + dy >= 0 && y + dy < rows) { grid[y][x] = from | (dy < 0 ? N : S); from = dy < 0 ? S : N; y += dy; lastDy = dy; vMoves++; continue; }
			grid[y][x] = from | E; from = Wd; x++; lastDy = 0; vMoves = 0;
		}
		const end = y;
		for (let yy = 0; yy < rows; yy++) for (let xx = 0; xx < cols; xx++) if (!grid[yy][xx]) grid[yy][xx] = [N | S, N | E, N | E | S][Math.floor(Math.random() * 3)];
		const locked = new Set(); for (let i = 0; i < (R.locks || 0); i++) locked.add(`${Math.floor(Math.random() * cols)},${Math.floor(Math.random() * rows)}`);
		const tiles = grid.map((row, yy) => row.map((m, xx) => { let t = m; const k2 = locked.has(`${xx},${yy}`) ? 0 : Math.floor(Math.random() * 4); for (let i = 0; i < k2; i++) t = rot(t); return { m: t, lock: locked.has(`${xx},${yy}`), spin: 0 }; }));
		return { cols, rows, start, end, tiles, sol: grid.map((row) => row.slice()) }; // sol: the generated (solved) masks, used by the QC bot
	};
	const reset = () => { st = { round: 0, b: build(0), t: 0, patience: 30, lost: 0, over: false, flow: 0, say: null, moves: 0 }; st.say = { t: ROUNDS[0].why, life: 3.5 }; };
	const solved = () => {
		const { cols, rows, start, end, tiles } = st.b, seen = new Set(), q = [];
		if (!(tiles[start][0].m & Wd)) return false;
		q.push([0, start]); seen.add(`0,${start}`);
		while (q.length) {
			const [x, y] = q.shift(), m = tiles[y][x].m;
			if (x === cols - 1 && y === end && (m & E)) return true;
			[[N, 0, -1, S], [E, 1, 0, Wd], [S, 0, 1, N], [Wd, -1, 0, E]].forEach(([d, dx, dy, back]) => { const nx = x + dx, ny = y + dy; if (!(m & d) || nx < 0 || ny < 0 || nx >= cols || ny >= rows || seen.has(`${nx},${ny}`)) return; if (tiles[ny][nx].m & back) { seen.add(`${nx},${ny}`); q.push([nx, ny]); } });
		}
		return false;
	};
	const geo = () => { const { cols, rows } = st.b, cell = Math.min((VW - 90) / cols, (VH - 56) / rows), gx = (VW - cell * cols) / 2, gy = 34 + (VH - 44 - cell * rows) / 2; return { cell, gx, gy }; };
	const tap = (px, py) => {
		if (st.over || st.flow > 0) return;
		const { cell, gx, gy } = geo(), x = Math.floor((px - gx) / cell), y = Math.floor((py - gy) / cell);
		if (x < 0 || y < 0 || x >= st.b.cols || y >= st.b.rows) return;
		const tl = st.b.tiles[y][x]; if (tl.lock) { cfg.sound && cfg.sound('wrong'); return; }
		tl.m = rot(tl.m); tl.spin = 1; st.moves++; cfg.sound && cfg.sound('click');
		if (solved()) { st.flow = 0.001; cfg.sound && cfg.sound('level'); }
	};
	el.addEventListener('pointerdown', (e) => { const r = cv.getBoundingClientRect(); tap(((e.clientX - r.left) / r.width) * VW, ((e.clientY - r.top) / r.height) * VH); });
	const update = (dt) => {
		if (st.over) return;
		st.t += dt;
		for (const row of st.b.tiles) for (const tl of row) if (tl.spin > 0) tl.spin = Math.max(0, tl.spin - dt * 6);
		if (st.flow > 0) { st.flow += dt * 0.9; if (st.flow >= 1) { st.round++; if (st.round >= ROUNDS.length) return finish(true); st.b = build(st.round); st.flow = 0; st.patience = 30; st.say = { t: ROUNDS[st.round].why, life: 3.5 }; } return; }
		st.patience -= dt;
		if (st.patience <= 0) { st.lost++; cfg.sound && cfg.sound('hurt'); st.say = { t: 'یک مشتری خسته شد و رفت… 😞', life: 2 }; st.patience = 22; if (st.lost >= 3) return finish(false); }
		if (st.say && (st.say.life -= dt) <= 0) st.say = null;
	};
	const pipe = (m, x, y, s2, col) => { c.strokeStyle = col; c.lineWidth = s2 * 0.26; c.lineCap = 'round'; const cx = x + s2 / 2, cy = y + s2 / 2; [[N, 0, -1], [E, 1, 0], [S, 0, 1], [Wd, -1, 0]].forEach(([d, dx, dy]) => { if (m & d) { c.beginPath(); c.moveTo(cx, cy); c.lineTo(cx + dx * s2 / 2, cy + dy * s2 / 2); c.stroke(); } }); c.fillStyle = col; c.beginPath(); c.arc(cx, cy, s2 * 0.13, 0, 7); c.fill(); };
	const draw = () => {
		c.setTransform(cv.width / VW, 0, 0, cv.height / VH, 0, 0);
		c.fillStyle = '#0f172a'; c.fillRect(0, 0, VW, VH);
		const { cell, gx, gy } = geo(), { cols, rows, start, end, tiles } = st.b;
		// customers queue: patience bar
		rr(c, 40, 5, 150, 22, 6, 'rgba(2,6,23,.85)', '#f472b6', 1);
		txt(c, `🧍‍♀️🧍‍♀️🧍 صف مشتری · رفته ${fa(st.lost)} از ۳`, 115, 12, 6.5, '#fbcfe8', 'center', false);
		c.fillStyle = '#1e293b'; c.fillRect(46, 18, 138, 5); c.fillStyle = st.patience < 8 ? '#ef4444' : '#ec4899'; c.fillRect(46, 18, 138 * Math.max(0, st.patience) / 30, 5);
		rr(c, 196, 5, 120, 22, 6, 'rgba(2,6,23,.85)', '#38bdf8', 1); txt(c, `💳 گره ${fa(st.round + 1)} از ${fa(ROUNDS.length)}`, 256, 16, 8, '#e0f2fe', 'center', false);
		txt(c, '🛒', gx - 14, gy + cell * (start + 0.5), 14, '#fff', 'center', false);
		txt(c, '🏦', gx + cell * cols + 14, gy + cell * (end + 0.5), 14, '#fff', 'center', false);
		for (let y = 0; y < rows; y++) for (let x = 0; x < cols; x++) {
			const tl = tiles[y][x], px = gx + x * cell, py = gy + y * cell;
			rr(c, px + 1, py + 1, cell - 2, cell - 2, 4, tl.lock ? '#3f1d1d' : '#1e293b', tl.lock ? '#ef4444' : '#334155', 1);
			c.save(); c.translate(px + cell / 2, py + cell / 2); c.rotate(-tl.spin * Math.PI / 2); c.translate(-(px + cell / 2), -(py + cell / 2));
			pipe(tl.m, px, py, cell, st.flow > 0 ? `hsl(${140 + st.flow * 40} 80% 55%)` : tl.lock ? '#fca5a5' : '#94a3b8');
			c.restore();
			if (tl.lock) txt(c, '🔒', px + cell - 6, py + 7, 6, '#fff', 'center', false);
		}
		if (st.say) { c.font = `900 7px ${font()}`; const w2 = Math.min(VW - 20, c.measureText(st.say.t).width + 16); rr(c, VW / 2 - w2 / 2, VH - 18, w2, 14, 7, '#fff', OL, 1.2); txt(c, st.say.t, VW / 2, VH - 11, 7, OL, 'center', false); }
	};
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = ok ? 2500 - st.lost * 600 + Math.max(0, 600 - st.moves * 8) : 0;
		endScreen(el, ok, ok ? 'درگاه وصل شد! اولین سفارش گیلدا ثبت شد 🎉' : 'مشتری‌ها رفتند… دوباره درگاه را وصل کن', [`مشتری از دست رفته ${fa(st.lost)} · حرکت ${fa(st.moves)}`, `امتیاز ${fa(Math.max(0, pts))}`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, Math.max(0, pts), { lost: st.lost, moves: st.moves }); });
	};
	reset();
	const lp = loop(update, draw);
	const close = wire(el, lp);
	close.state = () => st; close.solved = solved; close.tap = tap; close.geo = geo;
	return close;
}
