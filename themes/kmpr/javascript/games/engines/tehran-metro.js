/**
 * «کوروش در مترو تهران» — endless side-view runner in the metro tunnels. Starring the photo-based Korosh sprite.
 * Jump turnstiles and crates, slide under low signs, collect metro tickets; ☕ coffee = one-hit shield.
 * Speed rises every station (real Tehran Line 1 / Line 4 station names). One hit without coffee = run over.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas } from '../lib/canvas.js';
import { fa, listeners, rand } from '../lib/util.js';
import { drawHero, OL } from '../lib/hero.js';

export const achievements = CATALOG['tehran-metro'].achievements;
const VH = 200, GROUND = 168, G = 0.55;
const STATIONS = ['تجریش', 'قیطریه', 'شهید صدر', 'میرداماد', 'شهید بهشتی', 'هفت تیر', 'طالقانی', 'دروازه دولت', 'امام خمینی', 'میدان آزادی', 'انقلاب', 'دانشگاه', 'صادقیه', 'تئاتر شهر', 'میدان ولیعصر', 'کهریزک'];
let current = null;

export function mount(stage, api) {
	api.setRatio('16/9', '16/9');
	const L = listeners();
	const cv = createCanvas(stage, { vh: VH });
	const { ctx, view } = cv;
	cv.canvas.style.touchAction = 'none';
	let s = null, raf = 0, last = 0, touchY = null;
	const P = { x: 46, w: 13, h: 32, hs: 22 };

	const reset = () => {
		s = { t: 0, dist: 0, speed: 3.2, y: GROUND - P.h, vy: 0, ground: true, slide: 0, coffee: false, inv: 0, obs: [], tickets: [], parts: [], nextObs: 60, nextTk: 30, score: 0, tk: 0, station: 0, banner: { t: `ایستگاه ${STATIONS[0]}`, life: 2 }, over: false, jumpBuf: 0, anim: 0 };
	};
	const jump = () => { if (!s || s.over) return; s.jumpBuf = 0.15; };
	const slide = () => { if (!s || s.over) return; if (s.ground) s.slide = 0.7; else s.vy = Math.max(s.vy, 6); };

	const spawn = () => {
		const r = Math.random(), x = view.vw + 30;
		if (r < 0.38) s.obs.push({ k: 'gate', x, y: GROUND - 26, w: 22, h: 26 }); // turnstile → jump
		else if (r < 0.62) s.obs.push({ k: 'sign', x, y: GROUND - 58, w: 46, h: 22 }); // low sign → slide
		else if (r < 0.82) s.obs.push({ k: 'crate', x, y: GROUND - 20, w: 22, h: 20 });
		else { s.obs.push({ k: 'crate', x, y: GROUND - 20, w: 22, h: 20 }); s.obs.push({ k: 'crate', x: x + 22, y: GROUND - 40, w: 22, h: 20 }); }
		if (Math.random() < 0.08 && !s.coffee) s.tickets.push({ x: x + 90, y: GROUND - 70, coffee: true });
	};
	const hit = () => {
		if (s.inv > 0) return;
		if (s.coffee) { s.coffee = false; s.inv = 1.2; api.sfx('hurt'); s.banner = { t: 'قهوه نجاتت داد! ☕', life: 1.2 }; return; }
		s.over = true;
		api.sfx('over');
		cancelAnimationFrame(raf);
		api.over({ score: s.score, meta: { summary: `${fa(Math.floor(s.dist / 10))} متر تا ایستگاه ${STATIONS[s.station % STATIONS.length]} · ${fa(s.tk)} بلیت` } });
	};

	const update = (f) => {
		s.t += f;
		s.speed = Math.min(8, 3.2 + s.dist / 2500);
		const dx = s.speed * f;
		s.dist += dx;
		s.score = Math.floor(s.dist / 4) + s.tk * 50;
		api.setScore(s.score);
		const st = Math.floor(s.dist / 1800);
		if (st > s.station) {
			s.station = st;
			s.banner = { t: `ایستگاه ${STATIONS[st % STATIONS.length]} 🚇`, life: 1.8 };
			api.sfx('level');
			if (st === 3) api.unlock('st3');
			if (st === 8) api.unlock('st8');
		}
		if (s.banner) s.banner.life -= f / 60;
		if (s.inv > 0) s.inv -= f / 60;
		// player
		if (s.jumpBuf > 0) s.jumpBuf -= f / 60;
		if (s.jumpBuf > 0 && s.ground) { s.vy = -8.6; s.ground = false; s.slide = 0; s.jumpBuf = 0; api.sfx('jump'); }
		s.vy += G * f;
		s.y += s.vy * f;
		const h = s.slide > 0 ? P.hs : P.h;
		if (s.y >= GROUND - h) { s.y = GROUND - h; s.vy = 0; s.ground = true; }
		if (s.slide > 0) s.slide -= f / 60;
		s.anim += s.speed * f * 0.05;
		// world
		s.nextObs -= dx;
		if (s.nextObs <= 0) { spawn(); s.nextObs = rand(150, 260) + 40 / Math.max(1, s.speed - 2); }
		s.nextTk -= dx;
		if (s.nextTk <= 0) {
			const y = Math.random() < 0.5 ? GROUND - 30 : GROUND - 80;
			for (let i = 0; i < 4; i++) s.tickets.push({ x: view.vw + 20 + i * 22, y });
			s.nextTk = rand(220, 380);
		}
		const py = s.y, ph = h, px = P.x;
		s.obs = s.obs.filter((o) => {
			o.x -= dx;
			if (px + P.w - 2 > o.x && px + 2 < o.x + o.w && py + ph - 2 > o.y && py + 2 < o.y + o.h) {
				// standing on a crate is fine
				if (o.k === 'crate' && s.vy >= 0 && py + ph - o.y < 10) { s.y = o.y - ph; s.vy = 0; s.ground = true; }
				else hit();
			}
			return o.x + o.w > -10;
		});
		s.tickets = s.tickets.filter((t) => {
			t.x -= dx;
			if (Math.abs(t.x - (px + 6)) < 14 && Math.abs(t.y - (py + ph / 2)) < ph / 2 + 8) {
				if (t.coffee) { s.coffee = true; api.sfx('level'); api.unlock('coffee'); s.banner = { t: 'قهوه: یک بار محافظت ☕', life: 1.2 }; }
				else { s.tk++; api.sfx('coin'); if (s.tk === 50) api.unlock('tickets50'); }
				return false;
			}
			return t.x > -20;
		});
		if (s.score >= 5000) api.unlock('s5k');
	};

	const R = (x, y, w, h, c) => { ctx.fillStyle = c; ctx.fillRect(Math.round(x), Math.round(y), w, h); };
	const rr = (x, y, w, h, r, fill, stroke = OL, lw = 1.5) => { ctx.beginPath(); ctx.roundRect(x, y, w, h, r); ctx.fillStyle = fill; ctx.fill(); if (stroke) { ctx.lineWidth = lw; ctx.strokeStyle = stroke; ctx.stroke(); } };
	const text = (t, x, y, size, color, align = 'center') => { ctx.font = `900 ${size}px yekanBakh, Tahoma`; ctx.textAlign = align; ctx.lineWidth = 3; ctx.strokeStyle = OL; ctx.strokeText(t, x, y); ctx.fillStyle = color; ctx.fillText(t, x, y); };

	const draw = () => {
		cv.reset();
		const off = s ? s.dist : 0;
		// tunnel
		const g = ctx.createLinearGradient(0, 0, 0, VH);
		g.addColorStop(0, '#0b1220'); g.addColorStop(0.6, '#1e293b'); g.addColorStop(1, '#0f172a');
		ctx.fillStyle = g; ctx.fillRect(0, 0, view.vw, VH);
		// tiled wall with line colour stripe (Line 1 red)
		for (let x = -((off * 0.5) % 40); x < view.vw; x += 40) { R(x, 30, 39, 100, '#e2e8f0'); R(x, 30, 1, 100, '#cbd5e1'); }
		R(0, 92, view.vw, 8, '#dc2626');
		// station sign panels
		for (let x = -((off * 0.5) % 360); x < view.vw; x += 360) {
			rr(x + 120, 44, 92, 26, 4, '#1d4ed8');
			text(s ? STATIONS[(s.station + (x > view.vw / 2 ? 1 : 0)) % STATIONS.length] : 'تجریش', x + 166, 62, 10, '#fff');
		}
		// ceiling lights streaks
		for (let x = -((off * 1.2) % 120); x < view.vw; x += 120) { R(x, 18, 50, 4, '#fde68a'); }
		// platform floor + yellow safety line
		R(0, GROUND, view.vw, VH - GROUND, '#475569');
		R(0, GROUND, view.vw, 4, '#facc15');
		for (let x = -(off % 24); x < view.vw; x += 24) R(x, GROUND + 4, 12, 2, '#1e293b');
		if (!s) return;
		// tickets
		s.tickets.forEach((t) => {
			if (t.coffee) { rr(t.x - 6, t.y - 6, 12, 13, 2, '#fff'); R(t.x - 5, t.y - 2, 10, 3, '#b45309'); return; }
			const wob = Math.sin((s.t + t.x) / 8) * 2;
			rr(t.x - 7, t.y - 5 + wob, 14, 10, 2, '#22c55e'); R(t.x - 4, t.y - 2 + wob, 8, 2, '#fff');
		});
		// obstacles
		s.obs.forEach((o) => {
			if (o.k === 'gate') { rr(o.x, o.y, o.w, o.h, 3, '#94a3b8'); R(o.x + 3, o.y + 8, o.w - 6, 4, '#ef4444'); R(o.x + o.w / 2 - 1, o.y - 6, 2, 6, OL); }
			else if (o.k === 'sign') { R(o.x + 4, 0, 2, o.y, '#64748b'); R(o.x + o.w - 6, 0, 2, o.y, '#64748b'); rr(o.x, o.y, o.w, o.h, 3, '#fbbf24'); text('⚠ خم شو', o.x + o.w / 2, o.y + 15, 8, '#7c2d12'); }
			else { rr(o.x, o.y, o.w, o.h, 2, '#b45309'); R(o.x + 2, o.y + o.h / 2, o.w - 4, 2, '#78350f'); }
		});
		// hero (pixel-art Korosh) — sliding = small crouched sprite
		const sliding = s.slide > 0;
		const h = sliding ? P.hs : P.h;
		if (!(s.inv > 0 && Math.floor(s.t / 4) % 2)) {
			const frame = !s.ground ? 'jump' : ['run1', 'stand', 'run2', 'stand'][Math.floor(s.anim) % 4];
			drawHero(ctx, P.x + P.w / 2, s.y, sliding ? 'small' : 'big', s.coffee ? 'coffee' : 'none', sliding ? 'jump' : frame, 1);
			if (s.coffee) { ctx.strokeStyle = 'rgba(251,191,36,.8)'; ctx.lineWidth = 2; ctx.beginPath(); ctx.ellipse(P.x + 6, s.y + h / 2, 14, h / 2 + 5, 0, 0, 7); ctx.stroke(); }
		}
		void h;
		// HUD
		text(`🎫 ${fa(s.tk)}`, view.vw - 10, 14, 10, '#bbf7d0', 'right');
		text(`${fa(Math.floor(s.dist / 10))} متر`, 10, 14, 10, '#fff', 'left');
		if (s.banner && s.banner.life > 0) { ctx.globalAlpha = Math.min(1, s.banner.life * 1.5); rr(view.vw / 2 - 90, 108, 180, 24, 8, 'rgba(2,6,23,.8)', '#facc15'); text(s.banner.t, view.vw / 2, 124, 11, '#fde68a'); ctx.globalAlpha = 1; }
		if (s.t < 240) text(api.touch ? 'ضربه = پرش · کشیدن به پایین = سُر خوردن' : 'Space / ↑ پرش · ↓ سُر خوردن', view.vw / 2, 150, 9, '#cbd5e1');
	};

	const tick = (now) => {
		if (!s || s.over) return;
		const f = Math.min(3, ((now - last) / 1000) * 60);
		last = now;
		update(f);
		if (!s.over) { draw(); raf = requestAnimationFrame(tick); }
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };

	L.on(window, 'keydown', (e) => {
		if (!api.running || !s) return;
		if (['Space', 'ArrowUp', 'KeyW'].includes(e.code)) { e.preventDefault(); jump(); }
		if (['ArrowDown', 'KeyS'].includes(e.code)) { e.preventDefault(); slide(); }
	});
	L.on(cv.canvas, 'pointerdown', (e) => { touchY = e.clientY; });
	L.on(cv.canvas, 'pointerup', (e) => {
		if (touchY === null) return;
		const dy = e.clientY - touchY;
		touchY = null;
		if (dy > 24) slide(); else jump();
	});

	draw();
	const inst = {
		start() { reset(); api.setScore(0); loop(); },
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); cv.destroy(); },
	};
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
