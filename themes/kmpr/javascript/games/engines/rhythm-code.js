/**
 * «ریتم کد» — 4-lane rhythm game on an original chiptune. Notes fall onto the hit line in time with the music;
 * hit them with D F J K (or tap the lanes). Perfect / Good / Miss, combos; the music only plays when sound is on.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas } from '../lib/canvas.js';
import { store } from '../lib/store.js';
import { fa, listeners } from '../lib/util.js';

export const achievements = CATALOG['rhythm-code'].achievements;
const VH = 300, LANES = 4, COLORS = ['#f43f5e', '#f59e0b', '#22c55e', '#38bdf8'], KEYS = ['KeyD', 'KeyF', 'KeyJ', 'KeyK'];
// original melody (MIDI, -1 = rest), 8th notes
const MEL = [72, -1, 76, 79, 77, -1, 76, 74, 72, 74, 76, -1, 79, -1, 81, 79, 77, -1, 76, 74, 76, 77, 79, -1, 84, -1, 83, 81, 79, 77, 76, 74,
	72, 76, 79, 84, 83, -1, 79, 76, 77, -1, 81, 79, 77, 76, 74, -1, 76, -1, 74, 72, 74, 76, 77, 79, 81, 79, 77, 76, 74, 72, -1, -1];
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	const cv = createCanvas(stage, { vh: VH });
	const { ctx, view } = cv;
	cv.canvas.style.touchAction = 'none';
	let s = null, raf = 0, ac = null;
	const hitY = VH - 46;
	const laneX = (i) => view.vw / 2 - 110 + i * 55 + 27;

	const audio = () => {
		if (!store.player.sound) return null;
		try { ac = ac || new (window.AudioContext || window.webkitAudioContext)(); if (ac.resume) ac.resume(); return ac; } catch (e) { return null; }
	};
	const tone = (m, t, d, v, type = 'square') => {
		if (!ac) return;
		const o = ac.createOscillator(), g = ac.createGain();
		o.type = type; o.frequency.value = 440 * Math.pow(2, (m - 69) / 12);
		g.gain.setValueAtTime(v, t); g.gain.exponentialRampToValueAtTime(0.001, t + d);
		o.connect(g).connect(ac.destination); o.start(t); o.stop(t + d + 0.02);
	};
	const start = () => {
		const bpm = 112 + Math.min(40, s.round * 12), spb = 60 / bpm / 2;
		s.spb = spb;
		s.notes = [];
		MEL.forEach((m, i) => { if (m > 0) s.notes.push({ t: 2 + i * spb, lane: (m * 7) % LANES, m, hit: false, done: false }); });
		if (s.round > 0) MEL.forEach((m, i) => { if (m > 0 && i % 4 === 2) s.notes.push({ t: 2 + i * spb, lane: ((m * 7) + 2) % LANES, m: m - 12, hit: false, done: false }); });
		s.t0 = performance.now() / 1000;
		s.speed = 150 + s.round * 30;
		s.end = 2 + MEL.length * spb + 1.2;
		const a = audio();
		if (a) {
			const base = a.currentTime + 2;
			MEL.forEach((m, i) => { if (i % 2 === 0) tone(48 + (m > 0 ? (m % 12) : 0), base + i * spb, spb * 1.8, 0.08, 'triangle'); if (i % 4 === 0) tone(36, base + i * spb, 0.12, 0.2, 'sine'); });
		}
	};
	const now = () => performance.now() / 1000 - s.t0;
	const judge = (lane) => {
		if (!s || s.over) return;
		s.flash[lane] = 0.12;
		const t = now();
		let best = null;
		for (const n of s.notes) if (!n.done && n.lane === lane && Math.abs(n.t - t) < 0.2 && (!best || Math.abs(n.t - t) < Math.abs(best.t - t))) best = n;
		if (!best) return;
		const dt = Math.abs(best.t - t);
		best.done = best.hit = true;
		const perfect = dt < 0.07;
		s.combo++;
		s.maxCombo = Math.max(s.maxCombo, s.combo);
		s.score += (perfect ? 300 : 120) * (1 + Math.floor(s.combo / 10));
		s.hits++;
		s.label = { t: perfect ? 'Perfect!' : 'Good', c: perfect ? '#fde047' : '#86efac', life: 0.4 };
		if (ac) tone(best.m, ac.currentTime, 0.12, 0.07);
		api.setScore(s.score);
		if (s.combo === 20) api.unlock('combo20');
		if (s.combo === 50) api.unlock('combo50');
	};
	const tick = () => {
		if (!s || s.over) return;
		const t = now();
		for (const n of s.notes) if (!n.done && t - n.t > 0.2) { n.done = true; s.combo = 0; s.miss++; s.label = { t: 'Miss', c: '#fca5a5', life: 0.4 }; }
		Object.keys(s.flash).forEach((k) => (s.flash[k] = Math.max(0, s.flash[k] - 1 / 60)));
		if (s.label) s.label.life -= 1 / 60;
		if (t > s.end) {
			const acc = s.hits / Math.max(1, s.notes.length);
			if (acc >= 0.9) api.unlock('acc90');
			if (s.miss === 0) api.unlock('fc');
			if (acc >= 0.6 && s.round < 2) { s.round++; api.toast(`آهنگ بعدی، سریع‌تر! دقت ${fa(Math.round(acc * 100))}٪`); api.sfx('level'); start(); }
			else { s.over = true; return api.over({ score: s.score, meta: { summary: `دقت ${fa(Math.round(acc * 100))}٪ · بیشترین کمبو ${fa(s.maxCombo)}` } }); }
		}
		draw();
		raf = requestAnimationFrame(tick);
	};
	const draw = () => {
		cv.reset();
		const g = ctx.createLinearGradient(0, 0, 0, VH); g.addColorStop(0, '#120a2a'); g.addColorStop(1, '#030712');
		ctx.fillStyle = g; ctx.fillRect(0, 0, view.vw, VH);
		const x0 = view.vw / 2 - 110;
		for (let i = 0; i < LANES; i++) {
			ctx.fillStyle = i % 2 ? 'rgba(255,255,255,.035)' : 'rgba(255,255,255,.06)'; ctx.fillRect(x0 + i * 55, 0, 55, VH);
			const fl = s ? s.flash[i] : 0;
			ctx.fillStyle = fl > 0 ? COLORS[i] : 'rgba(255,255,255,.08)';
			ctx.beginPath(); ctx.arc(laneX(i), hitY, 20, 0, 7); ctx.fill();
			ctx.lineWidth = 3; ctx.strokeStyle = COLORS[i]; ctx.stroke();
			ctx.fillStyle = '#fff'; ctx.font = '900 11px monospace'; ctx.textAlign = 'center'; ctx.fillText('DFJK'[i], laneX(i), hitY + 4);
		}
		if (!s) { ctx.fillStyle = '#c4b5fd'; ctx.font = '900 16px yekanBakh, Tahoma'; ctx.fillText('♪ ریتم کد ♪', view.vw / 2, 110); return; }
		const t = now();
		for (const n of s.notes) {
			if (n.done) continue;
			const y = hitY - (n.t - t) * s.speed;
			if (y < -20 || y > VH + 20) continue;
			ctx.fillStyle = COLORS[n.lane]; ctx.strokeStyle = '#16120f'; ctx.lineWidth = 2;
			ctx.beginPath(); ctx.roundRect(laneX(n.lane) - 22, y - 8, 44, 16, 8); ctx.fill(); ctx.stroke();
			ctx.fillStyle = 'rgba(255,255,255,.6)'; ctx.fillRect(laneX(n.lane) - 14, y - 5, 28, 3);
		}
		ctx.textAlign = 'center'; ctx.font = '900 13px yekanBakh, Tahoma';
		if (s.combo > 2) { ctx.fillStyle = '#fff'; ctx.fillText(`کمبو ${fa(s.combo)}`, view.vw / 2, 40); }
		if (s.label && s.label.life > 0) { ctx.fillStyle = s.label.c; ctx.font = '900 18px monospace'; ctx.fillText(s.label.t, view.vw / 2, hitY - 60); }
		if (t < 2) { ctx.fillStyle = '#e9d5ff'; ctx.font = '900 14px yekanBakh, Tahoma'; ctx.fillText(store.player.sound ? `آماده… ${fa(Math.ceil(2 - t))}` : 'برای حس بهتر صدا را روشن کن 🔊', view.vw / 2, 120); }
	};
	L.on(window, 'keydown', (e) => { const i = KEYS.indexOf(e.code); if (i >= 0 && api.running && !e.repeat) { e.preventDefault(); judge(i); } });
	L.on(cv.canvas, 'pointerdown', (e) => {
		const w = cv.toWorld(e.clientX, e.clientY);
		const i = Math.floor((w.x - (view.vw / 2 - 110)) / 55);
		if (i >= 0 && i < LANES) judge(i);
	});
	draw();
	const inst = {
		start() { s = { score: 0, combo: 0, maxCombo: 0, hits: 0, miss: 0, round: 0, flash: { 0: 0, 1: 0, 2: 0, 3: 0 }, over: false }; api.setScore(0); start(); cancelAnimationFrame(raf); raf = requestAnimationFrame(tick); },
		pause() { cancelAnimationFrame(raf); if (ac) ac.suspend(); s && (s.pausedAt = performance.now()); },
		resume() { if (s && !s.over) { if (s.pausedAt) s.t0 += (performance.now() - s.pausedAt) / 1000; if (ac) ac.resume(); raf = requestAnimationFrame(tick); } },
		destroy() { cancelAnimationFrame(raf); if (ac) ac.close(); L.off(); cv.destroy(); },
	};
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
