/**
 * Chapter «ماکان · کاله» — five themed mini-games built from the owner's real nights (see KOROSH-DEBUGGER.md):
 *  showLogHunt    stage 0  the login-plugin edit floods the server; block the bad log lines (with Negar, until 23:00)
 *  showCodeFix    stage 1  at home until 01:00: fix the buggy ChatGPT functions of the «۷ لحظه» landing, move it to a new domain
 *  showDomainRace stage 2  (boss) the lottery site's domain expired — renew + DNS in the right order before the squatter bot
 *  showCrowd      stage 3  live draw on Bam-e Tehran: keep the site up under the crowd (route load, cache, squash bugs)
 *  showCues       stage 4  30 draws in the hall: Korosh can't see the host; press exactly when director-Negar says «الان»
 * Each returns close() (with pause/resume) and calls done(ok, points).
 */
import { drawPerson, drawBeetle } from './korosh-story.js';
import { drawHero } from '../lib/hero.js';
import { shell, txt, rr, endScreen, fa, clamp, OL, font } from './korosh-minis.js';

const clockStr = (m) => `${fa(Math.floor(m / 60) % 24)}:${fa(String(Math.floor(m) % 60).padStart(2, '0'))}`;
// shared loop: update(dt) / draw(); pause-aware; returns controller
function loop(update, draw) {
	let raf = 0, last = performance.now(), paused = false, closed = false;
	const tick = (now) => { if (closed) return; const dt = Math.min(0.05, (now - last) / 1000); last = now; if (!paused) update(dt); draw(); raf = requestAnimationFrame(tick); };
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
const bubble = (c, t, x, y, size = 7, max = 200) => { c.font = `900 ${size}px ${font()}`; const w = Math.min(max, c.measureText(t).width + 14); rr(c, x - w / 2, y - size - 3, w, size * 2 + 4, 8, '#fff', OL, 1.3); txt(c, t, x, y, size, OL, 'center', false); };

/* ------------------------------------------------------------------ 1) log hunt */
const GOOD_LOG = ['GET /product/mast-7 200', 'GET /wp-content/uploads/moment.jpg 200', 'GET /contest 200', 'GET /api/score?user=482 200', 'POST /upload 201', 'GET /rules 200', 'GET /sitemap.xml 200', 'GET /wp-json/kaleh/v1/rank 200'];
const BAD_LOG = ['POST /wp-login.php?redirect_to=wp-login.php 302 loop', 'GET /wp-login.php?action=auth_loop 302 loop', 'POST /wp-login.php?reauth=1&loop 302 loop', 'GET /wp-login.php?redirect_to=%2Fwp-login.php 302 loop'];
const NEGAR_LOG = ['نگار: اونجا! یه لوپ دیگه!', 'نگار: همه‌ش wp-login ـه…', 'نگار: سرور داره داغ می‌کنه!', 'نگار: کی این پلاگین رو ادیت کرده؟! 😤', 'نگار: داریم می‌رسیم!'];
export function showLogHunt(host, cfg) {
	const { el, cv, c, fit } = shell(host, 'kr-log');
	el.__fit = fit;
	const W = 320, H = 180, GOAL = cfg.goal || 14, LINE = 11, TOP = 40;
	let st;
	const reset = () => { st = { t: 0, lines: [], spawn: 0, blocked: 0, leaked: 0, load: 20, clock: 21 * 60, over: false, say: { t: 'نگار: لاگ‌ها رو ببین؛ خط‌های wp-login … loop رو ببند!', life: 3 }, flash: 0 }; };
	const update = (dt) => {
		if (st.over) return;
		st.t += dt; st.clock = Math.min(23 * 60, st.clock + dt * 2.6);
		st.spawn -= dt;
		if (st.spawn <= 0) { st.spawn = Math.max(0.32, 0.75 - st.t * 0.008); const bad = Math.random() < 0.34; const pool = bad ? BAD_LOG : GOOD_LOG; st.lines.push({ txt: pool[Math.floor(Math.random() * pool.length)], bad, y: H - 8, ip: `185.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`, hit: 0 }); }
		const sp = 26 + st.t * 0.5;
		for (const l of st.lines) l.y -= sp * dt;
		for (const l of st.lines.filter((x) => x.y < TOP)) { if (l.bad && !l.hit) { st.leaked++; st.load = Math.min(100, st.load + 9); cfg.sound && cfg.sound('hurt'); st.flash = 0.2; } }
		st.lines = st.lines.filter((x) => x.y >= TOP);
		st.load = Math.max(0, st.load - dt * 2.2);
		if (st.say && (st.say.life -= dt) <= 0) st.say = null;
		if (st.flash > 0) st.flash -= dt;
		if (st.load >= 100) finish(false);
	};
	const hitAt = (py) => {
		if (st.over) return;
		const l = st.lines.find((x) => !x.hit && Math.abs(x.y - py) < LINE / 2 + 1);
		if (!l) return;
		if (l.bad) { l.hit = 1; st.blocked++; st.load = Math.max(0, st.load - 6); cfg.sound && cfg.sound('coin'); if (st.blocked % 3 === 0) st.say = { t: NEGAR_LOG[Math.floor(Math.random() * NEGAR_LOG.length)], life: 2 }; if (st.blocked >= GOAL) finish(true); }
		else { l.hit = 2; st.wrong = (st.wrong || 0) + 1; st.load = Math.min(100, st.load + 7); cfg.sound && cfg.sound('wrong'); st.say = { t: 'نگار: اون که سالمه! 😅', life: 1.4 }; }
	};
	const draw = () => {
		c.setTransform(cv.width / W, 0, 0, cv.height / H, 0, 0);
		c.fillStyle = '#020617'; c.fillRect(0, 0, W, H);
		// header: server load + clock + Negar
		rr(c, 44, 6, 112, 26, 6, '#0f172a', '#334155', 1);
		txt(c, `بار سرور ${fa(Math.round(st.load))}٪`, 100, 13, 7, st.load > 75 ? '#fca5a5' : '#e2e8f0', 'center', false);
		c.fillStyle = '#1e293b'; c.fillRect(50, 20, 100, 6); c.fillStyle = st.load > 75 ? '#ef4444' : st.load > 45 ? '#f59e0b' : '#22c55e'; c.fillRect(50, 20, 100 * st.load / 100, 6);
		rr(c, 162, 6, 70, 26, 6, '#0f172a', '#334155', 1); txt(c, `⏰ ${clockStr(st.clock)}`, 197, 19, 8, '#fde68a', 'center', false);
		rr(c, 238, 6, 76, 26, 6, '#0f172a', '#334155', 1); txt(c, `🛑 ${fa(st.blocked)} از ${fa(GOAL)}`, 276, 19, 8, '#86efac', 'center', false);
		// terminal
		c.save(); c.beginPath(); c.rect(6, TOP, W - 60, H - TOP - 4); c.clip();
		c.fillStyle = '#030712'; c.fillRect(6, TOP, W - 60, H - TOP - 4);
		for (const l of st.lines) {
			if (l.hit === 1) { c.fillStyle = 'rgba(34,197,94,.18)'; c.fillRect(6, l.y - LINE / 2, W - 60, LINE); }
			if (l.hit === 2) { c.fillStyle = 'rgba(239,68,68,.18)'; c.fillRect(6, l.y - LINE / 2, W - 60, LINE); }
			c.font = '600 6.4px ui-monospace, monospace'; c.textAlign = 'left'; c.textBaseline = 'middle';
			c.fillStyle = '#64748b'; c.fillText(l.ip, 10, l.y);
			c.fillStyle = l.hit === 1 ? '#4ade80' : '#cbd5e1'; c.fillText(l.hit === 1 ? `BLOCKED  ${l.txt}` : l.txt, 72, l.y);
		}
		c.restore();
		if (st.flash > 0) { c.fillStyle = 'rgba(239,68,68,.18)'; c.fillRect(0, 0, W, H); }
		// Negar beside the screen
		drawPerson(c, W - 26, H - 6, 1.35, 'negar', { mood: st.load > 70 ? 'shock' : 'smile', name: 'نگار' }, st.t);
		if (st.say) bubble(c, st.say.t, W / 2 - 30, TOP + 12, 6.5, 220);
	};
	el.addEventListener('pointerdown', (e) => { const r = cv.getBoundingClientRect(); hitAt(((e.clientY - r.top) / r.height) * H); });
	const kd = (e) => { if (e.code !== 'Space' || st.over) return; e.preventDefault(); e.stopPropagation(); const l = st.lines.filter((x) => !x.hit).sort((a, b) => a.y - b.y)[0]; if (l) hitAt(l.y); };
	window.addEventListener('keydown', kd, true);
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = st.blocked * 120 + (ok ? 1000 : 0);
		endScreen(el, ok, ok ? 'مقصر پیدا شد: پلاگین لاگین ✓' : 'سرور از کار افتاد… 💥', ok ? ['ساعت ۱۱ شب؛ پلاگین غیرفعال شد ولی سایت باید از نو ساخته شود.', `امتیاز ${fa(pts)}`] : [`${fa(st.blocked)} خط بسته شد`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, ok ? pts : 0, st); });
	};
	reset();
	const lp = loop(update, draw);
	const close = wire(el, lp, () => window.removeEventListener('keydown', kd, true));
	close.state = () => st; close.hit = (y) => hitAt(y);
	return close;
}

/* ------------------------------------------------------------------ 2) code fix (ChatGPT v1 bugs) */
const FIXES = [
	{ q: 'هر عکس ۱ امتیاز؛ وقتی ۷ عکس کامل شد امتیاز ۱۰ می‌شود.', code: ['function score(photos) {', '  let points = photos.length;', '  if (photos.length === 7) points = 7; // ❓', '  return points;', '}'], bad: 2, opts: ['if (photos.length === 7) points = 10;', 'if (photos.length === 7) points = 70;', 'if (photos.length > 7) points = 0;'] },
	{ q: 'دوستت با کد معرف تو ثبت‌نام کند، ۵ امتیاز به تو می‌رسد.', code: ['function onSignup(friend) {', '  if (friend.refCode) {', '    owner.points += 50; // ❓', '  }', '}'], bad: 2, opts: ['owner.points += 5;', 'friend.points += 50;', 'owner.points = 5;'] },
	{ q: 'هر نفر حداکثر ۷ عکس از ۷ لحظه خاص زندگی‌اش.', code: ['function canUpload(user) {', '  return user.photos.length > 7; // ❓', '}'], bad: 1, opts: ['return user.photos.length < 7;', 'return user.photos.length > 7;', 'return true;'] },
	{ q: 'فقط عکس قبول است، نه فایل‌های دیگر.', code: ['function validFile(f) {', '  return f.size < 5_000_000; // ❓', '}'], bad: 1, opts: ["return f.type.startsWith('image/') && f.size < 5_000_000;", "return f.name.endsWith('.exe');", 'return f.size > 5_000_000;'] },
	{ q: 'لندینگ باید روی دامنه جدید بالا برود.', code: ["const SITE_URL = 'https://kaleh-old.ir'; // ❓", 'deploy(SITE_URL);'], bad: 0, opts: ["const SITE_URL = 'https://mast7-new.ir';", "const SITE_URL = 'http://localhost';", "const SITE_URL = '';"] },
];
export function showCodeFix(host, cfg) {
	const el = document.createElement('div');
	el.className = 'kr-mini kr-fix';
	el.innerHTML = `<div class="kr-fix__head"><span>🏠 خانه · بازسازی لندینگ از بکاپ</span><b data-clock></b><span data-step></span></div>
		<div class="kr-fix__body"><p class="kr-fix__q" data-q></p><pre class="kr-fix__code" dir="ltr" data-code></pre>
		<p class="kr-fix__gpt">🤖 ChatGPT نسخه اول این را نوشته… خط باگ‌دار (❓) را با کدام جایگزین کنیم؟</p><div class="kr-fix__opts" data-opts></div></div>
		<div class="kr-mini__end" hidden></div>`;
	host.appendChild(el);
	const $ = (q) => el.querySelector(q);
	let st;
	const reset = () => { st = { i: 0, clock: 23 * 60, miss: 0, pts: 0, over: false, lock: false, list: FIXES.slice().sort(() => Math.random() - 0.5) }; show(); };
	const show = () => {
		const f = st.list[st.i];
		$('[data-q]').textContent = f.q;
		$('[data-code]').innerHTML = f.code.map((l, j) => `<span class="${j === f.bad ? 'is-bad' : ''}">${l.replace(/[<>&]/g, (m) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[m]))}</span>`).join('\n');
		$('[data-opts]').innerHTML = f.opts.map((o, k) => [o, k]).sort(() => Math.random() - 0.5).map(([o, k]) => `<button type="button" dir="ltr" data-k="${k}">${o.replace(/[<>&]/g, (m) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[m]))}</button>`).join('');
		$('[data-step]').textContent = `باگ ${fa(st.i + 1)} از ${fa(st.list.length)}`;
		st.lock = false;
	};
	const pick = (b) => {
		if (!b || st.lock || st.over) return;
		st.lock = true;
		const ok = Number(b.dataset.k) === 0;
		b.classList.add(ok ? 'is-ok' : 'is-bad');
		if (ok) { st.pts += 400; st.clock += 12; cfg.sound && cfg.sound('level'); }
		else { st.miss++; st.clock += 22; cfg.sound && cfg.sound('wrong'); el.querySelector('[data-opts] [data-k="0"]').classList.add('is-ok'); }
		setTimeout(() => { if (st.over) return; if (st.clock >= 25 * 60) return finish(false); st.i++; if (st.i >= st.list.length) return finish(true); show(); }, ok ? 650 : 1500);
	};
	el.addEventListener('click', (e) => pick(e.target.closest('[data-opts] button')));
	const kd = (e) => { const n = '123۱۲۳'.indexOf(e.key); if (n < 0 || st.over) return; e.preventDefault(); e.stopPropagation(); pick(el.querySelectorAll('[data-opts] button')[n % 3]); };
	window.addEventListener('keydown', kd, true);
	const update = (dt) => { if (st.over) return; st.clock += dt * 0.9; if (st.clock >= 25 * 60) finish(false); };
	const draw = () => { const cl = $('[data-clock]'); if (cl) { cl.textContent = `⏰ ${clockStr(st.clock)}`; cl.classList.toggle('is-late', st.clock > 24.5 * 60); } };
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = st.pts + (ok ? Math.max(0, Math.round(25 * 60 - st.clock)) * 10 + 800 : 0);
		endScreen(el, ok, ok ? `ساعت ${clockStr(st.clock)}؛ لندینگ روی دامنه جدید بالا رفت 🚀` : 'ساعت از ۱ گذشت… خوابت برد 😴', [`اشتباه ${fa(st.miss)} · امتیاز ${fa(pts)}`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, ok ? pts : 0, st); });
	};
	reset();
	const lp = loop(update, draw);
	const close = wire(el, lp, () => window.removeEventListener('keydown', kd, true));
	close.state = () => st; close.pick = (k) => pick(el.querySelector(`[data-opts] [data-k="${k}"]`));
	return close;
}

/* ------------------------------------------------------------------ 3) domain race (boss) */
const DOMAIN_STEPS = [
	['🔍 whois: دامنه منقضی شده', '💳 تمدید یک‌ساله', '✅ پرداخت و تأیید', '🌐 رکورد A به سرور', '🧹 پاک کردن کش CDN'],
	['📞 تماس با ثبت‌کننده', '🔐 کد انتقال (Auth)', '💳 تمدید', '🌐 تنظیم DNS', '🔒 گواهی SSL'],
];
export function showDomainRace(host, cfg) {
	const { el, cv, c, fit } = shell(host, 'kr-race', '<div class="kr-race__opts" data-opts></div>');
	el.__fit = fit;
	const W = 320, H = 180;
	let st;
	const reset = () => { st = { round: 0, step: 0, me: 0, bot: 0, t: 0, over: false, say: null, shake: 0, seq: DOMAIN_STEPS[0] }; deal(); };
	const deal = () => {
		const seq = st.seq, nextI = st.step, opts = [seq[nextI], ...seq.filter((_, k) => k !== nextI).sort(() => Math.random() - 0.5).slice(0, 2)].sort(() => Math.random() - 0.5);
		el.querySelector('[data-opts]').innerHTML = opts.map((o) => `<button type="button" data-o="${seq.indexOf(o)}">${o}</button>`).join('');
	};
	const choose = (b) => {
		if (!b || st.over) return;
		if (Number(b.dataset.o) === st.step) {
			st.step++; st.me += 100 / (DOMAIN_STEPS[0].length + DOMAIN_STEPS[1].length); cfg.sound && cfg.sound('coin');
			if (st.step >= st.seq.length) { st.round++; if (st.round >= DOMAIN_STEPS.length) return finish(true); st.seq = DOMAIN_STEPS[st.round]; st.step = 0; st.say = { t: 'ربات هنوز پشت سرته! ادامه بده', life: 1.6 }; }
			deal();
		} else { st.bot = Math.min(100, st.bot + 6); st.shake = 0.25; cfg.sound && cfg.sound('wrong'); st.say = { t: 'ترتیب غلط! ربات جلو افتاد 😱', life: 1.2 }; }
	};
	el.addEventListener('pointerdown', (e) => { const b = e.target.closest('[data-o]'); if (b) { e.preventDefault(); choose(b); } });
	const kd = (e) => { const n = '123۱۲۳'.indexOf(e.key); if (n < 0 || st.over) return; e.preventDefault(); e.stopPropagation(); choose(el.querySelectorAll('[data-o]')[n % 3]); };
	window.addEventListener('keydown', kd, true);
	const update = (dt) => { if (st.over) return; st.t += dt; st.bot += dt * (3.6 + st.t * 0.05); if (st.say && (st.say.life -= dt) <= 0) st.say = null; if (st.shake > 0) st.shake -= dt; if (st.bot >= 100) finish(false); };
	const draw = () => {
		c.setTransform(cv.width / W, 0, 0, cv.height / H, 0, 0);
		if (st.shake > 0) c.translate((Math.random() - 0.5) * 4, 0);
		const g = c.createLinearGradient(0, 0, 0, H); g.addColorStop(0, '#1e1b4b'); g.addColorStop(1, '#312e81'); c.fillStyle = g; c.fillRect(0, 0, W, H);
		txt(c, '🌐 kaleh-lottery.ir · منقضی شده', W / 2, 14, 9, '#fca5a5');
		// two racing tracks
		[['کوروش', st.me, '#22c55e', 46], ['ربات دامنه‌ربا 🤖', st.bot, '#ef4444', 78]].forEach(([n, v, col, y]) => {
			rr(c, 20, y, W - 40, 16, 8, '#0f172a', '#475569', 1);
			c.fillStyle = col; c.beginPath(); c.roundRect(22, y + 2, (W - 44) * Math.min(1, v / 100), 12, 6); c.fill();
			txt(c, n, 30, y - 6, 7, '#e2e8f0', 'left', false);
			txt(c, '🏁', W - 26, y + 8, 9, '#fff', 'center', false);
		});
		c.save(); c.translate(22 + (W - 44) * Math.min(1, st.me / 100), 62); c.scale(0.55, 0.55); drawHero(c, 0, -32, 'big', 'none', 'run1', 1, 1, 1, (st.t * 2) % 1); c.restore();
		// the sequence so far
		const seq = st.seq;
		seq.forEach((s2, k) => { const x = 20 + k * ((W - 40) / seq.length); rr(c, x + 2, 104, (W - 40) / seq.length - 4, 18, 5, k < st.step ? '#166534' : '#1e293b', k === st.step ? '#fde047' : '#334155', 1); txt(c, k < st.step ? '✓' : fa(k + 1), x + (W - 40) / seq.length / 2, 113, 8, '#fff', 'center', false); });
		txt(c, `مرحله ${fa(st.round + 1)} از ${fa(DOMAIN_STEPS.length)} · گام بعدی را از دکمه‌ها انتخاب کن`, W / 2, 132, 7, '#c7d2fe', 'center', false);
		if (st.say) bubble(c, st.say.t, W / 2, 30, 7, 220);
	};
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = ok ? Math.round(3000 - st.t * 20) : 0;
		endScreen(el, ok, ok ? 'دامنه برگشت؛ سایت قرعه‌کشی در دسترس است ✓' : 'ربات دامنه را گرفت… دوباره!', [`زمان ${fa(Math.round(st.t))} ثانیه`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, Math.max(800, pts), st); });
	};
	reset();
	const lp = loop(update, draw);
	const close = wire(el, lp, () => window.removeEventListener('keydown', kd, true));
	close.state = () => st; close.choose = (i) => choose([...el.querySelectorAll('[data-o]')].find((b) => Number(b.dataset.o) === i));
	return close;
}

/* ------------------------------------------------------------------ 4) Bam-e Tehran crowd: keep the site up */
export function showCrowd(host, cfg) {
	const pads = '<button type="button" data-s="0">🖥 ۱</button><button type="button" data-s="1">🖥 ۲</button><button type="button" data-s="2">🖥 ۳</button><button type="button" data-cache>⚡ کش</button>';
	const { el, cv, c, fit } = shell(host, 'kr-crowd', pads);
	el.__fit = fit;
	const W = 320, H = 180, DUR = cfg.duration || 45, SX = [210, 250, 290];
	let st;
	const reset = () => { st = { t: 0, route: 0, load: [10, 10, 10], dots: [], spawn: 0, bugs: [], bugT: 3, hearts: 3, cache: 0, cacheCd: 0, over: false, down: 0, say: { t: 'نگار: فقط تا لحظه قرعه‌کشی دووم بیار…', life: 3 } }; };
	const update = (dt) => {
		if (st.over) return;
		st.t += dt;
		const rate = 2.6 + st.t * 0.07;
		st.spawn -= dt * rate * (st.cache > 0 ? 0.45 : 1);
		while (st.spawn <= 0) { st.spawn += 1; st.dots.push({ x: 20 + Math.random() * 120, y: 150 + Math.random() * 20, s: st.route, k: 0 }); }
		for (const d of st.dots) { d.k += dt * 1.6; }
		for (const d of st.dots.filter((x) => x.k >= 1)) st.load[d.s] = Math.min(100, st.load[d.s] + 2.1);
		st.dots = st.dots.filter((x) => x.k < 1);
		st.load = st.load.map((v) => Math.max(0, v - dt * 11.5));
		if (st.load.some((v) => v >= 100) && st.down <= 0) { st.hearts--; st.down = 1.5; cfg.sound && cfg.sound('hurt'); st.say = { t: 'سرور قرمز شد! 😱', life: 1.4 }; st.load = st.load.map((v) => (v >= 100 ? 60 : v)); if (st.hearts <= 0) return finish(false); }
		if (st.down > 0) st.down -= dt;
		st.bugT -= dt; if (st.bugT <= 0) { st.bugT = 2.2 + Math.random() * 1.6; st.bugs.push({ x: 160 + Math.random() * 140, y: 40 + Math.random() * 40, t: 5.5 }); }
		for (const b of st.bugs) { b.t -= dt; if (b.t <= 0 && !b.dead) { b.dead = true; st.hearts--; cfg.sound && cfg.sound('hurt'); st.say = { t: 'یه باگ روی سایت ماند! 🐞', life: 1.4 }; if (st.hearts <= 0) return finish(false); } }
		st.bugs = st.bugs.filter((b) => !b.dead);
		if (st.cache > 0) st.cache -= dt; if (st.cacheCd > 0) st.cacheCd -= dt;
		if (st.say && (st.say.life -= dt) <= 0) st.say = null;
		if (st.t >= DUR) finish(true);
	};
	const draw = () => {
		c.setTransform(cv.width / W, 0, 0, cv.height / H, 0, 0);
		const g = c.createLinearGradient(0, 0, 0, H); g.addColorStop(0, '#0b1033'); g.addColorStop(1, '#312e81'); c.fillStyle = g; c.fillRect(0, 0, W, H);
		for (let i = 0; i < 24; i++) { c.fillStyle = '#1e1b4b'; const bh = 20 + ((i * 37) % 40); c.fillRect(i * 14, 120 - bh, 12, bh); c.fillStyle = 'rgba(253,230,138,.6)'; c.fillRect(i * 14 + 3, 124 - bh, 2, 2); }
		c.fillStyle = '#1e293b'; c.fillRect(0, 120, 160, 60); txt(c, 'بام تهران 🌃', 70, 128, 7, '#c7d2fe', 'center', false);
		for (let i = 0; i < 26; i++) { const x = 8 + i * 5.6, y = 150 + Math.sin(i * 1.7 + st.t * 3) * 2; c.fillStyle = i % 3 ? '#475569' : '#64748b'; c.beginPath(); c.arc(x, y, 3.4, 0, 7); c.fill(); if (i % 4 === 0) { c.fillStyle = '#93c5fd'; c.fillRect(x - 1, y - 8, 2, 3); } }
		drawPerson(c, 128, 178, 0.9, 'negar', { mood: st.down > 0 ? 'shock' : 'smile', name: '' }, st.t);
		c.save(); c.translate(144, 178); c.scale(0.55, 0.55); drawHero(c, 0, -32, 'big', 'none', 'stand', -1); c.restore();
		// request dots fly to the chosen server
		for (const d of st.dots) { const tx = SX[d.s], ty = 128, x = d.x + (tx - d.x) * d.k, y = d.y + (ty - d.y) * d.k - Math.sin(d.k * Math.PI) * 30; c.fillStyle = st.cache > 0 ? '#a5f3fc' : '#fde047'; c.fillRect(x - 1, y - 1, 2.4, 2.4); }
		// the live site panel with bugs
		rr(c, 160, 22, 156, 76, 6, '#f8fafc', OL, 1.4); c.fillStyle = '#1d4ed8'; c.fillRect(162, 24, 152, 10); txt(c, 'قرعه‌کشی ماست سون · زنده 🔴', 238, 29, 6, '#fff', 'center', false);
		c.fillStyle = '#cbd5e1'; for (let i = 0; i < 3; i++) c.fillRect(170, 42 + i * 14, 130 - i * 20, 6);
		for (const b of st.bugs) { drawBeetle(c, b.x, b.y + 6, 0.75, 1, st.t, false); c.strokeStyle = '#ef4444'; c.lineWidth = 1; c.beginPath(); c.arc(b.x, b.y, 9, -Math.PI / 2, -Math.PI / 2 + (b.t / 5.5) * Math.PI * 2); c.stroke(); }
		// servers
		SX.forEach((x, i) => { const v = st.load[i]; rr(c, x - 16, 104, 32, 40, 4, i === st.route ? '#1e3a8a' : '#0f172a', i === st.route ? '#fde047' : '#475569', 1.4); c.fillStyle = v > 80 ? '#ef4444' : v > 55 ? '#f59e0b' : '#22c55e'; c.fillRect(x - 12, 140 - 32 * v / 100, 24, 32 * v / 100); txt(c, `${fa(i + 1)}`, x, 112, 8, '#fff'); });
		rr(c, 44, 6, 112, 24, 6, 'rgba(2,6,23,.8)', '#fbbf24', 1); txt(c, `⏳ تا قرعه‌کشی ${fa(Math.max(0, Math.ceil(DUR - st.t)))} · ${'❤'.repeat(Math.max(0, st.hearts))}`, 100, 18, 7, '#fde68a', 'center', false);
		if (st.cache > 0) txt(c, '⚡ کش فعال', 238, 152, 8, '#a5f3fc');
		if (st.say) bubble(c, st.say.t, 80, 52, 6.5, 150);
	};
	el.addEventListener('pointerdown', (e) => {
		const s = e.target.closest('[data-s]'), ca = e.target.closest('[data-cache]');
		if (s) { e.preventDefault(); st.route = Number(s.dataset.s); cfg.sound && cfg.sound('click'); return; }
		if (ca) { e.preventDefault(); if (st.cacheCd <= 0) { st.cache = 5; st.cacheCd = 12; cfg.sound && cfg.sound('level'); } return; }
		const r = cv.getBoundingClientRect(), px = ((e.clientX - r.left) / r.width) * W, py = ((e.clientY - r.top) / r.height) * H;
		const b = st.bugs.find((q) => Math.hypot(q.x - px, q.y - py) < 12); if (b) { st.bugs.splice(st.bugs.indexOf(b), 1); cfg.sound && cfg.sound('coin'); return; }
		SX.forEach((x, i) => { if (Math.abs(px - x) < 18 && py > 100 && py < 148) st.route = i; });
	});
	const kd = (e) => { if (st.over) return; const n = '123۱۲۳'.indexOf(e.key); if (n >= 0) { e.preventDefault(); e.stopPropagation(); st.route = n % 3; } else if (e.code === 'KeyC' || e.code === 'Space') { e.preventDefault(); e.stopPropagation(); if (st.cacheCd <= 0) { st.cache = 5; st.cacheCd = 12; } } };
	window.addEventListener('keydown', kd, true);
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = ok ? 1500 + st.hearts * 500 : 0;
		endScreen(el, ok, ok ? 'سایت تا لحظه قرعه‌کشی بالا ماند! 🎉' : 'سایت وسط قرعه‌کشی رفت… 😵', [ok ? 'جمعیت بام تهران قرعه‌کشی را زنده دید.' : 'بار را بین سرورها پخش کن و باگ‌ها را بزن.', `امتیاز ${fa(pts)}`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, pts, st); });
	};
	reset();
	const lp = loop(update, draw);
	const close = wire(el, lp, () => window.removeEventListener('keydown', kd, true));
	close.state = () => st; close.route = (i) => { st.route = i; }; close.squash = () => { st.bugs = []; };
	return close;
}

/* ------------------------------------------------------------------ 5) the hall: Negar's cues, 30 draws */
export function showCues(host, cfg) {
	const { el, cv, c, fit } = shell(host, 'kr-cues', '<button type="button" class="kr-cues__go" data-go>🎰 شروع قرعه‌کشی</button>');
	el.__fit = fit;
	const W = 320, H = 180, TOTAL = 30;
	let st;
	const reset = () => { st = { draw: 0, phase: 'ready', t: 1.2, miss: 0, over: false, applause: 0, say: { t: 'نگار: من مجری رو می‌بینم؛ هر وقت گفتم «الان» بزن!', life: 3 }, flash: null, combo: 0, pts: 0 }; };
	const next = () => {
		// «آماده…» then either «الان!» (press within the window) or a fake-out «وایسا!» (don't press)
		st.phase = 'ready'; st.t = 0.7 + Math.random() * 1.1 - Math.min(0.4, st.draw * 0.012);
	};
	const update = (dt) => {
		if (st.over) return;
		st.t -= dt;
		if (st.applause > 0) st.applause -= dt;
		if (st.say && (st.say.life -= dt) <= 0) st.say = null;
		if (st.flash && (st.flash.life -= dt) <= 0) st.flash = null;
		if (st.t > 0) return;
		if (st.phase === 'ready') { const fake = st.draw > 2 && Math.random() < 0.28; st.phase = fake ? 'wait' : 'now'; st.t = fake ? 0.9 : Math.max(0.55, 1.05 - st.draw * 0.015); }
		else if (st.phase === 'now') { st.miss++; st.combo = 0; st.flash = { t: 'دیر زدی! مجری منتظر ماند 😬', ok: false, life: 1 }; cfg.sound && cfg.sound('wrong'); if (st.miss >= 3) return finish(false); next(); }
		else if (st.phase === 'wait') { st.flash = { t: 'آفرین که صبر کردی ✓', ok: true, life: 0.8 }; next(); }
	};
	const press = () => {
		if (st.over) return;
		if (st.phase === 'now') {
			st.draw++; st.combo++; st.pts += 100 + st.combo * 10; st.applause = 0.8; cfg.sound && cfg.sound('coin');
			st.flash = { t: `قرعه‌کشی ${fa(st.draw)} از ${fa(TOTAL)} 🎉`, ok: true, life: 0.8 };
			if (st.draw >= TOTAL) return finish(true);
			next();
		} else { st.miss++; st.combo = 0; st.flash = { t: st.phase === 'wait' ? 'نگار گفت وایسا! ✋' : 'زود زدی! هنوز نگفته', ok: false, life: 1 }; cfg.sound && cfg.sound('wrong'); if (st.miss >= 3) return finish(false); next(); }
	};
	const draw = () => {
		c.setTransform(cv.width / W, 0, 0, cv.height / H, 0, 0);
		// backstage technical room (left) · doorway with Negar (middle) · the stage seen only by her (right)
		c.fillStyle = '#111827'; c.fillRect(0, 0, W, H);
		c.fillStyle = '#1f2937'; c.fillRect(0, 0, 150, H); c.fillStyle = '#0f172a'; c.fillRect(0, 130, 150, 50);
		rr(c, 20, 96, 70, 34, 3, '#334155', OL, 1.2); rr(c, 26, 70, 58, 30, 3, '#0b1220', OL, 1.2); c.fillStyle = '#22d3ee'; c.fillRect(30, 74, 50, 22); txt(c, '🎰', 55, 85, 12, '#fff', 'center', false);
		c.save(); c.translate(108, 140); c.scale(1.05, 1.05); drawHero(c, 0, -32, 'big', 'none', 'stand', -1, 1, 1, null, st.phase === 'now' ? 0.5 : 0); c.restore();
		c.fillStyle = '#7c2d12'; c.fillRect(150, 30, 10, 150); c.fillRect(150, 30, 60, 8);
		const mood = st.phase === 'now' ? 'shock' : st.phase === 'wait' ? 'angry' : 'smile';
		drawPerson(c, 182, 168, 1.7, 'negar', { mood, name: 'نگار' }, performance.now() / 1000);
		const sig = st.phase === 'now' ? ['الان بزن! 👉', '#22c55e'] : st.phase === 'wait' ? ['وایسا! ✋', '#ef4444'] : ['آماده…', '#fbbf24'];
		rr(c, 150, 44, 92, 26, 8, sig[1], OL, 1.6); txt(c, sig[0], 196, 57, 10, '#fff');
		// the stage: host + lights (Korosh can't see it)
		const lg = c.createLinearGradient(240, 0, W, 0); lg.addColorStop(0, '#4c1d95'); lg.addColorStop(1, '#7e22ce'); c.fillStyle = lg; c.fillRect(242, 0, 78, H);
		c.fillStyle = 'rgba(254,249,195,.18)'; c.beginPath(); c.moveTo(282, 0); c.lineTo(256, 150); c.lineTo(310, 150); c.fill();
		drawPerson(c, 282, 150, 1.3, 'mh', { mood: 'laugh', name: 'مجری' }, performance.now() / 1000);
		c.fillStyle = '#581c87'; c.fillRect(242, 150, 78, 30);
		if (st.applause > 0) txt(c, '👏👏👏', 282, 22, 10, '#fff', 'center', false);
		rr(c, 40, 6, 104, 22, 6, 'rgba(2,6,23,.8)', '#fbbf24', 1); txt(c, `🎰 ${fa(st.draw)} از ${fa(TOTAL)} · خطا ${'●'.repeat(st.miss)}${'○'.repeat(3 - st.miss)}`, 92, 17, 7, '#fde68a', 'center', false);
		if (st.flash) txt(c, st.flash.t, 75, 46, 8, st.flash.ok ? '#86efac' : '#fca5a5');
		if (st.say) bubble(c, st.say.t, W / 2, 168, 6.5, 260);
	};
	el.addEventListener('pointerdown', (e) => { if (e.target.closest('.kr-mini__end')) return; e.preventDefault(); press(); });
	const kd = (e) => { if (st.over || !['Space', 'Enter'].includes(e.code)) return; e.preventDefault(); e.stopPropagation(); if (!e.repeat) press(); };
	window.addEventListener('keydown', kd, true);
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const pts = st.pts + (ok ? 1500 : 0);
		endScreen(el, ok, ok ? 'هر ۳۰ قرعه‌کشی اجرا شد! 👏 همه تشویق کردند' : 'هماهنگی به هم ریخت… دوباره!', [`امتیاز ${fa(pts)}`], () => { el.querySelector('.kr-mini__end').hidden = true; reset(); next(); }, () => { close(); cfg.done && cfg.done(ok, ok ? pts : 0, st); });
	};
	reset(); next();
	const lp = loop(update, draw);
	const close = wire(el, lp, () => window.removeEventListener('keydown', kd, true));
	close.state = () => st; close.press = press;
	return close;
}
