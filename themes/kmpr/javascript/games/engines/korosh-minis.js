/**
 * «کوروش دیباگر» mini-games that are NOT platform levels:
 *  - showDrive: top-down lane-weaving drive (pickups, deadline clock / patience, bombs, near-miss bonus)
 *  - showQC:    Yousef's Trello QC storm — sort falling QC cards into the right fix column
 * Each returns close(); calls done(ok, points). Own canvas + rAF loop + keyboard/touch input.
 */
import { drawYousef, drawPerson } from './korosh-story.js';

const OL = '#16120f';
const fa = (v) => String(v).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
let FF = 'system-ui, sans-serif';

function shell(host, cls, pads) {
	if (typeof document !== 'undefined') FF = getComputedStyle(document.body).fontFamily || FF;
	const el = document.createElement('div');
	el.className = `kr-mini ${cls}`;
	el.innerHTML = `<canvas aria-hidden="true"></canvas>${pads ? `<div class="kr-mini__pads">${pads}</div>` : ''}<div class="kr-mini__end" hidden></div>`;
	host.appendChild(el);
	const cv = el.querySelector('canvas'), c = cv.getContext('2d');
	const fit = () => { const r = el.getBoundingClientRect(), dpr = Math.min(2, window.devicePixelRatio || 1); cv.width = Math.round(r.width * dpr); cv.height = Math.round(r.height * dpr); };
	fit();
	return { el, cv, c, fit };
}
const txt = (c, t, x, y, size, color, align = 'center', stroke = true) => {
	c.font = `900 ${size}px ${FF}`; c.textAlign = align; c.textBaseline = 'middle';
	if (stroke) { c.lineJoin = 'round'; c.lineWidth = size * 0.28; c.strokeStyle = OL; c.strokeText(t, x, y); }
	c.fillStyle = color; c.fillText(t, x, y);
};
const rr = (c, x, y, w, h, r, fill, stroke = OL, lw = 1.5) => { c.beginPath(); c.roundRect(x, y, w, h, r); c.fillStyle = fill; c.fill(); if (stroke) { c.lineWidth = lw; c.strokeStyle = stroke; c.stroke(); } };
function endScreen(el, ok, title, lines, onRetry, onBack) {
	const box = el.querySelector('.kr-mini__end');
	box.hidden = false;
	box.innerHTML = `<div class="kr-mini__card ${ok ? 'is-ok' : 'is-bad'}"><p class="kr-mini__h">${title}</p>${lines.map((l) => `<p>${l}</p>`).join('')}
		<div class="kr-mini__btns">${ok ? '<button type="button" data-m="go">ادامه ◂</button>' : '<button type="button" data-m="retry">دوباره ↻</button><button type="button" data-m="back">نقشه</button>'}</div></div>`;
	box.querySelector('button').focus({ preventScroll: true });
	box.addEventListener('click', (e) => { const b = e.target.closest('[data-m]'); if (!b) return; if (b.dataset.m === 'retry') onRetry(); else onBack(); }, { once: true });
}

/* ------------------------------------------------------------------ top-down drive */
// top-down vehicles (x,y = centre). kind: l90 | 207 | bus | taxi | car | truck | bike
function topCar(c, x, y, kind, color, t, braking) {
	const S = { l90: [15, 27], 207: [14, 24], bus: [17, 52], taxi: [14, 25], car: [14, 25], truck: [17, 44], bike: [6, 14] }[kind];
	const [w, h] = S;
	c.save(); c.translate(x, y);
	c.fillStyle = 'rgba(0,0,0,.28)'; c.beginPath(); c.roundRect(-w / 2 + 2, -h / 2 + 3, w, h, 4); c.fill(); // shadow
	if (kind === 'bike') {
		rr(c, -2, -h / 2, 4, h, 2, '#111827', OL, 1); c.fillStyle = color; c.beginPath(); c.arc(0, -1, 3, 0, 7); c.fill(); c.lineWidth = 1; c.strokeStyle = OL; c.stroke();
		c.restore(); return S;
	}
	rr(c, -w / 2, -h / 2, w, h, kind === 'bus' || kind === 'truck' ? 3 : 5, color, OL, 1.6);
	if (kind === 'truck') { rr(c, -w / 2 + 1.5, -h / 2 + 1.5, w - 3, 11, 2, '#334155', OL, 1); rr(c, -w / 2 + 1, -h / 2 + 15, w - 2, h - 17, 1, '#e5e7eb', OL, 1); }
	else if (kind === 'bus') { for (let i = 0; i < 6; i++) { c.fillStyle = '#1e293b'; c.fillRect(-w / 2 + 2, -h / 2 + 6 + i * 7, 3, 5); c.fillRect(w / 2 - 5, -h / 2 + 6 + i * 7, 3, 5); } rr(c, -w / 2 + 2, -h / 2 + 2, w - 4, 4, 1, '#1e293b', null); c.fillStyle = '#1d4ed8'; c.fillRect(-1.5, -h / 2 + 1, 3, h - 2); }
	else {
		rr(c, -w / 2 + 2, -h / 2 + 5, w - 4, 6, 2, '#1e293b', null); // windscreen
		rr(c, -w / 2 + 2.5, h / 2 - 8, w - 5, 4, 1.5, '#1e293b', null); // rear window
		if (kind === 'taxi') { rr(c, -3, -2, 6, 3, 1, '#111827', null); }
		if (kind === 'l90' || kind === '207') { c.fillStyle = '#121016'; c.beginPath(); c.arc(-2.5, 1, 3.2, 0, 7); c.fill(); } // Korosh's afro through the roof glass
	}
	c.fillStyle = '#fef9c3'; c.fillRect(-w / 2 + 1, -h / 2, 3, 1.5); c.fillRect(w / 2 - 4, -h / 2, 3, 1.5); // headlights
	c.fillStyle = braking ? '#f87171' : '#7f1d1d'; c.fillRect(-w / 2 + 1, h / 2 - 1.5, 3, 1.5); c.fillRect(w / 2 - 4, h / 2 - 1.5, 3, 1.5);
	c.restore();
	return S;
}
const TRAFFIC = [['taxi', '#facc15'], ['car', '#e2e8f0'], ['car', '#94a3b8'], ['car', '#dc2626'], ['car', '#1e40af'], ['truck', '#78716c'], ['bike', '#f97316']];

/**
 * cfg: { title, car, theme: day|night|war|road, lanes, length (px), clock: {start, deadline, rate} | null,
 *        patience: seconds | null, stops: [{at: 0..1, who, color}], bombs: bool, trucks: bool, density, sound, done(ok, pts) }
 */
export function showDrive(host, cfg) {
	const pads = '<button type="button" data-k="left" aria-label="چپ">◀</button><button type="button" data-k="right" aria-label="راست">▶</button><span></span><button type="button" data-k="brake" aria-label="ترمز">🛑</button><button type="button" data-k="gas" aria-label="گاز">⚡</button>';
	const { el, cv, c, fit } = shell(host, 'kr-drive', pads);
	const keys = { left: false, right: false, gas: false, brake: false };
	let raf = 0, closed = false, st, paused = false;
	const W = 320, H = 180, LANES = cfg.lanes || 4, LW = 34, ROAD = LANES * LW, RX = (W - ROAD) / 2;
	const big = cfg.car === 'bus';
	const reset = () => {
		st = { x: RX + ROAD - LW * 1.5, v: 0, dist: 0, t: 0, clock: cfg.clock ? cfg.clock.start : 0, pat: cfg.patience || 0, cars: [], spawn: 0.6, pts: 0, combo: 0, crashes: 0, hp: 3, stops: (cfg.stops || []).map((s) => ({ ...s, got: false, hold: 0, missed: 0 })), msgs: [], shake: 0, bombs: [], bombT: 2, over: false, deco: 0 };
	};
	const VMAX = big ? 150 : 190, CRUISE = big ? 95 : 120, PY = H - 34;
	const say = (t, color = '#fde68a') => st.msgs.push({ t, color, life: 1.6 });
	const laneX = (i) => RX + LW * (i + 0.5);
	const stopNow = () => st.stops.find((s) => !s.got && st.dist / cfg.length >= s.at - 0.04);
	const update = (dt) => {
		st.t += dt;
		// speed: cruise by default, ⚡ to push, 🛑 to stop for a pickup
		const target = keys.brake ? 0 : keys.gas ? VMAX : CRUISE;
		st.v += (target - st.v) * Math.min(1, dt * (keys.brake ? 3 : big ? 0.9 : 1.4));
		const steer = (big ? 70 : 105) * (st.v > 8 ? 1 : 0.4);
		if (keys.left) st.x -= steer * dt; if (keys.right) st.x += steer * dt;
		st.x = clamp(st.x, RX + 8, RX + ROAD - 8);
		st.dist += st.v * dt;
		st.deco = (st.deco + st.v * dt) % 40;
		if (cfg.clock) { st.clock += dt * cfg.clock.rate; if (st.clock >= cfg.clock.deadline) return finish(false, cfg.lateText || 'دیر رسیدی! ⏰'); }
		if (cfg.patience) { st.pat -= dt; if (st.pat <= 0) return finish(false, cfg.lateText || 'حوصله تمام شد…'); }
		// traffic
		st.spawn -= dt;
		if (st.spawn <= 0) {
			st.spawn = (0.55 + Math.random() * 0.7) / (cfg.density || 1);
			const [kind, color] = TRAFFIC[Math.floor(Math.random() * (cfg.trucks ? TRAFFIC.length : TRAFFIC.length - 1))];
			const lane = Math.floor(Math.random() * LANES);
			const sv = kind === 'bike' ? 70 + Math.random() * 40 : 40 + Math.random() * 55;
			if (!st.cars.some((o) => Math.abs(o.x - laneX(lane)) < 10 && o.y < 20)) st.cars.push({ x: laneX(lane) + (kind === 'bike' ? (Math.random() - 0.5) * 14 : 0), y: -40, v: sv, kind, color, passed: false, wob: Math.random() * 6 });
		}
		const [pw, ph] = { l90: [15, 27], 207: [14, 24], bus: [17, 52] }[cfg.car] || [15, 27];
		for (const o of st.cars) {
			o.y += (st.v - o.v) * dt;
			if (o.kind === 'bike') o.x += Math.sin(st.t * 3 + o.wob) * 12 * dt; // bikes weave
			const [ow, oh] = { taxi: [14, 25], car: [14, 25], truck: [17, 44], bike: [6, 14] }[o.kind];
			const dx = Math.abs(o.x - st.x), dy = Math.abs(o.y - PY);
			if (!o.hit && dx < (ow + pw) / 2 - 2 && dy < (oh + ph) / 2 - 2) {
				o.hit = true; st.crashes++; st.v *= 0.25; st.combo = 0; st.shake = 0.4; st.pts = Math.max(0, st.pts - 150);
				cfg.sound && cfg.sound('hurt'); say(cfg.bombs ? 'مراقب باش! 😰' : 'تصادف! 💥', '#fca5a5');
				if (cfg.bombs && --st.hp <= 0) return finish(false, '۲۰۷ از کار افتاد… 💥');
				if (cfg.clock) st.clock += 1;
			}
			// «لایی»: overtake a car with a tight gap → combo bonus
			if (!o.passed && o.y > PY + (oh + ph) / 2) { o.passed = true; if (!o.hit && dx < (ow + pw) / 2 + 9 && st.v > 70) { st.combo++; const b = 60 * Math.min(6, st.combo); st.pts += b; say(`لایی! 🔥 ×${fa(st.combo)}`); cfg.sound && cfg.sound('coin'); } }
		}
		st.cars = st.cars.filter((o) => o.y < H + 60 && o.y > -120);
		// pickups on the right curb: be in the right lane and stop inside the green zone
		const sp = stopNow();
		if (sp) {
			sp.zy = PY - (sp.at - st.dist / cfg.length) * cfg.length; // zone position on screen
			const inZone = Math.abs(sp.zy - PY) < 34 && st.x > RX + ROAD - LW - 2;
			if (inZone && st.v < 22) { sp.hold += dt; if (sp.hold > 0.7) { sp.got = true; st.pts += 300; cfg.sound && cfg.sound('level'); say(`${sp.who} سوار شد ✓`, '#86efac'); } }
			else sp.hold = 0;
			if (sp.zy > H + 40 && !sp.got) { sp.missed++; sp.at = st.dist / cfg.length + 0.07; say(`${sp.who} جا ماند! دور زدی… ⏱`, '#fca5a5'); if (cfg.clock) st.clock += 3; }
		}
		// war: red target circles on the road, then a blast
		if (cfg.bombs) {
			st.bombT -= dt;
			if (st.bombT <= 0) { st.bombT = 1.1 + Math.random() * 1.1; st.bombs.push({ x: laneX(Math.floor(Math.random() * LANES)), y: 10 + Math.random() * 50, t: 1.3 }); }
			for (const b of st.bombs) { b.y += st.v * dt; b.t -= dt; if (b.t <= 0 && !b.done) { b.done = true; st.shake = 0.35; cfg.sound && cfg.sound('boom'); if (Math.abs(b.x - st.x) < 20 && Math.abs(b.y - PY) < 26) { st.v *= 0.3; if (--st.hp <= 0) return finish(false, '۲۰۷ از کار افتاد… 💥'); say('نزدیک بود! 😰', '#fca5a5'); } } }
			st.bombs = st.bombs.filter((b) => b.t > -0.4);
		}
		st.msgs = st.msgs.filter((m) => (m.life -= dt) > 0);
		if (st.shake > 0) st.shake -= dt;
		if (st.dist >= cfg.length) {
			if (st.stops.some((s) => !s.got)) { const m = st.stops.find((s) => !s.got); m.at = st.dist / cfg.length - 0.02; cfg.length += 400; say(`هنوز ${m.who} مانده!`, '#fca5a5'); }
			else finish(true);
		}
	};
	const draw = () => {
		const k = cv.width / W; c.setTransform(k, 0, 0, cv.height / H, 0, 0);
		if (st.shake > 0) c.translate((Math.random() - 0.5) * 4, (Math.random() - 0.5) * 3);
		const night = cfg.theme === 'night' || cfg.theme === 'war';
		// sidewalks + roadside scenery
		c.fillStyle = cfg.theme === 'war' ? '#3f3a36' : night ? '#1e1b2e' : '#a3a3a3'; c.fillRect(0, 0, W, H);
		for (let side = 0; side < 2; side++) for (let i = -1; i < 6; i++) {
			const y = i * 40 + st.deco, x = side ? RX + ROAD + 6 : 4, w = RX - 10;
			c.fillStyle = cfg.theme === 'road' ? '#65a30d' : night ? '#312e81' : '#64748b'; c.fillRect(x, y - 30, w, 26);
			if (cfg.theme !== 'road') for (let j = 0; j < 3; j++) { c.fillStyle = night ? '#fde68a' : '#e2e8f0'; c.fillRect(x + 6 + j * 14, y - 24, 6, 6); }
			c.fillStyle = '#15803d'; c.beginPath(); c.arc(side ? RX + ROAD + 4 : RX - 4, y - 8, 4, 0, 7); c.fill();
		}
		// asphalt + scrolling lane dashes
		c.fillStyle = night ? '#1f2937' : '#374151'; c.fillRect(RX, 0, ROAD, H);
		c.fillStyle = '#f8fafc'; c.fillRect(RX - 2, 0, 2, H); c.fillRect(RX + ROAD, 0, 2, H);
		for (let i = 1; i < LANES; i++) for (let y = -20 + (st.deco % 20); y < H; y += 20) { c.fillStyle = '#e5e7eb'; c.fillRect(RX + i * LW - 1, y, 2, 10); }
		// pickup zone
		const sp = stopNow();
		if (sp && sp.zy !== undefined) {
			c.fillStyle = 'rgba(34,197,94,.35)'; c.fillRect(RX + ROAD - LW, sp.zy - 34, LW, 68); c.strokeStyle = '#22c55e'; c.setLineDash([4, 3]); c.lineWidth = 2; c.strokeRect(RX + ROAD - LW, sp.zy - 34, LW, 68); c.setLineDash([]);
			c.fillStyle = sp.color || '#f472b6'; c.beginPath(); c.arc(RX + ROAD + 8, sp.zy, 5, 0, 7); c.fill(); c.lineWidth = 1.2; c.strokeStyle = OL; c.stroke();
			txt(c, `🛑 ${sp.who}`, RX + ROAD + 8, sp.zy - 14, 7, '#bbf7d0');
			if (sp.hold > 0) { c.fillStyle = '#22c55e'; c.fillRect(RX + ROAD - LW + 3, sp.zy + 28, (LW - 6) * Math.min(1, sp.hold / 0.7), 3); }
		}
		// bombs
		for (const b of st.bombs) {
			if (b.t > 0) { const pl = 1 + Math.sin(st.t * 14) * 0.1; c.beginPath(); c.arc(b.x, b.y, 16 * pl, 0, 7); c.fillStyle = 'rgba(239,68,68,.3)'; c.fill(); c.lineWidth = 1.5; c.strokeStyle = '#ef4444'; c.setLineDash([3, 2]); c.stroke(); c.setLineDash([]); txt(c, '!', b.x, b.y, 10, '#fecaca'); }
			else { const u = -b.t / 0.4; c.beginPath(); c.arc(b.x, b.y, 10 + u * 26, 0, 7); c.fillStyle = `rgba(251,146,60,${1 - u})`; c.fill(); }
		}
		for (const o of st.cars) topCar(c, o.x, o.y, o.kind, o.color, st.t, false);
		if (night) { const g = c.createLinearGradient(0, PY - 90, 0, PY - 10); g.addColorStop(0, 'rgba(254,249,195,0)'); g.addColorStop(1, 'rgba(254,249,195,.28)'); c.fillStyle = g; c.beginPath(); c.moveTo(st.x - 6, PY - 12); c.lineTo(st.x - 22, PY - 90); c.lineTo(st.x + 22, PY - 90); c.lineTo(st.x + 6, PY - 12); c.fill(); }
		topCar(c, st.x, PY, cfg.car, cfg.car === '207' ? '#cbd5e1' : '#f8fafc', st.t, keys.brake);
		if (cfg.theme === 'war') { c.fillStyle = 'rgba(68,64,60,.25)'; c.fillRect(0, 0, W, H); }
		// HUD
		rr(c, 4, 4, 78, 20, 7, 'rgba(2,6,23,.8)', '#fbbf24', 1.2);
		if (cfg.clock) { const m = Math.floor(st.clock), hh = Math.floor(m / 60), mm = m % 60, late = cfg.clock.deadline - st.clock < 6; txt(c, `⏰ ${fa(hh)}:${fa(String(mm).padStart(2, '0'))}`, 43, 14.5, 10, late && Math.floor(st.t * 4) % 2 ? '#fca5a5' : '#fde68a'); }
		else if (cfg.patience) { c.fillStyle = '#ec4899'; c.fillRect(8, 16, 70 * (st.pat / cfg.patience), 4); txt(c, cfg.patienceLabel || 'حوصله', 43, 10, 7, '#fbcfe8', 'center', false); }
		else if (cfg.bombs) txt(c, `🚙 ${'■'.repeat(st.hp)}${'□'.repeat(3 - st.hp)}`, 43, 14.5, 9, '#fde047');
		rr(c, W - 82, 4, 78, 20, 7, 'rgba(2,6,23,.8)', '#38bdf8', 1.2);
		txt(c, `${fa(st.pts)} ⭐`, W - 43, 14.5, 9, '#e0f2fe');
		// progress bar along the left edge of the road + pickup marks
		c.fillStyle = 'rgba(2,6,23,.6)'; c.fillRect(RX - 9, 30, 4, H - 60); c.fillStyle = '#22c55e'; const pr = clamp(st.dist / cfg.length, 0, 1); c.fillRect(RX - 9, 30 + (H - 60) * (1 - pr), 4, (H - 60) * pr);
		for (const s of st.stops) { c.fillStyle = s.got ? '#22c55e' : '#f472b6'; c.beginPath(); c.arc(RX - 7, 30 + (H - 60) * (1 - s.at), 3, 0, 7); c.fill(); }
		txt(c, cfg.goal || '🏁', RX - 7, 24, 8, '#fff', 'center', false);
		if (st.stops.length) txt(c, st.stops.map((s) => (s.got ? '🙂' : '·')).join(' '), W / 2, 12, 9, '#fff');
		st.msgs.forEach((m, i) => txt(c, m.t, W / 2, 40 + i * 13, 10, m.color));
		if (st.t < 3.5) { rr(c, W / 2 - 120, H / 2 - 16, 240, 30, 8, 'rgba(2,6,23,.82)', '#fbbf24', 1.2); txt(c, cfg.hint, W / 2, H / 2 - 1, 8, '#fff', 'center', false); }
	};
	let last = performance.now();
	const tick = (now) => { if (closed) return; const dt = Math.min(0.05, (now - last) / 1000); last = now; if (!st.over && !paused) update(dt); draw(); raf = requestAnimationFrame(tick); };
	const finish = (ok, why) => {
		if (st.over) return; st.over = true;
		const bonus = ok && cfg.clock ? Math.max(0, Math.round(cfg.clock.deadline - st.clock)) * 40 : 0;
		const total = st.pts + bonus + (ok ? 800 : 0);
		cfg.sound && cfg.sound(ok ? 'level' : 'over');
		endScreen(el, ok, ok ? cfg.winText || 'رسیدی! ✓' : why, [ok && cfg.clock ? `زمان رسیدن ${fa(Math.floor(st.clock / 60))}:${fa(String(Math.floor(st.clock) % 60).padStart(2, '0'))}` : '', `امتیاز ${fa(total)} · تصادف ${fa(st.crashes)}`].filter(Boolean),
			() => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, ok ? total : 0); });
	};
	// input
	const KM = { ArrowLeft: 'left', KeyA: 'left', ArrowRight: 'right', KeyD: 'right', ArrowUp: 'gas', KeyW: 'gas', ArrowDown: 'brake', KeyS: 'brake', Space: 'brake' };
	const kd = (e) => { const k = KM[e.code]; if (!k || st.over) return; e.preventDefault(); e.stopPropagation(); keys[k] = true; };
	const ku = (e) => { const k = KM[e.code]; if (k) keys[k] = false; };
	window.addEventListener('keydown', kd, true); window.addEventListener('keyup', ku, true);
	const pd = el.querySelector('.kr-mini__pads');
	const pdn = (e) => { const b = e.target.closest('[data-k]'); if (!b) return; e.preventDefault(); b.setPointerCapture && b.setPointerCapture(e.pointerId); keys[b.dataset.k] = true; b.classList.add('is-on'); };
	const pup = (e) => { const b = e.target.closest('[data-k]'); if (!b) return; keys[b.dataset.k] = false; b.classList.remove('is-on'); };
	pd.addEventListener('pointerdown', pdn); pd.addEventListener('pointerup', pup); pd.addEventListener('pointercancel', pup);
	const onResize = () => fit(); window.addEventListener('resize', onResize);
	const close = () => { if (closed) return; closed = true; cancelAnimationFrame(raf); window.removeEventListener('keydown', kd, true); window.removeEventListener('keyup', ku, true); window.removeEventListener('resize', onResize); el.remove(); };
	reset();
	raf = requestAnimationFrame(tick);
	close.state = () => st; close.keys = keys; close.cfg = cfg;
	close.pause = () => { paused = true; Object.keys(keys).forEach((k) => (keys[k] = false)); }; close.resume = () => { paused = false; last = performance.now(); };
	return close;
}

/* ------------------------------------------------------------------ Yousef's QC sorting storm */
const QC_CATS = [
	{ id: 'ui', key: '←', icon: '🎨', name: 'UI', color: '#a855f7', items: ['دکمه کج شده', 'فاصله‌ها نامرتب', 'رنگ برند اشتباه', 'فونت عوض شده', 'آیکون تار است'] },
	{ id: 'mob', key: '↓', icon: '📱', name: 'موبایل', color: '#0ea5e9', items: ['منو در موبایل باز نمی‌شود', 'عکس از صفحه بیرون زده', 'دکمه خیلی کوچک است', 'اسکرول افقی دارد', 'متن روی هم افتاده'] },
	{ id: 'link', key: '→', icon: '🔗', name: 'لینک و فرم', color: '#f97316', items: ['لینک خراب است', 'فرم ارسال نمی‌شود', 'ریدایرکت اشتباه', 'دکمه پرداخت کار نمی‌کند', 'ایمیل تأیید نمی‌آید'] },
];
const YOUSEF_LINES = { 6: 'یوسف: دیدم تند می‌زنی، منم تندتر می‌فرستم 😏', 14: 'یوسف: اینم ده تای دیگه!', 22: 'یوسف: هنوز داری می‌رسی؟! 😳', 30: 'یوسف: باشه… قبول دارم سریع‌تری 🙌' };
/** cfg: { goal (cards to fix), maxMiss, sound, done(ok, pts) } */
export function showQC(host, cfg) {
	const pads = QC_CATS.map((k, i) => `<button type="button" data-c="${i}" style="--c:${k.color}">${k.icon} ${k.name}</button>`).join('');
	const { el, cv, c, fit } = shell(host, 'kr-qc', pads);
	const GOAL = cfg.goal || 36, MAXM = cfg.maxMiss || 6, W = 320, H = 180, COLX = [70, 160, 250], DROP = 132;
	let raf = 0, closed = false, st, paused = false;
	const reset = () => { st = { t: 0, cards: [], spawn: 1, fixed: 0, miss: 0, combo: 0, best: 0, pts: 0, fly: [], say: null, over: false, rate: 1.5 }; };
	const spawnCard = () => { const ci = Math.floor(Math.random() * 3), cat = QC_CATS[ci]; st.cards.push({ ci, text: cat.items[Math.floor(Math.random() * cat.items.length)], y: 26, x: 160, back: 0 }); };
	const active = () => st.cards.reduce((a, b) => (!a || b.y > a.y ? b : a), null);
	const send = (ci) => {
		if (st.over) return;
		const cd = active(); if (!cd) return;
		if (cd.ci === ci) {
			st.cards.splice(st.cards.indexOf(cd), 1); st.fly.push({ ...cd, tx: COLX[ci], ty: DROP + 18, life: 0.35 });
			st.fixed++; st.combo++; st.best = Math.max(st.best, st.combo); st.pts += 100 + Math.min(10, st.combo) * 20; cfg.sound && cfg.sound('coin');
			if (YOUSEF_LINES[st.fixed]) st.say = { t: YOUSEF_LINES[st.fixed], life: 2.4 };
			if (st.fixed >= GOAL) finish(true);
		} else { cd.back = 0.45; cd.y = Math.max(26, cd.y - 40); st.combo = 0; st.miss++; cfg.sound && cfg.sound('wrong'); st.say = { t: 'یوسف: دوباره QC شد 😏', life: 1.4 }; if (st.miss >= MAXM) finish(false); }
	};
	const update = (dt) => {
		st.t += dt;
		// Yousef speeds up the faster (and the better) you close cards
		st.rate = clamp(1.5 - st.fixed * 0.028 - Math.min(8, st.combo) * 0.03, 0.5, 1.5);
		st.spawn -= dt; if (st.spawn <= 0 && st.cards.length < 4) { st.spawn = st.rate; spawnCard(); }
		const fall = 26 + st.fixed * 1.3;
		for (const cd of st.cards) { cd.y += fall * dt * (cd.back > 0 ? 0 : 1); if (cd.back > 0) cd.back -= dt; }
		const out = st.cards.filter((cd) => cd.y > DROP + 4);
		for (const cd of out) { st.cards.splice(st.cards.indexOf(cd), 1); st.miss++; st.combo = 0; cfg.sound && cfg.sound('hurt'); st.say = { t: 'یوسف: اینو ندیدی! ❌', life: 1.4 }; if (st.miss >= MAXM) return finish(false); }
		st.fly = st.fly.filter((f) => (f.life -= dt) > 0);
		if (st.say && (st.say.life -= dt) <= 0) st.say = null;
	};
	const card = (x, y, cd, a = 1, sc = 1) => {
		const cat = QC_CATS[cd.ci];
		c.save(); c.globalAlpha = a; c.translate(x, y); c.scale(sc, sc); c.rotate(cd.back > 0 ? Math.sin(st.t * 40) * 0.08 : 0);
		rr(c, -46, -12, 92, 24, 4, '#fff', OL, 1.4); c.fillStyle = cat.color; c.fillRect(-46, -12, 5, 24);
		txt(c, `${cat.icon}`, -34, 0, 10, '#fff', 'center', false); txt(c, cd.text, 6, 0, 7, '#0f172a', 'center', false);
		c.restore();
	};
	const draw = () => {
		c.setTransform(cv.width / W, 0, 0, cv.height / H, 0, 0);
		c.fillStyle = '#0369a1'; c.fillRect(0, 0, W, H);
		// Trello-ish board: "QC" lane in the middle top, three «Done» columns at the bottom
		rr(c, 112, 18, 96, DROP - 4, 6, 'rgba(255,255,255,.18)', null);
		txt(c, 'QC 🔍', 160, 12, 8, '#fff', 'center', false);
		QC_CATS.forEach((k, i) => { rr(c, COLX[i] - 42, DROP + 6, 84, 34, 6, `${k.color}`, OL, 1.4); txt(c, `${k.key}  ${k.icon} ${k.name}`, COLX[i], DROP + 23, 8, '#fff'); });
		const cur = active();
		for (const cd of st.cards) card(cd.x, cd.y, cd, cd === cur ? 1 : 0.75, cd === cur ? 1.05 : 0.95);
		if (cur) { c.strokeStyle = '#fde047'; c.lineWidth = 1.5; c.setLineDash([3, 3]); c.strokeRect(112, cur.y - 15, 96, 30); c.setLineDash([]); }
		for (const f of st.fly) { const u = 1 - f.life / 0.35; card(f.x + (f.tx - f.x) * u, f.y + (f.ty - f.y) * u, f, 1 - u * 0.6, 1 - u * 0.5); }
		// Yousef at the left, firing cards
		drawYousef(c, 34, 116, 0.95, st.t, st.say ? Math.abs(Math.sin(st.t * 14)) : 0);
		if (st.say) { c.font = `900 7px ${FF}`; const w2 = c.measureText(st.say.t).width + 12; rr(c, 6, 4, Math.min(150, w2), 15, 7, '#fff', OL, 1.2); txt(c, st.say.t, 6 + Math.min(150, w2) / 2, 11.5, 7, OL, 'center', false); }
		// HUD
		rr(c, 222, 4, 94, 32, 7, 'rgba(2,6,23,.75)', '#fde047', 1.2);
		txt(c, `✓ ${fa(st.fixed)} از ${fa(GOAL)}`, 269, 13, 8, '#bbf7d0', 'center', false);
		txt(c, `❌ ${'●'.repeat(st.miss)}${'○'.repeat(Math.max(0, MAXM - st.miss))}`, 269, 27, 7, '#fecaca', 'center', false);
		if (st.combo > 2) txt(c, `×${fa(st.combo)}`, 160, DROP - 10, 11, '#fde047');
		if (st.t < 3.5) { rr(c, 70, 64, 180, 30, 8, 'rgba(2,6,23,.85)', '#fde047', 1.2); txt(c, 'هر کارت را به ستون درستش بفرست: ← UI · ↓ موبایل · → لینک و فرم', 160, 79, 6.5, '#fff', 'center', false); }
	};
	let last = performance.now();
	const tick = (now) => { if (closed) return; const dt = Math.min(0.05, (now - last) / 1000); last = now; if (!st.over && !paused) update(dt); draw(); raf = requestAnimationFrame(tick); };
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const total = st.pts + (ok ? 1000 : 0);
		endScreen(el, ok, ok ? 'همه QCها بسته شد! ✓' : 'یوسف برنده شد… 😏', [`بسته‌شده ${fa(st.fixed)} · بهترین زنجیره ×${fa(st.best)}`, `امتیاز ${fa(total)}`],
			() => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, ok ? total : 0); });
	};
	const KM = { ArrowLeft: 0, KeyA: 0, ArrowDown: 1, KeyS: 1, ArrowRight: 2, KeyD: 2 };
	const kd = (e) => { if (!(e.code in KM) || st.over || paused) return; e.preventDefault(); e.stopPropagation(); if (!e.repeat) send(KM[e.code]); };
	window.addEventListener('keydown', kd, true);
	el.querySelector('.kr-mini__pads').addEventListener('pointerdown', (e) => { const b = e.target.closest('[data-c]'); if (!b) return; e.preventDefault(); send(Number(b.dataset.c)); });
	const onResize = () => fit(); window.addEventListener('resize', onResize);
	const close = () => { if (closed) return; closed = true; cancelAnimationFrame(raf); window.removeEventListener('keydown', kd, true); window.removeEventListener('resize', onResize); el.remove(); };
	reset();
	raf = requestAnimationFrame(tick);
	close.state = () => st; close.send = send;
	close.pause = () => { paused = true; }; close.resume = () => { paused = false; last = performance.now(); };
	return close;
}

/* ------------------------------------------------------------------ Azizi's desk exam (puzzle) */
const DESK_PICK = [
	['برای فرم تماس چی نصب کنیم؟', ['Gravity Forms', 'فتوشاپ', 'اکسل']],
	['۲۵ هزار مقاله رو چطور یکجا وارد کنیم؟', ['ایمپورت CSV', 'کپی‌پیست دستی', 'فکس']],
	['سایت کند شده؛ اول چی؟', ['کش و فشرده‌سازی عکس', 'عوض کردن رنگ لوگو', 'خاموش‌روشن مانیتور']],
	['زبان اصلی وردپرس چیه؟', ['PHP', 'Swift', 'Excel']],
	['برای سئو چه افزونه‌ای؟', ['Yoast SEO', 'WinRAR', 'Paint']],
	['قالب اختصاصی رو کجا می‌سازیم؟', ['wp-content/themes', 'Desktop', 'سطل زباله']],
	['پنل مدیریت وردپرس کجاست؟', ['wp-admin', 'wp-coffee', 'Ctrl+Alt+Del']],
];
const DESK_ORDER = [
	['مراحل نصب وردپرس را به ترتیب بچین', ['ساخت دیتابیس', 'آپلود فایل‌ها', 'اجرای نصب']],
	['قبل از آپدیت سایت، به ترتیب؟', ['بکاپ کامل', 'تست روی استیج', 'آپدیت سایت اصلی']],
	['انتشار یک مقاله، به ترتیب؟', ['نوشتن متن', 'بهینه‌سازی سئو', 'انتشار']],
];
/** cfg: { rounds = 6, maxMiss = 3, sec = 22, sound, done(ok, pts) } */
export function showDesk(host, cfg) {
	if (typeof document !== 'undefined') FF = getComputedStyle(document.body).fontFamily || FF;
	const ROUNDS = cfg.rounds || 6, MAXM = cfg.maxMiss || 3, SEC = cfg.sec || 22;
	const el = document.createElement('div');
	el.className = 'kr-mini kr-desk';
	el.innerHTML = `<div class="kr-desk__top"><canvas aria-hidden="true"></canvas><p class="kr-desk__q" data-q aria-live="polite"></p></div>
		<div class="kr-desk__meters"><span class="kr-desk__m">اعتماد عزیزی <b data-trust>۰٪</b><i><u data-trustbar></u></i></span><span class="kr-desk__m is-pat">حوصله <b data-pat></b></span></div>
		<div class="kr-desk__time"><i data-time></i></div>
		<div class="kr-desk__table"><div class="kr-desk__laptop" data-laptop>💻 <span data-slot>جواب را انتخاب کن</span></div><div class="kr-desk__cards" data-cards role="group" aria-label="کارت‌های جواب"></div></div>
		<div class="kr-mini__end" hidden></div>`;
	host.appendChild(el);
	const $ = (q) => el.querySelector(q), cv = el.querySelector('canvas'), c = cv.getContext('2d');
	let st, raf = 0, closed = false, paused = false, last = performance.now();
	const fit = () => { const r = cv.getBoundingClientRect(), d = Math.min(2, window.devicePixelRatio || 1); cv.width = Math.round(r.width * d); cv.height = Math.round(r.height * d); };
	const reset = () => {
		const picks = DESK_PICK.slice().sort(() => Math.random() - 0.5).slice(0, ROUNDS - 2).map((q) => ({ type: 'pick', q: q[0], a: q[1] }));
		const orders = DESK_ORDER.slice().sort(() => Math.random() - 0.5).slice(0, 2).map((q) => ({ type: 'order', q: q[0], a: q[1] }));
		const list = picks.concat(orders).sort(() => Math.random() - 0.5);
		st = { list, i: 0, ok: 0, miss: 0, t: SEC, mood: 'meh', moodT: 0, pts: 0, picked: [], over: false, lock: false, clock: 0 };
		round();
	};
	const round = () => {
		const r = st.list[st.i];
		st.t = SEC; st.picked = []; st.lock = false;
		$('[data-q]').textContent = r.q;
		$('[data-slot]').textContent = r.type === 'order' ? 'به ترتیب بزن (اول، دوم، سوم)' : 'جواب درست را بزن';
		const cards = r.a.map((txt, k) => [txt, k]).sort(() => Math.random() - 0.5);
		$('[data-cards]').innerHTML = cards.map(([txt, k], j) => `<button type="button" class="kr-desk__card" data-k="${k}" style="--r:${(j - 1) * 4}deg">${txt}<em></em></button>`).join('');
		meters();
	};
	const meters = () => {
		const tr = Math.round((st.ok / ROUNDS) * 100);
		$('[data-trust]').textContent = `${fa(tr)}٪`; $('[data-trustbar]').style.width = `${tr}%`;
		$('[data-pat]').textContent = `${'❤'.repeat(MAXM - st.miss)}${'·'.repeat(st.miss)}`;
	};
	const judge = (good) => {
		st.lock = true;
		if (good) { st.ok++; st.pts += 300 + Math.round(st.t) * 15; st.mood = 'smile'; cfg.sound && cfg.sound('level'); }
		else { st.miss++; st.mood = 'angry'; cfg.sound && cfg.sound('wrong'); }
		st.moodT = 1.2; meters();
		$('[data-slot]').textContent = good ? 'آقای عزیزی: درسته! ✓' : 'آقای عزیزی: نه! 😠';
		setTimeout(() => {
			if (closed) return;
			if (st.miss >= MAXM) return finish(false);
			st.i++; if (st.i >= st.list.length) return finish(true);
			round();
		}, 900);
	};
	const pick = (btn) => {
		if (!btn || st.lock || st.over || paused || btn.disabled) return;
		const r = st.list[st.i], k = Number(btn.dataset.k);
		if (r.type === 'pick') { btn.classList.add('is-sent'); el.querySelectorAll('.kr-desk__card').forEach((b) => (b.disabled = true)); judge(k === 0); return; }
		st.picked.push(k); btn.disabled = true; btn.querySelector('em').textContent = fa(st.picked.length); btn.classList.add('is-num');
		cfg.sound && cfg.sound('click');
		if (st.picked.length === r.a.length) judge(st.picked.every((v, j) => v === j));
	};
	el.addEventListener('click', (e) => pick(e.target.closest('.kr-desk__card')));
	const kd = (e) => { const n = '123۱۲۳'.indexOf(e.key); if (n < 0 || st.over || paused) return; e.preventDefault(); e.stopPropagation(); pick(el.querySelectorAll('.kr-desk__card')[n % 3]); };
	window.addEventListener('keydown', kd, true);
	const draw = (t) => {
		const w = cv.width, h = cv.height, k = h / 70;
		c.setTransform(1, 0, 0, 1, 0, 0);
		const g = c.createLinearGradient(0, 0, 0, h); g.addColorStop(0, '#ccfbf1'); g.addColorStop(1, '#99f6e4'); c.fillStyle = g; c.fillRect(0, 0, w, h);
		c.fillStyle = 'rgba(15,118,110,.15)'; for (let i = 0; i < 6; i++) c.fillRect((i * w) / 6 + 6 * k, 6 * k, w / 9, 30 * k); // office windows
		const mood = st.moodT > 0 ? st.mood : 'meh';
		// big bust behind his desk (he sits across from you), desk edge in front of him
		const bk = k * 3.1;
		c.save(); c.translate(w * 0.5, h + 15 * bk); c.scale(bk, bk); drawPerson(c, 0, 0, 1, 'azizi', { mood, name: '' }, t); c.restore();
		c.fillStyle = '#5b3a1a'; c.fillRect(0, h - 7 * k, w, 7 * k); c.fillStyle = '#7c4f25'; c.fillRect(0, h - 7 * k, w, 2 * k);
		if (st.moodT > 0 && st.mood === 'angry') { c.fillStyle = 'rgba(220,38,38,.12)'; c.fillRect(0, 0, w, h); }
	};
	const tick = (now) => {
		if (closed) return;
		const dt = Math.min(0.05, (now - last) / 1000); last = now;
		if (!st.over && !paused) {
			st.clock += dt;
			if (st.moodT > 0) st.moodT -= dt;
			if (!st.lock) { st.t -= dt; if (st.t <= 0) { st.t = 0; judge(false); } }
			$('[data-time]').style.transform = `scaleX(${Math.max(0, st.t / SEC)})`;
		}
		draw(st.clock);
		raf = requestAnimationFrame(tick);
	};
	const finish = (ok) => {
		if (st.over) return; st.over = true; cfg.sound && cfg.sound(ok ? 'level' : 'over');
		const total = st.pts + (ok ? 1500 : 0);
		endScreen(el, ok, ok ? 'آقای عزیزی: قبول! به تو اعتماد دارم ✓' : 'آقای عزیزی: هنوز آماده نیستی…', [`جواب درست ${fa(st.ok)} از ${fa(ROUNDS)}`, `امتیاز ${fa(total)}`],
			() => { el.querySelector('.kr-mini__end').hidden = true; reset(); }, () => { close(); cfg.done && cfg.done(ok, ok ? total : 0); });
	};
	const onResize = () => fit(); window.addEventListener('resize', onResize);
	const close = () => { if (closed) return; closed = true; cancelAnimationFrame(raf); window.removeEventListener('keydown', kd, true); window.removeEventListener('resize', onResize); el.remove(); };
	fit(); reset();
	raf = requestAnimationFrame(tick);
	close.state = () => st; close.pick = (i) => pick(el.querySelectorAll('.kr-desk__card')[i]);
	close.pause = () => { paused = true; }; close.resume = () => { paused = false; last = performance.now(); };
	return close;
}
