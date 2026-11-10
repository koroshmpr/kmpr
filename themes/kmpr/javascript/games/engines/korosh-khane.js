/**
 * Chapter «خانه سرمایه» (main stages 9–11) — from a WordPress theme and articles to building apps:
 *  showNextRoutes  stage 9   learning Next.js: walk the file tree and create the right file for each route
 *  showApiDash     stage 10  live prices (gold / coin / USD / USDT): keep tiles fresh within the API quota, fix a changed JSON path
 *  showDeadlineAI  stage 11  (final boss) the Deadline Lord: ship 10 tickets — code them yourself or let AI do it and review its code
 * Each returns close() (pause/resume) and calls done(ok, points, stats).
 */
import { endScreen, fa, clamp } from './korosh-minis.js';

function loop(update) {
	let raf = 0, last = performance.now(), paused = false, closed = false;
	const tick = (now) => { if (closed) return; const dt = Math.min(0.05, (now - last) / 1000); last = now; if (!paused) update(dt); raf = requestAnimationFrame(tick); };
	raf = requestAnimationFrame(tick);
	return { stop() { closed = true; cancelAnimationFrame(raf); }, pause() { paused = true; }, resume() { paused = false; last = performance.now(); } };
}
function mount(host, cls, html, lp0) {
	const el = document.createElement('div');
	el.className = `kr-mini ${cls}`;
	el.innerHTML = `${html}<div class="kr-mini__end" hidden></div>`;
	host.appendChild(el);
	return el;
}
const close = (el, lp, extra) => { const fn = () => { lp.stop(); extra && extra(); el.remove(); }; fn.pause = () => lp.pause(); fn.resume = () => lp.resume(); return fn; };
const esc = (s) => String(s).replace(/[<>&]/g, (m) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[m]));

/* ------------------------------------------------------------------ 9) Next.js file-tree routing */
const TREE = { app: { 'page.tsx': 1, 'layout.tsx': 1, about: { 'page.tsx': 1 }, blog: { 'page.tsx': 1, '[slug]': { 'page.tsx': 1 } }, api: { prices: { 'route.ts': 1 } } }, components: { 'BuyButton.tsx': 1, 'Header.tsx': 1 }, public: { 'logo.svg': 1 }, pages: { 'old-about.html': 1 } };
const ROUTES = [
	{ ask: 'صفحه اصلی سایت: /', want: 'app/page.tsx' },
	{ ask: 'صفحه «درباره ما»: /about', want: 'app/about/page.tsx' },
	{ ask: 'هر مقاله با آدرس خودش: /blog/how-to-trade', want: 'app/blog/[slug]/page.tsx' },
	{ ask: 'API قیمت‌ها که JSON برگرداند: /api/prices', want: 'app/api/prices/route.ts' },
	{ ask: 'هدر و فوتر مشترک همه صفحه‌ها', want: 'app/layout.tsx' },
];
export function showNextRoutes(host, cfg) {
	const el = mount(host, 'kr-next', `<div class="kr-nx__bar"><span>▲ Next.js · یادگیری App Router</span><b data-time></b><span data-step></span></div>
		<div class="kr-nx__body"><div class="kr-nx__ask"><p class="kr-nx__url" data-ask></p><p class="kr-nx__crumb" dir="ltr" data-crumb></p></div>
		<div class="kr-nx__tree" data-tree dir="ltr"></div><p class="kr-nx__msg" data-msg></p></div>`);
	const $ = (q) => el.querySelector(q);
	let st;
	const reset = () => { st = { i: 0, path: [], t: 25, miss: 0, pts: 0, over: false, quiz: false }; show(); };
	const node = () => st.path.reduce((n, k) => n[k], TREE);
	const show = () => {
		if (st.quiz) {
			$('[data-ask]').textContent = '⚠️ ارور: «useState only works in Client Components». در BuyButton.tsx چه کنیم؟';
			$('[data-crumb]').textContent = 'components/BuyButton.tsx';
			$('[data-tree]').innerHTML = [["'use client' را بالای فایل بنویس", 1], ['useState را پاک کن', 0], ['فایل را .html کن', 0]].sort(() => Math.random() - 0.5).map(([t, ok]) => `<button type="button" data-q="${ok}">${esc(t)}</button>`).join('');
		} else {
			const r = ROUTES[st.i], n = node();
			$('[data-ask]').textContent = `🌐 ${r.ask}`;
			$('[data-crumb]').textContent = `📂 /${st.path.join('/')}`;
			const kids = Object.keys(n).sort((a, b) => (typeof n[a] === 'object' ? 0 : 1) - (typeof n[b] === 'object' ? 0 : 1));
			$('[data-tree]').innerHTML = `${st.path.length ? '<button type="button" data-up>⬆ ..</button>' : ''}${kids.map((k) => `<button type="button" data-k="${esc(k)}" class="${typeof n[k] === 'object' ? 'is-dir' : 'is-file'}">${typeof n[k] === 'object' ? '📁' : '📄'} ${esc(k)}</button>`).join('')}`;
		}
		$('[data-step]').textContent = `${fa(Math.min(st.i + 1, ROUTES.length + 1))} از ${fa(ROUTES.length + 1)}`;
	};
	const next = () => { st.path = []; st.t = 25; if (st.quiz) return finish(true); st.i++; if (st.i >= ROUTES.length) st.quiz = true; show(); };
	el.addEventListener('click', (e) => {
		if (st.over) return;
		const b = e.target.closest('button'); if (!b || el.querySelector('.kr-mini__end:not([hidden])')) return;
		if (b.dataset.q !== undefined) { if (b.dataset.q === '1') { st.pts += 600; $('[data-msg]').textContent = '✓ کامپوننت کلاینت شد؛ دکمه کار می‌کند'; cfg.sound && cfg.sound('level'); setTimeout(next, 700); } else { st.miss++; st.t -= 6; $('[data-msg]').textContent = '✗ نه… دوباره بخوان'; cfg.sound && cfg.sound('wrong'); } return; }
		if (b.hasAttribute('data-up')) { st.path.pop(); show(); return; }
		const k = b.dataset.k, n = node();
		if (typeof n[k] === 'object') { st.path.push(k); cfg.sound && cfg.sound('click'); show(); return; }
		const got = [...st.path, k].join('/');
		if (got === ROUTES[st.i].want) { st.pts += 400 + Math.round(st.t) * 20; $('[data-msg]').textContent = `✓ ${ROUTES[st.i].ask.split(':').pop().trim()} ساخته شد`; cfg.sound && cfg.sound('level'); setTimeout(next, 650); }
		else { st.miss++; st.t -= 5; $('[data-msg]').textContent = `✗ ${got} این صفحه را نمی‌سازد`; cfg.sound && cfg.sound('wrong'); }
	});
	const lp = loop((dt) => { if (st.over) return; st.t -= dt; const tm = $('[data-time]'); if (tm) { tm.textContent = `⏱ ${fa(Math.max(0, Math.ceil(st.t)))}`; tm.classList.toggle('is-late', st.t < 7); } if (st.t <= 0) finish(false); });
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = st.pts + (ok ? 800 : 0);
		endScreen(el, ok, ok ? 'App Router را یاد گرفتی! ▲ اولین سایت Next بالا آمد' : 'وقت تمام شد… مستندات را دوباره بخوان', [`اشتباه ${fa(st.miss)} · امتیاز ${fa(pts)}`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { cl(); cfg.done && cfg.done(ok, ok ? pts : 0, { miss: st.miss }); });
	};
	reset();
	const cl = close(el, lp); cl.state = () => st; cl.el = el;
	return cl;
}

/* ------------------------------------------------------------------ 10) live prices dashboard (API quota) */
const TILES = [['🥇', 'طلای ۱۸', 4_850_000], ['🪙', 'سکه امامی', 52_000_000], ['💵', 'دلار', 91_500], ['₮', 'تتر', 92_300]];
const JSON_Q = { sample: '{ "data": { "usdt": { "buy": 92100, "sell": 92300 } } }', opts: [['data.usdt.sell', 1], ['data.price.usdt', 0], ['usdt', 0]] };
export function showApiDash(host, cfg) {
	const DUR = cfg.duration || 50, QMAX = 6;
	const el = mount(host, 'kr-api', `<div class="kr-api__bar"><span>📊 قیمت روز · تترچند</span><b data-time></b><span data-quota></span><span data-strikes></span></div>
		<div class="kr-api__tiles" data-tiles>${TILES.map(([ic, n], i) => `<button type="button" class="kr-api__tile" data-t="${i}"><span class="kr-api__ic">${ic}</span><span class="kr-api__n">${n}</span><b data-v></b><i><u data-f></u></i><em data-age></em></button>`).join('')}</div>
		<p class="kr-api__msg" data-msg>هر کاشی را قبل از کهنه شدن به‌روز کن · سهمیه API محدود است!</p><div class="kr-api__modal" data-modal hidden></div>`);
	const $ = (q) => el.querySelector(q);
	let st;
	const reset = () => { st = { t: 0, quota: QMAX, lock: 0, fresh: TILES.map(() => 1), val: TILES.map((x) => x[2]), strikes: 0, r429: 0, over: false, fmtAt: 18 + Math.random() * 8, fmtDone: false, broken: false, pts: 0 }; $('[data-modal]').hidden = true; draw(); };
	const draw = () => {
		el.querySelectorAll('.kr-api__tile').forEach((tl, i) => {
			const f = st.fresh[i];
			tl.querySelector('[data-v]').textContent = st.broken && i === 3 ? 'NaN ❌' : fa(Math.round(st.val[i]).toLocaleString('en-US'));
			tl.querySelector('[data-f]').style.width = `${Math.max(0, f) * 100}%`;
			tl.querySelector('[data-f]').style.background = f > 0.5 ? '#22c55e' : f > 0.2 ? '#f59e0b' : '#ef4444';
			tl.querySelector('[data-age]').textContent = f <= 0 ? 'کهنه! 😡' : '';
			tl.classList.toggle('is-stale', f <= 0.2);
		});
		$('[data-quota]').textContent = st.lock > 0 ? `🚫 429 · ${fa(Math.ceil(st.lock))}` : `API ${'●'.repeat(Math.floor(st.quota))}${'○'.repeat(QMAX - Math.floor(st.quota))}`;
		$('[data-strikes]').textContent = `شکایت ${'⚠'.repeat(st.strikes)}${'·'.repeat(3 - st.strikes)}`;
		$('[data-time]').textContent = `⏱ ${fa(Math.max(0, Math.ceil(DUR - st.t)))}`;
	};
	const fetchTile = (i) => {
		if (st.over || !$('[data-modal]').hidden) return;
		if (st.lock > 0) { cfg.sound && cfg.sound('wrong'); return; }
		if (st.quota < 1) { st.lock = 2.5; st.r429++; $('[data-msg]').textContent = '429 Too Many Requests! چند ثانیه صبر کن ⏳'; cfg.sound && cfg.sound('hurt'); draw(); return; }
		st.quota -= 1; st.fresh[i] = 1; st.val[i] *= 1 + (Math.random() - 0.5) * 0.01; st.pts += 40; cfg.sound && cfg.sound('coin');
		if (st.broken && i === 3) { $('[data-msg]').textContent = 'تتر هنوز NaN است؛ اول مسیر JSON را درست کن'; }
		draw();
	};
	el.addEventListener('click', (e) => {
		const tl = e.target.closest('[data-t]'); if (tl) { fetchTile(Number(tl.dataset.t)); return; }
		const op = e.target.closest('[data-j]');
		if (op) { const ok = op.dataset.j === '1'; if (ok) { st.broken = false; st.fmtDone = true; st.pts += 800; $('[data-modal]').hidden = true; $('[data-msg]').textContent = '✓ مسیر جدید JSON درست شد؛ تتر برگشت'; cfg.sound && cfg.sound('level'); } else { st.strikes++; cfg.sound && cfg.sound('wrong'); op.disabled = true; if (st.strikes >= 3) finish(false); } draw(); }
	});
	const kd = (e) => { const n = '1234۱۲۳۴'.indexOf(e.key); if (n < 0 || st.over) return; e.preventDefault(); e.stopPropagation(); fetchTile(n % 4); };
	window.addEventListener('keydown', kd, true);
	const lp = loop((dt) => {
		if (st.over || !$('[data-modal]').hidden) return;
		st.t += dt;
		st.quota = Math.min(QMAX, st.quota + dt / 1.05);
		if (st.lock > 0) st.lock -= dt;
		st.fresh = st.fresh.map((f, i) => f - dt / (9 + i * 0.8));
		st.val = st.val.map((v, i) => v * (1 + (Math.random() - 0.5) * 0.0015));
		st.fresh.forEach((f, i) => { if (f < -1.2) { st.fresh[i] = 0; st.strikes++; $('[data-msg]').textContent = `کاربرها شاکی‌اند: قیمت ${TILES[i][1]} خیلی کهنه است! ⚠`; cfg.sound && cfg.sound('hurt'); } });
		if (!st.fmtDone && st.t > st.fmtAt) { // the price provider changed its JSON format
			st.broken = true; st.fmtDone = true;
			const m = $('[data-modal]'); m.hidden = false;
			m.innerHTML = `<p>⚠️ API فرمتش را عوض کرد و قیمت تتر NaN شد! پاسخ جدید:</p><pre dir="ltr">${esc(JSON_Q.sample)}</pre><p>قیمت فروش تتر کدام است؟</p><div>${JSON_Q.opts.slice().sort(() => Math.random() - 0.5).map(([t, ok]) => `<button type="button" dir="ltr" data-j="${ok}">${t}</button>`).join('')}</div>`;
		}
		if (st.strikes >= 3) return finish(false);
		if (st.t >= DUR) return finish(true);
		draw();
	});
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = st.pts + (ok ? 1200 - st.strikes * 300 : 0);
		endScreen(el, ok, ok ? 'قیمت‌ها همیشه زنده ماندند! 📈' : 'کاربرها رفتند سراغ یک سایت دیگر…', [`شکایت ${fa(st.strikes)} · خطای ۴۲۹: ${fa(st.r429)}`, `امتیاز ${fa(Math.max(0, pts))}`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { cl(); cfg.done && cfg.done(ok, Math.max(0, pts), { strikes: st.strikes, r429: st.r429 }); });
	};
	reset();
	const cl = close(el, lp, () => window.removeEventListener('keydown', kd, true)); cl.state = () => st; cl.fetch = fetchTile; cl.el = el;
	return cl;
}

/* ------------------------------------------------------------------ 11) final boss: the Deadline Lord vs Korosh + AI */
const TICKETS = ['لندینگ‌ساز وبینار', 'API قیمت تتر', 'دستیار تریدر: محاسبه حجم', 'ابزار کارت به شبا', 'افزونه قیمت روز', 'ورود با کد یک‌بارمصرف', 'صفحه همکاری هاب', 'اپ تترچند: مقایسه صرافی‌ها', 'داشبورد ادمین', 'سئوی صفحه‌های دوره'];
const AI_GOOD = ["const res = await fetch(API_URL, { next: { revalidate: 30 } });", 'export async function GET() { return Response.json(await getPrices()); }', 'const lot = (risk * balance) / (stopPips * pipValue);', "const iban = 'IR' + checksum(bank + account);", "if (!/^\\d{6}$/.test(code)) return error('کد نامعتبر');"];
const AI_BAD = ["import { magicPrices } from 'next/prices'; // این پکیج وجود ندارد", 'const lot = balance * 100; // کل سرمایه در یک معامله!', "fetch('http://localhost:3000/api') // روی سرور واقعی؟", 'password = req.query.pass; // رمز در آدرس!', "eval(userInput) // ورودی کاربر اجرا شود؟"];
export function showDeadlineAI(host, cfg) {
	const GOAL = cfg.goal || 10, DUR = cfg.duration || 75;
	const el = mount(host, 'kr-dl', `<div class="kr-dl__lord"><div class="kr-dl__clock" data-clock>⏰</div><p data-taunt>ارباب ددلاین: «تا آخر وقت، همه‌ی این‌ها تحویل!»</p><div class="kr-dl__bar"><u data-bar></u></div></div>
		<div class="kr-dl__hud"><span data-done></span><span data-aiq></span><span data-bugs></span></div>
		<div class="kr-dl__tickets" data-tickets></div><div class="kr-dl__review" data-review hidden></div>`);
	const $ = (q) => el.querySelector(q);
	let st, uid = 0;
	const reset = () => { st = { t: 0, done: 0, ai: 3, aiT: 0, bugs: 0, list: [], spawn: 0, over: false, review: null, idx: 0, pts: 0 }; $('[data-review]').hidden = true; render(); };
	const addTicket = () => { if (st.list.length >= 3) return; st.list.push({ id: ++uid, name: TICKETS[st.idx++ % TICKETS.length], work: 0 }); render(); };
	const render = () => {
		$('[data-tickets]').innerHTML = st.list.map((tk) => `<div class="kr-dl__tk" data-id="${tk.id}"><p>🎫 ${tk.name}</p><i><u style="width:${tk.work * 25}%"></u></i>
			<div><button type="button" data-hand="${tk.id}">⌨️ خودم کد بزنم</button><button type="button" data-ai="${tk.id}" ${st.ai < 1 ? 'disabled' : ''}>🤖 بده به AI</button></div></div>`).join('');
		hud();
	};
	const hud = () => { $('[data-done]').textContent = `✅ ${fa(st.done)} از ${fa(GOAL)}`; $('[data-aiq]').textContent = `🤖 AI ${'●'.repeat(st.ai)}${'○'.repeat(3 - st.ai)}`; $('[data-bugs]').textContent = `🐞 باگ در پروداکشن ${fa(st.bugs)}`; };
	const ship = (tk) => { st.list = st.list.filter((x) => x !== tk); st.done++; st.pts += 300; cfg.sound && cfg.sound('level'); if (st.done >= GOAL) return finish(true); render(); };
	el.addEventListener('click', (e) => {
		if (st.over) return;
		const h = e.target.closest('[data-hand]'), a = e.target.closest('[data-ai]'), rv = e.target.closest('[data-rv]');
		if (rv && st.review) {
			const accept = rv.dataset.rv === '1', { tk, bad } = st.review; st.review = null; $('[data-review]').hidden = true;
			if (accept && !bad) { st.pts += 150; ship(tk); }
			else if (!accept && bad) { st.pts += 250; tk.work = Math.max(tk.work, 2); $('[data-taunt]').textContent = 'آفرین! توهم AI را گرفتی 👀'; cfg.sound && cfg.sound('coin'); render(); }
			else if (accept && bad) { st.bugs++; st.t += 6; $('[data-taunt]').textContent = 'ارباب ددلاین: «باگ رفت روی پروداکشن! 😈»'; cfg.sound && cfg.sound('hurt'); ship(tk); }
			else { $('[data-taunt]').textContent = 'کد سالم بود! وقت هدر رفت…'; st.t += 3; cfg.sound && cfg.sound('wrong'); render(); }
			return;
		}
		if (h) { const tk = st.list.find((x) => x.id === Number(h.dataset.hand)); if (!tk) return; tk.work++; cfg.sound && cfg.sound('click'); if (tk.work >= 4) ship(tk); else render(); return; }
		if (a) {
			const tk = st.list.find((x) => x.id === Number(a.dataset.ai)); if (!tk || st.ai < 1) return;
			st.ai--; const bad = Math.random() < 0.35, code = bad ? AI_BAD[Math.floor(Math.random() * AI_BAD.length)] : AI_GOOD[Math.floor(Math.random() * AI_GOOD.length)];
			st.review = { tk, bad };
			const r = $('[data-review]'); r.hidden = false;
			r.innerHTML = `<p>🤖 AI برای «${tk.name}» نوشت؛ قبل از تحویل بررسی کن:</p><pre dir="ltr">${esc(code)}</pre><div><button type="button" data-rv="1">✅ تحویل بده</button><button type="button" data-rv="0">❌ رد کن، خودم درستش می‌کنم</button></div>`;
			hud();
		}
	});
	const lp = loop((dt) => {
		if (st.over || st.review) return;
		st.t += dt;
		st.aiT += dt; if (st.aiT > 7) { st.aiT = 0; st.ai = Math.min(3, st.ai + 1); hud(); }
		st.spawn -= dt; if (st.spawn <= 0) { st.spawn = 2.4; addTicket(); }
		const left = clamp(1 - st.t / DUR, 0, 1);
		$('[data-bar]').style.width = `${left * 100}%`;
		$('[data-clock]').style.transform = `scale(${1 + (1 - left) * 0.6}) rotate(${Math.sin(st.t * 3) * (1 - left) * 8}deg)`;
		if (st.t >= DUR) finish(false);
	});
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = st.pts + (ok ? 2500 - st.bugs * 400 : 0);
		endScreen(el, ok, ok ? 'ددلاین رو شکست دادی! از یک قالب وردپرس تا ساختن اپلیکیشن 🚀' : 'ددلاین رسید… تیکت‌ها ماند', [`تحویل ${fa(st.done)} از ${fa(GOAL)} · باگ ${fa(st.bugs)}`, `امتیاز ${fa(Math.max(0, pts))}`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { cl(); cfg.done && cfg.done(ok, Math.max(0, pts), { bugs: st.bugs, done: st.done }); });
	};
	reset();
	const cl = close(el, lp); cl.state = () => st; cl.el = el;
	return cl;
}
