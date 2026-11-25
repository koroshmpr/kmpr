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
const reduce = !!window.KMPR_NO_MOTION || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
	if (el.classList.contains('aos-wait')) {
		// Replay the entrance now that it is on screen (CSS animation for [data-aos], word transition for [data-split]).
		el.classList.remove('aos-wait');
	}
	el.classList.add('is-in');
	if (el.dataset.count && el.dataset.countArmed) countUp(el);
}

// Below the viewport at bind time? (Elements on screen or already scrolled past keep their first-paint entrance.)
const isBelowFold = (el) => {
	const r = el.getBoundingClientRect();
	return r.top > window.innerHeight * 0.96 && (r.width || r.height);
};

/**
 * Bind every not-yet-bound reveal target. Idempotent: call again after Alpine/AJAX renders new markup.
 * Only off-screen targets are parked (.aos-wait); on-screen ones were already animating from the first paint,
 * so nothing that the visitor can see is ever hidden, split or reset (no flash, no layout jump).
 */
export function scan(root = document) {
	const targets = [...root.querySelectorAll('[data-aos], [data-split], [data-count]')].filter((el) => !el.dataset.motionBound);
	if (!targets.length) return;
	const canWait = !reduce && !!io;
	const parked = canWait ? targets.filter(isBelowFold) : [];
	const parkedSet = new Set(parked);

	// Stagger only the parked children (changing the delay of a running animation would make it jump).
	root.querySelectorAll('[data-stagger]').forEach((group) => {
		const step = parseInt(group.dataset.stagger, 10) || 80;
		group.querySelectorAll('[data-aos]').forEach((el, i) => {
			if (parkedSet.has(el) && !el.dataset.aosDelay) el.style.setProperty('--aos-delay', `${Math.min(i * step, 600)}ms`);
		});
	});

	targets.forEach((el) => {
		el.dataset.motionBound = '1';
		if (!parkedSet.has(el)) {
			reveal(el);
			return;
		}
		if (el.matches('[data-split]')) splitWords(el);
		el.classList.add('aos-wait');
		// Counters below the fold start from zero (invisible while parked) and count up on reveal.
		if (el.dataset.count) {
			el.dataset.countArmed = '1';
			el.textContent = faDigits(0) + (el.dataset.suffix || '');
		}
		io.observe(el);
	});
}

function initReveal() {
	if (!reduce && 'IntersectionObserver' in window) {
		document.documentElement.classList.add('motion-ready');
		io = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (!entry.isIntersecting) return;
					// 12% visible — or, for elements taller than the screen (long articles), a quarter of the viewport.
					if (entry.intersectionRatio < 0.12 && entry.intersectionRect.height < window.innerHeight * 0.25) return;
					reveal(entry.target);
					io.unobserve(entry.target);
				});
			},
			{ rootMargin: '0px 0px -8% 0px', threshold: [0, 0.02, 0.06, 0.12] }
		);
	} else {
		document.documentElement.classList.remove('motion-ready');
	}
	scan();

	// Safety net: never leave on-screen content parked (print, very tall elements, missed IO callbacks).
	const sweep = () => document.querySelectorAll('.aos-wait').forEach((el) => {
		const r = el.getBoundingClientRect();
		if (r.top < window.innerHeight && r.bottom > 0) reveal(el);
	});
	setTimeout(sweep, 2500);
	window.addEventListener('beforeprint', () => document.querySelectorAll('.aos-wait').forEach(reveal));
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

// Pause infinite decorations while they are off-screen (see .anim-off in motion.css).
function initOffscreenPause() {
	if (!('IntersectionObserver' in window)) return;
	const els = document.querySelectorAll('.kb-banner, .border-glow, .marquee, .aurora, .orbit, .float-y, .text-shimmer, .word-rotator, .hp-scan, .rq-fill, [data-anim-pause]');
	if (!els.length) return;
	const pio = new IntersectionObserver((entries) => entries.forEach((e) => e.target.classList.toggle('anim-off', !e.isIntersecting)), { rootMargin: '120px 0px' });
	els.forEach((el) => pio.observe(el));
}

export default function initMotion() {
	initReveal();
	initOffscreenPause();
	initPointerEffects();
	initScrollProgress();
}
