/**
 * Home "courses" section (template-parts/home/articles.php).
 *
 * - Alpine.data('kmprCourses', count): course switcher tabs (roving tabindex, RTL-aware arrow keys, Home/End).
 * - Alpine.data('kmprRail'): CSS scroll-snap slider helper — prev/next buttons + start/end state for the edge fades.
 *   Scrolling itself is native (touch, trackpad, keyboard); without JS the row is still a scrollable list of links.
 */
const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export default function initHomeCourses(Alpine) {
	Alpine.data('kmprCourses', (count = 1) => ({
		active: 0,
		select(i, focus = false) {
			this.active = (i + count) % count;
			if (focus) {
				this.$nextTick(() => {
					const tabs = this.$root.querySelectorAll('[role="tab"]');
					if (tabs[this.active]) tabs[this.active].focus();
				});
			}
		},
		onKey(e) {
			const rtl = getComputedStyle(this.$root).direction === 'rtl';
			const map = { ArrowLeft: rtl ? 1 : -1, ArrowRight: rtl ? -1 : 1 };
			if (e.key in map) this.select(this.active + map[e.key], true);
			else if (e.key === 'Home') this.select(0, true);
			else if (e.key === 'End') this.select(count - 1, true);
			else return;
			e.preventDefault();
		},
	}));

	Alpine.data('kmprRail', () => ({
		atStart: true,
		atEnd: false,
		revealed: false,
		init() {
			const track = this.$refs.track;
			if (!track) return;
			if ('ResizeObserver' in window) new ResizeObserver(() => this.update()).observe(track);
			this.$nextTick(() => this.update());
		},
		update() {
			const t = this.$refs.track;
			if (!t) return;
			if (!this.revealed && t.clientWidth) this.revealNext(t);
			const pos = Math.abs(t.scrollLeft); // RTL: scrollLeft runs 0 → negative
			this.atStart = pos < 4;
			this.atEnd = pos + t.clientWidth >= t.scrollWidth - 4;
		},
		/** Once visible: bring the next unread lesson to the row start (instant, no page scroll). */
		revealNext(t) {
			this.revealed = true;
			const card = t.querySelector('.is-next');
			const item = card && card.closest('li');
			if (!item || item === t.firstElementChild) return;
			const rtl = getComputedStyle(t).direction === 'rtl';
			const pad = parseFloat(getComputedStyle(t).scrollPaddingInlineStart) || 0;
			const ir = item.getBoundingClientRect();
			const tr = t.getBoundingClientRect();
			const delta = rtl ? ir.right - (tr.right - pad) : ir.left - (tr.left + pad);
			const prev = t.style.scrollBehavior;
			t.style.scrollBehavior = 'auto';
			t.scrollLeft += delta;
			t.style.scrollBehavior = prev;
		},
		/** dir: +1 = forward in reading order, -1 = back. */
		go(dir) {
			const t = this.$refs.track;
			const item = t.querySelector('li');
			const gap = parseFloat(getComputedStyle(t).columnGap) || 0;
			const card = item ? item.getBoundingClientRect().width + gap : t.clientWidth * 0.8;
			const step = Math.max(card, Math.floor(t.clientWidth / card) * card);
			const rtl = getComputedStyle(t).direction === 'rtl';
			t.scrollBy({ left: dir * step * (rtl ? -1 : 1), behavior: reduce() ? 'auto' : 'smooth' });
		},
	}));
}
