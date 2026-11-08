/** Juice: particles, screen shake, parallax starfield, floating texts. */
import { rand } from './util.js';

export class Particles {
	constructor(max = 500) {
		this.max = max;
		this.list = [];
	}
	burst(x, y, { n = 16, color = '#f59e0b', speed = 220, life = 0.7, size = 3, gravity = 0, spread = Math.PI * 2, angle = 0, drag = 0.9 } = {}) {
		for (let i = 0; i < n; i++) {
			if (this.list.length >= this.max) this.list.shift();
			const a = angle + (Math.random() - 0.5) * spread;
			const s = speed * (0.35 + Math.random() * 0.65);
			this.list.push({ x, y, vx: Math.cos(a) * s, vy: Math.sin(a) * s, life, max: life, size: size * (0.6 + Math.random() * 0.8), color, gravity, drag });
		}
	}
	update(dt) {
		const l = this.list;
		for (let i = l.length - 1; i >= 0; i--) {
			const p = l[i];
			p.life -= dt;
			if (p.life <= 0) {
				l.splice(i, 1);
				continue;
			}
			p.vx *= Math.pow(p.drag, dt * 10);
			p.vy = p.vy * Math.pow(p.drag, dt * 10) + p.gravity * dt;
			p.x += p.vx * dt;
			p.y += p.vy * dt;
		}
	}
	draw(ctx) {
		for (const p of this.list) {
			const k = p.life / p.max;
			ctx.globalAlpha = k;
			ctx.fillStyle = p.color;
			const s = p.size * (0.4 + k * 0.6);
			ctx.fillRect(p.x - s / 2, p.y - s / 2, s, s);
		}
		ctx.globalAlpha = 1;
	}
	clear() {
		this.list.length = 0;
	}
}

export class Shake {
	constructor(enabled = true) {
		this.t = 0;
		this.mag = 0;
		this.enabled = enabled;
	}
	add(mag, t = 0.25) {
		if (!this.enabled) return;
		this.mag = Math.max(this.mag, mag);
		this.t = Math.max(this.t, t);
	}
	update(dt) {
		this.t = Math.max(0, this.t - dt);
		if (!this.t) this.mag = 0;
	}
	apply(ctx) {
		if (!this.t) return;
		ctx.translate(rand(-this.mag, this.mag), rand(-this.mag, this.mag));
	}
}

/** Parallax starfield, scrolling right→left. */
export class Starfield {
	constructor(layers = [[70, 12, 0.6], [45, 40, 1.1], [20, 110, 1.8]]) {
		this.layers = layers.map(([n, speed, size]) => ({ n, speed, size, stars: [] }));
		this.w = 0;
		this.h = 0;
	}
	resize(w, h) {
		if (Math.abs(w - this.w) < 2 && Math.abs(h - this.h) < 2) return;
		this.w = w;
		this.h = h;
		for (const L of this.layers) {
			L.stars = Array.from({ length: L.n }, () => ({ x: Math.random() * w, y: Math.random() * h, tw: Math.random() * 6 }));
		}
	}
	update(dt, boost = 1) {
		for (const L of this.layers) {
			for (const s of L.stars) {
				s.x -= L.speed * boost * dt;
				s.tw += dt * 3;
				if (s.x < -4) {
					s.x = this.w + 4;
					s.y = Math.random() * this.h;
				}
			}
		}
	}
	draw(ctx) {
		ctx.fillStyle = '#e2e8f0';
		for (const L of this.layers) {
			for (const s of L.stars) {
				ctx.globalAlpha = 0.35 + 0.35 * Math.sin(s.tw) * (L.size < 1 ? 1 : 0.4) + L.size * 0.15;
				ctx.fillRect(s.x, s.y, L.size, L.size);
			}
		}
		ctx.globalAlpha = 1;
	}
}

export class FloatTexts {
	constructor() {
		this.list = [];
	}
	add(x, y, text, color = '#fde68a', size = 18) {
		this.list.push({ x, y, text, color, size, life: 0.9 });
	}
	update(dt) {
		for (let i = this.list.length - 1; i >= 0; i--) {
			const f = this.list[i];
			f.life -= dt;
			f.y -= 40 * dt;
			if (f.life <= 0) this.list.splice(i, 1);
		}
	}
	draw(ctx) {
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		for (const f of this.list) {
			ctx.globalAlpha = Math.min(1, f.life * 2);
			ctx.font = `900 ${f.size}px yekanBakh, Tahoma, sans-serif`;
			ctx.fillStyle = f.color;
			ctx.fillText(f.text, f.x, f.y);
		}
		ctx.globalAlpha = 1;
	}
}
