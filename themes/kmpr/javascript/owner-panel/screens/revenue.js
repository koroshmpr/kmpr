/**
 * درآمد — pass A/B funnel + verdict, prices / support link / surface toggles, sponsor stats,
 * advergame clicks, pre-signups (call / Telegram / WhatsApp / copy / delete, CSV export).
 */
import { h, fill, icon, num, pct, ago, sw, toast, confirmBox, skeleton, errorBox, empty, chips, field, copyText, linkBtn, latin } from '../ui.js';
import { api } from '../api.js';

const TONE = { win: ['is-good', 'نتیجه قابل اتکاست'], lean: ['is-amber', 'تمایل دارد، هنوز قطعی نیست'], wait: ['is-muted', 'در انتظار داده'] };

export default {
	async render(el, ctx) {
		let range = 30;
		const body = h('div');
		const signups = h('ul', { class: 'op-list' });
		const more = h('button', { type: 'button', class: 'op-btn is-ghost', hidden: true }, 'بیشتر');
		let next = 0;

		async function load() {
			if (!ctx.soft && !body.firstChild) body.append(skeleton(3, 'is-grid'), skeleton(4));
			let d;
			try {
				d = await api('revenue', { query: { range } });
			} catch (e) {
				fill(body, errorBox(e, load));
				return;
			}
			draw(d);
		}

		function draw(d) {
			// CSV export reuses the monetisation module's admin export (nonce-protected).
			csv = d.csv;
			csvBtn.setAttribute('href', csv);
			const [tone, toneLabel] = TONE[d.verdict.tone] || TONE.wait;
			const t = d.totals;
			fill(body, 
				h(
					'section',
					{ class: 'op-kpis' },
					kpi('دست بازی', num(t.plays), num(range) + ' روز'),
					kpi('کلیک حمایت', num(t.support.c), 'از ' + num(t.support.i) + ' نمایش · ' + pct(t.support.c, t.support.i)),
					kpi('کلیک بازی تبلیغاتی', num(t.adv.c), 'از ' + num(t.adv.i) + ' نمایش · ' + pct(t.adv.c, t.adv.i)),
					kpi('«جای برند شما»', num(t.spc.c), 'کلیک از ' + num(t.spc.i) + ' نمایش'),
				),
				h(
					'section',
					{ class: 'op-card' },
					h('div', { class: 'op-card__head' }, h('h2', { class: 'op-card__title' }, 'تست قیمت پاس آرکید'), h('span', { class: 'op-tag ' + tone }, toneLabel)),
					h('p', { class: 'op-verdict ' + tone }, d.verdict.text),
					h('div', { class: 'op-ab' }, ['A', 'B'].map((v) => variant(v, d.variants[v]))),
				),
				h('div', { class: 'op-grid2' }, settingsCard(d), sponsorsCard(d)),
			);
		}

		function kpi(label, value, sub) {
			return h('div', { class: 'op-kpi' }, h('span', { class: 'op-kpi__label' }, label), h('strong', { class: 'op-kpi__value' }, value), h('span', { class: 'op-kpi__sub' }, sub));
		}

		function variant(v, x) {
			const t = x.total;
			const steps = [
				['نمایش', t.i, null],
				['کلیک «خرید»', t.c, pct(t.c, t.i)],
				['پیش‌ثبت‌نام', t.s, pct(t.s, t.c)],
			];
			const max = Math.max(1, t.i);
			return h(
				'div',
				{ class: 'op-variant is-' + v.toLowerCase() },
				h('div', { class: 'op-variant__head' }, h('span', { class: 'op-variant__tag' }, v), h('strong', null, num(x.price), ' تومان'), h('span', { class: 'op-muted' }, 'تبدیل کل ', pct(t.s, t.i, 2))),
				h(
					'ol',
					{ class: 'op-funnel' },
					steps.map(([label, n, rate]) => h('li', null, h('span', { class: 'op-funnel__label' }, label), h('span', { class: 'op-funnel__track' }, h('i', { style: { width: Math.max(2, (n / max) * 100) + '%' } })), h('span', { class: 'op-funnel__n' }, num(n), rate ? h('small', null, rate) : null))),
				),
				h('p', { class: 'op-muted op-variant__split' }, 'هاب: ', num(x.surfaces.hub.c), ' کلیک / ', num(x.surfaces.hub.i), ' · پایان بازی: ', num(x.surfaces.over.c), ' / ', num(x.surfaces.over.i)),
			);
		}

		function settingsCard(d) {
			const s = d.settings;
			const a = h('input', { type: 'text', inputmode: 'numeric', class: 'op-input', value: num(s.price_a).replace(/٬/g, ''), dir: 'ltr' });
			const b = h('input', { type: 'text', inputmode: 'numeric', class: 'op-input', value: num(s.price_b).replace(/٬/g, ''), dir: 'ltr' });
			const sup = h('input', { type: 'url', class: 'op-input', value: s.support_url, dir: 'ltr', placeholder: 'https://…', inputmode: 'url' });
			const save = h('button', { type: 'submit', class: 'op-btn is-primary' }, 'ذخیره');
			const form = h(
				'form',
				{ class: 'op-form', novalidate: true },
				h('div', { class: 'op-row2' }, field('قیمت نسخه A (تومان)', a), field('قیمت نسخه B (تومان)', b)),
				field('لینک حمایت (کافه‌بده، حامی‌باش…)', sup, 'خالی = دکمه حمایت نمایش داده نمی‌شود.'),
				h('div', { class: 'op-actions' }, save),
			);
			form.addEventListener('submit', async (e) => {
				e.preventDefault();
				const pa = parseInt(latin(a.value).replace(/\D/g, ''), 10);
				const pb = parseInt(latin(b.value).replace(/\D/g, ''), 10);
				if (!(pa >= 0) || !(pb >= 0)) return toast('قیمت را به عدد وارد کنید.', { tone: 'err' });
				save.disabled = true;
				try {
					await api('revenue/settings', { method: 'POST', body: { price_a: pa, price_b: pb, support_url: sup.value.trim() } });
					toast('ذخیره شد');
					load();
				} catch (err) {
					toast(err.message, { tone: 'err' });
				} finally {
					save.disabled = false;
				}
			});
			const toggles = [
				['pass', 'پاس آرکید (تست قیمت)'],
				['support', 'حمایت از سازنده'],
				['sponsor', 'اسپانسر بازی‌ها'],
				['adv', 'دعوت به بازی تبلیغاتی'],
			].map(([k, label]) =>
				h('label', { class: 'op-toggle' }, h('span', null, label), sw(s.on[k], label, async (v, set) => {
					try {
						await api('revenue/settings', { method: 'POST', body: { on: { [k]: v } } });
						toast(label + (v ? ' روشن شد' : ' خاموش شد'), {
							undo: async () => {
								await api('revenue/settings', { method: 'POST', body: { on: { [k]: !v } } }).catch(() => null);
								set(!v);
							},
						});
					} catch (err) {
						set(!v);
						toast(err.message, { tone: 'err' });
					}
				})),
			);
			return h('section', { class: 'op-card' }, h('h2', { class: 'op-card__title' }, 'قیمت‌ها و لینک‌ها'), form, h('h3', { class: 'op-form__sub' }, 'بخش‌های نمایش'), h('div', { class: 'op-toggles' }, toggles), s.pay_url ? h('p', { class: 'op-card__note' }, '«لینک پرداخت» فعال است؛ کلیک‌ها به صفحه پرداخت می‌روند.') : null, linkBtn(d.admin, 'صفحه کامل درآمد در وردپرس', 'wp', 'op-btn is-ghost is-sm', false));
		}

		function sponsorsCard(d) {
			return h(
				'section',
				{ class: 'op-card' },
				h('h2', { class: 'op-card__title' }, 'اسپانسرها و بازی‌ها'),
				h(
					'ul',
					{ class: 'op-mini' },
					d.sponsors.map((g) =>
						h('li', null, h('span', { class: 'op-mini__t' }, g.title, g.sponsor ? h('small', { class: 'op-tag is-sky' }, g.sponsor) : null), h('span', { class: 'op-mini__v' }, num(g.plays), ' دست'), h('span', { class: 'op-mini__v' }, g.i ? num(g.c) + ' کلیک / ' + num(g.i) + ' · ' + pct(g.c, g.i) : '—')),
					),
				),
				h('p', { class: 'op-card__note' }, 'ویرایش اسپانسر هر بازی از صفحه «بازی‌ها».'),
			);
		}

		async function loadSignups(reset = false) {
			if (reset) {
				next = 0;
				fill(signups, skeleton(3, 'is-list'));
			}
			if (next === null) return;
			try {
				const r = await api('presignups', { query: { offset: next } });
				if (reset) fill(signups);
				for (const s of r.items) signups.append(signupRow(s));
				if (!signups.children.length) signups.append(h('li', null, empty('هنوز پیش‌ثبت‌نامی نیست.')));
				next = r.next;
				more.hidden = next === null;
			} catch (e) {
				fill(signups, errorBox(e, () => loadSignups(true)));
			}
		}

		function signupRow(s) {
			const L = s.links;
			const li = h(
				'li',
				{ class: 'op-signup' },
				h(
					'div',
					{ class: 'op-signup__main' },
					h('strong', null, s.nick || 'بی‌نام'),
					h('span', { class: 'op-signup__contact', dir: 'ltr' }, s.contact || '—'),
					h('span', { class: 'op-muted' }, 'نسخه ', s.variant, ' · ', num(s.price), ' تومان · ', s.surface, s.game ? ' · ' + s.game : '', ' · ', ago(s.date.ts)),
				),
				h(
					'div',
					{ class: 'op-actions is-tight' },
					L.tel ? h('a', { class: 'op-iconbtn is-good', href: L.tel, 'aria-label': 'تماس با ' + (s.nick || s.contact) }, icon('phone', 18)) : null,
					L.wa ? h('a', { class: 'op-iconbtn', href: L.wa, target: '_blank', rel: 'noopener', 'aria-label': 'واتساپ' }, icon('chat', 18)) : null,
					L.tg ? h('a', { class: 'op-iconbtn is-sky', href: L.tg, target: '_blank', rel: 'noopener', 'aria-label': 'تلگرام' }, icon('send', 18)) : null,
					s.contact ? h('button', { type: 'button', class: 'op-iconbtn', 'aria-label': 'کپی تماس', onClick: () => copyText(s.contact) }, icon('copy', 18)) : null,
					h('button', {
						type: 'button',
						class: 'op-iconbtn is-danger',
						'aria-label': 'حذف',
						onClick: async () => {
							if (!(await confirmBox('حذف پیش‌ثبت‌نام', `«${s.nick || s.contact || 'این مورد'}» حذف شود؟`, { ok: 'حذف', danger: true }))) return;
							try {
								await api('presignups/' + s.id, { method: 'DELETE' });
								li.remove();
								toast('حذف شد');
							} catch (e) {
								toast(e.message, { tone: 'err' });
							}
						},
					}, icon('trash', 18)),
				),
			);
			return li;
		}

		more.addEventListener('click', () => loadSignups());
		let csv = '';
		const csvBtn = h('a', { class: 'op-btn is-ghost is-sm', href: '#', onClick: (e) => { if (!csv) e.preventDefault(); } }, icon('download', 16), 'خروجی CSV');
		fill(el, 
			chips([7, 30, 90].map((d) => ({ value: d, label: num(d) + ' روز' })), range, (v) => {
				range = v;
				load();
			}, 'بازه زمانی'),
			body,
			h('section', { class: 'op-card' }, h('div', { class: 'op-card__head' }, h('h2', { class: 'op-card__title' }, 'پیش‌ثبت‌نام‌های پاس آرکید'), csvBtn), signups, more),
		);
		await Promise.all([load(), loadSignups(true)]);
	},
};
