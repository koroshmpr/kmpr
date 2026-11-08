/** Small shared helpers for the KMPR arcade. */
export const fa = (n) => Math.round(Number(n) || 0).toLocaleString('fa-IR');
export const faDec = (n, d = 1) => (Number(n) || 0).toLocaleString('fa-IR', { maximumFractionDigits: d, minimumFractionDigits: 0 });
export const clamp = (v, a, b) => (v < a ? a : v > b ? b : v);
export const lerp = (a, b, t) => a + (b - a) * t;
export const rand = (a, b) => a + Math.random() * (b - a);
export const randInt = (a, b) => Math.floor(rand(a, b + 1));
export const pick = (arr) => arr[Math.floor(Math.random() * arr.length)];
export const shuffle = (arr) => {
	const a = arr.slice();
	for (let i = a.length - 1; i > 0; i--) {
		const j = Math.floor(Math.random() * (i + 1));
		[a[i], a[j]] = [a[j], a[i]];
	}
	return a;
};
export const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
export const isTouch = () => window.matchMedia('(pointer: coarse)').matches || 'ontouchstart' in window;

/** Create an element from an HTML string. */
export function h(html) {
	const t = document.createElement('template');
	t.innerHTML = html.trim();
	return t.content.firstElementChild;
}

export const esc = (s) =>
	String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** Keys the arcade swallows while a game runs (prevents page scroll). */
export const GAME_KEYS = new Set(['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', ' ', 'Spacebar']);

/** Track window listeners and remove them in one go. */
export function listeners() {
	const list = [];
	return {
		on(target, type, fn, opts) {
			target.addEventListener(type, fn, opts);
			list.push([target, type, fn, opts]);
		},
		off() {
			list.forEach(([t, ty, f, o]) => t.removeEventListener(ty, f, o));
			list.length = 0;
		},
	};
}

/** Canvas font stack (the theme's @font-face family is `yekanBakh`). */
export const FONT = 'yekanBakh, Tahoma, sans-serif';
/** Resolve once Yekan Bakh weights used on canvas are loaded (never rejects). */
export function loadFont() {
	if (!document.fonts || !document.fonts.load) return Promise.resolve();
	return Promise.all(['400', '700', '900'].map((w) => document.fonts.load(`${w} 20px yekanBakh`, 'سلام ۱۲۳'))).catch(() => {});
}
