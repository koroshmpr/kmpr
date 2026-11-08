/**
 * JotA — «سفر فضانورد» (Journey of the Astronaut)
 * Re-imagined from my old React prototype (github.com/koroshmpr/React-Games/JotA):
 * the same astronaut, now a side-scrolling space shooter with stages, bosses,
 * power-ups, combo multiplier, parallax stars, particles and screen shake.
 */
import { CATALOG } from '../catalog.js';
import astronautSrc from '../assets/astronaut.webp';
import { createCanvas, createLoop } from '../lib/canvas.js';
import { Particles, Shake, Starfield, FloatTexts } from '../lib/fx.js';
import { clamp, rand, pick, listeners, GAME_KEYS, fa, loadFont } from '../lib/util.js';

export const achievements = CATALOG.jota.achievements;

const STAGES = [
	{ name: 'مدار زمین', hue: '#38bdf8', planet: '#1d4ed8' },
	{ name: 'کمربند سیارک', hue: '#f59e0b', planet: '#92400e' },
	{ name: 'قلمرو ناشناخته', hue: '#a78bfa', planet: '#5b21b6' },
	{ name: 'قمر یخی', hue: '#34d399', planet: '#0f766e' },
	{ name: 'سحابی سرخ', hue: '#fb7185', planet: '#9f1239' },
];
const STAGE_TIME = 32;
const VH = 540;

let current = null;

export function mount(stage, api) {
	const img = new Image();
	img.src = astronautSrc;
	const L = listeners();
	const fx = new Particles(api.reducedMotion ? 160 : 600);
	const shake = new Shake(!api.reducedMotion);
	const stars = new Starfield();
	const texts = new FloatTexts();
	const keys = new Set();
	let s = null; // run state
	let pointer = null;
	let loop = null;
	let ready = false;

	const cv = createCanvas(stage, {
		vh: VH,
		onResize: (v) => {
			stars.resize(v.vw, VH);
			if (ready && (!loop || !loop.running)) draw();
		},
	});
	cv.canvas.setAttribute('aria-label', 'صحنه بازی سفر فضانورد');
	const { ctx, view } = cv;

	const newRun = () => ({
		t: 0,
		score: 0,
		lives: 3,
		stage: 1,
		stageT: 0,
		banner: 2.2,
		boss: null,
		bossDue: false,
		hitThisStage: false,
		p: { x: 140, y: VH / 2, vx: 0, vy: 0, inv: 2, fire: 0, triple: 0, shield: 0 },
		bullets: [],
		enemyShots: [],
		rocks: [],
		drones: [],
		pickups: [],
		spawnRock: 1,
		spawnDrone: 6,
		spawnStar: 3,
		combo: 0,
		comboT: 0,
		maxMult: 1,
		kills: 0,
	});

	const theme = () => STAGES[(s ? s.stage - 1 : 0) % STAGES.length];
	const mult = () => Math.min(5, 1 + Math.floor(s.combo / 4));

	/* ---------- spawning ---------- */
	const rockShape = (r) => Array.from({ length: 9 }, (_, i) => {
		const a = (i / 9) * Math.PI * 2;
		return [Math.cos(a) * r * rand(0.75, 1.1), Math.sin(a) * r * rand(0.75, 1.1)];
	});
	const addRock = (x, y, size = 3, vx, vy) => {
		const r = [0, 14, 22, 34][size];
		const speed = 1 + (s.stage - 1) * 0.12;
		s.rocks.push({ x, y, r, size, hp: size, vx: vx ?? -rand(80, 150) * speed, vy: vy ?? rand(-30, 30), rot: rand(0, 6), vr: rand(-1.5, 1.5), shape: rockShape(r), flash: 0 });
	};
	const addDrone = () => {
		s.drones.push({ x: view.vw + 40, y: rand(60, VH - 60), baseY: 0, r: 20, hp: 2 + Math.floor(s.stage / 3), t: rand(0, 6), fire: rand(1.2, 2.6), flash: 0 });
		s.drones[s.drones.length - 1].baseY = s.drones[s.drones.length - 1].y;
	};
	const addPickup = (x, y, kind) => s.pickups.push({ x, y, kind, t: 0, r: 14 });
	const spawnBoss = () => {
		const hp = 40 + s.stage * 14;
		s.boss = { x: view.vw + 120, y: VH / 2, r: 58, hp, max: hp, t: 0, fire: 2, flash: 0, enter: true };
		api.toast('<span class="game-toast__icon" aria-hidden="true">🛸</span><span><b>هشدار!</b> غول این مرحله رسید</span>', 'warn');
		api.sfx('hurt');
	};

	/* ---------- scoring ---------- */
	const gain = (pts, x, y) => {
		s.combo++;
		s.comboT = 1.8;
		const m = mult();
		if (m > s.maxMult) s.maxMult = m;
		if (m >= 5) api.unlock('combo');
		const v = pts * m;
		s.score += v;
		api.setScore(s.score);
		if (x !== undefined) texts.add(x, y, '+' + fa(v), m > 1 ? '#fbbf24' : '#e2e8f0', m > 1 ? 18 : 15);
		if (s.score >= 25000) api.unlock('s25k');
	};

	const hurt = () => {
		const p = s.p;
		if (p.inv > 0) return;
		if (p.shield > 0) {
			p.shield = 0;
			p.inv = 1;
			fx.burst(p.x, p.y, { n: 24, color: '#38bdf8', speed: 260 });
			api.sfx('hit');
			return;
		}
		s.lives--;
		s.hitThisStage = true;
		s.combo = 0;
		p.inv = 2.2;
		shake.add(12, 0.4);
		fx.burst(p.x, p.y, { n: 40, color: '#fb7185', speed: 320, life: 0.9 });
		api.sfx('hurt');
		api.hud({ lives: s.lives });
		if (s.lives <= 0) {
			fx.burst(p.x, p.y, { n: 80, color: '#f59e0b', speed: 420, life: 1.2 });
			gameOver();
		}
	};

	const gameOver = () => {
		s.dead = true;
		draw();
		api.over({ score: s.score, meta: { stage: s.stage, combo: s.maxMult, lives: 0, summary: `به مرحله ${fa(s.stage)} (${theme().name}) رسیدید و ${fa(s.kills)} هدف را منفجر کردید.` } });
	};

	const nextStage = () => {
		if (!s.hitThisStage) api.unlock('flawless');
		s.stage++;
		s.stageT = 0;
		s.banner = 2.2;
		s.hitThisStage = false;
		s.bossDue = false;
		if (s.stage >= 3) api.unlock('stage3');
		gain(500 * (s.stage - 1));
		api.hud({ level: s.stage });
		api.sfx('level');
	};

	/* ---------- update ---------- */
	const update = (dt) => {
		if (!s || s.dead) return;
		s.t += dt;
		const p = s.p;
		const speed = 1 + (s.stage - 1) * 0.12;
		stars.update(dt, 1 + (s.stage - 1) * 0.1);
		fx.update(dt);
		texts.update(dt);
		shake.update(dt);
		if (s.banner > 0) s.banner -= dt;

		// player movement
		const acc = 2600;
		let ax = 0;
		let ay = 0;
		if (keys.has('left')) ax -= 1;
		if (keys.has('right')) ax += 1;
		if (keys.has('up')) ay -= 1;
		if (keys.has('down')) ay += 1;
		if (pointer) {
			const tx = clamp(pointer.x + pointer.ox, 30, view.vw - 30);
			const ty = clamp(pointer.y + pointer.oy, 30, VH - 30);
			p.vx = (tx - p.x) * 12;
			p.vy = (ty - p.y) * 12;
		} else {
			p.vx += ax * acc * dt;
			p.vy += ay * acc * dt;
			p.vx *= Math.pow(0.0025, dt);
			p.vy *= Math.pow(0.0025, dt);
			p.vx = clamp(p.vx, -420, 420);
			p.vy = clamp(p.vy, -420, 420);
		}
		p.x = clamp(p.x + p.vx * dt, 30, view.vw - 30);
		p.y = clamp(p.y + p.vy * dt, 30, VH - 30);
		p.inv = Math.max(0, p.inv - dt);
		p.triple = Math.max(0, p.triple - dt);
		p.shield = Math.max(0, p.shield - dt);

		// jetpack exhaust
		if (Math.random() < (api.reducedMotion ? 0.3 : 0.9)) {
			fx.burst(p.x - 26, p.y + 8, { n: 1, color: pick(['#f59e0b', '#fbbf24', '#fb7185']), speed: 140, life: 0.35, size: 4, angle: Math.PI, spread: 0.6 });
		}

		// auto-fire
		p.fire -= dt;
		if (p.fire <= 0 && s.banner < 1.6) {
			p.fire = p.triple > 0 ? 0.13 : 0.2;
			const gx = p.x + 34;
			const gy = p.y - 2;
			s.bullets.push({ x: gx, y: gy, vx: 760, vy: 0 });
			if (p.triple > 0) {
				s.bullets.push({ x: gx, y: gy, vx: 740, vy: -150 }, { x: gx, y: gy, vx: 740, vy: 150 });
			}
			api.sfx('shoot');
		}

		// stage timer / boss
		if (!s.boss && s.banner <= 0) {
			s.stageT += dt;
			if (s.stageT >= STAGE_TIME) {
				if (s.stage % 3 === 0 && !s.bossDue) {
					s.bossDue = true;
					spawnBoss();
				} else if (!s.bossDue) nextStage();
			}
		}

		// spawns (not during banner, fewer during boss)
		if (s.banner <= 0) {
			s.spawnRock -= dt;
			if (s.spawnRock <= 0) {
				s.spawnRock = Math.max(0.32, 1.25 - s.stage * 0.1) * (s.boss ? 2.5 : 1) * rand(0.7, 1.3);
				addRock(view.vw + 40, rand(40, VH - 40), Math.random() < 0.55 ? 3 : 2);
			}
			if (s.stage >= 2) {
				s.spawnDrone -= dt;
				if (s.spawnDrone <= 0) {
					s.spawnDrone = Math.max(1.6, 5 - s.stage * 0.4) * (s.boss ? 2 : 1);
					addDrone();
				}
			}
			s.spawnStar -= dt;
			if (s.spawnStar <= 0) {
				s.spawnStar = rand(2.5, 4.5);
				addPickup(view.vw + 20, rand(50, VH - 50), 'star');
			}
		}

		// bullets
		for (let i = s.bullets.length - 1; i >= 0; i--) {
			const b = s.bullets[i];
			b.x += b.vx * dt;
			b.y += b.vy * dt;
			if (b.x > view.vw + 20 || b.y < -20 || b.y > VH + 20) s.bullets.splice(i, 1);
		}
		const hitTest = (o) => {
			for (let i = s.bullets.length - 1; i >= 0; i--) {
				const b = s.bullets[i];
				const dx = b.x - o.x;
				const dy = b.y - o.y;
				if (dx * dx + dy * dy < (o.r + 4) * (o.r + 4)) {
					s.bullets.splice(i, 1);
					fx.burst(b.x, b.y, { n: 4, color: api.accent, speed: 120, life: 0.25, size: 2 });
					return true;
				}
			}
			return false;
		};
		const touches = (o, pad = 0) => {
			const dx = o.x - p.x;
			const dy = o.y - p.y;
			const r = o.r + 22 + pad;
			return dx * dx + dy * dy < r * r;
		};

		// rocks
		for (let i = s.rocks.length - 1; i >= 0; i--) {
			const r = s.rocks[i];
			r.x += r.vx * dt;
			r.y += r.vy * dt;
			r.rot += r.vr * dt;
			r.flash = Math.max(0, r.flash - dt);
			if (r.y < r.r || r.y > VH - r.r) r.vy *= -1;
			if (r.x < -60) {
				s.rocks.splice(i, 1);
				continue;
			}
			if (hitTest(r)) {
				r.hp--;
				r.flash = 0.08;
				api.sfx('hit');
				if (r.hp <= 0) {
					s.rocks.splice(i, 1);
					s.kills++;
					api.unlock('first_kill');
					fx.burst(r.x, r.y, { n: 10 + r.size * 8, color: pick(['#94a3b8', '#cbd5e1', '#f59e0b']), speed: 180 + r.size * 40 });
					shake.add(r.size * 1.5, 0.15);
					gain([0, 25, 50, 100][r.size], r.x, r.y);
					if (r.size > 1) {
						addRock(r.x, r.y, r.size - 1, r.vx * 0.9, -rand(40, 90));
						addRock(r.x, r.y, r.size - 1, r.vx * 0.9, rand(40, 90));
					}
					if (Math.random() < 0.08) addPickup(r.x, r.y, pick(['oxygen', 'triple', 'shield', 'triple']));
				}
				continue;
			}
			if (touches(r, -6)) {
				s.rocks.splice(i, 1);
				fx.burst(r.x, r.y, { n: 20, color: '#94a3b8', speed: 200 });
				hurt();
				if (s.dead) return;
			}
		}

		// drones
		for (let i = s.drones.length - 1; i >= 0; i--) {
			const d = s.drones[i];
			d.t += dt;
			d.x -= 110 * speed * dt;
			d.y = clamp(d.baseY + Math.sin(d.t * 2.2) * 70, 30, VH - 30);
			d.flash = Math.max(0, d.flash - dt);
			d.fire -= dt;
			if (d.fire <= 0 && d.x < view.vw - 20 && d.x > p.x + 60) {
				d.fire = rand(1.6, 2.8) / Math.min(2, speed);
				const a = Math.atan2(p.y - d.y, p.x - d.x);
				s.enemyShots.push({ x: d.x, y: d.y, vx: Math.cos(a) * 220, vy: Math.sin(a) * 220, r: 6 });
			}
			if (d.x < -40) {
				s.drones.splice(i, 1);
				continue;
			}
			if (hitTest(d)) {
				d.hp--;
				d.flash = 0.08;
				api.sfx('hit');
				if (d.hp <= 0) {
					s.drones.splice(i, 1);
					s.kills++;
					fx.burst(d.x, d.y, { n: 30, color: pick(['#a78bfa', '#c4b5fd', '#f0abfc']), speed: 260 });
					shake.add(4, 0.2);
					gain(150, d.x, d.y);
					if (Math.random() < 0.2) addPickup(d.x, d.y, pick(['oxygen', 'triple', 'shield']));
				}
				continue;
			}
			if (touches(d, -4)) {
				s.drones.splice(i, 1);
				hurt();
				if (s.dead) return;
			}
		}

		// boss
		const B = s.boss;
		if (B) {
			B.t += dt;
			B.flash = Math.max(0, B.flash - dt);
			const targetX = view.vw - 110;
			if (B.enter) {
				B.x -= 120 * dt;
				if (B.x <= targetX) B.enter = false;
			} else {
				B.x = targetX + Math.sin(B.t * 0.7) * 30;
			}
			B.y = VH / 2 + Math.sin(B.t * 1.1) * (VH / 2 - 90);
			B.fire -= dt;
			if (B.fire <= 0 && !B.enter) {
				B.fire = Math.max(0.8, 1.8 - s.stage * 0.08);
				const base = Math.atan2(p.y - B.y, p.x - B.x);
				const n = s.stage >= 6 ? 7 : 5;
				for (let k = 0; k < n; k++) {
					const a = base + (k - (n - 1) / 2) * 0.22;
					s.enemyShots.push({ x: B.x - 40, y: B.y, vx: Math.cos(a) * 240, vy: Math.sin(a) * 240, r: 7 });
				}
			}
			if (hitTest(B)) {
				B.hp--;
				B.flash = 0.06;
				if (B.hp % 5 === 0) api.sfx('hit');
				if (B.hp <= 0) {
					fx.burst(B.x, B.y, { n: 140, color: '#fb7185', speed: 480, life: 1.4, size: 5 });
					fx.burst(B.x, B.y, { n: 80, color: '#fbbf24', speed: 380, life: 1.1 });
					shake.add(16, 0.7);
					api.sfx('boom');
					s.kills++;
					gain(2000 * Math.ceil(s.stage / 3), B.x, B.y);
					api.unlock('boss');
					s.boss = null;
					addPickup(B.x, B.y, 'oxygen');
					nextStage();
				}
			} else if (touches(B, -10)) {
				hurt();
				if (s.dead) return;
			}
		}

		// enemy shots
		for (let i = s.enemyShots.length - 1; i >= 0; i--) {
			const e = s.enemyShots[i];
			e.x += e.vx * dt;
			e.y += e.vy * dt;
			if (e.x < -20 || e.x > view.vw + 20 || e.y < -20 || e.y > VH + 20) {
				s.enemyShots.splice(i, 1);
				continue;
			}
			if (touches(e, -10)) {
				s.enemyShots.splice(i, 1);
				hurt();
				if (s.dead) return;
			}
		}

		// pickups
		for (let i = s.pickups.length - 1; i >= 0; i--) {
			const k = s.pickups[i];
			k.t += dt;
			k.x -= 90 * dt;
			k.y += Math.sin(k.t * 3) * 0.4;
			if (k.x < -30) {
				s.pickups.splice(i, 1);
				continue;
			}
			if (touches(k, 6)) {
				s.pickups.splice(i, 1);
				api.sfx('coin');
				fx.burst(k.x, k.y, { n: 14, color: '#fde68a', speed: 160 });
				if (k.kind === 'star') gain(60, k.x, k.y);
				else if (k.kind === 'oxygen') {
					s.lives = Math.min(5, s.lives + 1);
					api.hud({ lives: s.lives });
					texts.add(k.x, k.y, 'اکسیژن +۱', '#34d399');
				} else if (k.kind === 'triple') {
					p.triple = 8;
					texts.add(k.x, k.y, 'شلیک سه‌گانه', '#38bdf8');
				} else if (k.kind === 'shield') {
					p.shield = 6;
					texts.add(k.x, k.y, 'سپر', '#38bdf8');
				}
			}
		}

		// combo decay
		if (s.comboT > 0) {
			s.comboT -= dt;
			if (s.comboT <= 0) s.combo = 0;
		}
	};

	/* ---------- render ---------- */
	const drawBg = () => {
		const th = theme();
		const g = ctx.createLinearGradient(0, 0, 0, VH);
		g.addColorStop(0, '#020617');
		g.addColorStop(1, '#0b1028');
		ctx.fillStyle = g;
		ctx.fillRect(-20, -20, view.vw + 40, VH + 40);
		// nebula glow
		const t = s ? s.t : 0;
		const ng = ctx.createRadialGradient(view.vw * 0.7, VH * 0.3, 10, view.vw * 0.7, VH * 0.3, VH * 0.8);
		ng.addColorStop(0, th.hue + '30');
		ng.addColorStop(1, 'transparent');
		ctx.fillStyle = ng;
		ctx.fillRect(0, 0, view.vw, VH);
		// distant planet (slow parallax)
		const px = ((view.vw + 260 - ((t * 6) % (view.vw + 520))) | 0) + 0;
		const pr = 90;
		const pg = ctx.createRadialGradient(px - 30, VH * 0.72 - 30, 10, px, VH * 0.72, pr);
		pg.addColorStop(0, th.hue);
		pg.addColorStop(0.6, th.planet);
		pg.addColorStop(1, '#020617');
		ctx.fillStyle = pg;
		ctx.beginPath();
		ctx.arc(px, VH * 0.72, pr, 0, Math.PI * 2);
		ctx.fill();
		ctx.strokeStyle = th.hue + '55';
		ctx.lineWidth = 3;
		ctx.beginPath();
		ctx.ellipse(px, VH * 0.72, pr * 1.6, pr * 0.32, -0.3, 0, Math.PI * 2);
		ctx.stroke();
		stars.draw(ctx);
	};

	const drawAstronaut = (p, alpha = 1) => {
		ctx.save();
		ctx.translate(p.x, p.y);
		ctx.rotate(clamp(p.vy / 1600, -0.25, 0.25));
		ctx.globalAlpha = alpha;
		if (img.complete && img.naturalWidth) ctx.drawImage(img, -38, -38, 76, 76);
		else {
			ctx.fillStyle = '#e2e8f0';
			ctx.beginPath();
			ctx.arc(0, 0, 26, 0, Math.PI * 2);
			ctx.fill();
		}
		ctx.restore();
		if (p.shield > 0) {
			ctx.strokeStyle = `rgba(56,189,248,${0.4 + 0.3 * Math.sin(performance.now() / 90)})`;
			ctx.lineWidth = 3;
			ctx.beginPath();
			ctx.arc(p.x, p.y, 44, 0, Math.PI * 2);
			ctx.stroke();
		}
	};

	const drawSaucer = (x, y, r, body, flash) => {
		ctx.fillStyle = flash > 0 ? '#ffffff' : body;
		ctx.beginPath();
		ctx.ellipse(x, y, r * 1.3, r * 0.5, 0, 0, Math.PI * 2);
		ctx.fill();
		ctx.fillStyle = flash > 0 ? '#ffffff' : 'rgba(186,230,253,0.85)';
		ctx.beginPath();
		ctx.ellipse(x, y - r * 0.3, r * 0.6, r * 0.5, 0, Math.PI, 0);
		ctx.fill();
		ctx.fillStyle = '#fde68a';
		for (let k = -2; k <= 2; k++) {
			ctx.fillRect(x + k * r * 0.45 - 2, y + r * 0.05, 4, 4);
		}
	};

	const draw = () => {
		ctx.save();
		cv.reset();
		if (s) shake.apply(ctx);
		drawBg();
		if (!s) {
			// attract frame
			drawAstronaut({ x: Math.min(view.vw * 0.3, 220), y: VH / 2 + Math.sin(performance.now() / 600) * 10, vy: 0, shield: 0 });
			ctx.restore();
			return;
		}
		// pickups
		for (const k of s.pickups) {
			ctx.save();
			ctx.translate(k.x, k.y);
			if (k.kind === 'star') {
				ctx.rotate(k.t * 2);
				ctx.fillStyle = '#fbbf24';
				ctx.beginPath();
				for (let i = 0; i < 10; i++) {
					const rr = i % 2 ? 6 : 14;
					const a = (i / 10) * Math.PI * 2 - Math.PI / 2;
					ctx.lineTo(Math.cos(a) * rr, Math.sin(a) * rr);
				}
				ctx.fill();
			} else {
				const col = { oxygen: '#34d399', triple: '#38bdf8', shield: '#818cf8' }[k.kind];
				ctx.fillStyle = col + '33';
				ctx.strokeStyle = col;
				ctx.lineWidth = 2;
				ctx.beginPath();
				ctx.arc(0, 0, 15, 0, Math.PI * 2);
				ctx.fill();
				ctx.stroke();
				ctx.fillStyle = col;
				ctx.font = '900 14px yekanBakh, Tahoma, sans-serif';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'middle';
				ctx.fillText({ oxygen: 'O₂', triple: '≡', shield: '◈' }[k.kind], 0, 1);
			}
			ctx.restore();
		}
		// rocks
		for (const r of s.rocks) {
			ctx.save();
			ctx.translate(r.x, r.y);
			ctx.rotate(r.rot);
			ctx.fillStyle = r.flash > 0 ? '#f8fafc' : '#334155';
			ctx.strokeStyle = '#64748b';
			ctx.lineWidth = 2;
			ctx.beginPath();
			r.shape.forEach(([x, y], i) => (i ? ctx.lineTo(x, y) : ctx.moveTo(x, y)));
			ctx.closePath();
			ctx.fill();
			ctx.stroke();
			ctx.fillStyle = '#1e293b';
			ctx.beginPath();
			ctx.arc(r.r * 0.25, -r.r * 0.2, r.r * 0.22, 0, Math.PI * 2);
			ctx.arc(-r.r * 0.3, r.r * 0.3, r.r * 0.14, 0, Math.PI * 2);
			ctx.fill();
			ctx.restore();
		}
		for (const d of s.drones) drawSaucer(d.x, d.y, d.r, '#8b5cf6', d.flash);
		if (s.boss) {
			const B = s.boss;
			drawSaucer(B.x, B.y, B.r, '#be123c', B.flash);
			// HP bar
			const w = Math.min(360, view.vw - 80);
			ctx.fillStyle = 'rgba(15,23,42,0.8)';
			ctx.fillRect(view.vw / 2 - w / 2, 18, w, 10);
			ctx.fillStyle = '#fb7185';
			ctx.fillRect(view.vw / 2 - w / 2, 18, (w * B.hp) / B.max, 10);
		}
		// bullets
		ctx.lineCap = 'round';
		ctx.strokeStyle = '#fde68a';
		ctx.lineWidth = 4;
		ctx.beginPath();
		for (const b of s.bullets) {
			ctx.moveTo(b.x, b.y);
			ctx.lineTo(b.x - 16, b.y - (b.vy / b.vx) * 16);
		}
		ctx.stroke();
		ctx.fillStyle = '#fb7185';
		for (const e of s.enemyShots) {
			ctx.beginPath();
			ctx.arc(e.x, e.y, e.r, 0, Math.PI * 2);
			ctx.fill();
		}
		// player
		if (!s.dead) {
			const blink = s.p.inv > 0 && Math.floor(s.p.inv * 12) % 2 === 0;
			drawAstronaut(s.p, blink ? 0.35 : 1);
		}
		fx.draw(ctx);
		texts.draw(ctx);

		// stage progress bar
		if (!s.boss) {
			ctx.fillStyle = 'rgba(148,163,184,0.18)';
			ctx.fillRect(16, VH - 10, view.vw - 32, 3);
			ctx.fillStyle = theme().hue;
			ctx.fillRect(16, VH - 10, (view.vw - 32) * Math.min(1, s.stageT / STAGE_TIME), 3);
		}
		// combo
		const m = mult();
		if (m > 1) {
			ctx.font = '900 20px yekanBakh, Tahoma, sans-serif';
			ctx.textAlign = 'left';
			ctx.fillStyle = '#fbbf24';
			ctx.fillText('×' + fa(m), 16, 30);
		}
		// banner
		if (s.banner > 0) {
			const a = Math.min(1, s.banner * 2, (2.2 - s.banner) * 4);
			ctx.globalAlpha = a;
			ctx.textAlign = 'center';
			ctx.fillStyle = '#ffffff';
			ctx.font = '900 34px yekanBakh, Tahoma, sans-serif';
			ctx.fillText('مرحله ' + fa(s.stage), view.vw / 2, VH / 2 - 14);
			ctx.fillStyle = theme().hue;
			ctx.font = '700 18px yekanBakh, Tahoma, sans-serif';
			ctx.fillText(theme().name + (s.stage % 3 === 0 ? ' — غول در راه است' : ''), view.vw / 2, VH / 2 + 22);
			ctx.globalAlpha = 1;
		}
		ctx.restore();
	};

	loop = createLoop((dt) => {
		update(dt);
		draw();
	});

	/* ---------- input ---------- */
	const KEYMAP = { ArrowLeft: 'left', a: 'left', A: 'left', ش: 'left', ArrowRight: 'right', d: 'right', D: 'right', ی: 'right', ArrowUp: 'up', w: 'up', W: 'up', ص: 'up', ArrowDown: 'down', s: 'down', S: 'down', س: 'down' };
	L.on(window, 'keydown', (e) => {
		if (!api.running) return;
		const k = KEYMAP[e.key];
		if (k) keys.add(k);
		if (k || GAME_KEYS.has(e.key)) e.preventDefault();
	});
	L.on(window, 'keyup', (e) => {
		const k = KEYMAP[e.key];
		if (k) keys.delete(k);
	});
	L.on(cv.canvas, 'pointerdown', (e) => {
		if (!api.running || !s) return;
		const w = cv.toWorld(e.clientX, e.clientY);
		pointer = { x: w.x, y: w.y, ox: s.p.x - w.x, oy: s.p.y - w.y, id: e.pointerId };
		cv.canvas.setPointerCapture(e.pointerId);
	});
	L.on(cv.canvas, 'pointermove', (e) => {
		if (!pointer || e.pointerId !== pointer.id) return;
		const w = cv.toWorld(e.clientX, e.clientY);
		pointer.x = w.x;
		pointer.y = w.y;
	});
	const up = (e) => {
		if (pointer && e.pointerId === pointer.id) pointer = null;
	};
	L.on(cv.canvas, 'pointerup', up);
	L.on(cv.canvas, 'pointercancel', up);

	ready = true;
	draw();
	img.onload = () => !loop.running && draw();
	loadFont().then(() => !loop.running && draw());

	const inst = {
		start() {
			s = newRun();
			fx.clear();
			keys.clear();
			pointer = null;
			api.hud({ level: s.stage, lives: s.lives, levelLabel: 'مرحله' });
			api.setScore(0);
			loop.start();
		},
		pause() {
			keys.clear();
			pointer = null;
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
