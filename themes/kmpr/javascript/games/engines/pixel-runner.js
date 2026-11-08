/**
 * «رانر پیکسلی» — original endless runner.
 * A pixel-art developer (hoodie + laptop) sprints through a server-room city:
 * jump ground bugs, slide under flying deadline clocks, double-jump merge-conflict walls,
 * grab coffee to fill the focus meter → invincible focus dash. Speed ramps, day/night shifts.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas, createLoop } from '../lib/canvas.js';
import { Particles, Shake, FloatTexts } from '../lib/fx.js';
import { clamp, rand, pick, listeners, GAME_KEYS, fa, FONT, loadFont } from '../lib/util.js';

export const achievements = CATALOG['pixel-runner'].achievements;

const VH = 360;
const GROUND = 300;
const PX = 3; // pixel size for sprites
const GRAV = 2300;
const JUMP_V = 760;
const DJUMP_V = 660;
const PLAYER_X = 90;
const M_PER_PX = 1 / 30;

// 8×12 sprite frames: . transparent, h hoodie, s skin, d dark (hair/shoes), l laptop, p pants, g glow
const RUN_A = [
	'..kkkk..',
	'.ksssk..',
	'..sss...',
	'.hhhhh..',
	'hhhhhhll',
	'hhhhhhlg',
	'.hhhh.ll',
	'.pppp...',
	'.pp.pp..',
	'pp...pp.',
	'p.....p.',
	'dd....dd',
];
const RUN_B = [
	'..kkkk..',
	'.ksssk..',
	'..sss...',
	'.hhhhh..',
	'hhhhhhll',
	'hhhhhhlg',
	'.hhhh.ll',
	'.pppp...',
	'..ppp...',
	'..pp.p..',
	'.pp..p..',
	'.dd..dd.',
];
const SLIDE = [
	'..........kkkk',
	'.........ksssk',
	'hhhhhhhhhhsss.',
	'hhhhhhhhhhllg.',
	'pppppppp..ll..',
	'dd....dd......',
];
const BUG = [
	'.y....y.',
	'..y..y..',
	'.rrrrrr.',
	'rrwrrwrr',
	'rrrrrrrr',
	'yrrrrrry',
	'y.y..y.y',
];
const COLORS = { h: '#f59e0b', k: '#7c2d12', s: '#fcd9b6', d: '#f8fafc', l: '#cbd5e1', p: '#2563eb', g: '#38bdf8', r: '#e11d48', w: '#fef2f2', y: '#fda4af' };

function sprite(ctx, rows, x, y, px = PX, tint) {
	for (let r = 0; r < rows.length; r++) {
		const row = rows[r];
		for (let c = 0; c < row.length; c++) {
			const ch = row[c];
			if (ch === '.') continue;
			ctx.fillStyle = tint || COLORS[ch];
			ctx.fillRect(x + c * px, y + r * px, px + 0.35, px + 0.35);
		}
	}
}

let current = null;

export function mount(stage, api) {
	api.setRatio('16/9', '4/5');
	const L = listeners();
	const fx = new Particles(api.reducedMotion ? 120 : 400);
	const shake = new Shake(!api.reducedMotion);
	const texts = new FloatTexts();
	let s = null;
	let skyline = [];

	let loop = null;
	let ready = false; // onResize fires synchronously inside createCanvas — wait until mount finished
	const cv = createCanvas(stage, {
		vh: VH,
		onResize: (v) => {
			if (!ready) return;
			buildSkyline(v.vw);
			if (!loop || !loop.running) draw();
		},
	});
	cv.canvas.setAttribute('aria-label', 'صحنه بازی رانر پیکسلی');
	const { ctx, view } = cv;

	function buildSkyline(w) {
		skyline = [];
		// two parallax layers of server racks / monitors
		for (let layer = 0; layer < 2; layer++) {
			let x = 0;
			const items = [];
			while (x < w + 400) {
				const bw = layer ? rand(40, 70) : rand(30, 50);
				const bh = layer ? rand(60, 130) : rand(90, 170);
				items.push({ x, w: bw, h: bh, kind: Math.random() < 0.3 ? 'monitor' : 'rack', leds: Array.from({ length: 6 }, () => Math.random()) });
				x += bw + rand(6, 24);
			}
			skyline.push({ layer, items, span: x, speed: layer ? 0.35 : 0.15 });
		}
	}

	const newRun = () => ({
		t: 0,
		dist: 0,
		speed: 300,
		coffee: 0,
		smashed: 0,
		bonus: 0,
		focus: 0,
		focusT: 0,
		p: { y: GROUND, vy: 0, onGround: true, jumps: 0, slideT: 0, usedDouble: false },
		obs: [],
		cups: [],
		spawn: 1.2,
		cupSpawn: 2,
		dead: false,
		frame: 0,
		frameT: 0,
		scroll: 0,
		lastLevel: 1,
	});

	const score = () => Math.floor(s.dist) + s.coffee * 50 + s.smashed * 100 + s.bonus;
	const level = () => 1 + Math.floor((s.speed - 300) / 70);

	/* ---------- actions ---------- */
	const jump = () => {
		if (!s || s.dead || !api.running) return;
		const p = s.p;
		if (p.onGround) {
			p.vy = -JUMP_V;
			p.onGround = false;
			p.jumps = 1;
			p.slideT = 0;
			api.sfx('jump');
			fx.burst(PLAYER_X + 12, GROUND, { n: 6, color: '#94a3b8', speed: 90, life: 0.3, angle: Math.PI, spread: 1.4 });
		} else if (p.jumps < 2) {
			p.vy = -DJUMP_V;
			p.jumps = 2;
			p.usedDouble = true;
			api.sfx([['square', 440, 880, 0.1, 0.05]]);
			fx.burst(PLAYER_X + 12, p.y, { n: 10, color: '#38bdf8', speed: 140, life: 0.35 });
		}
	};
	const slide = () => {
		if (!s || s.dead || !api.running) return;
		const p = s.p;
		if (p.onGround) {
			p.slideT = 0.65;
			api.sfx([['noise', 0, 0, 0.12, 0.04]]);
		} else {
			p.vy = Math.max(p.vy, 900); // fast fall
			p.slideT = 0.5;
		}
	};

	/* ---------- spawning ---------- */
	const spawnObstacle = () => {
		const lv = level();
		const r = Math.random();
		const x = view.vw + 40;
		if (r < 0.45 || lv < 2) {
			s.obs.push({ type: 'bug', x, y: GROUND - BUG.length * PX, w: 8 * PX, h: BUG.length * PX, t: 0 });
			if (lv >= 4 && Math.random() < 0.3) s.obs.push({ type: 'bug', x: x + 34, y: GROUND - BUG.length * PX, w: 8 * PX, h: BUG.length * PX, t: 0.3 });
		} else if (r < 0.78) {
			s.obs.push({ type: 'clock', x, y: GROUND - 64, w: 30, h: 30, t: 0 });
		} else {
			s.obs.push({ type: 'wall', x, y: GROUND - 132, w: 26, h: 132, t: 0, cleared: false });
		}
	};

	/* ---------- update ---------- */
	const hit = (o) => {
		const p = s.p;
		const sliding = p.slideT > 0 && p.onGround;
		const ph = sliding ? SLIDE.length * PX : 12 * PX;
		const pw = sliding ? 14 * PX : 8 * PX;
		const px = PLAYER_X + 3;
		const py = p.y - ph + 3;
		return px < o.x + o.w - 3 && px + pw - 6 > o.x + 3 && py < o.y + o.h - 2 && py + ph - 6 > o.y + 2;
	};

	const die = () => {
		s.dead = true;
		shake.add(10, 0.45);
		fx.burst(PLAYER_X + 12, s.p.y - 18, { n: 50, color: pick(['#d97706', '#fbbf24', '#fb7185']), speed: 300, life: 1, gravity: 600 });
		api.sfx('hurt');
		const sc = score();
		api.setScore(sc);
		// let the burst play for a moment before handing over to the shell
		setTimeoutDt(0.6, () =>
			api.over({ score: sc, meta: { level: level(), length: Math.floor(s.dist), combo: s.coffee, summary: `${fa(Math.floor(s.dist))} متر دویدید و ${fa(s.coffee)} فنجان قهوه جمع کردید.` } })
		);
	};

	// dt-based timers (pause-safe)
	let timers = [];
	const setTimeoutDt = (sec, fn) => timers.push({ t: sec, fn });

	const update = (dt) => {
		for (let i = timers.length - 1; i >= 0; i--) {
			timers[i].t -= dt;
			if (timers[i].t <= 0) {
				const f = timers[i].fn;
				timers.splice(i, 1);
				f();
			}
		}
		fx.update(dt);
		texts.update(dt);
		shake.update(dt);
		if (!s || s.dead) return;
		s.t += dt;
		const focusing = s.focusT > 0;
		s.speed = Math.min(760, 300 + s.t * 7);
		const v = s.speed * (focusing ? 1.6 : 1);
		s.dist += v * dt * M_PER_PX; // metres (1 m = 30 px)
		s.scroll += v * dt;
		const p = s.p;

		// animation
		s.frameT += dt * (v / 300);
		if (s.frameT > 0.12) {
			s.frameT = 0;
			s.frame ^= 1;
		}

		// physics
		if (!p.onGround) {
			p.vy += GRAV * dt;
			p.y += p.vy * dt;
			if (p.y >= GROUND) {
				p.y = GROUND;
				p.vy = 0;
				p.onGround = true;
				p.jumps = 0;
				fx.burst(PLAYER_X + 12, GROUND, { n: 5, color: '#64748b', speed: 70, life: 0.25, angle: -Math.PI / 2, spread: 2.5 });
			}
		}
		if (p.slideT > 0) p.slideT -= dt;
		if (focusing) {
			s.focusT -= dt;
			if (Math.random() < 0.8) fx.burst(PLAYER_X, p.y - 20, { n: 1, color: pick(['#38bdf8', '#a78bfa', '#fbbf24']), speed: 200, life: 0.35, angle: Math.PI, spread: 0.5 });
		}

		// spawns
		s.spawn -= dt;
		if (s.spawn <= 0) {
			spawnObstacle();
			const gap = clamp(1.5 - s.t * 0.012, 0.75, 1.5);
			s.spawn = gap * rand(0.8, 1.35);
		}
		s.cupSpawn -= dt;
		if (s.cupSpawn <= 0) {
			s.cupSpawn = rand(1.4, 3);
			s.cups.push({ x: view.vw + 30, y: GROUND - rand(40, 150), t: 0 });
		}

		// obstacles
		for (let i = s.obs.length - 1; i >= 0; i--) {
			const o = s.obs[i];
			o.x -= v * dt;
			o.t += dt;
			if (o.type === 'clock') o.y = GROUND - 64 + Math.sin(o.t * 4) * 5;
			if (o.x + o.w < -20) {
				s.obs.splice(i, 1);
				continue;
			}
			if (o.type === 'wall' && !o.cleared && o.x + o.w < PLAYER_X) {
				o.cleared = true;
				if (p.usedDouble) api.unlock('double');
			}
			if (hit(o)) {
				if (focusing) {
					s.obs.splice(i, 1);
					s.smashed++;
					shake.add(4, 0.15);
					fx.burst(o.x + o.w / 2, o.y + o.h / 2, { n: 24, color: o.type === 'bug' ? '#e11d48' : o.type === 'clock' ? '#fbbf24' : '#a78bfa', speed: 260, gravity: 500 });
					texts.add(o.x + o.w / 2, o.y - 10, '+' + fa(100), '#fde68a', 16);
					api.sfx('hit');
					continue;
				}
				die();
				return;
			}
		}
		if (p.onGround) p.usedDouble = false;

		// coffee
		for (let i = s.cups.length - 1; i >= 0; i--) {
			const c = s.cups[i];
			c.x -= v * dt;
			c.t += dt;
			if (c.x < -20) {
				s.cups.splice(i, 1);
				continue;
			}
			const ph = p.slideT > 0 && p.onGround ? 18 : 36;
			if (Math.abs(c.x - (PLAYER_X + 12)) < 22 && c.y > p.y - ph - 14 && c.y < p.y + 6) {
				s.cups.splice(i, 1);
				s.coffee++;
				api.sfx('coin');
				fx.burst(c.x, c.y, { n: 12, color: '#fde68a', speed: 140, life: 0.4 });
				texts.add(c.x, c.y - 12, '+' + fa(50), '#fde68a', 15);
				if (s.coffee >= 10) api.unlock('coffee10');
				if (!focusing) {
					s.focus = Math.min(1, s.focus + 0.2);
					if (s.focus >= 1) {
						s.focus = 0;
						s.focusT = 3.2;
						api.unlock('focus');
						api.sfx('level');
						api.toast('<span class="game-toast__icon" aria-hidden="true">🧘</span><span><b>حالت تمرکز!</b> هیچ چیز جلودارت نیست</span>');
					}
				}
			}
		}

		const lv = level();
		if (lv !== s.lastLevel) {
			s.lastLevel = lv;
			api.hud({ level: lv });
		}
		const m = Math.floor(s.dist);
		if (m >= 500) api.unlock('m500');
		if (m >= 2000) api.unlock('m2000');
		const sc = score();
		if (sc >= 10000) api.unlock('s10k');
		if (sc !== s.shown) {
			s.shown = sc;
			api.setScore(sc);
		}
		if (m !== s.shownM) {
			s.shownM = m;
			api.hud({ extra: fa(m) + ' متر' });
		}
	};

	/* ---------- render ---------- */
	const mix = (a, b, t) => {
		const pa = a.match(/\w\w/g).map((x) => parseInt(x, 16));
		const pb = b.match(/\w\w/g).map((x) => parseInt(x, 16));
		return '#' + pa.map((x, i) => Math.round(x + (pb[i] - x) * t).toString(16).padStart(2, '0')).join('');
	};

	const drawBg = () => {
		const d = s ? s.dist : 0;
		// day/night: cycles every 1000 m
		const phase = (d % 1000) / 1000;
		const night = 0.5 - 0.5 * Math.cos(phase * Math.PI * 2); // 0 day → 1 night → 0
		const top = mix('#1e3a8a', '#020617', night);
		const bot = mix('#f59e0b', '#1e1b4b', night);
		const g = ctx.createLinearGradient(0, 0, 0, GROUND);
		g.addColorStop(0, top);
		g.addColorStop(1, bot);
		ctx.fillStyle = g;
		ctx.fillRect(-20, -20, view.vw + 40, VH + 40);
		// sun / moon
		ctx.globalAlpha = 0.9;
		ctx.fillStyle = night > 0.5 ? '#e2e8f0' : '#fde68a';
		const sy = 60 + Math.abs(night - 0.5) * 60;
		const sx = view.vw * 0.78;
		for (let y = -4; y <= 4; y++) {
			const w = Math.round(Math.sqrt(16 - y * y) * 2);
			ctx.fillRect(Math.round(sx - w * 3), Math.round(sy + y * 6), w * 6, 6);
		}
		ctx.globalAlpha = 1;
		// stars at night
		if (night > 0.4) {
			ctx.fillStyle = '#e2e8f0';
			ctx.globalAlpha = (night - 0.4) * 1.6;
			for (let i = 0; i < 40; i++) {
				const x = (i * 97.3 + 13) % view.vw;
				const y = (i * 53.7) % (GROUND - 140);
				ctx.fillRect(Math.round(x), Math.round(y), 2, 2);
			}
			ctx.globalAlpha = 1;
		}
		// skyline layers
		const sc = s ? s.scroll : performance.now() * 0.03;
		for (const layer of skyline) {
			const off = (sc * layer.speed) % layer.span;
			const base = layer.layer ? GROUND : GROUND - 30;
			const col = layer.layer ? mix('#1e293b', '#0b1222', night) : mix('#2b3a55', '#111827', night);
			for (const it of layer.items) {
				let x = it.x - off;
				if (x + it.w < 0) x += layer.span;
				if (x > view.vw) continue;
				const y = base - it.h;
				ctx.fillStyle = col;
				ctx.fillRect(Math.round(x), Math.round(y), Math.round(it.w), it.h);
				if (it.kind === 'monitor') {
					ctx.fillStyle = layer.layer ? mix('#0ea5e9', '#38bdf8', night) : '#1e40af';
					ctx.globalAlpha = layer.layer ? 0.4 : 0.25;
					ctx.fillRect(Math.round(x + 5), Math.round(y + 6), Math.round(it.w - 10), Math.round(Math.min(40, it.h * 0.3)));
					ctx.globalAlpha = 1;
				} else {
					// rack units + blinking LEDs
					for (let u = 0; u < 6; u++) {
						const uy = y + 10 + u * (it.h / 7);
						ctx.fillStyle = 'rgba(148,163,184,0.12)';
						ctx.fillRect(Math.round(x + 4), Math.round(uy), Math.round(it.w - 8), 3);
						const on = Math.sin((s ? s.t : performance.now() / 1000) * 4 + it.leds[u] * 20) > 0.2;
						ctx.fillStyle = on ? (it.leds[u] > 0.5 ? '#34d399' : '#fbbf24') : 'rgba(51,65,85,0.8)';
						ctx.fillRect(Math.round(x + it.w - 9), Math.round(uy), 3, 3);
					}
				}
			}
		}
		// ground
		ctx.fillStyle = mix('#334155', '#1e293b', night);
		ctx.fillRect(-10, GROUND, view.vw + 20, VH - GROUND + 20);
		ctx.fillStyle = '#d97706';
		ctx.fillRect(-10, GROUND, view.vw + 20, 3);
		const go = (s ? s.scroll : 0) % 24;
		ctx.fillStyle = 'rgba(15,23,42,0.55)';
		for (let x = -go; x < view.vw + 24; x += 24) {
			ctx.fillRect(Math.round(x), GROUND + 9, 12, 3);
			ctx.fillRect(Math.round(x + 12), GROUND + 24, 9, 3);
		}
	};

	const drawClock = (o) => {
		const cx = o.x + 15;
		const cy = o.y + 15;
		ctx.fillStyle = '#fbbf24';
		ctx.fillRect(Math.round(o.x), Math.round(o.y + 3), 30, 24);
		ctx.fillRect(Math.round(o.x + 3), Math.round(o.y), 24, 30);
		ctx.fillStyle = '#fff7ed';
		ctx.fillRect(Math.round(o.x + 4), Math.round(o.y + 6), 22, 18);
		ctx.fillRect(Math.round(o.x + 6), Math.round(o.y + 4), 18, 22);
		ctx.fillStyle = '#b91c1c';
		const a = o.t * 6;
		for (let k = 0; k < 4; k++) ctx.fillRect(Math.round(cx + Math.cos(a) * k * 2.5 - 1), Math.round(cy + Math.sin(a) * k * 2.5 - 1), 3, 3);
		ctx.fillStyle = '#0f172a';
		ctx.fillRect(Math.round(cx - 1), Math.round(cy - 7), 3, 8);
		// wings (it flies!)
		ctx.fillStyle = '#e2e8f0';
		const flap = Math.sin(o.t * 18) > 0 ? 0 : 4;
		ctx.fillRect(Math.round(o.x - 8), Math.round(o.y + 6 + flap), 8, 4);
		ctx.fillRect(Math.round(o.x + 30), Math.round(o.y + 6 + flap), 8, 4);
		// label
		ctx.font = `700 12px ${FONT}`;
		ctx.textAlign = 'center';
		ctx.fillStyle = '#fecaca';
		ctx.fillText('ددلاین', cx, o.y - 8);
	};

	const drawWall = (o) => {
		// merge-conflict wall: stacked <<<<<<< / >>>>>>> blocks
		const rows = Math.floor(o.h / 12);
		for (let r = 0; r < rows; r++) {
			const y = o.y + r * 12;
			ctx.fillStyle = r % 2 ? '#7c3aed' : '#a78bfa';
			ctx.fillRect(Math.round(o.x), Math.round(y), o.w, 11);
			ctx.fillStyle = 'rgba(15,23,42,0.45)';
			ctx.fillRect(Math.round(o.x + (r % 2 ? 4 : 13)), Math.round(y + 4), 6, 3);
		}
		ctx.font = `900 12px ${FONT}`;
		ctx.textAlign = 'center';
		ctx.fillStyle = '#ddd6fe';
		ctx.direction = 'ltr';
		ctx.fillText('<<<<', o.x + o.w / 2, o.y - 8);
		ctx.direction = 'inherit';
	};

	const drawCup = (c) => {
		const x = Math.round(c.x - 9);
		const y = Math.round(c.y - 9 + Math.sin(c.t * 5) * 2);
		ctx.fillStyle = '#f8fafc';
		ctx.fillRect(x, y + 4, 15, 14);
		ctx.fillRect(x + 15, y + 7, 4, 3);
		ctx.fillRect(x + 18, y + 7, 3, 8);
		ctx.fillRect(x + 15, y + 13, 4, 3);
		ctx.fillStyle = '#92400e';
		ctx.fillRect(x + 2, y + 6, 11, 4);
		ctx.fillStyle = 'rgba(226,232,240,0.6)';
		const st = Math.floor(c.t * 6) % 2;
		ctx.fillRect(x + 3 + st * 3, y - 2, 2, 4);
		ctx.fillRect(x + 9 - st * 3, y - 4, 2, 4);
	};

	const draw = () => {
		ctx.save();
		cv.reset();
		shake.apply(ctx);
		drawBg();
		const p = s ? s.p : { y: GROUND, onGround: true, slideT: 0 };
		if (s) {
			for (const c of s.cups) drawCup(c);
			for (const o of s.obs) {
				if (o.type === 'bug') sprite(ctx, BUG, Math.round(o.x), Math.round(o.y + (Math.sin(o.t * 20) > 0 ? 0 : -1)), PX);
				else if (o.type === 'clock') drawClock(o);
				else drawWall(o);
			}
		}
		// player
		if (!s || !s.dead) {
			const focusing = s && s.focusT > 0;
			if (focusing) {
				ctx.globalAlpha = 0.25;
				sprite(ctx, RUN_A, PLAYER_X - 14, Math.round(p.y - 12 * PX), PX, '#38bdf8');
				ctx.globalAlpha = 1;
			}
			if (p.slideT > 0 && p.onGround) sprite(ctx, SLIDE, PLAYER_X - 8, Math.round(p.y - SLIDE.length * PX), PX);
			else sprite(ctx, !p.onGround ? RUN_B : s && s.frame ? RUN_B : RUN_A, PLAYER_X, Math.round(p.y - 12 * PX), PX);
		}
		fx.draw(ctx);
		texts.draw(ctx);

		// focus meter
		if (s) {
			const w = Math.min(180, view.vw * 0.35);
			const x = 16;
			const y = 16;
			ctx.fillStyle = 'rgba(2,6,23,0.65)';
			ctx.fillRect(x - 4, y - 4, w + 8, 28);
			ctx.fillStyle = 'rgba(148,163,184,0.25)';
			ctx.fillRect(x, y + 14, w, 6);
			const f = s.focusT > 0 ? s.focusT / 3.2 : s.focus;
			ctx.fillStyle = s.focusT > 0 ? '#38bdf8' : '#fbbf24';
			ctx.fillRect(x, y + 14, w * f, 6);
			ctx.font = `700 12px ${FONT}`;
			ctx.textAlign = 'left';
			ctx.textBaseline = 'alphabetic';
			ctx.fillStyle = '#e2e8f0';
			ctx.fillText(s.focusT > 0 ? 'تمرکز فعال!' : '☕ تمرکز', x, y + 9);
		}
		ctx.restore();
	};

	loop = createLoop((dt) => {
		update(dt);
		draw();
	});

	/* ---------- input ---------- */
	L.on(window, 'keydown', (e) => {
		if (!api.running) return;
		const k = e.key;
		if (k === ' ' || k === 'ArrowUp' || k === 'w' || k === 'W' || k === 'ص') {
			if (!e.repeat) jump();
			e.preventDefault();
		} else if (k === 'ArrowDown' || k === 's' || k === 'S' || k === 'س') {
			slide();
			e.preventDefault();
		} else if (GAME_KEYS.has(k)) e.preventDefault();
	});
	let touch = null;
	L.on(cv.canvas, 'pointerdown', (e) => {
		if (!api.running) return;
		touch = { x: e.clientX, y: e.clientY, id: e.pointerId, used: false };
		try {
			cv.canvas.setPointerCapture(e.pointerId);
		} catch (err) {
			/* ignore */
		}
	});
	L.on(cv.canvas, 'pointermove', (e) => {
		if (!touch || touch.id !== e.pointerId || touch.used) return;
		if (e.clientY - touch.y > 26) {
			touch.used = true;
			slide();
		} else if (touch.y - e.clientY > 26) {
			touch.used = true;
			jump();
		}
	});
	L.on(cv.canvas, 'pointerup', (e) => {
		if (!touch || touch.id !== e.pointerId) return;
		if (!touch.used) jump();
		touch = null;
	});
	L.on(cv.canvas, 'pointercancel', () => (touch = null));

	ready = true;
	buildSkyline(view.vw);
	draw();
	loadFont().then(() => !loop.running && draw());

	const inst = {
		/** test/debug hook (read-only use) */
		get _s() {
			return s;
		},
		/** Optional shell hook: snapshot of the run (obstacles/cups included, a few hundred bytes). */
		serialize() {
			if (!s || s.dead || s.t < 1) return null;
			return JSON.parse(JSON.stringify(s));
		},
		restore(d) {
			if (!d || typeof d.dist !== 'number') return;
			s = { ...newRun(), ...d, dead: false };
			timers = [];
			api.hud({ level: level(), levelLabel: 'سرعت', extra: fa(Math.floor(s.dist)) + ' متر', extraLabel: 'مسافت' });
			api.setScore(score());
			draw();
		},
		start() {
			s = newRun();
			timers = [];
			fx.clear();
			api.hud({ level: 1, levelLabel: 'سرعت', extra: fa(0) + ' متر', extraLabel: 'مسافت' });
			api.setScore(0);
			loop.start();
		},
		pause() {
			loop.stop();
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
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
