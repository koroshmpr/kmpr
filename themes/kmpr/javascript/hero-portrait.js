/**
 * Hero portrait: layered "digital rain" depth effect.
 * - Canvas code-rain behind the cut-out portrait (Persian + Latin + code glyphs).
 * - Pointer parallax between layers (--px/--py), gentle idle drift on touch devices.
 * - Pauses when off-screen or tab hidden; static single frame for reduced motion.
 */
const GLYPHS = 'アカサタナ01{}<>/=;:+*#$ابپتثجچحخدذرزسشصضطظعغفقکگلمنوهی0123456789KMPR'.split('');

/**
 * Code rain with depth: three size "layers" (far → near), longer trails, glowing heads on the near layer,
 * occasional bright column flashes. opts: { fps, layers: [0,1,2] subset, alpha }.
 */
function startRain(canvas, opts = {}) {
	const ctx = canvas.getContext('2d', { alpha: true });
	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
	const frameMs = 1000 / (opts.fps || 30);
	const layerSizes = [10, 14, 19];
	const layers = opts.layers || [0, 1, 2];
	const fade = opts.fade || 0.12;
	let w = 0;
	let h = 0;
	let cols = [];
	let running = false;
	let raf = 0;
	let last = 0;

	const spawn = (c, top) => {
		c.layer = layers[(Math.random() * layers.length) | 0];
		c.size = layerSizes[c.layer];
		c.speed = (0.45 + Math.random() * 0.6) * (0.7 + c.layer * 0.45);
		c.len = 4 + ((Math.random() * 6) | 0) + c.layer * 2;
		c.y = top ? Math.random() * -h * 0.6 : Math.random() * h * 1.1 - h * 0.1;
		c.flash = 0;
	};

	const resize = () => {
		const r = canvas.getBoundingClientRect();
		w = Math.max(1, r.width);
		h = Math.max(1, r.height);
		canvas.width = Math.round(w * dpr);
		canvas.height = Math.round(h * dpr);
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		const gap = 12;
		cols = Array.from({ length: Math.ceil(w / gap) }, (_, i) => {
			const c = { x: i * gap + gap / 2 };
			spawn(c, false);
			return c;
		});
		if (!opts.transparent) {
			ctx.fillStyle = '#010604';
			ctx.fillRect(0, 0, w, h);
		}
	};

	const glyph = () => GLYPHS[(Math.random() * GLYPHS.length) | 0];

	const frame = (t) => {
		if (!running) return;
		raf = requestAnimationFrame(frame);
		if (t - last < frameMs) return;
		last = t;
		if (opts.transparent) {
			// fade trails to transparent so the page background shows through
			ctx.globalCompositeOperation = 'destination-out';
			ctx.fillStyle = `rgba(0, 0, 0, ${fade})`;
			ctx.fillRect(0, 0, w, h);
			ctx.globalCompositeOperation = 'source-over';
		} else {
			ctx.fillStyle = `rgba(1, 6, 4, ${fade})`;
			ctx.fillRect(0, 0, w, h);
		}
		ctx.textAlign = 'center';
		// draw far → near so near glyphs sit on top
		for (const L of [0, 1, 2]) {
			if (!layers.includes(L)) continue;
			ctx.font = `600 ${layerSizes[L]}px ui-monospace, Menlo, monospace`;
			const depthA = 0.35 + L * 0.3;
			for (const c of cols) {
				if (c.layer !== L) continue;
				const s = c.size;
				// trail body: phosphor green fading out
				for (let k = 1; k < 4; k++) {
					const a = depthA * (1 - k / 4) * 0.9;
					ctx.fillStyle = k === 1 ? `rgba(0, 255, 70, ${a})` : `rgba(22, 163, 74, ${a})`;
					ctx.fillText(glyph(), c.x, c.y - s * k);
				}
				// head
				if (L === 2) {
					ctx.shadowColor = 'rgba(74, 222, 128, 0.9)';
					ctx.shadowBlur = 8;
				}
				ctx.fillStyle = c.flash > 0 ? 'rgba(255, 255, 255, 1)' : `rgba(220, 255, 230, ${0.55 + L * 0.2})`;
				ctx.fillText(glyph(), c.x, c.y);
				if (L === 2) ctx.shadowBlur = 0;
				if (c.flash > 0) c.flash--;
				else if (Math.random() < 0.0025) c.flash = 6;
				c.y += s * c.speed * 0.55;
				if (c.y - c.len * s > h && Math.random() > 0.9) spawn(c, true);
			}
		}
	};

	const play = () => {
		if (running || reduce) return;
		running = true;
		raf = requestAnimationFrame(frame);
	};
	const pause = () => {
		running = false;
		cancelAnimationFrame(raf);
	};

	resize();
	if (reduce) {
		for (let k = 0; k < 40; k++) {
			last = -1e9;
			running = true;
			frame(performance.now());
			running = false;
		}
		cancelAnimationFrame(raf);
	}

	let rt;
	window.addEventListener('resize', () => {
		clearTimeout(rt);
		rt = setTimeout(resize, 150);
	}, { passive: true });

	const io = new IntersectionObserver((entries) => entries.forEach((e) => (e.isIntersecting && !document.hidden ? play() : pause())), { threshold: 0.05 });
	io.observe(canvas);
	document.addEventListener('visibilitychange', () => (document.hidden ? pause() : canvas.getBoundingClientRect().bottom > 0 && play()));
}

function parallax(root) {
	const fine = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	if (!fine) {
		root.classList.add('hp-idle');
		return;
	}
	let raf = 0;
	let tx = 0;
	let ty = 0;
	let cx = 0;
	let cy = 0;
	const tick = () => {
		cx += (tx - cx) * 0.08;
		cy += (ty - cy) * 0.08;
		root.style.setProperty('--px', cx.toFixed(3));
		root.style.setProperty('--py', cy.toFixed(3));
		if (Math.abs(tx - cx) > 0.001 || Math.abs(ty - cy) > 0.001) raf = requestAnimationFrame(tick);
		else raf = 0;
	};
	const host = root.closest('section') || root;
	host.addEventListener('pointermove', (e) => {
		const r = root.getBoundingClientRect();
		tx = Math.max(-1, Math.min(1, (e.clientX - (r.left + r.width / 2)) / (r.width * 1.2)));
		ty = Math.max(-1, Math.min(1, (e.clientY - (r.top + r.height / 2)) / (r.height * 1.2)));
		if (!raf) raf = requestAnimationFrame(tick);
	}, { passive: true });
	host.addEventListener('pointerleave', () => {
		tx = 0;
		ty = 0;
		if (!raf) raf = requestAnimationFrame(tick);
	});
}

export default function initHeroPortrait() {
	document.querySelectorAll('[data-hero-portrait]').forEach((root) => {
		const canvas = root.querySelector('canvas.hp-rain');
		const outer = root.querySelector('canvas.hp-rain-outer');
		if (canvas) {
			// Start the effect only after the page is loaded and the main thread is idle (keeps LCP/TBT clean).
			const start = () => {
				startRain(canvas, { fps: 30 });
				// Dim spill outside the frame: far layer only, lower fps; skipped when hidden (mobile).
				if (outer && outer.offsetParent !== null) startRain(outer, { fps: 20, layers: [0, 1], fade: 0.14, transparent: true });
			};
			const go = () => ('requestIdleCallback' in window ? requestIdleCallback(start, { timeout: 2500 }) : setTimeout(start, 1200));
			if (document.readyState === 'complete') go();
			else window.addEventListener('load', go, { once: true });
		}
		parallax(root);
	});
}
