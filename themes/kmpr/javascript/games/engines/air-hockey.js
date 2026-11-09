/**
 * «ایرهاکی» — 2D air hockey. vs AI (3 levels) or two players on one device (bottom half = player 1, top = player 2,
 * multi-touch). Keyboard: P1 arrows, P2 W A S D. First to N goals.
 */
import { CATALOG } from '../catalog.js';
import { createMatchUI, playerAvatar } from '../lib/match.js';
import { createCanvas } from '../lib/canvas.js';
import { fa, listeners, clamp } from '../lib/util.js';

export const achievements = CATALOG['air-hockey'].achievements;
const W = 300, H = 520, GOAL = 104, PR = 12, MR = 23;
const AI_SPEED = { easy: 230, medium: 400, hard: 600 };
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	let mode = null, raf = 0, last = 0, running = false;
	let s = null;
	const ui = createMatchUI(stage, api, {
		ai: [{ id: 'easy', label: 'آسان' }, { id: 'medium', label: 'متوسط' }, { id: 'hard', label: 'حرفه‌ای' }],
		local: true,
		options: [{ key: 'to', label: 'تا چند گل؟', choices: [[5, '۵'], [7, '۷'], [10, '۱۰']], value: 7 }],
		onStart: (m) => { mode = m; startMatch(); },
		onMenu: () => { stop(); mode = null; s = null; draw(); },
	});
	const players = () => [
		{ name: mode && mode.type === 'local' ? 'بازیکن ۱' : 'شما', avatar: playerAvatar('me'), sub: 'پایین', seat: 0 },
		{ name: mode && mode.type === 'local' ? 'بازیکن ۲' : 'ربات', avatar: playerAvatar({ player: 'ah-bot', avatar: { style: 'blocks', hue: 200 } }), sub: 'بالا', seat: 1 },
	];
	ui.players(players());
	const host = document.createElement('div');
	host.style.cssText = 'position:absolute;inset:0';
	ui.board.appendChild(host);
	const cv = createCanvas(host, { vh: H + 20 });
	const { ctx, view } = cv;
	const ox = () => (view.vw - W) / 2;

	const reset = (toward) => {
		s.puck = { x: W / 2, y: toward === 0 ? H * 0.62 : H * 0.38, vx: 0, vy: 0 };
		s.m = [{ x: W / 2, y: H - 60, vx: 0, vy: 0, tx: W / 2, ty: H - 60 }, { x: W / 2, y: 60, vx: 0, vy: 0, tx: W / 2, ty: 60 }];
		s.freeze = 0.6;
	};
	const startMatch = () => {
		stop();
		s = { score: [0, 0], to: Number((mode.options && mode.options.to) || 7), hits: 0, over: false, keys: {} };
		reset(Math.random() < 0.5 ? 0 : 1);
		ui.players(players());
		status();
		running = true;
		last = performance.now();
		raf = requestAnimationFrame(loop);
	};
	const stop = () => { running = false; cancelAnimationFrame(raf); };
	const status = () => ui.status(`${fa(s.score[0])} : ${fa(s.score[1])} — تا ${fa(s.to)} گل`, 'turn');

	const goal = (scorer) => {
		s.score[scorer]++;
		api.sfx(scorer === 0 || mode.type === 'local' ? 'coin' : 'hurt');
		status();
		if (s.score[scorer] >= s.to) return end(scorer);
		reset(scorer === 0 ? 1 : 0);
	};
	const end = (w) => {
		s.over = true;
		stop();
		const [a, b] = s.score;
		if (mode.type === 'local') {
			ui.status(`بازیکن ${fa(w + 1)} برد!`, 'win');
			return setTimeout(() => ui.result({ title: `بازیکن ${fa(w + 1)} برد!`, text: `نتیجه ${fa(a)} — ${fa(b)}`, tone: 'win', onPrimary: startMatch }), 500);
		}
		if (w === 0) {
			api.unlock('first_win');
			if (b === 0) api.unlock('clean');
			if (mode.level === 'hard') api.unlock('pro');
			const pts = Math.min(20000, a * 300 + (a - b) * 200 + { easy: 0, medium: 1500, hard: 3500 }[mode.level]);
			api.sfx('level');
			return setTimeout(() => api.over({ score: pts, meta: { mode: mode.level, result: 'win', summary: `${fa(a)} — ${fa(b)} در برابر ربات` } }), 500);
		}
		ui.status('ربات برد', 'loss');
		setTimeout(() => ui.result({ title: 'ربات برد', text: `نتیجه ${fa(a)} — ${fa(b)}. دسته را جلوی دروازه نگه دار و از کنار بزن!`, tone: 'loss', onPrimary: startMatch }), 500);
	};

	const collide = (m) => {
		const p = s.puck, dx = p.x - m.x, dy = p.y - m.y, d = Math.hypot(dx, dy), min = PR + MR;
		if (d >= min || d === 0) return;
		const nx = dx / d, ny = dy / d;
		p.x = m.x + nx * min;
		p.y = m.y + ny * min;
		const rv = (p.vx - m.vx) * nx + (p.vy - m.vy) * ny;
		if (rv < 0) {
			p.vx -= 1.9 * rv * nx;
			p.vy -= 1.9 * rv * ny;
		}
		const sp = Math.hypot(p.vx, p.vy);
		if (sp > 1100) { p.vx *= 1100 / sp; p.vy *= 1100 / sp; }
		api.sfx('click');
		s.hits++;
		if (sp > 900) api.unlock('rocket');
	};
	const aiMove = (m, dt) => {
		const p = s.puck, sp = AI_SPEED[mode.level] || 400;
		let tx, ty;
		if (p.y < H / 2 + 20 && p.vy < 160) { tx = p.x + (p.x - W / 2) * 0.15; ty = p.y - 26; }
		else { tx = W / 2 + (p.x - W / 2) * 0.55; ty = 56; }
		const dx = tx - m.x, dy = ty - m.y, d = Math.hypot(dx, dy) || 1, step = Math.min(d, sp * dt);
		m.tx = m.x + (dx / d) * step;
		m.ty = m.y + (dy / d) * step;
	};
	const keyMove = (m, up, down, left, right, dt) => {
		const k = s.keys, v = 420 * dt;
		if (k[up]) m.ty -= v;
		if (k[down]) m.ty += v;
		if (k[left]) m.tx -= v;
		if (k[right]) m.tx += v;
	};
	const step = (dt) => {
		const p = s.puck;
		keyMove(s.m[0], 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', dt);
		if (mode.type === 'local') keyMove(s.m[1], 'KeyW', 'KeyS', 'KeyA', 'KeyD', dt);
		else aiMove(s.m[1], dt);
		s.m.forEach((m, i) => {
			const nx = clamp(m.tx, MR, W - MR), ny = i === 0 ? clamp(m.ty, H / 2 + MR, H - MR) : clamp(m.ty, MR, H / 2 - MR);
			m.tx = nx; m.ty = ny;
			m.vx = (nx - m.x) / dt; m.vy = (ny - m.y) / dt;
			m.x = nx; m.y = ny;
		});
		if (s.freeze > 0) { s.freeze -= dt; return; }
		const sub = 4;
		for (let k = 0; k < sub; k++) {
			p.x += (p.vx * dt) / sub;
			p.y += (p.vy * dt) / sub;
			if (p.x < PR) { p.x = PR; p.vx = Math.abs(p.vx) * 0.9; }
			if (p.x > W - PR) { p.x = W - PR; p.vx = -Math.abs(p.vx) * 0.9; }
			const inGoal = Math.abs(p.x - W / 2) < GOAL / 2;
			if (p.y < PR && !inGoal) { p.y = PR; p.vy = Math.abs(p.vy) * 0.9; }
			if (p.y > H - PR && !inGoal) { p.y = H - PR; p.vy = -Math.abs(p.vy) * 0.9; }
			if (p.y < -PR) return goal(0);
			if (p.y > H + PR) return goal(1);
			s.m.forEach(collide);
		}
		p.vx *= 0.995; p.vy *= 0.995;
	};
	const draw = () => {
		cv.reset();
		ctx.fillStyle = '#020617';
		ctx.fillRect(0, 0, view.vw, H + 20);
		ctx.save();
		ctx.translate(ox(), 10);
		ctx.fillStyle = '#0b1530';
		ctx.strokeStyle = '#38bdf8';
		ctx.lineWidth = 3;
		ctx.beginPath(); ctx.roundRect(0, 0, W, H, 18); ctx.fill(); ctx.stroke();
		ctx.strokeStyle = 'rgba(56,189,248,.35)';
		ctx.lineWidth = 2;
		ctx.beginPath(); ctx.moveTo(0, H / 2); ctx.lineTo(W, H / 2); ctx.stroke();
		ctx.beginPath(); ctx.arc(W / 2, H / 2, 46, 0, Math.PI * 2); ctx.stroke();
		ctx.fillStyle = '#f43f5e'; ctx.fillRect(W / 2 - GOAL / 2, -4, GOAL, 6);
		ctx.fillStyle = '#f59e0b'; ctx.fillRect(W / 2 - GOAL / 2, H - 2, GOAL, 6);
		if (s) {
			const dot = (x, y, r, c, glow) => { ctx.shadowColor = c; ctx.shadowBlur = glow; ctx.fillStyle = c; ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2); ctx.fill(); ctx.shadowBlur = 0; };
			dot(s.m[1].x, s.m[1].y, MR, '#f43f5e', 14); dot(s.m[1].x, s.m[1].y, MR * 0.45, '#fda4af', 0);
			dot(s.m[0].x, s.m[0].y, MR, '#f59e0b', 14); dot(s.m[0].x, s.m[0].y, MR * 0.45, '#fde68a', 0);
			dot(s.puck.x, s.puck.y, PR, '#e2e8f0', 18);
			ctx.fillStyle = 'rgba(226,232,240,.18)';
			ctx.font = '900 64px yekanBakh, Tahoma';
			ctx.textAlign = 'center';
			ctx.fillText(fa(s.score[1]), W / 2, H / 2 - 60);
			ctx.fillText(fa(s.score[0]), W / 2, H / 2 + 100);
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
	const toTable = (e) => { const w = cv.toWorld(e.clientX, e.clientY); return { x: w.x - ox(), y: w.y - 10 }; };
	const onPtr = (e) => {
		if (!s || s.over) return;
		const t = toTable(e);
		let who = ptr.get(e.pointerId);
		if (who === undefined) { who = mode.type === 'local' && t.y < H / 2 ? 1 : 0; ptr.set(e.pointerId, who); }
		s.m[who].tx = t.x;
		s.m[who].ty = t.y + (api.touch ? (who === 0 ? -30 : 30) : 0);
	};
	L.on(cv.canvas, 'pointerdown', (e) => { cv.canvas.setPointerCapture(e.pointerId); onPtr(e); });
	L.on(cv.canvas, 'pointermove', (e) => (e.pointerType === 'mouse' || ptr.has(e.pointerId)) && onPtr(e));
	L.on(cv.canvas, 'pointerup', (e) => ptr.delete(e.pointerId));
	L.on(cv.canvas, 'pointercancel', (e) => ptr.delete(e.pointerId));
	cv.canvas.style.touchAction = 'none';
	L.on(window, 'keydown', (e) => { if (s && running) { s.keys[e.code] = true; if (e.code.startsWith('Arrow')) e.preventDefault(); } });
	L.on(window, 'keyup', (e) => s && (s.keys[e.code] = false));
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
