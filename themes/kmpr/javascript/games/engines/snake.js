/**
 * «مار نئونی» — neon snake, upgraded.
 * Two modes (wraparound / walls), levels with speed + obstacle blocks, power-ups
 * (slow-mo, ghost, double points, shrink), golden apple, buffered input, swipe + d-pad.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas, createLoop } from '../lib/canvas.js';
import { Particles, Shake, FloatTexts } from '../lib/fx.js';
import { rand, randInt, pick, listeners, GAME_KEYS, fa, FONT, loadFont } from '../lib/util.js';

export const achievements = CATALOG.snake.achievements;

const VH = 600;
const ROWS = 20;
const APPLES_PER_LEVEL = 5;
const POWERS = {
	slow: { label: 'آهسته', color: '#38bdf8', icon: '⏳', dur: 6 },
	ghost: { label: 'شبح', color: '#a78bfa', icon: '👻', dur: 6 },
	double: { label: 'امتیاز دوبل', color: '#fbbf24', icon: '×۲', dur: 8 },
	shrink: { label: 'کوچک‌شدن', color: '#fb7185', icon: '✂', dur: 0 },
};
const DIRS = { up: [0, -1], down: [0, 1], left: [-1, 0], right: [1, 0] };
const MODE_KEY = 'kmpr_snake_mode';

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '4/5');
	const L = listeners();
	const fx = new Particles(api.reducedMotion ? 120 : 400);
	const shake = new Shake(!api.reducedMotion);
	const texts = new FloatTexts();
	let s = null;
	let grid = { cols: 32, rows: ROWS, cell: VH / ROWS, ox: 0, oy: 0 };
	let mode = 'wrap';
	try {
		mode = localStorage.getItem(MODE_KEY) === 'walls' ? 'walls' : 'wrap';
	} catch (e) {
		/* ignore */
	}

	let loop = null;
	let ready = false; // onResize fires synchronously inside createCanvas — wait until mount finished
	const cv = createCanvas(stage, {
		vh: VH,
		onResize: () => {
			if (!ready) return;
			layout();
			if (!loop || !loop.running) draw();
		},
	});
	cv.canvas.setAttribute('aria-label', 'صحنه بازی مار نئونی');
	const { ctx, view } = cv;

	// Mode chooser chip (shown after «شروع» before the run begins)
	const chooser = document.createElement('div');
	chooser.className = 'snk-choose';
	chooser.hidden = true;
	chooser.innerHTML = `
		<p class="snk-choose__title">حالت بازی را انتخاب کنید</p>
		<div class="snk-choose__row" role="group" aria-label="حالت بازی">
			<button type="button" class="snk-chip" data-mode="wrap"><b>بی‌دیوار</b><small>از لبه رد شو، از طرف دیگر برگرد</small></button>
			<button type="button" class="snk-chip" data-mode="walls"><b>دیواردار</b><small>لبه‌ها مرگبارند — امتیاز ×۱٫۵</small></button>
		</div>
		<p class="snk-choose__hint">کلید ۱ یا ۲ · یا ضربه بزنید</p>`;
	stage.appendChild(chooser);

	// On-screen d-pad for touch
	const pad = document.createElement('div');
	pad.className = 'snk-pad';
	pad.hidden = true;
	const arrow = (rot) => `<svg width="22" height="22" class="shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="transform:rotate(${rot}deg)"><path d="M12 5 4 15h16z"/></svg>`;
	pad.innerHTML = `
		<button type="button" data-dir="up" aria-label="بالا">${arrow(0)}</button>
		<button type="button" data-dir="left" aria-label="چپ">${arrow(-90)}</button>
		<button type="button" data-dir="right" aria-label="راست">${arrow(90)}</button>
		<button type="button" data-dir="down" aria-label="پایین">${arrow(180)}</button>`;
	stage.appendChild(pad);

	function layout() {
		const vw = view.vw || 960;
		const rows = s ? s.rows : ROWS;
		const cols = s ? s.cols : Math.max(14, Math.min(36, Math.round((rows * vw) / VH)));
		const m = 12; // margin so the border is visible
		const cell = Math.min((vw - m * 2) / cols, (VH - m * 2) / rows);
		grid = { cols, rows, cell, ox: (vw - cols * cell) / 2, oy: (VH - rows * cell) / 2 };
	}

	const free = (x, y) => !s.snake.some((p) => p.x === x && p.y === y) && !s.blocks.some((b) => b.x === x && b.y === y) && !(s.apple && s.apple.x === x && s.apple.y === y) && !(s.power && s.power.x === x && s.power.y === y);
	const freeCell = (avoidHead = 4) => {
		for (let k = 0; k < 500; k++) {
			const x = randInt(0, s.cols - 1);
			const y = randInt(0, s.rows - 1);
			const h = s.snake[0];
			if (Math.abs(x - h.x) + Math.abs(y - h.y) < avoidHead) continue;
			if (free(x, y)) return { x, y };
		}
		return null;
	};

	const spawnApple = () => {
		const c = freeCell(2);
		if (!c) return;
		const golden = s.eaten > 3 && Math.random() < 0.12;
		s.apple = { ...c, golden, t: 0, life: golden ? 5 : Infinity };
	};
	const spawnPower = () => {
		const c = freeCell(3);
		if (!c) return;
		s.power = { ...c, kind: pick(Object.keys(POWERS)), t: 0, life: 8 };
	};
	const addBlocks = () => {
		const n = Math.min(3 + s.level, 8);
		for (let k = 0; k < n; k++) {
			const c = freeCell(6);
			if (!c) continue;
			// small L / I pieces
			const shape = pick([[[0, 0], [1, 0]], [[0, 0], [0, 1]], [[0, 0]], [[0, 0], [1, 0], [0, 1]]]);
			shape.forEach(([dx, dy]) => {
				const x = c.x + dx;
				const y = c.y + dy;
				if (x < s.cols && y < s.rows && free(x, y)) s.blocks.push({ x, y, t: 0 });
			});
		}
	};

	const newRun = () => {
		const vw = view.vw || 960;
		const rows = ROWS;
		const cols = Math.max(14, Math.min(36, Math.round((rows * vw) / VH)));
		const cx = Math.floor(cols / 2);
		const cy = Math.floor(rows / 2);
		const run = {
			cols,
			rows,
			mode,
			snake: [{ x: cx, y: cy }, { x: cx - 1, y: cy }, { x: cx - 2, y: cy }],
			prev: null,
			dir: 'right',
			queue: [],
			grow: 0,
			acc: 0,
			score: 0,
			level: 1,
			eaten: 0,
			powersTaken: 0,
			apple: null,
			power: null,
			blocks: [],
			effects: { slow: 0, ghost: 0, double: 0 },
			powerTimer: 6,
			dead: false,
			deadT: 0,
			started: false,
		};
		return run;
	};

	const tickTime = () => {
		const base = Math.max(0.055, 0.13 - (s.level - 1) * 0.009);
		return s.effects.slow > 0 ? base * 1.7 : base;
	};

	const turn = (d) => {
		if (!s || s.dead || !s.started || !api.running) return;
		const last = s.queue.length ? s.queue[s.queue.length - 1] : s.dir;
		const [ax, ay] = DIRS[last];
		const [bx, by] = DIRS[d];
		if (d === last || (ax + bx === 0 && ay + by === 0)) return;
		if (s.queue.length < 3) s.queue.push(d);
	};

	const die = () => {
		s.dead = true;
		shake.add(10, 0.4);
		const h = s.snake[0];
		const c = toPx(h.x, h.y);
		fx.burst(c.x, c.y, { n: 60, color: '#34d399', speed: 320, life: 1 });
		api.sfx('hurt');
		if (navigator.vibrate) navigator.vibrate(120);
	};

	const step = () => {
		if (s.queue.length) s.dir = s.queue.shift();
		const [dx, dy] = DIRS[s.dir];
		const h = s.snake[0];
		let nx = h.x + dx;
		let ny = h.y + dy;
		const ghost = s.effects.ghost > 0;
		const out = nx < 0 || ny < 0 || nx >= s.cols || ny >= s.rows;
		if (out) {
			if (s.mode === 'walls' && !ghost) return die();
			nx = (nx + s.cols) % s.cols;
			ny = (ny + s.rows) % s.rows;
		}
		if (!ghost) {
			const tailMoves = s.grow === 0;
			const bodyHit = s.snake.some((p, i) => p.x === nx && p.y === ny && !(tailMoves && i === s.snake.length - 1));
			if (bodyHit || s.blocks.some((b) => b.x === nx && b.y === ny)) return die();
		}
		s.prev = s.snake.map((p) => ({ ...p }));
		s.snake.unshift({ x: nx, y: ny });
		if (s.grow > 0) s.grow--;
		else s.snake.pop();

		// apple
		if (s.apple && s.apple.x === nx && s.apple.y === ny) {
			const golden = s.apple.golden;
			const base = golden ? 60 : 10 + s.level * 2;
			const pts = Math.round(base * (s.effects.double > 0 ? 2 : 1) * (s.mode === 'walls' ? 1.5 : 1));
			s.score += pts;
			s.eaten++;
			s.grow += golden ? 4 : 1;
			const c = toPx(nx, ny);
			fx.burst(c.x, c.y, { n: golden ? 40 : 18, color: golden ? '#fde047' : '#fb7185', speed: 200, life: 0.6 });
			texts.add(c.x, c.y - 16, '+' + fa(pts), golden ? '#fde047' : '#fecdd3', 18);
			api.sfx(golden ? 'level' : 'coin');
			if (golden) api.unlock('golden');
			if (s.mode === 'walls' && s.eaten >= 15) api.unlock('walls');
			if (s.eaten % APPLES_PER_LEVEL === 0) {
				s.level++;
				addBlocks();
				api.hud({ level: s.level });
				api.sfx('level');
				if (s.level >= 5) api.unlock('level5');
			}
			api.setScore(s.score);
			if (s.score >= 3000) api.unlock('s3k');
			spawnApple();
		}
		// power-up
		if (s.power && s.power.x === nx && s.power.y === ny) {
			const k = s.power.kind;
			const P = POWERS[k];
			const c = toPx(nx, ny);
			fx.burst(c.x, c.y, { n: 24, color: P.color, speed: 220, life: 0.6 });
			texts.add(c.x, c.y - 16, P.label, P.color, 16);
			api.sfx('ach');
			s.powersTaken++;
			if (s.powersTaken >= 3) api.unlock('power');
			if (k === 'shrink') {
				const cut = Math.floor(s.snake.length / 3);
				if (s.snake.length - cut >= 3) s.snake.length -= cut;
				s.score += 15;
				api.setScore(s.score);
			} else s.effects[k] = P.dur;
			s.power = null;
		}
		if (s.snake.length >= 20) api.unlock('len20');
		api.hud({ extra: fa(s.snake.length) });
	};

	const update = (dt) => {
		fx.update(dt);
		texts.update(dt);
		shake.update(dt);
		if (!s || !s.started) return;
		if (s.dead) {
			s.deadT += dt;
			if (s.deadT > 0.7 && !s.reported) {
				s.reported = true;
				api.over({
					score: s.score,
					meta: { level: s.level, length: s.snake.length, mode: s.mode, summary: `مار به طول ${fa(s.snake.length)} رسید و ${fa(s.eaten)} سیب خورد (حالت ${s.mode === 'walls' ? 'دیواردار' : 'بی‌دیوار'}).` },
				});
			}
			return;
		}
		for (const k of Object.keys(s.effects)) s.effects[k] = Math.max(0, s.effects[k] - dt);
		if (s.apple) {
			s.apple.t += dt;
			if (s.apple.golden && s.apple.t > s.apple.life) spawnApple();
		}
		if (s.power) {
			s.power.t += dt;
			if (s.power.t > s.power.life) s.power = null;
		} else {
			s.powerTimer -= dt;
			if (s.powerTimer <= 0) {
				s.powerTimer = rand(7, 13);
				spawnPower();
			}
		}
		for (const b of s.blocks) b.t += dt;
		s.acc += dt;
		const tt = tickTime();
		let guard = 0;
		while (s.acc >= tt && !s.dead && guard++ < 4) {
			s.acc -= tt;
			step();
		}
	};

	/* ---------- render ---------- */
	const toPx = (x, y) => ({ x: grid.ox + (x + 0.5) * grid.cell, y: grid.oy + (y + 0.5) * grid.cell });

	const draw = () => {
		ctx.save();
		cv.reset();
		shake.apply(ctx);
		ctx.fillStyle = '#020617';
		ctx.fillRect(-20, -20, view.vw + 40, VH + 40);
		const { cols, rows, cell, ox, oy } = grid;
		// board
		const walls = (s ? s.mode : mode) === 'walls';
		ctx.fillStyle = '#060b1d';
		ctx.fillRect(ox, oy, cols * cell, rows * cell);
		ctx.strokeStyle = 'rgba(56,189,248,0.07)';
		ctx.lineWidth = 1;
		ctx.beginPath();
		for (let x = 0; x <= cols; x++) {
			ctx.moveTo(ox + x * cell, oy);
			ctx.lineTo(ox + x * cell, oy + rows * cell);
		}
		for (let y = 0; y <= rows; y++) {
			ctx.moveTo(ox, oy + y * cell);
			ctx.lineTo(ox + cols * cell, oy + y * cell);
		}
		ctx.stroke();
		ctx.strokeStyle = walls ? '#f43f5e' : 'rgba(52,211,153,0.35)';
		ctx.lineWidth = walls ? 3 : 2;
		ctx.setLineDash(walls ? [] : [8, 8]);
		ctx.strokeRect(ox, oy, cols * cell, rows * cell);
		ctx.setLineDash([]);

		const now = performance.now() / 1000;
		if (s) {
			// blocks
			for (const b of s.blocks) {
				const p = toPx(b.x, b.y);
				const k = Math.min(1, b.t * 3);
				const sz = cell * 0.86 * k;
				ctx.fillStyle = '#475569';
				ctx.fillRect(p.x - sz / 2, p.y - sz / 2, sz, sz);
				ctx.strokeStyle = '#f43f5e';
				ctx.lineWidth = 1.5;
				ctx.strokeRect(p.x - sz / 2, p.y - sz / 2, sz, sz);
			}
			// apple
			if (s.apple) {
				const p = toPx(s.apple.x, s.apple.y);
				const pulse = 1 + Math.sin(now * 6) * 0.08;
				const r = cell * 0.36 * pulse;
				ctx.shadowBlur = 18;
				ctx.shadowColor = s.apple.golden ? '#fde047' : '#fb7185';
				ctx.fillStyle = s.apple.golden ? '#facc15' : '#f43f5e';
				ctx.beginPath();
				ctx.arc(p.x, p.y, r, 0, Math.PI * 2);
				ctx.fill();
				ctx.shadowBlur = 0;
				ctx.fillStyle = '#34d399';
				ctx.fillRect(p.x - 1, p.y - r - 4, 3, 5);
				if (s.apple.golden) {
					ctx.strokeStyle = 'rgba(253,224,71,0.6)';
					ctx.lineWidth = 2;
					ctx.beginPath();
					ctx.arc(p.x, p.y, r + 6, -Math.PI / 2, -Math.PI / 2 + Math.PI * 2 * (1 - s.apple.t / s.apple.life));
					ctx.stroke();
				}
			}
			// power
			if (s.power) {
				const P = POWERS[s.power.kind];
				const p = toPx(s.power.x, s.power.y);
				const blink = s.power.life - s.power.t < 2 && Math.floor(now * 8) % 2;
				if (!blink) {
					ctx.fillStyle = P.color + '33';
					ctx.strokeStyle = P.color;
					ctx.lineWidth = 2;
					ctx.beginPath();
					ctx.arc(p.x, p.y, cell * 0.44, 0, Math.PI * 2);
					ctx.fill();
					ctx.stroke();
					ctx.fillStyle = '#fff';
					ctx.font = `900 ${Math.max(12, cell * 0.45)}px ${FONT}`;
					ctx.textAlign = 'center';
					ctx.textBaseline = 'middle';
					ctx.fillText(P.icon, p.x, p.y + 1);
				}
			}
			// snake (interpolated)
			const tt = tickTime();
			const k = s.dead || !s.prev ? 1 : Math.min(1, s.acc / tt);
			const ghost = s.effects.ghost > 0;
			const n = s.snake.length;
			ctx.shadowBlur = api.reducedMotion ? 0 : 14;
			ctx.shadowColor = ghost ? '#a78bfa' : '#34d399';
			const pts = [];
			for (let i = 0; i < n; i++) {
				const cur = s.snake[i];
				const pr = s.prev && s.prev[i] ? s.prev[i] : cur;
				let x = cur.x;
				let y = cur.y;
				if (Math.abs(cur.x - pr.x) + Math.abs(cur.y - pr.y) === 1) {
					x = pr.x + (cur.x - pr.x) * k;
					y = pr.y + (cur.y - pr.y) * k;
				}
				pts.push({ gx: x, gy: y, ...toPx(x, y) });
			}
			ctx.globalAlpha = ghost ? 0.55 : 1;
			ctx.lineCap = 'round';
			for (let i = n - 1; i >= 1; i--) {
				const a = pts[i];
				const b = pts[i - 1];
				const t = i / Math.max(1, n - 1);
				ctx.strokeStyle = `hsl(${ghost ? 260 : 158 - t * 30}, 80%, ${55 - t * 18}%)`;
				ctx.lineWidth = cell * (0.74 - t * 0.22);
				ctx.beginPath();
				// don't bridge across the board when wrapping
				if (Math.abs(a.gx - b.gx) + Math.abs(a.gy - b.gy) <= 1.01) {
					ctx.moveTo(a.x, a.y);
					ctx.lineTo(b.x, b.y);
				} else {
					ctx.moveTo(a.x, a.y);
					ctx.lineTo(a.x + 0.01, a.y);
				}
				ctx.stroke();
			}
			const hp = pts[0];
			const hs = cell * 0.9;
			ctx.fillStyle = ghost ? '#c4b5fd' : '#6ee7b7';
			ctx.beginPath();
			ctx.roundRect ? ctx.roundRect(hp.x - hs / 2, hp.y - hs / 2, hs, hs, hs * 0.32) : ctx.rect(hp.x - hs / 2, hp.y - hs / 2, hs, hs);
			ctx.fill();
			ctx.shadowBlur = 0;
			ctx.globalAlpha = 1;
			// eyes
			if (!s.dead) {
				const hd = s.snake[0];
				const pr = s.prev ? s.prev[0] : hd;
				const hx = Math.abs(hd.x - pr.x) + Math.abs(hd.y - pr.y) === 1 ? pr.x + (hd.x - pr.x) * k : hd.x;
				const hy = Math.abs(hd.x - pr.x) + Math.abs(hd.y - pr.y) === 1 ? pr.y + (hd.y - pr.y) * k : hd.y;
				const p = toPx(hx, hy);
				const [dx, dy] = DIRS[s.dir];
				ctx.fillStyle = '#020617';
				const e = cell * 0.16;
				ctx.beginPath();
				ctx.arc(p.x + dx * e + dy * e * 1.2, p.y + dy * e + dx * e * 1.2, e * 0.7, 0, Math.PI * 2);
				ctx.arc(p.x + dx * e - dy * e * 1.2, p.y + dy * e - dx * e * 1.2, e * 0.7, 0, Math.PI * 2);
				ctx.fill();
			}
			// active effects
			let ey = oy + 14;
			ctx.textAlign = 'left';
			ctx.textBaseline = 'middle';
			for (const [key, left] of Object.entries(s.effects)) {
				if (left <= 0) continue;
				const P = POWERS[key];
				ctx.fillStyle = 'rgba(2,6,23,0.75)';
				ctx.fillRect(ox + 8, ey - 11, 128, 22);
				ctx.fillStyle = P.color;
				ctx.fillRect(ox + 8, ey + 9, 128 * (left / P.dur), 2);
				ctx.font = `700 13px ${FONT}`;
				ctx.fillText(`${P.icon} ${P.label}`, ox + 14, ey);
				ey += 28;
			}
		}
		fx.draw(ctx);
		texts.draw(ctx);
		ctx.restore();
	};

	loop = createLoop((dt) => {
		update(dt);
		draw();
	});

	/* ---------- input ---------- */
	const KEYS = {
		ArrowUp: 'up', w: 'up', W: 'up', ص: 'up',
		ArrowDown: 'down', s: 'down', S: 'down', س: 'down',
		ArrowLeft: 'left', a: 'left', A: 'left', ش: 'left',
		ArrowRight: 'right', d: 'right', D: 'right', ی: 'right',
	};
	const begin = (m) => {
		if (!s || s.started) return;
		mode = m;
		s.mode = m;
		try {
			localStorage.setItem(MODE_KEY, m);
		} catch (e) {
			/* ignore */
		}
		chooser.hidden = true;
		s.started = true;
		spawnApple();
		if (api.touch) pad.hidden = false;
		api.sfx('click');
		stage.focus({ preventScroll: true });
	};
	L.on(chooser, 'click', (e) => {
		const b = e.target.closest('[data-mode]');
		if (b && api.running) begin(b.dataset.mode);
	});
	L.on(window, 'keydown', (e) => {
		if (!api.running || !s) return;
		if (!s.started) {
			if (e.key === '1' || e.key === '۱') begin('wrap');
			else if (e.key === '2' || e.key === '۲') begin('walls');
			return;
		}
		const d = KEYS[e.key];
		if (d) {
			e.preventDefault();
			turn(d);
		} else if (GAME_KEYS.has(e.key)) e.preventDefault();
	});
	L.on(pad, 'pointerdown', (e) => {
		const b = e.target.closest('[data-dir]');
		if (!b) return;
		e.preventDefault();
		turn(b.dataset.dir);
		if (navigator.vibrate) navigator.vibrate(8);
	});
	let sw = null;
	L.on(cv.canvas, 'pointerdown', (e) => {
		if (!api.running) return;
		sw = { x: e.clientX, y: e.clientY, id: e.pointerId };
	});
	L.on(cv.canvas, 'pointermove', (e) => {
		if (!sw || sw.id !== e.pointerId) return;
		const dx = e.clientX - sw.x;
		const dy = e.clientY - sw.y;
		if (Math.max(Math.abs(dx), Math.abs(dy)) < 22) return;
		turn(Math.abs(dx) > Math.abs(dy) ? (dx > 0 ? 'right' : 'left') : dy > 0 ? 'down' : 'up');
		sw = { x: e.clientX, y: e.clientY, id: e.pointerId }; // allow chained swipes
	});
	L.on(cv.canvas, 'pointerup', () => (sw = null));
	L.on(cv.canvas, 'pointercancel', () => (sw = null));

	ready = true;
	layout();
	draw();
	loadFont().then(() => !loop.running && draw());

	const inst = {
		/** test/debug hook (read-only use) */
		get _s() {
			return s;
		},
		/** Optional shell hook: tiny JSON snapshot of the run (null = nothing worth resuming). */
		serialize() {
			if (!s || s.dead || !s.started) return null;
			const { cols, rows, mode: m, snake, dir, grow, score, level, eaten, powersTaken, apple, power, blocks, effects, powerTimer } = s;
			return { cols, rows, mode: m, snake, dir, grow, score, level, eaten, powersTaken, apple, power, blocks, effects, powerTimer };
		},
		restore(d) {
			if (!d || !Array.isArray(d.snake) || !d.snake.length) return;
			s = { ...newRun(), ...d, queue: [], prev: null, acc: 0, dead: false, deadT: 0, started: true };
			mode = s.mode || mode;
			chooser.hidden = true;
			if (api.touch) pad.hidden = false;
			layout();
			api.hud({ level: s.level, levelLabel: 'مرحله', extra: fa(s.snake.length), extraLabel: 'طول' });
			api.setScore(s.score);
			draw();
		},
		start() {
			s = newRun();
			fx.clear();
			layout();
			chooser.hidden = false;
			pad.hidden = true;
			chooser.querySelectorAll('[data-mode]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.mode === mode)));
			const pref = chooser.querySelector(`[data-mode="${mode}"]`);
			if (pref && !api.touch) pref.focus({ preventScroll: true });
			api.hud({ level: 1, levelLabel: 'مرحله', extra: fa(3), extraLabel: 'طول' });
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
			chooser.remove();
			pad.remove();
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
