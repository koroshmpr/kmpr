/**
 * «تیرانداز آسمان» — an original dusk shooting gallery.
 * Paper-origami birds, kites and little drones rise from behind the hills and fly arcs
 * across a parallax sunset. Waves of two targets, three shots per wave, a hit quota per
 * round, a rare golden kite. All art is drawn in code; all sound is synthesized.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas, createLoop } from '../lib/canvas.js';
import { Particles, Shake, FloatTexts } from '../lib/fx.js';
import { clamp, rand, pick, listeners, GAME_KEYS, fa, FONT, loadFont } from '../lib/util.js';

export const achievements = CATALOG['sky-shooter'].achievements;

const VH = 540;
const WAVES = 5;
const PER_WAVE = 2;
const SHOTS = 3;
const TYPES = {
	bird: { pts: 100, r: 30, speed: 1 },
	kite: { pts: 160, r: 30, speed: 0.85 },
	drone: { pts: 220, r: 26, speed: 1.25 },
	golden: { pts: 600, r: 28, speed: 1.4 },
};
const SFX_SHOT = [['noise', 0, 0, 0.09, 0.09], ['square', 520, 90, 0.08, 0.04]];
const SFX_POP = [['triangle', 900, 1400, 0.08, 0.06], ['noise', 0, 0, 0.06, 0.03, 0.02]];
const SFX_ESCAPE = [['sine', 500, 260, 0.25, 0.05]];

let current = null;

export function mount(stage, api) {
	api.setRatio('16/9', '4/5');
	const L = listeners();
	const fx = new Particles(api.reducedMotion ? 160 : 600);
	const shake = new Shake(!api.reducedMotion);
	const texts = new FloatTexts();
	let s = null;
	let aim = { x: 480, y: 220, visible: false };
	let clouds = [];

	let loop = null;
	let ready = false; // onResize fires synchronously inside createCanvas — wait until mount finished
	const cv = createCanvas(stage, {
		vh: VH,
		onResize: (v) => {
			if (!ready) return;
			if (!clouds.length) clouds = Array.from({ length: 6 }, () => ({ x: rand(0, v.vw), y: rand(40, 220), w: rand(80, 180), sp: rand(6, 16) }));
			if (!loop || !loop.running) draw();
		},
	});
	cv.canvas.setAttribute('aria-label', 'صحنه بازی تیرانداز آسمان');
	cv.canvas.style.cursor = 'none';
	const { ctx, view } = cv;

	const quotaFor = (round) => Math.min(9, 5 + Math.floor(round / 2)); // of WAVES*PER_WAVE = 10
	const waveTime = (round) => Math.max(3.2, 7 - (round - 1) * 0.45);

	const newRun = () => ({
		t: 0,
		score: 0,
		round: 1,
		wave: 0,
		hits: 0,
		roundHits: 0,
		shots: SHOTS,
		targets: [],
		results: [], // per target in round: 'hit' | 'miss'
		phase: 'intro', // intro | wave | between | roundEnd | over
		phaseT: 1.6,
		banner: '',
		goldenUsed: false,
		muzzle: 0,
	});

	const spawnWave = () => {
		s.wave++;
		s.shots = SHOTS;
		s.waveT = waveTime(s.round);
		s.targets = [];
		for (let i = 0; i < PER_WAVE; i++) {
			let type = s.round < 2 ? 'bird' : pick(s.round < 4 ? ['bird', 'bird', 'kite'] : ['bird', 'kite', 'drone', 'drone']);
			if (!s.goldenUsed && s.round >= 2 && Math.random() < 0.1) {
				type = 'golden';
				s.goldenUsed = true;
			}
			const T = TYPES[type];
			const fromLeft = Math.random() < 0.5;
			const x0 = rand(view.vw * 0.15, view.vw * 0.85);
			const speedK = (1 + (s.round - 1) * 0.12) * T.speed;
			s.targets.push({
				type,
				x: x0,
				y: VH - 90,
				vx: (fromLeft ? 1 : -1) * rand(90, 170) * speedK,
				vy: -rand(150, 210) * speedK,
				t: rand(0, 3),
				r: T.r,
				state: 'fly', // fly | fall | flee | gone
				turnT: rand(0.8, 1.6),
				rot: 0,
			});
		}
		api.hud({ extra: '●'.repeat(s.shots) });
	};

	const endWave = () => {
		for (const t of s.targets) {
			if (t.state === 'fly') {
				t.state = 'flee';
				s.results.push('miss');
				api.sfx(SFX_ESCAPE);
			}
		}
		s.phase = 'between';
		s.phaseT = 1.1;
	};

	const shoot = (x, y) => {
		if (!s || !api.running || s.phase !== 'wave' || s.shots <= 0) return;
		s.shots--;
		s.muzzle = 0.08;
		api.sfx(SFX_SHOT);
		api.hud({ extra: s.shots ? '●'.repeat(s.shots) : '—' });
		shake.add(2, 0.08);
		fx.burst(x, y, { n: 8, color: '#fef3c7', speed: 160, life: 0.25, size: 2 });
		let hitCount = 0;
		for (const t of s.targets) {
			if (t.state !== 'fly' || t.age < 0.45) continue; // still rising from behind the hills
			const dx = t.x - x;
			const dy = t.y - y;
			if (dx * dx + dy * dy <= (t.r + 8) * (t.r + 8)) {
				hitCount++;
				t.state = 'fall';
				t.vy = -120;
				t.vx *= 0.3;
				s.results.push('hit');
				s.hits++;
				s.roundHits++;
				const pts = Math.round(TYPES[t.type].pts * Math.min(2, 1 + (s.round - 1) * 0.1) * (s.shots === SHOTS - 1 ? 1.2 : 1));
				s.score += pts;
				texts.add(t.x, t.y - 30, '+' + fa(pts), t.type === 'golden' ? '#fde047' : '#fff7ed', t.type === 'golden' ? 22 : 17);
				const cols = { bird: ['#f8fafc', '#e2e8f0', '#fda4af'], kite: ['#38bdf8', '#f59e0b', '#fb7185'], drone: ['#94a3b8', '#a78bfa', '#fbbf24'], golden: ['#fde047', '#facc15', '#fff7ed'] }[t.type];
				fx.burst(t.x, t.y, { n: 30, color: pick(cols), speed: 260, life: 0.9, gravity: 260, size: 5 });
				fx.burst(t.x, t.y, { n: 16, color: pick(cols), speed: 160, life: 1.2, gravity: 160, size: 7 });
				api.sfx(SFX_POP);
				api.unlock('first');
				if (t.type === 'golden') api.unlock('golden');
			}
		}
		if (hitCount >= 2) {
			api.unlock('double');
			s.score += 200;
			texts.add(x, y - 50, 'دو با یک تیر! +' + fa(200), '#fde68a', 19);
		}
		if (hitCount) {
			shake.add(5, 0.18);
			api.setScore(s.score);
			if (s.score >= 10000) api.unlock('s10k');
		} else {
			s.score = Math.max(0, s.score - 10);
			api.setScore(s.score);
			texts.add(x, y - 20, '−' + fa(10), '#fda4af', 14);
		}
		if (s.targets.every((t) => t.state !== 'fly')) {
			s.phase = 'between';
			s.phaseT = 0.9;
		} else if (s.shots <= 0) endWave();
	};

	const endRound = () => {
		const quota = quotaFor(s.round);
		const total = WAVES * PER_WAVE;
		if (s.roundHits >= quota) {
			const perfect = s.roundHits === total;
			const bonus = perfect ? 600 : (s.roundHits - quota) * 50;
			s.score += bonus;
			api.setScore(s.score);
			if (perfect) api.unlock('perfect_round');
			s.banner = perfect ? `دور بی‌نقص! +${fa(bonus)}` : `دور ${fa(s.round)} پاس شد${bonus ? ' +' + fa(bonus) : ''}`;
			s.round++;
			if (s.round >= 5) api.unlock('round5');
			if (s.score >= 10000) api.unlock('s10k');
			s.wave = 0;
			s.roundHits = 0;
			s.results = [];
			s.goldenUsed = false;
			s.phase = 'intro';
			s.phaseT = 2;
			api.hud({ level: s.round });
			api.sfx('level');
		} else {
			s.phase = 'over';
			s.banner = `سهمیه ${fa(quota)} هدف بود — ${fa(s.roundHits)} زدید`;
			s.phaseT = 1.6;
			api.sfx('over');
		}
	};

	/* ---------- update ---------- */
	const update = (dt) => {
		fx.update(dt);
		texts.update(dt);
		shake.update(dt);
		for (const c of clouds) {
			c.x -= c.sp * dt;
			if (c.x + c.w < -20) c.x = view.vw + 20;
		}
		if (!s) return;
		s.t += dt;
		s.muzzle = Math.max(0, s.muzzle - dt);

		// targets
		for (const t of s.targets) {
			t.t += dt;
			t.age = (t.age || 0) + dt;
			if (t.state === 'fly') {
				t.turnT -= dt;
				if (t.turnT <= 0) {
					t.turnT = rand(0.7, 1.5);
					t.vx = -t.vx * rand(0.8, 1.2);
					t.vy = -Math.abs(t.vy) * rand(0.6, 1.1) * (t.y < 150 ? -0.6 : 1);
				}
				t.x += t.vx * dt;
				t.y += t.vy * dt + Math.sin(t.t * 4) * 0.6;
				if (t.x < t.r) {
					t.x = t.r;
					t.vx = Math.abs(t.vx);
				}
				if (t.x > view.vw - t.r) {
					t.x = view.vw - t.r;
					t.vx = -Math.abs(t.vx);
				}
				if (t.y < 50) {
					t.y = 50;
					t.vy = Math.abs(t.vy) * 0.6;
				}
				if (t.y > VH - 140) t.vy = -Math.abs(t.vy);
			} else if (t.state === 'fall') {
				t.vy += 900 * dt;
				t.y += t.vy * dt;
				t.x += t.vx * dt;
				t.rot += dt * 8;
				if (t.y > VH + 40) t.state = 'gone';
			} else if (t.state === 'flee') {
				t.y -= 420 * dt;
				t.x += t.vx * 0.5 * dt;
				if (t.y < -60) t.state = 'gone';
			}
		}

		if (s.phase === 'wave') {
			s.waveT -= dt;
			if (s.waveT <= 0) endWave();
		} else {
			s.phaseT -= dt;
			if (s.phaseT <= 0) {
				if (s.phase === 'intro' || s.phase === 'between') {
					if (s.phase === 'between' && s.wave >= WAVES) {
						s.phase = 'roundEnd';
						endRound();
					} else {
						spawnWave();
						s.phase = 'wave';
						s.banner = '';
					}
				} else if (s.phase === 'over' && !s.reported) {
					s.reported = true;
					api.over({ score: s.score, meta: { round: s.round, level: s.round, summary: `تا دور ${fa(s.round)} پیش رفتید و ${fa(s.hits)} هدف را زدید.` } });
				}
			}
		}
	};

	/* ---------- render ---------- */
	const drawHills = (offset, color, amp, base, freq) => {
		ctx.fillStyle = color;
		ctx.beginPath();
		ctx.moveTo(-10, VH + 10);
		for (let x = -10; x <= view.vw + 10; x += 12) {
			ctx.lineTo(x, base - Math.sin(x * freq + offset) * amp - Math.sin(x * freq * 2.3 + offset * 1.7) * amp * 0.4);
		}
		ctx.lineTo(view.vw + 10, VH + 10);
		ctx.closePath();
		ctx.fill();
	};

	const drawBird = (t) => {
		const flap = Math.sin(t.t * 12);
		ctx.fillStyle = t.type === 'golden' ? '#fde047' : '#f8fafc';
		ctx.strokeStyle = 'rgba(15,23,42,0.35)';
		ctx.lineWidth = 1.5;
		// body
		ctx.beginPath();
		ctx.moveTo(-26, 2);
		ctx.lineTo(22, -2);
		ctx.lineTo(30, -12);
		ctx.lineTo(24, 4);
		ctx.closePath();
		ctx.fill();
		ctx.stroke();
		// wings (folded paper)
		ctx.fillStyle = t.type === 'golden' ? '#facc15' : '#e2e8f0';
		ctx.beginPath();
		ctx.moveTo(-6, 0);
		ctx.lineTo(10, 0);
		ctx.lineTo(-2, -28 * flap);
		ctx.closePath();
		ctx.fill();
		ctx.stroke();
		ctx.fillStyle = '#fda4af';
		ctx.beginPath();
		ctx.moveTo(-6, 0);
		ctx.lineTo(10, 0);
		ctx.lineTo(4, -18 * flap);
		ctx.closePath();
		ctx.fill();
	};
	const drawKite = (t) => {
		const gold = t.type === 'golden';
		ctx.save();
		ctx.rotate(Math.sin(t.t * 3) * 0.2);
		ctx.fillStyle = gold ? '#facc15' : '#38bdf8';
		ctx.beginPath();
		ctx.moveTo(0, -30);
		ctx.lineTo(20, 0);
		ctx.lineTo(0, 30);
		ctx.lineTo(-20, 0);
		ctx.closePath();
		ctx.fill();
		ctx.fillStyle = gold ? '#fde047' : '#f59e0b';
		ctx.beginPath();
		ctx.moveTo(0, -30);
		ctx.lineTo(20, 0);
		ctx.lineTo(0, 0);
		ctx.closePath();
		ctx.fill();
		ctx.beginPath();
		ctx.moveTo(0, 30);
		ctx.lineTo(-20, 0);
		ctx.lineTo(0, 0);
		ctx.closePath();
		ctx.fill();
		ctx.strokeStyle = 'rgba(15,23,42,0.45)';
		ctx.lineWidth = 1.5;
		ctx.beginPath();
		ctx.moveTo(0, -30);
		ctx.lineTo(0, 30);
		ctx.moveTo(-20, 0);
		ctx.lineTo(20, 0);
		ctx.stroke();
		// tail
		ctx.strokeStyle = gold ? '#fef08a' : '#fb7185';
		ctx.lineWidth = 2;
		ctx.beginPath();
		ctx.moveTo(0, 30);
		for (let k = 1; k <= 5; k++) ctx.quadraticCurveTo(Math.sin(t.t * 6 + k) * 10, 30 + k * 9 - 4, Math.sin(t.t * 6 + k + 1) * 6, 30 + k * 9);
		ctx.stroke();
		ctx.restore();
	};
	const drawDrone = (t) => {
		ctx.fillStyle = '#334155';
		ctx.fillRect(-16, -6, 32, 12);
		ctx.fillStyle = '#a78bfa';
		ctx.fillRect(-6, -9, 12, 6);
		ctx.strokeStyle = '#94a3b8';
		ctx.lineWidth = 3;
		ctx.beginPath();
		ctx.moveTo(-16, -4);
		ctx.lineTo(-24, -12);
		ctx.moveTo(16, -4);
		ctx.lineTo(24, -12);
		ctx.stroke();
		ctx.fillStyle = 'rgba(226,232,240,0.7)';
		const w = Math.abs(Math.sin(t.t * 40)) * 14 + 4;
		ctx.fillRect(-24 - w / 2, -14, w, 3);
		ctx.fillRect(24 - w / 2, -14, w, 3);
		ctx.fillStyle = Math.floor(t.t * 4) % 2 ? '#f43f5e' : '#34d399';
		ctx.fillRect(-2, 2, 4, 3);
	};

	const draw = () => {
		ctx.save();
		cv.reset();
		shake.apply(ctx);
		// dusk sky
		const g = ctx.createLinearGradient(0, 0, 0, VH);
		g.addColorStop(0, '#1e1b4b');
		g.addColorStop(0.45, '#7c2d63');
		g.addColorStop(0.75, '#ea580c');
		g.addColorStop(1, '#fbbf24');
		ctx.fillStyle = g;
		ctx.fillRect(-20, -20, view.vw + 40, VH + 40);
		// sun
		const sg = ctx.createRadialGradient(view.vw * 0.62, VH * 0.66, 10, view.vw * 0.62, VH * 0.66, 120);
		sg.addColorStop(0, 'rgba(254,243,199,0.95)');
		sg.addColorStop(0.35, 'rgba(253,186,116,0.6)');
		sg.addColorStop(1, 'rgba(253,186,116,0)');
		ctx.fillStyle = sg;
		ctx.fillRect(0, 0, view.vw, VH);
		// clouds
		ctx.fillStyle = 'rgba(253,230,138,0.12)';
		for (const c of clouds) {
			ctx.beginPath();
			ctx.ellipse(c.x, c.y, c.w / 2, 14, 0, 0, Math.PI * 2);
			ctx.ellipse(c.x + c.w * 0.2, c.y - 8, c.w / 3, 12, 0, 0, Math.PI * 2);
			ctx.fill();
		}
		const off = s ? s.t * 0.05 : 0;
		drawHills(off * 0.3, '#5b2148', 26, VH - 150, 0.008);
		drawHills(off * 0.6, '#3b1a40', 20, VH - 110, 0.013);

		// targets (behind front hills so they "rise" from them)
		if (s) {
			for (const t of s.targets) {
				if (t.state === 'gone') continue;
				ctx.save();
				ctx.translate(t.x, t.y);
				if (t.state === 'fall') ctx.rotate(t.rot);
				if (t.vx < 0 && t.type !== 'kite') ctx.scale(-1, 1);
				if (t.type === 'bird') drawBird(t);
				else if (t.type === 'drone') drawDrone(t);
				else drawKite(t);
				ctx.restore();
				if (t.type === 'golden' && t.state === 'fly') {
					ctx.strokeStyle = 'rgba(253,224,71,0.55)';
					ctx.lineWidth = 2;
					ctx.beginPath();
					ctx.arc(t.x, t.y, 38 + Math.sin(t.t * 8) * 3, 0, Math.PI * 2);
					ctx.stroke();
				}
			}
		}
		drawHills(off, '#1f1030', 16, VH - 70, 0.02);
		// grass blades
		ctx.fillStyle = '#140a20';
		for (let x = 0; x < view.vw; x += 9) {
			const h = 10 + ((x * 37) % 13);
			ctx.fillRect(x, VH - 40 - h + Math.sin((s ? s.t : 0) * 2 + x) * 1.5, 3, h + 2);
		}
		ctx.fillRect(0, VH - 42, view.vw, 50);

		fx.draw(ctx);
		texts.draw(ctx);

		// bottom HUD (round tracker + shots)
		if (s) {
			const total = WAVES * PER_WAVE;
			const quota = quotaFor(s.round);
			const size = 14;
			const gap = 6;
			const w = total * (size + gap);
			const x0 = view.vw / 2 - w / 2;
			const y0 = VH - 28;
			for (let i = 0; i < total; i++) {
				const r = s.results[i];
				ctx.fillStyle = r === 'hit' ? '#fbbf24' : r === 'miss' ? 'rgba(244,63,94,0.8)' : 'rgba(226,232,240,0.25)';
				ctx.beginPath();
				ctx.arc(x0 + i * (size + gap) + size / 2, y0, size / 2, 0, Math.PI * 2);
				ctx.fill();
			}
			// quota marker
			const qx = x0 + quota * (size + gap) - gap / 2;
			ctx.fillStyle = '#fde68a';
			ctx.fillRect(qx - 1, y0 - 12, 2, 24);
			// shots
			ctx.textAlign = 'left';
			ctx.textBaseline = 'middle';
			ctx.font = `700 14px ${FONT}`;
			ctx.fillStyle = '#fde68a';
			for (let i = 0; i < SHOTS; i++) {
				ctx.globalAlpha = i < s.shots ? 1 : 0.25;
				ctx.fillRect(16 + i * 14, VH - 36, 8, 18);
			}
			ctx.globalAlpha = 1;
			// wave timer
			if (s.phase === 'wave') {
				const k = clamp(s.waveT / waveTime(s.round), 0, 1);
				ctx.fillStyle = 'rgba(2,6,23,0.5)';
				ctx.fillRect(view.vw / 2 - 80, 14, 160, 5);
				ctx.fillStyle = k < 0.3 ? '#fb7185' : '#fbbf24';
				ctx.fillRect(view.vw / 2 - 80, 14, 160 * k, 5);
			}
			// banner
			const msg = s.phase === 'intro' ? (s.banner ? s.banner : `دور ${fa(s.round)} — سهمیه ${fa(quota)} از ${fa(total)}`) : s.phase === 'over' ? s.banner : '';
			if (msg) {
				ctx.textAlign = 'center';
				ctx.font = `900 30px ${FONT}`;
				ctx.fillStyle = 'rgba(2,6,23,0.55)';
				const tw = ctx.measureText(msg).width + 48;
				ctx.fillRect(view.vw / 2 - tw / 2, VH / 2 - 60, tw, 56);
				ctx.fillStyle = '#fff7ed';
				ctx.fillText(msg, view.vw / 2, VH / 2 - 30);
				if (s.phase === 'intro' && s.banner) {
					ctx.font = `700 16px ${FONT}`;
					ctx.fillStyle = '#fde68a';
					ctx.fillText(`دور ${fa(s.round)} — سهمیه ${fa(quota)} هدف`, view.vw / 2, VH / 2 + 16);
				}
			}
		}

		// crosshair
		if (aim.visible || (s && api.running)) {
			const { x, y } = aim;
			ctx.strokeStyle = s && s.muzzle > 0 ? '#fef3c7' : '#ffffff';
			ctx.lineWidth = 2;
			ctx.beginPath();
			ctx.arc(x, y, 18, 0, Math.PI * 2);
			ctx.moveTo(x - 28, y);
			ctx.lineTo(x - 8, y);
			ctx.moveTo(x + 8, y);
			ctx.lineTo(x + 28, y);
			ctx.moveTo(x, y - 28);
			ctx.lineTo(x, y - 8);
			ctx.moveTo(x, y + 8);
			ctx.lineTo(x, y + 28);
			ctx.stroke();
			ctx.fillStyle = '#f43f5e';
			ctx.fillRect(x - 2, y - 2, 4, 4);
			if (s && s.muzzle > 0) {
				ctx.fillStyle = 'rgba(254,243,199,0.18)';
				ctx.fillRect(0, 0, view.vw, VH);
			}
		}
		ctx.restore();
	};

	loop = createLoop((dt) => {
		kbAim(dt);
		update(dt);
		draw();
	});

	/* ---------- input ---------- */
	L.on(cv.canvas, 'pointermove', (e) => {
		const w = cv.toWorld(e.clientX, e.clientY);
		aim = { x: w.x, y: w.y, visible: true };
		if (!loop.running) draw();
	});
	L.on(cv.canvas, 'pointerleave', () => {
		aim.visible = false;
	});
	L.on(cv.canvas, 'pointerdown', (e) => {
		if (!api.running) return;
		e.preventDefault();
		const w = cv.toWorld(e.clientX, e.clientY);
		aim = { x: w.x, y: w.y, visible: true };
		shoot(w.x, w.y);
	});
	const held = new Set();
	L.on(window, 'keydown', (e) => {
		if (!api.running) return;
		if (e.key === ' ' || e.key === 'Enter') {
			e.preventDefault();
			if (!e.repeat) shoot(aim.x, aim.y);
			return;
		}
		if (GAME_KEYS.has(e.key)) {
			e.preventDefault();
			held.add(e.key);
		}
	});
	L.on(window, 'keyup', (e) => held.delete(e.key));
	// keyboard aiming (accessibility)
	function kbAim(dt) {
		if (!held.size) return;
		const sp = 420 * dt;
		if (held.has('ArrowLeft')) aim.x -= sp;
		if (held.has('ArrowRight')) aim.x += sp;
		if (held.has('ArrowUp')) aim.y -= sp;
		if (held.has('ArrowDown')) aim.y += sp;
		aim.x = clamp(aim.x, 0, view.vw);
		aim.y = clamp(aim.y, 0, VH);
		aim.visible = true;
	}
	ready = true;
	clouds = Array.from({ length: 6 }, () => ({ x: rand(0, view.vw), y: rand(40, 220), w: rand(80, 180), sp: rand(6, 16) }));
	draw();
	loadFont().then(() => !loop.running && draw());

	const inst = {
		/** test/debug hook (read-only use) */
		get _s() {
			return s;
		},
		start() {
			s = newRun();
			fx.clear();
			held.clear();
			aim = { x: view.vw / 2, y: VH * 0.4, visible: true };
			api.hud({ level: 1, levelLabel: 'دور', extra: '●●●', extraLabel: 'تیر' });
			api.setScore(0);
			loop.start();
		},
		pause() {
			held.clear();
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
