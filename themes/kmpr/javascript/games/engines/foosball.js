/**
 * «فوتبال دستی» — table football on one phone (two players: each drags in their own half) or vs AI.
 * Each side controls a goalkeeper rod and a 3-man attack rod that slide together; men kick the ball on contact
 * (rod speed adds power). Tap/space = power kick for 0.3 s. First to N goals.
 */
import { CATALOG } from '../catalog.js';
import { createMatchUI, playerAvatar } from '../lib/match.js';
import { createCanvas } from '../lib/canvas.js';
import { fa, listeners, clamp } from '../lib/util.js';

export const achievements = CATALOG.foosball.achievements;
const W = 260, H = 440, GOAL = 90, BR = 7, MW = 16, MH = 10;
const AI = { easy: 150, medium: 250, hard: 360 };
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	let mode = null, raf = 0, last = 0, running = false, s = null;
	const ui = createMatchUI(stage, api, {
		ai: [{ id: 'easy', label: 'آسان' }, { id: 'medium', label: 'متوسط' }, { id: 'hard', label: 'حرفه‌ای' }],
		local: true,
		options: [{ key: 'to', label: 'تا چند گل؟', choices: [[3, '۳'], [5, '۵'], [7, '۷']], value: 5 }],
		onStart: (m) => { mode = m; startMatch(); },
		onMenu: () => { stop(); mode = null; s = null; draw(); },
	});
	const players = () => [
		{ name: mode && mode.type === 'local' ? 'بازیکن ۱ (قرمز)' : 'شما (قرمز)', avatar: playerAvatar('me'), sub: 'پایین', seat: 0 },
		{ name: mode && mode.type === 'local' ? 'بازیکن ۲ (آبی)' : 'ربات (آبی)', avatar: playerAvatar({ player: 'fb-bot', avatar: { style: 'bot', hue: 210 } }), sub: 'بالا', seat: 1 },
	];
	ui.players(players());
	const host = document.createElement('div');
	host.style.cssText = 'position:absolute;inset:0';
	ui.board.appendChild(host);
	const cv = createCanvas(host, { vh: H + 16 });
	const { ctx, view } = cv;
	cv.canvas.style.touchAction = 'none';
	const ox = () => (view.vw - W) / 2;
	// rods: side 0 (bottom, red, attacks up) / side 1 (top, blue, attacks down)
	const RODS = [
		{ side: 0, y: H - 46, men: [0], gk: true },
		{ side: 0, y: H * 0.36, men: [-70, 0, 70] },
		{ side: 1, y: 46, men: [0], gk: true },
		{ side: 1, y: H * 0.64, men: [-70, 0, 70] },
	];
	const startMatch = () => {
		stop();
		s = { score: [0, 0], to: Number((mode.options && mode.options.to) || 5), off: [W / 2, W / 2], vel: [0, 0], target: [W / 2, W / 2], kick: [0, 0], over: false, keys: {}, shots: 0 };
		serve(Math.random() < 0.5 ? 0 : 1);
		ui.players(players());
		status();
		running = true;
		last = performance.now();
		raf = requestAnimationFrame(loop);
	};
	const serve = (toward) => { s.ball = { x: W / 2, y: H / 2, vx: (Math.random() - 0.5) * 120, vy: toward === 0 ? 140 : -140 }; s.freeze = 0.6; };
	const stop = () => { running = false; cancelAnimationFrame(raf); };
	const status = () => ui.status(`${fa(s.score[0])} : ${fa(s.score[1])} — تا ${fa(s.to)} گل`, 'turn');
	const goal = (scorer) => {
		s.score[scorer]++;
		api.sfx(scorer === 0 || mode.type === 'local' ? 'coin' : 'hurt');
		status();
		if (scorer === 0 && mode.type === 'ai') api.unlock('first_goal');
		if (s.score[scorer] >= s.to) return end(scorer);
		serve(scorer === 0 ? 1 : 0);
	};
	const end = (w) => {
		s.over = true;
		stop();
		const [a, b] = s.score;
		if (mode.type === 'local') return setTimeout(() => ui.result({ title: `${w === 0 ? 'قرمز' : 'آبی'} برد!`, text: `نتیجه ${fa(a)} — ${fa(b)}`, tone: 'win', onPrimary: startMatch }), 400);
		if (w === 0) {
			api.unlock('first_win');
			if (b === 0) api.unlock('clean');
			if (mode.level === 'hard') api.unlock('pro');
			api.sfx('level');
			return setTimeout(() => api.over({ score: Math.min(20000, a * 400 + (a - b) * 300 + { easy: 0, medium: 1500, hard: 3500 }[mode.level]), meta: { mode: mode.level, result: 'win', summary: `${fa(a)} — ${fa(b)} مقابل ربات` } }), 400);
		}
		setTimeout(() => ui.result({ title: 'ربات برد', text: `نتیجه ${fa(a)} — ${fa(b)}. میله‌ها را جلوی توپ نگه دار و موقع رسیدن توپ ضربه بزن!`, tone: 'loss', onPrimary: startMatch }), 400);
	};
	const step = (dt) => {
		const b = s.ball;
		// AI side 1
		if (mode.type === 'ai') {
			const lead = b.vy < 0 ? b.x + b.vx * 0.15 : W / 2 + (b.x - W / 2) * 0.6;
			s.target[1] = lead;
			const nearRod = RODS.filter((r) => r.side === 1).some((r) => Math.abs(b.y - r.y) < 22);
			if (nearRod && Math.random() < 0.06 + (mode.level === 'hard' ? 0.08 : 0)) s.kick[1] = 0.3;
		}
		for (const side of [0, 1]) {
			if (s.keys[side]) s.target[side] += s.keys[side] * 300 * dt;
			s.target[side] = clamp(s.target[side], 70, W - 70);
			const max = side === 1 && mode.type === 'ai' ? AI[mode.level] : 900;
			const d = s.target[side] - s.off[side], mv = clamp(d, -max * dt, max * dt);
			s.vel[side] = mv / dt;
			s.off[side] += mv;
			if (s.kick[side] > 0) s.kick[side] -= dt;
		}
		if (s.freeze > 0) { s.freeze -= dt; return; }
		const sub = 4;
		for (let k = 0; k < sub; k++) {
			const h = dt / sub;
			b.x += b.vx * h; b.y += b.vy * h;
			if (b.x < BR) { b.x = BR; b.vx = Math.abs(b.vx) * 0.9; }
			if (b.x > W - BR) { b.x = W - BR; b.vx = -Math.abs(b.vx) * 0.9; }
			const inGoal = Math.abs(b.x - W / 2) < GOAL / 2;
			if (b.y < BR && !inGoal) { b.y = BR; b.vy = Math.abs(b.vy) * 0.9; }
			if (b.y > H - BR && !inGoal) { b.y = H - BR; b.vy = -Math.abs(b.vy) * 0.9; }
			if (b.y < -BR) return goal(0);
			if (b.y > H + BR) return goal(1);
			for (const rod of RODS) {
				for (const m of rod.men) {
					const mx = s.off[rod.side] + m * (rod.gk ? 0 : 1) + (rod.gk ? 0 : 0), my = rod.y;
					const cx = clamp(b.x, mx - MW / 2, mx + MW / 2), cy = clamp(b.y, my - MH / 2, my + MH / 2);
					const dx = b.x - cx, dy = b.y - cy, d2 = dx * dx + dy * dy;
					if (d2 < BR * BR) {
						const dir = rod.side === 0 ? -1 : 1; // kick toward the opponent's goal
						const power = s.kick[rod.side] > 0 ? 520 : 300;
						b.vy = dir * power;
						b.vx = b.vx * 0.4 + s.vel[rod.side] * 0.5 + (b.x - mx) * 6;
						b.y = my + dir * (MH / 2 + BR + 1);
						api.sfx('click');
						if (s.kick[rod.side] > 0 && rod.side === 0) { s.shots++; if (s.shots === 10) api.unlock('striker'); }
					}
				}
			}
		}
		b.vx *= 0.998; b.vy *= 0.998;
		if (Math.abs(b.vy) < 40) b.vy += (b.y < H / 2 ? 1 : -1) * 30 * dt; // never stall on the centre line
	};
	const R = (x, y, w, h, c) => { ctx.fillStyle = c; ctx.fillRect(x, y, w, h); };
	const draw = () => {
		cv.reset();
		ctx.fillStyle = '#020617'; ctx.fillRect(0, 0, view.vw, H + 16);
		ctx.save(); ctx.translate(ox(), 8);
		ctx.beginPath(); ctx.roundRect(-6, -6, W + 12, H + 12, 14); ctx.fillStyle = '#78350f'; ctx.fill(); ctx.strokeStyle = '#16120f'; ctx.lineWidth = 3; ctx.stroke();
		R(0, 0, W, H, '#16a34a');
		for (let i = 0; i < 8; i++) if (i % 2) R(0, (H / 8) * i, W, H / 8, '#15803d');
		ctx.strokeStyle = 'rgba(255,255,255,.7)'; ctx.lineWidth = 2;
		ctx.strokeRect(1, 1, W - 2, H - 2); ctx.beginPath(); ctx.moveTo(0, H / 2); ctx.lineTo(W, H / 2); ctx.stroke();
		ctx.beginPath(); ctx.arc(W / 2, H / 2, 34, 0, 7); ctx.stroke();
		R(W / 2 - GOAL / 2, -6, GOAL, 6, '#e2e8f0'); R(W / 2 - GOAL / 2, H, GOAL, 6, '#e2e8f0');
		for (const rod of RODS) {
			R(-6, rod.y - 2, W + 12, 4, '#cbd5e1');
			const off = s ? s.off[rod.side] : W / 2;
			for (const m of rod.men) {
				const mx = off + m;
				const col = rod.side === 0 ? '#ef4444' : '#3b82f6';
				const kick = s && s.kick[rod.side] > 0;
				ctx.beginPath(); ctx.roundRect(mx - MW / 2, rod.y - MH / 2 + (kick ? (rod.side === 0 ? -3 : 3) : 0), MW, MH, 3); ctx.fillStyle = col; ctx.fill(); ctx.strokeStyle = '#16120f'; ctx.lineWidth = 1.5; ctx.stroke();
				ctx.beginPath(); ctx.arc(mx, rod.y, 4, 0, 7); ctx.fillStyle = '#fde68a'; ctx.fill(); ctx.stroke();
			}
		}
		if (s) {
			ctx.beginPath(); ctx.arc(s.ball.x, s.ball.y, BR, 0, 7); ctx.fillStyle = '#fff'; ctx.fill(); ctx.strokeStyle = '#16120f'; ctx.lineWidth = 1.5; ctx.stroke();
			ctx.fillStyle = 'rgba(255,255,255,.22)'; ctx.font = '900 54px yekanBakh, Tahoma'; ctx.textAlign = 'center';
			ctx.fillText(fa(s.score[1]), W / 2, H / 2 - 50); ctx.fillText(fa(s.score[0]), W / 2, H / 2 + 92);
		}
		ctx.restore();
	};
	const loop = (now) => {
		if (!running) return;
		const dt = Math.min(1 / 30, (now - last) / 1000);
		last = now;
		step(dt);
		draw();
		if (running) raf = requestAnimationFrame(loop);
	};
	const ptr = new Map();
	const toTable = (e) => { const w = cv.toWorld(e.clientX, e.clientY); return { x: w.x - ox(), y: w.y - 8 }; };
	L.on(cv.canvas, 'pointerdown', (e) => {
		if (!s || s.over) return;
		cv.canvas.setPointerCapture(e.pointerId);
		const t = toTable(e);
		const side = mode.type === 'local' && t.y < H / 2 ? 1 : 0;
		ptr.set(e.pointerId, { side, x0: t.x, moved: false });
		s.kick[side] = 0.3; // every touch is also a kick
	});
	L.on(cv.canvas, 'pointermove', (e) => {
		if (!s) return;
		const p = ptr.get(e.pointerId);
		const t = toTable(e);
		if (p) { s.target[p.side] = t.x; p.moved = true; }
		else if (e.pointerType === 'mouse') s.target[0] = t.x;
	});
	const up = (e) => ptr.delete(e.pointerId);
	L.on(cv.canvas, 'pointerup', up);
	L.on(cv.canvas, 'pointercancel', up);
	L.on(window, 'keydown', (e) => {
		if (!s || !running) return;
		if (e.code === 'ArrowLeft') { s.keys[0] = -1; e.preventDefault(); }
		if (e.code === 'ArrowRight') { s.keys[0] = 1; e.preventDefault(); }
		if (e.code === 'Space' || e.code === 'ArrowUp') { s.kick[0] = 0.3; e.preventDefault(); }
		if (mode.type === 'local') { if (e.code === 'KeyA') s.keys[1] = -1; if (e.code === 'KeyD') s.keys[1] = 1; if (e.code === 'KeyS') s.kick[1] = 0.3; }
	});
	L.on(window, 'keyup', (e) => {
		if (!s) return;
		if (e.code === 'ArrowLeft' || e.code === 'ArrowRight') s.keys[0] = 0;
		if (e.code === 'KeyA' || e.code === 'KeyD') s.keys[1] = 0;
	});
	draw();
	const inst = {
		noAutoPause: true,
		start(opts = {}) { stop(); ui.start(opts); },
		pause() { stop(); },
		resume() { if (s && !s.over && !running) { running = true; last = performance.now(); raf = requestAnimationFrame(loop); } },
		destroy() { stop(); L.off(); cv.destroy(); ui.destroy(); },
	};
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
