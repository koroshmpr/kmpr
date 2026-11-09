/**
 * «پرتاب سه‌امتیازی» — 2D basketball, pull back from the ball and release (slingshot) with a short aim preview.
 * Solo: 60s, shoot as many as you can (swish = 3, rim-in = 2). 2–4 players: 6 shots each, turns alternate on one device.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas } from '../lib/canvas.js';
import { fa, listeners, rand, clamp } from '../lib/util.js';

export const achievements = CATALOG.basket.achievements;
const VH = 540, G = 1500, BR = 15, RIM = 34, SOLO_T = 60, SHOTS = 6;
const COLORS = ['#f59e0b', '#38bdf8', '#f43f5e', '#22c55e'];
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	const cv = createCanvas(stage, { vh: VH });
	const { ctx, view } = cv;
	cv.canvas.style.touchAction = 'none';
	const pick = document.createElement('div');
	pick.className = 'bk-pick';
	pick.hidden = true;
	pick.innerHTML = `<p class="bk-pick__t">چند نفر بازی می‌کنید؟</p><div class="bk-pick__row">${[1, 2, 3, 4].map((n) => `<button type="button" data-n="${n}">${n === 1 ? 'تک‌نفره · ۶۰ ثانیه' : `${fa(n)} نفره`}</button>`).join('')}</div><p class="bk-pick__h">چند نفره: هر نفر ${fa(SHOTS)} پرتاب، نوبتی روی همین دستگاه</p>`;
	stage.appendChild(pick);
	let s = null, raf = 0, last = 0, drag = null;

	const hoop = () => ({ x: view.vw - 90 + (s ? Math.sin(s.t * s.hm) * s.ha : 0), y: 210 });
	const ballHome = () => ({ x: Math.max(110, Math.min(170, view.vw * 0.22)), y: VH - 110 });
	const newBall = () => {
		const h = ballHome();
		s.ball = { x: h.x + rand(-10, 25), y: h.y, vx: 0, vy: 0, live: false, rim: false, scored: false, t: 0 };
	};
	const begin = (n) => {
		pick.hidden = true;
		s = { n, t: 0, left: SOLO_T, turn: 0, shots: Array(n).fill(0), pts: Array(n).fill(0), streak: 0, hm: 0, ha: 0, over: false, msg: '', msgT: 0 };
		api.setScore(0);
		newBall();
		hud();
		loop();
	};
	const hud = () => {
		if (s.n === 1) api.hud({ extra: fa(Math.max(0, Math.ceil(s.left))), extraLabel: 'زمان' });
		else api.hud({ level: `بازیکن ${fa(s.turn + 1)}`, levelLabel: 'نوبت', extra: `${fa(s.shots[s.turn])}/${fa(SHOTS)}`, extraLabel: 'پرتاب' });
	};
	const say = (m) => { s.msg = m; s.msgT = 1.1; };
	const shotDone = () => {
		const b = s.ball;
		if (!b.scored) { s.streak = 0; if (s.n === 1) say('نشد!'); }
		if (s.n > 1) {
			s.shots[s.turn]++;
			if (s.shots.every((x) => x >= SHOTS)) return finish();
			s.turn = (s.turn + 1) % s.n;
			while (s.shots[s.turn] >= SHOTS) s.turn = (s.turn + 1) % s.n;
		}
		const made = s.pts.reduce((a, c) => a + c, 0);
		s.hm = made > 6 ? 0.9 : 0;
		s.ha = made > 6 ? Math.min(70, made * 3) : 0;
		hud();
		newBall();
	};
	const finish = () => {
		s.over = true;
		cancelAnimationFrame(raf);
		const best = Math.max(...s.pts);
		if (s.n === 1) return api.over({ score: s.pts[0] * 100, meta: { summary: `${fa(s.pts[0])} امتیاز در ۶۰ ثانیه` } });
		const winners = s.pts.map((p, i) => (p === best ? i + 1 : 0)).filter(Boolean);
		const table = s.pts.map((p, i) => `بازیکن ${fa(i + 1)}: ${fa(p)}`).join(' · ');
		api.over({ score: best * 100, meta: { result: 'win', summary: `${winners.length > 1 ? 'مساوی بین بازیکن ' + winners.map(fa).join(' و ') : 'برنده: بازیکن ' + fa(winners[0])} — ${table}` } });
	};
	const step = (dt) => {
		s.t += dt;
		if (s.n === 1) {
			s.left -= dt;
			if (Math.ceil(s.left) !== Math.ceil(s.left + dt)) hud();
			if (s.left <= 0 && !s.ball.live) return finish();
		}
		s.msgT -= dt;
		const b = s.ball;
		if (!b.live) return;
		b.t += dt;
		const sub = 4;
		for (let k = 0; k < sub; k++) {
			const d = dt / sub;
			b.vy += G * d;
			const py = b.y;
			b.x += b.vx * d;
			b.y += b.vy * d;
			const h = hoop();
			// rim points (front / back) and backboard
			for (const rx of [h.x - RIM, h.x + RIM]) {
				const dx = b.x - rx, dy = b.y - h.y, dd = Math.hypot(dx, dy);
				if (dd < BR + 3) {
					const nx = dx / dd, ny = dy / dd, rv = b.vx * nx + b.vy * ny;
					b.x = rx + nx * (BR + 3); b.y = h.y + ny * (BR + 3);
					if (rv < 0) { b.vx -= 1.6 * rv * nx; b.vy -= 1.6 * rv * ny; }
					if (!b.rim) api.sfx('click');
					b.rim = true;
				}
			}
			const bx = h.x + RIM + 14;
			if (b.x + BR > bx && b.x < bx + 10 && b.y > h.y - 110 && b.y < h.y + 10) { b.x = bx - BR; b.vx = -Math.abs(b.vx) * 0.55; b.rim = true; }
			if (!b.scored && py < h.y && b.y >= h.y && b.vy > 0 && Math.abs(b.x - h.x) < RIM - 4) {
				b.scored = true;
				const p = b.rim ? 2 : 3;
				s.pts[s.turn] += p;
				s.streak++;
				api.sfx(p === 3 ? 'level' : 'coin');
				say(p === 3 ? 'تور! +۳' : '+۲');
				api.unlock('first');
				if (p === 3) api.unlock('swish');
				if (s.streak === 5) api.unlock('streak5');
				if (s.n === 1) { api.setScore(s.pts[0] * 100); if (s.pts[0] >= 40) api.unlock('p40'); }
				else api.setScore(Math.max(...s.pts) * 100);
			}
			if (b.y > VH - BR) { b.y = VH - BR; b.vy *= -0.5; b.vx *= 0.8; }
		}
		if (b.x < -60 || b.x > view.vw + 60 || b.t > 3.2 || (b.y >= VH - BR - 1 && Math.abs(b.vy) < 60)) shotDone();
	};
	const aimV = () => ({ vx: clamp((drag.sx - drag.x) * 6.5, -1300, 1700), vy: clamp((drag.sy - drag.y) * 6.5, -1900, 600) });
	const draw = () => {
		cv.reset();
		const g = ctx.createLinearGradient(0, 0, 0, VH);
		g.addColorStop(0, '#0b1023'); g.addColorStop(1, '#1e1b4b');
		ctx.fillStyle = g; ctx.fillRect(0, 0, view.vw, VH);
		ctx.fillStyle = '#7c2d12'; ctx.fillRect(0, VH - 14, view.vw, 14);
		if (!s) return;
		const h = hoop();
		ctx.fillStyle = 'rgba(255,255,255,.9)'; ctx.fillRect(h.x + RIM + 14, h.y - 110, 8, 120);
		ctx.strokeStyle = '#94a3b8'; ctx.lineWidth = 2;
		ctx.beginPath(); for (let i = 0; i <= 4; i++) { ctx.moveTo(h.x - RIM + (i * RIM) / 2, h.y); ctx.lineTo(h.x - RIM * 0.6 + (i * RIM * 0.6) / 2, h.y + 42); } ctx.stroke();
		ctx.strokeStyle = '#f97316'; ctx.lineWidth = 5;
		ctx.beginPath(); ctx.moveTo(h.x - RIM, h.y); ctx.lineTo(h.x + RIM + 14, h.y); ctx.stroke();
		const b = s.ball;
		if (drag && !b.live) {
			const v = aimV();
			ctx.fillStyle = 'rgba(255,255,255,.5)';
			for (let i = 1; i <= 7; i++) { const t = i * 0.045; ctx.beginPath(); ctx.arc(b.x + v.vx * t, b.y + v.vy * t + 0.5 * G * t * t, 3, 0, Math.PI * 2); ctx.fill(); }
		}
		ctx.fillStyle = COLORS[s.turn % 4];
		ctx.beginPath(); ctx.arc(b.x, b.y, BR, 0, Math.PI * 2); ctx.fill();
		ctx.strokeStyle = 'rgba(0,0,0,.45)'; ctx.lineWidth = 2;
		ctx.beginPath(); ctx.moveTo(b.x - BR, b.y); ctx.lineTo(b.x + BR, b.y); ctx.moveTo(b.x, b.y - BR); ctx.lineTo(b.x, b.y + BR); ctx.stroke();
		ctx.textAlign = 'center';
		ctx.fillStyle = '#e2e8f0';
		ctx.font = '700 15px yekanBakh, Tahoma';
		if (s.n > 1) ctx.fillText(s.pts.map((p, i) => `${i === s.turn ? '▶ ' : ''}ب${fa(i + 1)}: ${fa(p)}`).join('   '), view.vw / 2, 34);
		else ctx.fillText(`${fa(s.pts[0])} امتیاز`, view.vw / 2, 34);
		if (s.msgT > 0) { ctx.font = '900 34px yekanBakh, Tahoma'; ctx.fillStyle = '#fbbf24'; ctx.fillText(s.msg, view.vw / 2, 120); }
		if (drag && !b.live) {
			// pull indicator: line from the touch start to the finger
			ctx.strokeStyle = 'rgba(251,191,36,.6)'; ctx.lineWidth = 3; ctx.setLineDash([6, 6]);
			ctx.beginPath(); ctx.moveTo(drag.sx, drag.sy); ctx.lineTo(drag.x, drag.y); ctx.stroke(); ctx.setLineDash([]);
		}
		if (!b.live && !drag && s.t < 5) { ctx.font = '700 14px yekanBakh, Tahoma'; ctx.fillStyle = '#94a3b8'; ctx.fillText('هر جای صفحه را بگیر، به عقب بکش و رها کن', view.vw / 2, VH - 40); }
	};
	const tick = (now) => {
		if (!s || s.over) return;
		step(Math.min(1 / 30, (now - last) / 1000));
		last = now;
		draw();
		if (!s.over) raf = requestAnimationFrame(tick);
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };
	L.on(cv.canvas, 'pointerdown', (e) => {
		if (!s || s.over || s.ball.live) return;
		// Aim from anywhere on the court: pull back from where the finger lands (no screen-edge limit).
		const w = cv.toWorld(e.clientX, e.clientY);
		drag = { sx: w.x, sy: w.y, x: w.x, y: w.y, id: e.pointerId };
		cv.canvas.setPointerCapture(e.pointerId);
	});
	L.on(cv.canvas, 'pointermove', (e) => { if (drag && e.pointerId === drag.id) { const w = cv.toWorld(e.clientX, e.clientY); drag.x = w.x; drag.y = w.y; } });
	L.on(cv.canvas, 'pointercancel', () => (drag = null));
	L.on(cv.canvas, 'pointerup', (e) => {
		if (!drag || e.pointerId !== drag.id) return;
		const v = aimV();
		drag = null;
		if (Math.hypot(v.vx, v.vy) < 200) return;
		Object.assign(s.ball, { vx: v.vx, vy: v.vy, live: true });
		api.sfx('jump');
	});
	L.on(pick, 'click', (e) => { const b = e.target.closest('[data-n]'); if (b) begin(+b.dataset.n); });
	cv.fit();
	s = { n: 1, t: 0, turn: 0, pts: [0], hm: 0, ha: 0, msgT: 0, ball: null };
	newBall();
	draw();
	s = null;
	const inst = {
		start() { cancelAnimationFrame(raf); s = null; draw(); pick.hidden = false; pick.querySelector('button').focus({ preventScroll: true }); },
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); cv.destroy(); pick.remove(); },
	};
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
