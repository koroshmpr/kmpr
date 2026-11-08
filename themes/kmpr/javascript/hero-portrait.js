/**
 * Hero portrait: layered "digital rain" depth effect.
 * - Canvas code-rain behind the cut-out portrait (Persian + Latin + code glyphs).
 * - Pointer parallax between layers (--px/--py), gentle idle drift on touch devices.
 * - Pauses when off-screen or tab hidden; static single frame for reduced motion.
 */
const GLYPHS = 'アカサタナ01{}<>/=;:+*#$ابپتثجچحخدذرزسشصضطظعغفقکگلمنوهی0123456789KMPR'.split('');

function startRain(canvas) {
	const ctx = canvas.getContext('2d', { alpha: true });
	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
	let w = 0;
	let h = 0;
	let cols = [];
	let size = 14;
	let running = false;
	let raf = 0;
	let last = 0;

	const resize = () => {
		const r = canvas.getBoundingClientRect();
		w = Math.max(1, r.width);
		h = Math.max(1, r.height);
		canvas.width = Math.round(w * dpr);
		canvas.height = Math.round(h * dpr);
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		size = w < 320 ? 12 : 14;
		const n = Math.ceil(w / size);
		cols = Array.from({ length: n }, () => ({
			y: Math.random() * h * 1.2 - h * 0.2,
			speed: 0.6 + Math.random() * 1.4,
			glow: Math.random() < 0.12,
			depth: Math.random(),
		}));
		ctx.fillStyle = '#010604';
		ctx.fillRect(0, 0, w, h);
	};

	const frame = (t) => {
		if (!running) return;
		raf = requestAnimationFrame(frame);
		if (t - last < 33) return; // ~30fps is plenty for this effect
		last = t;
		// Trail fade
		ctx.fillStyle = 'rgba(1, 6, 4, 0.13)';
		ctx.fillRect(0, 0, w, h);
		ctx.font = `600 ${size}px ui-monospace, Menlo, monospace`;
		ctx.textAlign = 'center';
		cols.forEach((c, i) => {
			const x = i * size + size / 2;
			const ch = GLYPHS[(Math.random() * GLYPHS.length) | 0];
			// Matrix green spectrum: near-white head → phosphor green → deep green body.
			ctx.fillStyle = c.glow ? 'rgba(236, 253, 245, 0.95)' : `rgba(180, 255, 200, ${0.55 + c.depth * 0.4})`;
			ctx.fillText(ch, x, c.y);
			ctx.fillStyle = `rgba(0, 255, 65, ${0.25 + c.depth * 0.45})`;
			ctx.fillText(GLYPHS[(Math.random() * GLYPHS.length) | 0], x, c.y - size);
			ctx.fillStyle = `rgba(21, 128, 61, ${0.2 + c.depth * 0.3})`;
			ctx.fillText(GLYPHS[(Math.random() * GLYPHS.length) | 0], x, c.y - size * 2);
			c.y += size * c.speed * 0.5;
			if (c.y > h + size * 4 && Math.random() > 0.975) {
				c.y = Math.random() * -60;
				c.speed = 0.6 + Math.random() * 1.4;
				c.glow = Math.random() < 0.12;
				c.depth = Math.random();
			}
		});
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
		// One static, readable frame.
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
		const canvas = root.querySelector('canvas');
		if (canvas) {
			// Start the effect only after the page is loaded and the main thread is idle (keeps LCP/TBT clean).
			const go = () => ('requestIdleCallback' in window ? requestIdleCallback(() => startRain(canvas), { timeout: 2500 }) : setTimeout(() => startRain(canvas), 1200));
			if (document.readyState === 'complete') go();
			else window.addEventListener('load', go, { once: true });
		}
		parallax(root);
	});
}
