/**
 * KMPR Motion — tiny, dependency-free animation layer.
 *
 * Declarative attributes (work on any template):
 *   data-aos="fade-up|fade-down|fade-left|fade-right|zoom-in|zoom-out|blur"  reveal on scroll
 *   data-aos-delay="200"  data-aos-duration="800"
 *   data-stagger="80"     children with [data-aos] inside get incremental delays
 *   data-split            split heading into words that rise in sequence
 *   data-count="120"      animated counter (data-suffix="+")
 *   data-tilt             3D tilt following the pointer
 *   data-magnetic         button gently follows the pointer
 *   .spotlight            sets --mx/--my so CSS can draw a glow under the cursor
 *   data-progress         element receives --p (0→1) while it scrolls through the viewport
 *
 * Respects prefers-reduced-motion: everything is shown immediately.
 */
const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const faDigits = (n) => Number(n).toLocaleString('fa-IR');

function splitWords(el) {
	if (el.dataset.splitDone) return;
	el.dataset.splitDone = '1';
	const walk = (node) => {
		[...node.childNodes].forEach((child) => {
			if (child.nodeType === 3) {
				const frag = document.createDocumentFragment();
				// Group consecutive Latin (LTR) words into one unit so phrases like "Core Web Vitals"
				// keep their order inside RTL headings (inline-blocks are bidi-neutral otherwise).
				const isLatin = (w) => /[A-Za-z0-9]/.test(w) && !/[\u0600-\u06FF]/.test(w);
				const tokens = child.textContent.split(/(\s+)/).filter(Boolean);
				const units = [];
				tokens.forEach((tok) => {
					const prev = units[units.length - 1];
					if (/^\s+$/.test(tok)) {
						units.push({ space: tok });
						return;
					}
					const latin = isLatin(tok);
					const prevWord = units[units.length - 2];
					if (latin && prev && prev.space && prevWord && prevWord.latin) {
						units.pop();
						prevWord.text += prev.space + tok;
						return;
					}
					units.push({ text: tok, latin });
				});
				units.forEach((u) => {
					if (u.space) {
						frag.appendChild(document.createTextNode(u.space));
						return;
					}
					const outer = document.createElement('span');
					outer.className = 'split-word';
					if (u.latin) outer.dir = 'ltr';
					const inner = document.createElement('span');
					inner.textContent = u.text;
					outer.appendChild(inner);
					frag.appendChild(outer);
				});
				child.replaceWith(frag);
			} else if (child.nodeType === 1 && !child.classList.contains('split-word')) {
				walk(child);
			}
		});
	};
	walk(el);
	el.querySelectorAll('.split-word > span').forEach((s, i) => s.style.setProperty('--i', i));
}

function countUp(el) {
	const target = parseFloat(el.dataset.count) || 0;
	const suffix = el.dataset.suffix || '';
	if (reduce) {
		el.textContent = faDigits(target) + suffix;
		return;
	}
	const dur = 1600;
	const start = performance.now();
	const tick = (now) => {
		const t = Math.min((now - start) / dur, 1);
		const eased = 1 - Math.pow(1 - t, 4);
		el.textContent = faDigits(Math.round(target * eased)) + suffix;
		if (t < 1) requestAnimationFrame(tick);
	};
	requestAnimationFrame(tick);
}

let io = null;

function reveal(el) {
	if (el.dataset.aosDelay) el.style.transitionDelay = `${parseInt(el.dataset.aosDelay, 10)}ms`;
	if (el.dataset.aosDuration) el.style.transitionDuration = `${parseInt(el.dataset.aosDuration, 10)}ms`;
	el.classList.add('is-in');
	if (el.dataset.count) countUp(el);
}

/**
 * Bind every not-yet-bound reveal target. Idempotent: call again after Alpine/AJAX renders new markup.
 */
export function scan(root = document) {
	root.querySelectorAll('[data-stagger]').forEach((group) => {
		const step = parseInt(group.dataset.stagger, 10) || 80;
		group.querySelectorAll('[data-aos]').forEach((el, i) => {
			if (!el.dataset.aosDelay) el.dataset.aosDelay = String(i * step);
		});
	});
	root.querySelectorAll('[data-split]').forEach(splitWords);

	const targets = [...root.querySelectorAll('[data-aos], [data-split], [data-count]')].filter((el) => !el.dataset.motionBound);
	targets.forEach((el) => {
		el.dataset.motionBound = '1';
		// Counters start from zero immediately so the final value never flashes first.
		if (el.dataset.count && !reduce) el.textContent = faDigits(0) + (el.dataset.suffix || '');
	});
	if (reduce || !io) {
		targets.forEach(reveal);
		return;
	}
	targets.forEach((el) => io.observe(el));
}

function initReveal() {
	if (!reduce && 'IntersectionObserver' in window) {
		document.documentElement.classList.add('motion-ready');
		io = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (!entry.isIntersecting) return;
					reveal(entry.target);
					io.unobserve(entry.target);
				});
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: 0.12 }
		);
	}
	scan();

	// Safety net: never leave content hidden (e.g. print, very tall elements).
	setTimeout(() => document.querySelectorAll('[data-motion-bound]:not(.is-in)').forEach((el) => {
		const r = el.getBoundingClientRect();
		if (r.top < window.innerHeight && r.bottom > 0) reveal(el);
	}), 2500);
}

function initPointerEffects() {
	if (!finePointer || reduce) return;

	document.querySelectorAll('[data-tilt]').forEach((el) => {
		const max = parseFloat(el.dataset.tilt) || 8;
		el.addEventListener('pointermove', (e) => {
			const r = el.getBoundingClientRect();
			const x = (e.clientX - r.left) / r.width - 0.5;
			const y = (e.clientY - r.top) / r.height - 0.5;
			el.style.transform = `perspective(900px) rotateY(${x * max}deg) rotateX(${-y * max}deg)`;
		});
		el.addEventListener('pointerleave', () => (el.style.transform = ''));
	});

	document.querySelectorAll('[data-magnetic]').forEach((el) => {
		el.addEventListener('pointermove', (e) => {
			const r = el.getBoundingClientRect();
			const x = e.clientX - r.left - r.width / 2;
			const y = e.clientY - r.top - r.height / 2;
			el.style.transform = `translate(${x * 0.18}px, ${y * 0.28}px)`;
		});
		el.addEventListener('pointerleave', () => (el.style.transform = ''));
	});

	document.addEventListener('pointermove', (e) => {
		const card = e.target.closest && e.target.closest('.spotlight');
		if (!card) return;
		const r = card.getBoundingClientRect();
		card.style.setProperty('--mx', `${e.clientX - r.left}px`);
		card.style.setProperty('--my', `${e.clientY - r.top}px`);
	}, { passive: true });

	const glow = document.querySelector('[data-cursor-glow]');
	if (glow) {
		const host = glow.parentElement;
		host.addEventListener('pointermove', (e) => {
			const r = host.getBoundingClientRect();
			glow.style.setProperty('--gx', `${e.clientX - r.left}px`);
			glow.style.setProperty('--gy', `${e.clientY - r.top}px`);
			glow.style.opacity = '1';
		}, { passive: true });
		host.addEventListener('pointerleave', () => (glow.style.opacity = '0'));
	}
}

function initScrollProgress() {
	const ring = document.querySelector('[data-scroll-ring]');
	const tracked = [...document.querySelectorAll('[data-progress]')];
	if (!ring && !tracked.length) return;
	let ticking = false;
	const update = () => {
		ticking = false;
		const max = document.documentElement.scrollHeight - window.innerHeight;
		const ratio = max > 0 ? Math.min(1, window.scrollY / max) : 0;
		if (ring) ring.style.setProperty('--p', ratio.toFixed(3));
		tracked.forEach((el) => {
			const r = el.getBoundingClientRect();
			const p = (window.innerHeight * 0.75 - r.top) / r.height;
			el.style.setProperty('--p', Math.max(0, Math.min(1, p)).toFixed(3));
		});
	};
	window.addEventListener('scroll', () => {
		if (!ticking) {
			ticking = true;
			requestAnimationFrame(update);
		}
	}, { passive: true });
	update();
}

export default function initMotion() {
	initReveal();
	initPointerEffects();
	initScrollProgress();
}
