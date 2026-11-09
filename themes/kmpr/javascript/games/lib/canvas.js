/**
 * Canvas helpers: a devicePixelRatio-aware canvas that scales a fixed
 * virtual height to the stage, and a rAF loop with clamped delta time.
 */
export function createCanvas(stage, { vh = 540, onResize } = {}) {
	const canvas = document.createElement('canvas');
	canvas.className = 'game-canvas';
	canvas.setAttribute('role', 'img');
	stage.appendChild(canvas);
	const ctx = canvas.getContext('2d', { alpha: false });
	const view = { w: 0, h: 0, vw: 0, vh, scale: 1, dpr: 1 };

	const fit = () => {
		// Layout size (not the bounding box) so a CSS-rotated stage (forced landscape) still fits correctly.
		const r = { width: stage.clientWidth, height: stage.clientHeight };
		if (!r.width || !r.height) return;
		const dpr = Math.min(window.devicePixelRatio || 1, 2.5);
		canvas.width = Math.round(r.width * dpr);
		canvas.height = Math.round(r.height * dpr);
		view.w = r.width;
		view.h = r.height;
		view.dpr = dpr;
		view.scale = r.height / vh;
		view.vw = r.width / view.scale;
		reset();
		onResize && onResize(view);
	};
	const reset = () => ctx.setTransform(view.dpr * view.scale, 0, 0, view.dpr * view.scale, 0, 0);

	const ro = new ResizeObserver(fit);
	ro.observe(stage);
	fit();

	return {
		canvas,
		ctx,
		view,
		reset,
		fit,
		/** client coords → virtual world coords */
		toWorld(clientX, clientY) {
			const r = canvas.getBoundingClientRect();
			// html.game-rotate = frame rotated 90° clockwise: local x runs down the screen, local y runs right→left.
			if (document.documentElement.classList.contains('game-rotate')) return { x: (clientY - r.top) / view.scale, y: (r.right - clientX) / view.scale };
			return { x: (clientX - r.left) / view.scale, y: (clientY - r.top) / view.scale };
		},
		destroy() {
			ro.disconnect();
			canvas.remove();
		},
	};
}

export function createLoop(step) {
	let raf = 0;
	let last = 0;
	let running = false;
	const frame = (t) => {
		if (!running) return;
		const dt = Math.min(0.05, Math.max(0, (t - last) / 1000));
		last = t;
		step(dt, t);
		raf = requestAnimationFrame(frame);
	};
	return {
		start() {
			if (running) return;
			running = true;
			last = performance.now();
			raf = requestAnimationFrame(frame);
		},
		stop() {
			running = false;
			cancelAnimationFrame(raf);
		},
		get running() {
			return running;
		},
	};
}
