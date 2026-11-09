/**
 * «ماهیگیری خزر» — calm fishing on the Caspian Sea. Hold to lower the hook, release to stop; when a fish bites,
 * tap while the needle is in the green zone to land it (miss twice and it escapes). Rare: sturgeon (خاویار).
 * 90 s per trip. Junk (boots) costs time.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas } from '../lib/canvas.js';
import { fa, listeners, rand } from '../lib/util.js';

export const achievements = CATALOG['caspian-fishing'].achievements;
const VH = 320, OL = '#16120f', SURF = 92, TIME = 90;
const FISH = [
	{ name: 'کیلکا', pts: 40, c: '#cbd5e1', w: 14, sp: 1.4, d: [100, 170], zone: 0.34 },
	{ name: 'ماهی سفید', pts: 120, c: '#e2e8f0', w: 24, sp: 1.0, d: [140, 230], zone: 0.26 },
	{ name: 'کپور', pts: 180, c: '#d97706', w: 28, sp: 0.8, d: [190, 270], zone: 0.22 },
	{ name: 'قزل‌آلا', pts: 260, c: '#f472b6', w: 24, sp: 1.6, d: [170, 260], zone: 0.18 },
	{ name: 'تاس‌ماهی (خاویار)', pts: 900, c: '#64748b', w: 44, sp: 0.6, d: [260, 300], zone: 0.12, rare: true },
	{ name: 'پوتین کهنه', pts: -1, c: '#78350f', w: 16, sp: 0.4, d: [250, 300], zone: 0.5, junk: true },
];
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	const cv = createCanvas(stage, { vh: VH });
	const { ctx, view } = cv;
	cv.canvas.style.touchAction = 'none';
	let s = null, raf = 0, last = 0, holding = false;
	const boatX = () => view.vw / 2;
	const reset = () => { s = { t: 0, left: TIME, hookY: SURF + 8, fish: [], caught: [], bite: null, needle: 0, nd: 1, tries: 0, score: 0, over: false, msg: null }; for (let i = 0; i < 6; i++) spawnFish(); };
	const spawnFish = () => {
		const r = Math.random();
		const kind = r < 0.32 ? FISH[0] : r < 0.55 ? FISH[1] : r < 0.72 ? FISH[2] : r < 0.88 ? FISH[3] : r < 0.95 ? FISH[5] : FISH[4];
		const dir = Math.random() < 0.5 ? 1 : -1;
		s.fish.push({ k: kind, x: dir > 0 ? -40 : view.vw + 40, y: rand(kind.d[0], kind.d[1]), dir, wob: Math.random() * 6 });
	};
	const say = (t, c = '#fde68a') => (s.msg = { t, c, life: 1.4 });
	const tap = () => {
		if (!s || s.over) return;
		if (!s.bite) return;
		const z = s.bite.k.zone, inZone = Math.abs(s.needle) < z;
		if (inZone) {
			const k = s.bite.k;
			if (k.junk) { s.left -= 5; say('یک پوتین کهنه! −۵ ثانیه 👢', '#fca5a5'); api.sfx('wrong'); }
			else { s.score += k.pts; s.caught.push(k.name); api.setScore(s.score); say(`${k.name}! +${fa(k.pts)}`); api.sfx(k.rare ? 'level' : 'coin'); if (s.caught.length === 1) api.unlock('first'); if (k.rare) api.unlock('caviar'); if (s.caught.length === 15) api.unlock('fifteen'); }
			s.bite = null; s.hookY = SURF + 8;
		} else if (++s.tries >= 2) { say('ماهی در رفت…', '#93c5fd'); s.bite = null; api.sfx('hurt'); }
		else api.sfx('click');
	};
	const update = (dt) => {
		s.t += dt;
		s.left -= dt;
		if (s.msg) s.msg.life -= dt;
		if (s.left <= 0) { s.over = true; cancelAnimationFrame(raf); return api.over({ score: Math.max(0, s.score), meta: { summary: `${fa(s.caught.length)} ماهی صید شد` } }); }
		if (s.bite) {
			s.needle += s.nd * dt * (1.6 + s.bite.k.sp);
			if (Math.abs(s.needle) > 1) { s.nd *= -1; s.needle = Math.sign(s.needle); }
			s.bite.x = boatX() + Math.sin(s.t * 9) * 6; s.bite.y = s.hookY + 4;
			return;
		}
		if (holding) s.hookY = Math.min(VH - 14, s.hookY + 90 * dt);
		s.fish.forEach((f) => { f.x += f.dir * f.k.sp * 40 * dt; f.y += Math.sin(s.t * 2 + f.wob) * 0.2; });
		s.fish = s.fish.filter((f) => f.x > -60 && f.x < view.vw + 60);
		while (s.fish.length < 6) spawnFish();
		if (s.hookY > SURF + 20) for (const f of s.fish) if (Math.abs(f.x - boatX()) < f.k.w / 2 + 4 && Math.abs(f.y - s.hookY) < 8) {
			s.bite = f; s.fish = s.fish.filter((x) => x !== f); s.needle = -1; s.nd = 1; s.tries = 0; api.sfx('jump');
			say(f.k.junk ? 'چیزی گیر کرد…' : 'نوک زد! در ناحیه سبز بزن', '#86efac');
			if (f.k.d[0] >= 250) api.unlock('deep');
			break;
		}
	};
	const fishShape = (x, y, k, dir) => {
		ctx.save(); ctx.translate(x, y); ctx.scale(dir, 1);
		if (k.junk) { ctx.fillStyle = k.c; ctx.beginPath(); ctx.roundRect(-7, -8, 9, 14, 2); ctx.roundRect(-7, 2, 16, 6, 2); ctx.fill(); ctx.strokeStyle = OL; ctx.lineWidth = 1.5; ctx.stroke(); ctx.restore(); return; }
		const w = k.w;
		ctx.fillStyle = k.c; ctx.strokeStyle = OL; ctx.lineWidth = 1.5;
		ctx.beginPath(); ctx.ellipse(0, 0, w / 2, w / 4 + 2, 0, 0, 7); ctx.fill(); ctx.stroke();
		ctx.beginPath(); ctx.moveTo(-w / 2, 0); ctx.lineTo(-w / 2 - 7, -6); ctx.lineTo(-w / 2 - 7, 6); ctx.closePath(); ctx.fill(); ctx.stroke();
		ctx.fillStyle = OL; ctx.beginPath(); ctx.arc(w / 2 - 5, -1.5, 1.6, 0, 7); ctx.fill();
		if (k.rare) { ctx.fillStyle = '#e2e8f0'; for (let i = -2; i <= 2; i++) ctx.fillRect(i * 6, -w / 4 - 1, 3, 2); }
		ctx.restore();
	};
	const draw = () => {
		cv.reset();
		const t = s ? s.t : 0;
		let g = ctx.createLinearGradient(0, 0, 0, SURF); g.addColorStop(0, '#fdba74'); g.addColorStop(1, '#fde68a');
		ctx.fillStyle = g; ctx.fillRect(0, 0, view.vw, SURF);
		ctx.fillStyle = '#fff7ed'; ctx.beginPath(); ctx.arc(view.vw * 0.8, 40, 16, 0, 7); ctx.fill();
		ctx.fillStyle = '#4d7c0f'; ctx.beginPath(); ctx.moveTo(0, SURF); ctx.quadraticCurveTo(60, 50, 140, SURF); ctx.fill(); // Alborz foothill
		g = ctx.createLinearGradient(0, SURF, 0, VH); g.addColorStop(0, '#0ea5e9'); g.addColorStop(1, '#0c4a6e');
		ctx.fillStyle = g; ctx.fillRect(0, SURF, view.vw, VH - SURF);
		ctx.strokeStyle = 'rgba(255,255,255,.5)'; ctx.lineWidth = 2; ctx.beginPath();
		for (let x = 0; x <= view.vw; x += 8) ctx.lineTo(x, SURF + Math.sin(x / 20 + t * 2) * 2);
		ctx.stroke();
		ctx.fillStyle = '#a16207'; ctx.fillRect(0, VH - 10, view.vw, 10);
		if (s) s.fish.forEach((f) => fishShape(f.x, f.y, f.k, f.dir));
		// boat + fisher
		const bx = boatX();
		ctx.fillStyle = '#b45309'; ctx.strokeStyle = OL; ctx.lineWidth = 2;
		ctx.beginPath(); ctx.moveTo(bx - 34, SURF - 8); ctx.lineTo(bx + 34, SURF - 8); ctx.lineTo(bx + 24, SURF + 6); ctx.lineTo(bx - 24, SURF + 6); ctx.closePath(); ctx.fill(); ctx.stroke();
		ctx.fillStyle = '#1d1d24'; ctx.fillRect(bx - 14, SURF - 26, 10, 18); ctx.fillStyle = '#eab38a'; ctx.beginPath(); ctx.arc(bx - 9, SURF - 31, 6, 0, 7); ctx.fill(); ctx.fillStyle = '#111'; ctx.fillRect(bx - 13, SURF - 33, 9, 3);
		ctx.strokeStyle = '#78350f'; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(bx - 6, SURF - 18); ctx.lineTo(bx + 10, SURF - 44); ctx.stroke();
		const hy = s ? s.hookY : SURF + 8;
		ctx.strokeStyle = 'rgba(255,255,255,.8)'; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(bx + 10, SURF - 44); ctx.lineTo(bx, hy); ctx.stroke();
		ctx.strokeStyle = '#e2e8f0'; ctx.lineWidth = 2; ctx.beginPath(); ctx.arc(bx + 3, hy, 3, 0, Math.PI); ctx.stroke();
		if (!s) return;
		if (s.bite) {
			fishShape(s.bite.x, s.bite.y + 6, s.bite.k, 1);
			const w = 170, x = view.vw / 2 - w / 2, y = 112;
			ctx.fillStyle = 'rgba(2,6,23,.75)'; ctx.beginPath(); ctx.roundRect(x - 6, y - 6, w + 12, 26, 10); ctx.fill();
			ctx.fillStyle = '#475569'; ctx.fillRect(x, y, w, 14);
			const z = s.bite.k.zone; ctx.fillStyle = '#22c55e'; ctx.fillRect(x + w / 2 - (w / 2) * z, y, w * z, 14);
			ctx.fillStyle = '#fff'; ctx.fillRect(x + w / 2 + (w / 2) * s.needle - 2, y - 3, 4, 20);
		}
		ctx.fillStyle = '#0f172a'; ctx.font = '900 12px yekanBakh, Tahoma'; ctx.textAlign = 'right';
		ctx.fillText(`⏱ ${fa(Math.max(0, Math.ceil(s.left)))}`, view.vw - 10, 20);
		ctx.textAlign = 'left'; ctx.fillText(`🐟 ${fa(s.caught.length)}`, 10, 20);
		if (s.msg && s.msg.life > 0) { ctx.textAlign = 'center'; ctx.fillStyle = s.msg.c; ctx.strokeStyle = OL; ctx.lineWidth = 3; ctx.strokeText(s.msg.t, view.vw / 2, 150); ctx.fillText(s.msg.t, view.vw / 2, 150); }
		if (s.t < 4 && !s.bite) { ctx.textAlign = 'center'; ctx.fillStyle = '#fff'; ctx.fillText('نگه دار تا قلاب پایین برود؛ وقتی نوک زد، در ناحیه سبز بزن', view.vw / 2, 175); }
	};
	const tick = (now) => {
		if (!s || s.over) return;
		update(Math.min(0.05, (now - last) / 1000)); last = now;
		if (!s.over) { draw(); raf = requestAnimationFrame(tick); }
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };
	const down = () => { if (!s) return; if (s.bite) tap(); else holding = true; };
	const up = () => { holding = false; if (s && !s.bite && s.hookY > SURF + 8) s.hookY = Math.max(SURF + 8, s.hookY); };
	L.on(cv.canvas, 'pointerdown', (e) => { e.preventDefault(); down(); });
	L.on(window, 'pointerup', up);
	L.on(window, 'keydown', (e) => { if (!api.running || e.repeat) return; if (e.code === 'Space' || e.code === 'ArrowDown') { e.preventDefault(); down(); } if (e.code === 'ArrowUp' && s && !s.bite) s.hookY = SURF + 8; });
	L.on(window, 'keyup', (e) => { if (e.code === 'Space' || e.code === 'ArrowDown') up(); });
	draw();
	const inst = {
		start() { reset(); api.setScore(0); loop(); },
		pause() { cancelAnimationFrame(raf); holding = false; },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); cv.destroy(); },
	};
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
