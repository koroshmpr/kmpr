/**
 * «پینگ سرور» — one-thumb timing game.
 * A data packet orbits the server ring; tap anywhere while it is inside the open (green) port.
 * Each ping flips the packet's direction, speeds it up a little and opens a new, narrower port.
 * Tap outside the port or let the packet slip past it = timeout (game over).
 * Perfect (centre) hits build a combo multiplier; golden ports pay a bonus; from level 4 ports drift.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas, createLoop } from '../lib/canvas.js';
import { Particles, Shake, FloatTexts } from '../lib/fx.js';
import { rand, listeners, fa, FONT, loadFont } from '../lib/util.js';

export const achievements = CATALOG['server-ping'].achievements;

const VH = 600;
const TAU = Math.PI * 2;
const OUTLINE = '#16120f';
const PER_LEVEL = 10;
const CODES = ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2', 'HTTP/3', 'WebSocket', 'gRPC', 'QUIC', 'Edge', 'CDN', 'Quantum'];
const BEST_KEY = 'kmpr_sping_best';
const PORTS = [80, 443, 22, 3000, 8080, 5432, 6379, 9000];

/** Signed angle a→b in (-π, π]. */
const diff = (a, b) => {
	let d = (b - a) % TAU;
	if (d > Math.PI) d -= TAU;
	if (d <= -Math.PI) d += TAU;
	return d;
};

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	const fx = new Particles(api.reducedMotion ? 80 : 260);
	const shake = new Shake(!api.reducedMotion);
	const texts = new FloatTexts();
	let s = null;
	let loop = null;
	let ready = false;

	const cv = createCanvas(stage, {
		vh: VH,
		onResize: () => ready && !(loop && loop.running) && draw(),
	});
	cv.canvas.setAttribute('aria-label', 'صحنه بازی پینگ سرور');
	cv.canvas.classList.add('sp-canvas');
	const { ctx, view } = cv;

	const geo = () => {
		const vw = view.vw || 960;
		const R = Math.max(130, Math.min(205, vw / 2 - 34, VH / 2 - 70));
		return { cx: vw / 2, cy: VH / 2 + 12, R };
	};

	const newRun = () => ({
		a: -Math.PI / 2,
		dir: 1,
		w: 1.75, // rad/s
		port: null,
		hits: 0,
		perfects: 0,
		combo: 0,
		bestCombo: 0,
		score: 0,
		level: 1,
		golden: 0,
		dead: false,
		deadT: 0,
		t: 0,
		flash: 0,
		pulse: 0,
		started: false,
		trail: [],
	});

	const openPort = () => {
		const lvl = s.level;
		const hw = Math.max(0.15, 0.44 - s.hits * 0.0085);
		const ahead = rand(1.15, 2.55);
		const golden = s.hits >= 6 && Math.random() < 0.13;
		const drift = lvl >= 4 ? rand(0.25, 0.25 + Math.min(0.75, (lvl - 3) * 0.12)) * (Math.random() < 0.5 ? -1 : 1) : 0;
		s.port = { c: s.a + s.dir * ahead, hw, golden, drift, entered: false, born: s.t };
	};

	const setHud = () => {
		api.hud({ level: s.level, levelLabel: 'مرحله', extra: `×${fa(mult())}`, extraLabel: 'ضریب' });
		api.setScore(s.score);
	};
	const mult = () => Math.min(5, 1 + Math.floor(s.combo / 3));

	const pos = (ang, R) => {
		const g = geo();
		return { x: g.cx + Math.cos(ang) * R, y: g.cy + Math.sin(ang) * R };
	};

	const hit = () => {
		const d = Math.abs(diff(s.a, s.port.c));
		const perfect = d <= s.port.hw * 0.32;
		const golden = s.port.golden;
		s.hits++;
		if (perfect) {
			s.combo++;
			s.perfects++;
			s.bestCombo = Math.max(s.bestCombo, s.combo);
		} else s.combo = 0;
		let pts = (perfect ? 20 : 10) * mult();
		if (golden) {
			pts += 50;
			s.golden++;
			api.unlock('golden');
		}
		s.score += pts;
		const p = pos(s.a, geo().R);
		const col = golden ? '#fbbf24' : perfect ? '#a7f3d0' : '#4ade80';
		fx.burst(p.x, p.y, { n: perfect ? 22 : 12, color: col, speed: perfect ? 260 : 180, life: 0.55, size: 4 });
		const vw = view.vw || 960;
		texts.add(Math.max(70, Math.min(vw - 70, p.x)), p.y - 30, golden ? `قهوه! +${fa(pts)}` : perfect ? `دقیق! +${fa(pts)}` : `+${fa(pts)}`, col, perfect ? 22 : 18);
		api.sfx(golden ? 'coin' : perfect ? 'jump' : 'click');
		if (navigator.vibrate && api.touch) navigator.vibrate(perfect ? 14 : 8);
		s.flash = perfect ? 1 : 0.6;
		s.pulse = 1;
		s.dir *= -1;
		s.w = Math.min(5.1, s.w + (perfect ? 0.05 : 0.07));
		if (s.hits === 1) api.unlock('first');
		if (s.combo >= 5) api.unlock('combo5');
		if (s.hits >= 50) api.unlock('p50');
		if (s.hits >= 100) api.unlock('p100');
		if (s.score >= 2000) api.unlock('s2k');
		if (s.hits % PER_LEVEL === 0) {
			s.level++;
			api.sfx('level');
			api.toast(`مرحله ${fa(s.level)}: ${CODES[Math.min(CODES.length - 1, s.level - 1)]}${s.level === 4 ? ' — پورت‌ها حرکت می‌کنند!' : ''}`);
		}
		openPort();
		setHud();
		api.save && api.save();
	};

	const die = (why) => {
		if (s.dead) return;
		s.dead = true;
		s.deadT = 0;
		s.why = why;
		const p = pos(s.a, geo().R);
		fx.burst(p.x, p.y, { n: 36, color: '#fb7185', speed: 320, life: 0.9, size: 5 });
		shake.add(12, 0.35);
		api.sfx('boom');
		if (navigator.vibrate && api.touch) navigator.vibrate([30, 40, 30]);
	};

	const finish = () => {
		const prev = Number(localStorage.getItem(BEST_KEY) || 0) || 0;
		try {
			if (s.hits > prev) localStorage.setItem(BEST_KEY, String(s.hits));
		} catch (e) {
			/* ignore */
		}
		api.over({
			score: s.score,
			meta: {
				hits: s.hits,
				level: s.level,
				result: s.hits > 0 ? 'win' : 'loss',
				summary: `${fa(s.hits)} پینگ موفق · ${fa(s.perfects)} ضربه دقیق · بهترین کمبو ${fa(s.bestCombo)}${s.why === 'late' ? ' — بسته از پورت رد شد (Timeout)' : ' — زود زدی، پورت بسته بود'}`,
			},
		});
	};

	const tap = () => {
		if (!s || !api.running || s.dead) return;
		if (!s.started) {
			s.started = true;
			return;
		}
		const d = Math.abs(diff(s.a, s.port.c));
		if (d <= s.port.hw + 0.035) hit();
		else die('early');
	};

	const update = (dt) => {
		s.t += dt;
		s.flash = Math.max(0, s.flash - dt * 2.5);
		s.pulse = Math.max(0, s.pulse - dt * 3);
		fx.update(dt);
		texts.update(dt);
		shake.update(dt);
		if (s.dead) {
			s.deadT += dt;
			if (s.deadT > 0.9 && !s.done) {
				s.done = true;
				finish();
			}
			return;
		}
		if (!s.started) return;
		s.a += s.dir * s.w * dt;
		if (s.port.drift) s.port.c += s.port.drift * dt;
		// trail
		s.trail.unshift(s.a);
		if (s.trail.length > 10) s.trail.pop();
		const d = diff(s.a, s.port.c) * s.dir; // > 0 = port still ahead
		if (Math.abs(d) <= s.port.hw) s.port.entered = true;
		if (d < -s.port.hw - 0.02 && (s.port.entered || d > -Math.PI / 2)) die('late');
	};

	/* ---------- drawing ---------- */
	const rr = (x, y, w, h, r) => {
		ctx.beginPath();
		if (ctx.roundRect) ctx.roundRect(x, y, w, h, r);
		else ctx.rect(x, y, w, h);
	};

	const drawServer = (cx, cy, R) => {
		const w = Math.min(120, R * 0.68);
		const h = w * 1.12;
		const x = cx - w / 2;
		const y = cy - h / 2;
		const bump = s ? s.pulse * 4 : 0;
		ctx.lineWidth = 4;
		ctx.strokeStyle = OUTLINE;
		ctx.fillStyle = '#334155';
		rr(x - bump / 2, y - bump / 2, w + bump, h + bump, 14);
		ctx.fill();
		ctx.stroke();
		// three rack units
		for (let i = 0; i < 3; i++) {
			const uy = y + 10 + i * (h - 20) / 3;
			const uh = (h - 20) / 3 - 6;
			ctx.fillStyle = '#1e293b';
			rr(x + 9, uy, w - 18, uh, 6);
			ctx.fill();
			ctx.lineWidth = 2.5;
			ctx.stroke();
			// LEDs
			for (let k = 0; k < 3; k++) {
				const on = s && (s.flash > 0.2 ? (k + i) % 2 === 0 : Math.floor(s.t * 3 + k + i * 2) % 4 !== 0);
				ctx.fillStyle = on ? (k === 0 ? '#4ade80' : k === 1 ? '#38bdf8' : '#fbbf24') : '#475569';
				ctx.beginPath();
				ctx.arc(x + 20 + k * 11, uy + uh / 2, 3.2, 0, TAU);
				ctx.fill();
			}
			ctx.fillStyle = '#64748b';
			ctx.fillRect(x + w - 42, uy + uh / 2 - 1.5, 24, 3);
		}
		// face on the middle unit (the server is a character too)
		const fy = y + 10 + (h - 20) / 3 + ((h - 20) / 3 - 6) / 2;
		const fx0 = x + w - 30;
		ctx.fillStyle = '#0b1220';
		rr(fx0 - 16, fy - 9, 34, 18, 5);
		ctx.fill();
		ctx.fillStyle = s && s.dead ? '#fb7185' : '#e2e8f0';
		if (s && s.dead) {
			ctx.font = `900 13px ${FONT}`;
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.fillText('× ×', fx0 + 1, fy + 1);
		} else {
			const blink = s && Math.floor(s.t * 1.3) % 5 === 0 && (s.t * 1.3) % 1 < 0.15;
			ctx.fillRect(fx0 - 9, fy - (blink ? 1 : 4), 5, blink ? 2 : 7);
			ctx.fillRect(fx0 + 6, fy - (blink ? 1 : 4), 5, blink ? 2 : 7);
		}
	};

	const drawPacket = (x, y, ang, scale = 1, alpha = 1) => {
		const size = 30 * scale;
		ctx.save();
		ctx.globalAlpha = alpha;
		ctx.translate(x, y);
		ctx.rotate(ang + Math.PI / 2);
		ctx.lineWidth = 3.5;
		ctx.strokeStyle = OUTLINE;
		ctx.fillStyle = '#38bdf8';
		rr(-size / 2, -size / 2.6, size, size / 1.3, 7);
		ctx.fill();
		ctx.stroke();
		// envelope flap
		ctx.beginPath();
		ctx.moveTo(-size / 2 + 3, -size / 2.6 + 3);
		ctx.lineTo(0, 1);
		ctx.lineTo(size / 2 - 3, -size / 2.6 + 3);
		ctx.lineWidth = 2.5;
		ctx.stroke();
		// eyes
		ctx.fillStyle = '#fff';
		ctx.beginPath();
		ctx.arc(-6 * scale, 5 * scale, 3.6 * scale, 0, TAU);
		ctx.arc(6 * scale, 5 * scale, 3.6 * scale, 0, TAU);
		ctx.fill();
		ctx.fillStyle = OUTLINE;
		ctx.beginPath();
		ctx.arc(-5.4 * scale, 5.6 * scale, 1.8 * scale, 0, TAU);
		ctx.arc(6.6 * scale, 5.6 * scale, 1.8 * scale, 0, TAU);
		ctx.fill();
		ctx.restore();
	};

	const draw = () => {
		const vw = view.vw || 960;
		ctx.save();
		ctx.fillStyle = '#060b1a';
		ctx.fillRect(0, 0, vw, VH);
		shake.apply && shake.apply(ctx);
		// background grid
		ctx.strokeStyle = 'rgba(56,189,248,0.06)';
		ctx.lineWidth = 1;
		for (let x = (vw / 2) % 40; x < vw; x += 40) {
			ctx.beginPath();
			ctx.moveTo(x, 0);
			ctx.lineTo(x, VH);
			ctx.stroke();
		}
		for (let y = 12; y < VH; y += 40) {
			ctx.beginPath();
			ctx.moveTo(0, y);
			ctx.lineTo(vw, y);
			ctx.stroke();
		}
		const { cx, cy, R } = geo();
		if (s && s.flash) {
			ctx.fillStyle = `rgba(74,222,128,${s.flash * 0.12})`;
			ctx.fillRect(0, 0, vw, VH);
		}
		// ring (cable)
		ctx.lineCap = 'round';
		ctx.strokeStyle = OUTLINE;
		ctx.lineWidth = 22;
		ctx.beginPath();
		ctx.arc(cx, cy, R, 0, TAU);
		ctx.stroke();
		ctx.strokeStyle = '#1e293b';
		ctx.lineWidth = 15;
		ctx.stroke();
		ctx.setLineDash([3, 14]);
		ctx.strokeStyle = 'rgba(148,163,184,0.35)';
		ctx.lineWidth = 3;
		ctx.stroke();
		ctx.setLineDash([]);
		// port
		if (s && s.port) {
			const p = s.port;
			const col = p.golden ? '#fbbf24' : '#22c55e';
			const age = Math.min(1, (s.t - p.born) * 6);
			ctx.strokeStyle = OUTLINE;
			ctx.lineWidth = 26;
			ctx.beginPath();
			ctx.arc(cx, cy, R, p.c - p.hw * age, p.c + p.hw * age);
			ctx.stroke();
			ctx.strokeStyle = col;
			ctx.lineWidth = 19;
			ctx.stroke();
			// perfect zone
			ctx.strokeStyle = p.golden ? '#fef3c7' : '#bbf7d0';
			ctx.lineWidth = 7;
			ctx.beginPath();
			ctx.arc(cx, cy, R, p.c - p.hw * 0.32 * age, p.c + p.hw * 0.32 * age);
			ctx.stroke();
			// port label
			const lp = { x: cx + Math.cos(p.c) * (R + 34), y: cy + Math.sin(p.c) * (R + 34) };
			ctx.font = `900 19px ui-monospace, Menlo, Consolas, monospace`;
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.direction = 'ltr';
			ctx.fillStyle = col;
			ctx.fillText(p.golden ? '☕' : ':' + PORTS[s.hits % PORTS.length], lp.x, lp.y);
			ctx.direction = 'inherit';
		}
		// server in the middle
		drawServer(cx, cy - 6, R);
		// centre counter under the server
		if (s) {
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.font = `900 ${Math.round(R * 0.16)}px ${FONT}`;
			ctx.fillStyle = '#f8fafc';
			ctx.fillText(fa(s.hits), cx, cy + R * 0.52);
			ctx.font = `700 13px ui-monospace, Menlo, Consolas, monospace`;
			ctx.fillStyle = '#94a3b8';
			ctx.direction = 'ltr';
			ctx.fillText(CODES[Math.min(CODES.length - 1, s.level - 1)], cx, cy - R * 0.55);
			ctx.direction = 'inherit';
		}
		// packet + trail
		if (s && !s.dead) {
			s.trail.forEach((ang, i) => {
				const p = pos(ang, R);
				ctx.globalAlpha = (1 - i / s.trail.length) * 0.35;
				ctx.fillStyle = '#38bdf8';
				ctx.beginPath();
				ctx.arc(p.x, p.y, 7 - i * 0.5, 0, TAU);
				ctx.fill();
			});
			ctx.globalAlpha = 1;
			const p = pos(s.a, R);
			drawPacket(p.x, p.y, s.a, 1 + s.pulse * 0.25);
		}
		fx.draw(ctx);
		texts.draw(ctx);
		// intro hint
		if (s && !s.started && !s.dead) {
			ctx.fillStyle = 'rgba(2,6,23,0.78)';
			const bw = Math.min(vw - 24, 420);
			rr(cx - bw / 2, VH - 96, bw, 74, 16);
			ctx.fill();
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.fillStyle = '#f8fafc';
			ctx.font = `900 18px ${FONT}`;
			ctx.fillText('هر جای صفحه بزن تا بسته راه بیفتد', cx, VH - 70);
			ctx.font = `700 14px ${FONT}`;
			ctx.fillStyle = '#86efac';
			ctx.fillText('وقتی داخل پورت سبز است دوباره بزن', cx, VH - 44);
		}
		ctx.restore();
	};

	loop = createLoop((dt) => {
		if (!s) return;
		update(dt);
		draw();
	});

	/* ---------- input ---------- */
	L.on(cv.canvas, 'pointerdown', (e) => {
		if (e.button !== undefined && e.button > 0) return;
		e.preventDefault();
		tap();
	});
	L.on(cv.canvas, 'contextmenu', (e) => e.preventDefault());
	L.on(window, 'keydown', (e) => {
		if (!api.running || !s) return;
		if (e.target.closest && e.target.closest('input, textarea')) return;
		if (e.key === ' ' || e.key === 'Enter' || e.key === 'ArrowUp' || e.key === 'ArrowDown') {
			e.preventDefault();
			if (!e.repeat) tap();
		}
	});

	ready = true;
	s = newRun();
	openPort();
	draw();
	loadFont().then(() => !loop.running && draw());

	const inst = {
		/** test/debug hook (read-only use) */
		get _s() {
			return s;
		},
		/** test hook: signed angular distance packet → port centre and the half width */
		_probe() {
			if (!s || !s.port) return null;
			return { d: diff(s.a, s.port.c) * s.dir, hw: s.port.hw, started: s.started, dead: s.dead };
		},
		serialize() {
			if (!s || s.dead || !s.started) return null;
			return { hits: s.hits, score: s.score, level: s.level, w: s.w, combo: s.combo, perfects: s.perfects, bestCombo: s.bestCombo, golden: s.golden };
		},
		restore(d) {
			if (!d || typeof d.hits !== 'number') return;
			s = { ...newRun(), ...d, started: false };
			openPort();
			setHud();
			draw();
		},
		start() {
			s = newRun();
			fx.clear();
			openPort();
			setHud();
			loop.start();
		},
		pause() {
			loop.stop();
			draw();
		},
		resume() {
			loop.start();
		},
		destroy() {
			loop.stop();
			L.off();
			cv.destroy();
		},
	};
	stage.kmprDebug = inst; // QC hook (tools/game-qc/new-games)
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
