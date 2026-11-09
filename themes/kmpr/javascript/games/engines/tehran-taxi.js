/**
 * «تاکسی تهران» — top-down 3-lane dash through Tehran traffic in a yellow taxi. Dodge cars, buses and potholes,
 * pick up passengers (fare), grab fuel cans. Speed rises with distance; 3 dents = game over.
 * Controls: ← → (or swipe / tap a side) to change lanes.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas } from '../lib/canvas.js';
import { fa, listeners, rand, pick } from '../lib/util.js';

export const achievements = CATALOG['tehran-taxi'].achievements;
const VH = 320, OL = '#16120f';
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	const cv = createCanvas(stage, { vh: VH });
	const { ctx, view } = cv;
	cv.canvas.style.touchAction = 'none';
	let s = null, raf = 0, last = 0, tx0 = null;
	const ROAD = 156;
	const laneX = (i) => view.vw / 2 - ROAD / 2 + 26 + i * 52;
	const PY = VH - 70;
	const reset = () => { s = { t: 0, dist: 0, speed: 3, lane: 1, x: 0, lives: 3, inv: 0, items: [], fare: 0, score: 0, next: 40, over: false, shake: 0 }; s.x = laneX(1); };
	const move = (d) => { if (!s || s.over) return; s.lane = Math.max(0, Math.min(2, s.lane + d)); api.sfx('click'); };
	// Spawning rules: never two things in the same lane closer than GAP, and never all three lanes blocked
	// within the same band — there is always a way through.
	const GAP = 110;
	const blocked = (lane, y0 = -60) => s.items.some((it) => it.lane === lane && Math.abs(it.y - y0) < GAP);
	const blockers = () => [0, 1, 2].filter((l) => s.items.some((it) => it.lane === l && it.kind !== 'fare' && it.kind !== 'fuel' && it.y < 140 && it.y > -200));
	const spawn = () => {
		const free = [0, 1, 2].filter((l) => !blocked(l));
		if (!free.length) return;
		const lane = free[Math.floor(Math.random() * free.length)], r = Math.random();
		let kind = r < 0.5 ? 'car' : r < 0.65 ? 'bus' : r < 0.75 ? 'hole' : r < 0.93 ? 'fare' : 'fuel';
		const obstacle = kind === 'car' || kind === 'bus' || kind === 'hole';
		if (obstacle && new Set([...blockers(), lane]).size >= 3) kind = 'fare'; // keep one lane open
		s.items.push({ kind, lane, y: -60, h: kind === 'bus' ? 70 : kind === 'hole' ? 18 : kind === 'car' ? 38 : 22, c: pick(['#ef4444', '#3b82f6', '#a855f7', '#f8fafc', '#10b981']) });
		// later: sometimes a second car in another free lane (still leaving one lane open)
		if (obstacle && s.dist > 1500 && Math.random() < 0.3) {
			const other = [0, 1, 2].filter((l) => l !== lane && !blocked(l));
			if (other.length && new Set([...blockers(), lane, other[0]]).size < 3) s.items.push({ kind: 'car', lane: other[0], y: -60, h: 38, c: '#64748b' });
		}
	};
	const update = (f) => {
		s.t += f;
		s.speed = Math.min(9, 3 + s.dist / 3000);
		const dy = s.speed * f;
		s.dist += dy;
		s.score = Math.floor(s.dist / 5) + s.fare * 150;
		api.setScore(s.score);
		s.x += (laneX(s.lane) - s.x) * Math.min(1, 0.3 * f);
		if (s.inv > 0) s.inv -= f / 60;
		if (s.shake > 0) s.shake -= f / 60;
		s.next -= dy;
		if (s.next <= 0) { spawn(); s.next = rand(90, 170) - Math.min(60, s.dist / 400); }
		s.items = s.items.filter((it) => {
			it.y += dy * 0.7; // one shared speed: nothing can catch up and overlap another item
			const ix = laneX(it.lane);
			if (Math.abs(ix - s.x) < 20 && it.y + it.h > PY - 2 && it.y < PY + 36) {
				if (it.kind === 'fare') { s.fare++; api.sfx('coin'); if (s.fare === 20) api.unlock('fare20'); return false; }
				if (it.kind === 'fuel') { s.lives = Math.min(3, s.lives + 1); api.sfx('level'); api.hud({ lives: s.lives }); return false; }
				if (s.inv <= 0) {
					s.lives--; s.inv = 1.4; s.shake = 0.3; api.sfx('hit'); api.hud({ lives: s.lives });
					if (s.lives <= 0) { s.over = true; cancelAnimationFrame(raf); return api.over({ score: s.score, meta: { summary: `${fa(Math.floor(s.dist / 10))} متر · ${fa(s.fare)} مسافر` } }); }
				}
			}
			return it.y < VH + 80;
		});
		if (s.dist > 8000) api.unlock('km');
		if (s.score >= 5000) api.unlock('s5k');
	};
	const car = (x, y, w, h, color, taxi) => {
		ctx.fillStyle = OL; ctx.beginPath(); ctx.roundRect(x - w / 2 - 1.5, y - 1.5, w + 3, h + 3, 7); ctx.fill();
		ctx.fillStyle = color; ctx.beginPath(); ctx.roundRect(x - w / 2, y, w, h, 6); ctx.fill();
		ctx.fillStyle = '#93c5fd'; ctx.fillRect(x - w / 2 + 4, y + 6, w - 8, 7); ctx.fillRect(x - w / 2 + 4, y + h - 11, w - 8, 5);
		ctx.fillStyle = '#1f2937'; [[-1, 4], [1, 4], [-1, h - 12], [1, h - 12]].forEach(([sx, yy]) => ctx.fillRect(x + sx * (w / 2) - (sx > 0 ? 1 : 2), y + yy, 3, 8));
		if (taxi) { ctx.fillStyle = '#16120f'; ctx.fillRect(x - 8, y + 15, 16, 5); ctx.fillStyle = '#fff'; ctx.font = '900 5px monospace'; ctx.textAlign = 'center'; ctx.fillText('TAXI', x, y + 19.3); for (let i = 0; i < 5; i++) { ctx.fillStyle = i % 2 ? '#16120f' : '#fff'; ctx.fillRect(x - w / 2, y + 22 + i * 2, w, 2); } }
	};
	const draw = () => {
		cv.reset();
		const off = s ? s.dist : 0;
		ctx.fillStyle = '#4d7c0f'; ctx.fillRect(0, 0, view.vw, VH);
		// sidewalks + trees (Valiasr plane trees)
		const rx = view.vw / 2 - ROAD / 2;
		ctx.fillStyle = '#a8a29e'; ctx.fillRect(rx - 18, 0, 18, VH); ctx.fillRect(rx + ROAD, 0, 18, VH);
		for (let y = -((-off) % 60) - 60; y < VH; y += 60) { [rx - 34, rx + ROAD + 34].forEach((tx) => { ctx.fillStyle = '#166534'; ctx.beginPath(); ctx.arc(tx, y, 14, 0, 7); ctx.fill(); ctx.fillStyle = '#22c55e'; ctx.beginPath(); ctx.arc(tx - 3, y - 3, 8, 0, 7); ctx.fill(); }); }
		ctx.fillStyle = '#334155'; ctx.fillRect(rx, 0, ROAD, VH);
		ctx.fillStyle = '#f8fafc';
		for (let i = 1; i < 3; i++) for (let y = -((-off * 1) % 40) - 40; y < VH; y += 40) ctx.fillRect(rx + i * 52 - 1, y, 2, 20);
		if (!s) { car(laneX(1), PY, 30, 44, '#facc15', true); return; }
		for (const it of s.items) {
			const x = laneX(it.lane);
			if (it.kind === 'car') car(x, it.y, 30, it.h, it.c);
			else if (it.kind === 'bus') { car(x, it.y, 36, it.h, '#dc2626'); ctx.fillStyle = '#fff'; ctx.font = '900 7px yekanBakh, Tahoma'; ctx.textAlign = 'center'; ctx.fillText('BRT', x, it.y + 40); }
			else if (it.kind === 'hole') { ctx.fillStyle = '#0f172a'; ctx.beginPath(); ctx.ellipse(x, it.y + 9, 16, 8, 0, 0, 7); ctx.fill(); ctx.strokeStyle = '#475569'; ctx.lineWidth = 2; ctx.stroke(); }
			else if (it.kind === 'fare') { ctx.fillStyle = '#fde68a'; ctx.beginPath(); ctx.arc(x, it.y + 6, 6, 0, 7); ctx.fill(); ctx.strokeStyle = OL; ctx.lineWidth = 1.5; ctx.stroke(); ctx.fillStyle = '#2563eb'; ctx.beginPath(); ctx.roundRect(x - 6, it.y + 12, 12, 12, 3); ctx.fill(); ctx.stroke(); ctx.fillStyle = '#22c55e'; ctx.font = '900 9px monospace'; ctx.textAlign = 'center'; ctx.fillText('✋', x + 9, it.y + 4); }
			else { ctx.fillStyle = '#ef4444'; ctx.beginPath(); ctx.roundRect(x - 8, it.y, 16, 20, 3); ctx.fill(); ctx.strokeStyle = OL; ctx.lineWidth = 1.5; ctx.stroke(); ctx.fillStyle = '#fff'; ctx.font = '900 8px monospace'; ctx.fillText('⛽', x, it.y + 14); }
		}
		const sh = s.shake > 0 ? (Math.random() - 0.5) * 4 : 0;
		if (!(s.inv > 0 && Math.floor(s.t / 4) % 2)) car(s.x + sh, PY, 30, 44, '#facc15', true);
		ctx.textAlign = 'right'; ctx.font = '900 12px yekanBakh, Tahoma'; ctx.fillStyle = '#fff';
		ctx.fillText(`${fa(Math.floor(s.dist / 10))} متر`, view.vw - 10, 20);
		ctx.textAlign = 'left'; ctx.fillText(`🧍 ${fa(s.fare)}   ${'❤'.repeat(s.lives)}`, 10, 20);
		if (s.t < 200) { ctx.textAlign = 'center'; ctx.fillStyle = '#fef3c7'; ctx.fillText(api.touch ? 'سمت چپ/راست صفحه را بزن یا بکش' : '← → عوض کردن لاین', view.vw / 2, 60); }
	};
	const tick = (now) => {
		if (!s || s.over) return;
		update(Math.min(3, ((now - last) / 1000) * 60)); last = now;
		if (!s.over) { draw(); raf = requestAnimationFrame(tick); }
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };
	L.on(window, 'keydown', (e) => { if (!api.running) return; if (e.code === 'ArrowLeft' || e.code === 'KeyA') { e.preventDefault(); move(-1); } if (e.code === 'ArrowRight' || e.code === 'KeyD') { e.preventDefault(); move(1); } });
	L.on(cv.canvas, 'pointerdown', (e) => { tx0 = e.clientX; });
	L.on(cv.canvas, 'pointerup', (e) => {
		if (tx0 === null) return;
		const dx = e.clientX - tx0; tx0 = null;
		if (Math.abs(dx) > 24) move(dx > 0 ? 1 : -1);
		else { const r = cv.canvas.getBoundingClientRect(); move(e.clientX < r.left + r.width / 2 ? -1 : 1); }
	});
	draw();
	const inst = {
		start() { reset(); api.setScore(0); api.hud({ lives: 3 }); loop(); },
		pause() { cancelAnimationFrame(raf); },
		resume() { if (s && !s.over) loop(); },
		destroy() { cancelAnimationFrame(raf); L.off(); cv.destroy(); },
	};
	current = inst;
	stage.kmprDebug = { get s() { return s; }, laneX };
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
