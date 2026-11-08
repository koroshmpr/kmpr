/**
 * KMPR Blog "course" layer.
 *
 * - Alpine.store('lessons'): completed lessons kept in localStorage (no account needed).
 *     has(id) · toggle(id) · mark(id) · doneCount(ids) · progress(ids) · nextUrl(ids, urls) · started(ids)
 * - Alpine.data('kmprRating', (postId, average, count)): AJAX star rating (wp_ajax save_rating).
 * - Alpine.data('kmprReading'): reading progress of the lesson article (0-100).
 */
const KEY = 'kmpr_lessons_done';

const read = () => {
	try {
		const v = JSON.parse(window.localStorage.getItem(KEY) || '[]');
		return Array.isArray(v) ? v.map(Number).filter(Boolean) : [];
	} catch (e) {
		return [];
	}
};

const ajaxUrl = () =>
	(typeof window.jsData !== 'undefined' && window.jsData.root_url ? window.jsData.root_url : '') + '/wp-admin/admin-ajax.php';

const write = (ids) => {
	try {
		window.localStorage.setItem(KEY, JSON.stringify(ids));
	} catch (e) {
		/* private mode: progress lives for this page only */
	}
};

export default function initBlog(Alpine) {
	Alpine.store('lessons', {
		done: [],
		init() {
			this.done = read();
			window.addEventListener('storage', (e) => {
				if (e.key === KEY) this.done = read();
			});
		},
		has(id) {
			return this.done.includes(Number(id));
		},
		mark(id) {
			id = Number(id);
			if (!id || this.has(id)) return;
			this.done = [...this.done, id];
			write(this.done);
		},
		toggle(id) {
			id = Number(id);
			this.done = this.has(id) ? this.done.filter((d) => d !== id) : [...this.done, id];
			write(this.done);
		},
		doneCount(ids) {
			return (ids || []).filter((id) => this.has(id)).length;
		},
		progress(ids) {
			return ids && ids.length ? Math.round((this.doneCount(ids) / ids.length) * 100) : 0;
		},
		started(ids) {
			return this.doneCount(ids) > 0;
		},
		nextUrl(ids, urls) {
			const i = (ids || []).findIndex((id) => !this.has(id));
			return urls[i === -1 ? 0 : i] || urls[0] || '#';
		},
		fa(n) {
			return Number(n).toLocaleString('fa-IR');
		},
	});

	Alpine.data('kmprRating', (postId, average = 0, count = 0) => ({
		average: Number(average),
		count: Number(count),
		hover: 0,
		mine: 0,
		busy: false,
		message: '',
		init() {
			try {
				this.mine = Number(window.localStorage.getItem('kmpr_rated_' + postId) || 0);
			} catch (e) {
				this.mine = 0;
			}
		},
		shown() {
			return this.hover || this.mine || Math.round(this.average);
		},
		async rate(value) {
			if (this.busy || this.mine) {
				this.message = this.mine ? 'امتیاز شما قبلاً ثبت شده است.' : '';
				return;
			}
			this.busy = true;
			const root = typeof window.jsData !== 'undefined' && window.jsData.root_url ? window.jsData.root_url : '';
			const body = new URLSearchParams({ action: 'save_rating', post_id: postId, rating_value: value });
			try {
				const res = await fetch(root + '/wp-admin/admin-ajax.php', { method: 'POST', body, credentials: 'same-origin' });
				const json = await res.json();
				if (json && json.success) {
					this.average = json.data.average;
					this.count = json.data.count;
					this.mine = value;
					this.message = 'ممنونم! امتیاز شما ثبت شد.';
					try {
						window.localStorage.setItem('kmpr_rated_' + postId, String(value));
					} catch (e) {
						/* ignore */
					}
				} else {
					this.message = 'ثبت امتیاز انجام نشد.';
				}
			} catch (e) {
				this.message = 'ارتباط برقرار نشد؛ دوباره تلاش کنید.';
			}
			this.busy = false;
		},
	}));

	Alpine.data('kmprReading', () => ({
		pct: 0,
		update() {
			const el = document.getElementById('lesson-body');
			if (!el) return;
			const r = el.getBoundingClientRect();
			const total = r.height - window.innerHeight * 0.6;
			const seen = Math.min(Math.max(-r.top + window.innerHeight * 0.25, 0), total > 0 ? total : 1);
			this.pct = Math.round((seen / (total > 0 ? total : 1)) * 100);
		},
		init() {
			let ticking = false;
			const onScroll = () => {
				if (ticking) return;
				ticking = true;
				requestAnimationFrame(() => {
					this.update();
					ticking = false;
				});
			};
			window.addEventListener('scroll', onScroll, { passive: true });
			window.addEventListener('resize', onScroll, { passive: true });
			this.update();
		},
	}));
	/* ---------- Comments ---------- */
	Alpine.data('kmprComments', (cfg) => ({
		max: cfg.max || 3000,
		total: cfg.total || 0,
		name: cfg.name || '',
		text: '',
		replyTo: null,
		busy: false,
		notice: '',
		order: 'newest',
		errors: { comment: '', author: '', email: '', general: '' },
		init() {
			if (this.total > 1) this.sort('newest');
		},
		initials() {
			const parts = (this.name || '').trim().split(/\s+/).filter(Boolean);
			if (!parts.length) return '؟';
			return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
		},
		avatarStyle() {
			let h = 0;
			for (const ch of this.name || '') h = (h * 31 + ch.charCodeAt(0)) % 360;
			return `background:linear-gradient(135deg,hsl(${h} 70% 55%),hsl(${(h + 50) % 360} 75% 40%))`;
		},
		sort(order) {
			this.order = order;
			const list = this.$refs.list;
			if (!list) return;
			const items = [...list.children].filter((li) => li.matches('li.kmpr-comment'));
			const key = (li) => Number(li.dataset[order === 'helpful' ? 'votes' : 'time']) || 0;
			items.sort((a, b) => {
				if (order === 'oldest') return key(a) - key(b);
				if (order === 'helpful') return key(b) - key(a) || Number(b.dataset.time) - Number(a.dataset.time);
				return key(b) - key(a);
			});
			items.forEach((li) => list.appendChild(li));
		},
		reply(detail) {
			const li = document.getElementById('comment-' + detail.id);
			if (!li) return;
			this.replyTo = detail;
			const card = li.querySelector(':scope > article');
			(card || li).after(this.$refs.composer);
			this.$refs.composer.classList.add('mt-4');
			this.$nextTick(() => {
				this.$refs.composer.scrollIntoView({ behavior: 'smooth', block: 'center' });
				this.$refs.text.focus({ preventScroll: true });
			});
		},
		cancelReply() {
			this.replyTo = null;
			this.$refs.composer.classList.remove('mt-4');
			this.$refs.home.after(this.$refs.composer);
		},
		validate(form) {
			this.errors = { comment: '', author: '', email: '', general: '' };
			if (!this.text.trim()) this.errors.comment = 'متن دیدگاه را بنویسید.';
			else if (this.text.length > this.max) this.errors.comment = 'متن دیدگاه بیش از حد طولانی است.';
			const author = form.querySelector('#author');
			const email = form.querySelector('#email');
			if (author && author.required && !author.value.trim()) this.errors.author = 'نام خود را وارد کنید.';
			if (email && email.required && !email.value.trim()) this.errors.email = 'ایمیل را وارد کنید.';
			else if (email && email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) this.errors.email = 'ایمیل معتبر نیست.';
			const first = ['comment', 'author', 'email'].find((k) => this.errors[k]);
			if (first) {
				const el = form.querySelector('#' + first);
				if (el) el.focus();
				return false;
			}
			return true;
		},
		async submit(form) {
			this.notice = '';
			if (this.busy || !this.validate(form)) return;
			this.busy = true;
			const data = new FormData(form);
			data.append('action', 'kmpr_comment');
			try {
				const res = await fetch(ajaxUrl(), { method: 'POST', body: data, credentials: 'same-origin' });
				const json = await res.json().catch(() => null);
				if (!json || !json.success) {
					const d = (json && json.data) || {};
					const field = d.field && this.errors[d.field] !== undefined ? d.field : 'general';
					this.errors[field] = d.message || 'ارسال انجام نشد؛ دوباره تلاش کنید.';
				} else {
					this.insert(json.data);
					this.text = '';
					this.notice = json.data.approved ? 'دیدگاه شما منتشر شد. ممنونم!' : 'دیدگاه شما ثبت شد و پس از تأیید نمایش داده می‌شود.';
					if (this.replyTo) this.cancelReply();
				}
			} catch (e) {
				this.errors.general = 'ارتباط برقرار نشد؛ اتصال اینترنت را بررسی کنید.';
			}
			this.busy = false;
		},
		insert(data) {
			const tpl = document.createElement('template');
			tpl.innerHTML = data.html.trim();
			const li = tpl.content.firstElementChild;
			if (!li) return;
			li.classList.add('kmpr-comment--new');
			let target = this.$refs.list;
			const parent = data.parent ? document.getElementById('comment-' + data.parent) : null;
			if (parent) {
				target = parent.querySelector(':scope > ol.children');
				if (!target) {
					target = document.createElement('ol');
					target.className = 'children';
					parent.appendChild(target);
				}
				target.appendChild(li);
			} else if (this.order === 'oldest') {
				target.appendChild(li);
			} else {
				target.prepend(li);
			}
			target.classList.add('mb-10');
			this.total++;
			this.$nextTick(() => li.scrollIntoView({ behavior: 'smooth', block: 'center' }));
		},
	}));

	Alpine.data('kmprVote', (id, count = 0) => ({
		count: Number(count),
		voted: false,
		busy: false,
		init() {
			try {
				this.voted = JSON.parse(window.localStorage.getItem('kmpr_votes') || '[]').includes(id);
			} catch (e) {
				this.voted = false;
			}
		},
		async vote() {
			if (this.voted || this.busy) return;
			this.busy = true;
			const form = document.querySelector('#commentform input[name="kmpr_nonce"]');
			const body = new URLSearchParams({ action: 'kmpr_comment_vote', comment_id: id, kmpr_nonce: form ? form.value : '' });
			try {
				const res = await fetch(ajaxUrl(), { method: 'POST', body, credentials: 'same-origin' });
				const json = await res.json();
				if (json && json.success) {
					this.count = json.data.count;
					this.voted = true;
					const li = document.getElementById('comment-' + id);
					if (li) li.dataset.votes = String(this.count);
					try {
						const list = JSON.parse(window.localStorage.getItem('kmpr_votes') || '[]');
						list.push(id);
						window.localStorage.setItem('kmpr_votes', JSON.stringify(list.slice(-500)));
					} catch (e) {
						/* ignore */
					}
				}
			} catch (e) {
				/* network error: leave button active */
			}
			this.busy = false;
		},
	}));
}
