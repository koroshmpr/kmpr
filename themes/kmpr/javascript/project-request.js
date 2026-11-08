/**
 * Project Request wizard (Alpine component).
 * Posts to /wp-json/kmpr/v1/project-request — server stores the lead and notifies Telegram.
 */
const DRAFT_KEY = 'kmpr_request_draft_v1';

const toLatinDigits = (value) =>
	String(value || '')
		.replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
		.replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));

export default function projectRequest(config = {}) {
	return {
		options: config.options || {},
		endpoint: config.endpoint || '/wp-json/kmpr/v1/project-request',
		service: config.service || '',
		directTelegram: config.telegram || '',
		step: 1,
		dir: 1, // 1 = forward, -1 = back (drives slide direction)
		totalSteps: 4,
		launched: false,
		status: 'idle', // idle | sending | done | error
		errorMessage: '',
		errorField: '',
		trackingCode: '',
		startedAt: Date.now(),
		form: {
			type: config.defaultType || '',
			features: [],
			budget: '',
			timeline: '',
			business: '',
			reference: '',
			message: '',
			name: '',
			phone: '',
			email: '',
			telegram: '',
			channel: 'telegram',
			website_url_confirm: '', // honeypot
		},

		init() {
			try {
				const saved = JSON.parse(window.localStorage.getItem(DRAFT_KEY) || 'null');
				if (saved && typeof saved === 'object') {
					Object.assign(this.form, saved, { website_url_confirm: '' });
					if (config.defaultType) this.form.type = config.defaultType;
				}
			} catch (e) {
				/* storage unavailable */
			}
			const preset = new URLSearchParams(window.location.search).get('project');
			if (preset && this.options.types && this.options.types[preset]) {
				this.form.type = preset;
			}
			this.$watch('form', (value) => {
				try {
					const { website_url_confirm, ...draft } = value;
					window.localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
				} catch (e) {
					/* ignore */
				}
			}, { deep: true });
		},

		get progress() {
			return Math.round(((this.step - 1) / this.totalSteps) * 100);
		},

		// How "built" the live preview is (0–100), weighted by what the user has filled.
		get completion() {
			const f = this.form;
			let c = 0;
			if (f.type) c += 25;
			c += Math.min(f.features.length, 4) * 5;
			if (f.budget) c += 8;
			if (f.timeline) c += 7;
			if (f.business.trim()) c += 8;
			if (f.reference.trim()) c += 4;
			if (f.message.trim().length > 10) c += 8;
			if (f.name.trim().length > 1) c += 8;
			if (toLatinDigits(f.phone).replace(/\D/g, '').length >= 10) c += 12;
			return Math.min(100, c);
		},

		has(feature) {
			return this.form.features.includes(feature);
		},

		get brandName() {
			return this.form.business.trim() || 'برند شما';
		},

		get previewUrl() {
			const ref = this.form.reference.trim().replace(/^https?:\/\//i, '').replace(/\/$/, '');
			if (ref) return ref;
			const slug = this.form.business.trim() ? 'your-brand' : 'your-site';
			return `${slug}.ir`;
		},

		label(group, key) {
			return (this.options[group] && this.options[group][key]) || '';
		},

		pickType(id) {
			this.form.type = id;
			this.errorField = '';
			setTimeout(() => this.go(2), 180);
		},

		toggleFeature(id) {
			const i = this.form.features.indexOf(id);
			if (i > -1) this.form.features.splice(i, 1);
			else this.form.features.push(id);
		},

		canLeave(step) {
			if (step === 1 && !this.form.type) {
				this.fail('ابتدا نوع پروژه را انتخاب کنید.', 'type');
				return false;
			}
			return true;
		},

		go(target) {
			if (this.status === 'sending') return;
			if (target > this.step) {
				for (let s = this.step; s < target; s++) {
					if (!this.canLeave(s)) return;
				}
			}
			this.errorMessage = '';
			this.errorField = '';
			const next = Math.min(Math.max(target, 1), this.totalSteps);
			if (next === this.step) return;
			this.dir = next >= this.step ? 1 : -1;
			// The card has a fixed height and steps cross-fade in the same grid cell: the page never changes height.
			this.step = next;
			const vp = this.$refs.viewport;
			if (vp) vp.scrollTop = 0;
			// Only nudge the viewport when the card's top is genuinely out of sight (never jump on desktop).
			this.$nextTick(() => {
				const card = this.$refs.card;
				if (!card) return;
				const r = card.getBoundingClientRect();
				if (r.top < -40) window.scrollBy({ top: r.top - 96, behavior: 'smooth' });
			});
		},

		fail(message, field = '') {
			this.errorMessage = message;
			this.errorField = field;
		},

		validateContact() {
			const phone = toLatinDigits(this.form.phone).replace(/\D+/g, '').replace(/^98(?=9\d{9}$)/, '0');
			if (this.form.name.trim().length < 2) return this.fail('نام خود را وارد کنید.', 'name'), false;
			if (!/^09\d{9}$/.test(phone)) return this.fail('شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷).', 'phone'), false;
			if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email.trim())) {
				return this.fail('ایمیل معتبر نیست.', 'email'), false;
			}
			if (this.form.channel === 'email' && !this.form.email) {
				return this.fail('برای ارتباط از طریق ایمیل، آدرس ایمیل را وارد کنید.', 'email'), false;
			}
			this.form.phone = phone;
			return true;
		},

		async submit() {
			if (this.status === 'sending') return;
			if (!this.form.type) return this.go(1);
			if (!this.validateContact()) return;

			this.status = 'sending';
			this.errorMessage = '';
			try {
				const response = await fetch(this.endpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					credentials: 'omit',
					body: JSON.stringify({
						...this.form,
						reference: this.normalizedReference(),
						service: this.service,
						source: window.location.href,
						started_at: this.startedAt,
					}),
				});
				const data = await response.json().catch(() => ({}));
				if (!response.ok || !data.ok) {
					this.status = 'error';
					const field = data.field || '';
					if (['type'].includes(field)) this.step = 1;
					return this.fail(data.message || 'ارسال ناموفق بود. لطفاً دوباره تلاش کنید.', field);
				}
				this.trackingCode = data.code;
				this.launched = true; // play the launch animation, then show success
				await new Promise((r) => setTimeout(r, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 1400));
				this.status = 'done';
				try {
					window.localStorage.removeItem(DRAFT_KEY);
				} catch (e) {
					/* ignore */
				}
				if (typeof window.gtag === 'function') window.gtag('event', 'generate_lead', { project_type: this.form.type });
			} catch (e) {
				this.status = 'error';
				this.fail('اتصال برقرار نشد. اینترنت خود را بررسی کنید یا مستقیم در تلگرام پیام دهید.');
			}
		},

		normalizedReference() {
			const ref = this.form.reference.trim();
			if (!ref) return '';
			return /^https?:\/\//i.test(ref) ? ref : `https://${ref}`;
		},

		summaryText() {
			const features = this.form.features.map((f) => this.label('features', f)).join('، ');
			return [
				`سلام کوروش، درخواست پروژه ${this.trackingCode} را ثبت کردم.`,
				`نوع پروژه: ${this.label('types', this.form.type)}`,
				features ? `امکانات: ${features}` : '',
				this.form.budget ? `بودجه: ${this.label('budgets', this.form.budget)}` : '',
			]
				.filter(Boolean)
				.join('\n');
		},

		copyCode() {
			if (navigator.clipboard) navigator.clipboard.writeText(this.trackingCode);
		},

		reset() {
			this.form = { ...this.form, type: '', features: [], budget: '', timeline: '', business: '', reference: '', message: '' };
			this.status = 'idle';
			this.launched = false;
			this.trackingCode = '';
			this.startedAt = Date.now();
			this.step = 1;
		},
	};
}
