/**
 * بازی‌ها — per-game stats, optimistic publish/featured toggles (undo), edit sheet (tagline, difficulty,
 * sponsor incl. logo upload + Jalali dates), leaderboard reset.
 */
import { h, fill, icon, num, pct, sw, toast, sheet, field, confirmBox, skeleton, errorBox, empty, chips, ymdToJalali, jalaliToYmd, linkBtn } from '../ui.js';
import { api } from '../api.js';

const STATUS = { publish: 'منتشر شده', draft: 'پیش‌نویس', pending: 'در انتظار', private: 'خصوصی', future: 'زمان‌بندی' };

export default {
	async render(el, ctx) {
		if (!ctx.soft) el.append(skeleton(6, 'is-cards'));
		let data;
		try {
			data = await api('games');
		} catch (e) {
			fill(el, errorBox(e, () => ctx.navigate('games/', { replace: true })));
			return;
		}
		const list = h('div', { class: 'op-cards' });
		const draw = () => fill(list, ...(data.items.length ? data.items.map(card) : [empty('هنوز بازی‌ای ساخته نشده است.')]));

		/** Optimistic single-field update with undo. */
		async function patch(g, body, undoBody, okMsg, revert) {
			try {
				const fresh = await api('games/' + g.id, { method: 'POST', body });
				Object.assign(g, fresh);
				toast(okMsg, {
					undo: undoBody
						? async () => {
								try {
									Object.assign(g, await api('games/' + g.id, { method: 'POST', body: undoBody }));
									draw();
									toast('برگردانده شد');
								} catch (e) {
									toast(e.message, { tone: 'err' });
								}
							}
						: null,
				});
				draw();
			} catch (e) {
				revert && revert();
				toast(e.message, { tone: 'err' });
			}
		}

		function card(g) {
			const pub = g.status === 'publish';
			return h(
				'article',
				{ class: 'op-game' + (pub ? '' : ' is-draft') },
				h(
					'div',
					{ class: 'op-game__head' },
					g.thumb ? h('img', { class: 'op-game__thumb', src: g.thumb, alt: 'کاور ' + g.title, width: '56', height: '56', loading: 'lazy' }) : h('span', { class: 'op-game__thumb is-empty', 'aria-hidden': 'true' }, icon('games', 24)),
					h(
						'div',
						{ class: 'op-game__meta' },
						h('h2', { class: 'op-game__title' }, g.title),
						h(
							'div',
							{ class: 'op-tags' },
							h('span', { class: 'op-tag ' + (pub ? 'is-good' : 'is-muted') }, STATUS[g.status] || g.status),
							g.online ? h('span', { class: 'op-tag is-arcade' }, 'آنلاین') : null,
							g.featured ? h('span', { class: 'op-tag is-amber' }, 'ویژه') : null,
							g.sponsor.active ? h('span', { class: 'op-tag is-sky' }, 'حامی: ' + g.sponsor.name) : null,
						),
					),
				),
				g.tagline ? h('p', { class: 'op-game__tagline' }, g.tagline) : null,
				h(
					'dl',
					{ class: 'op-stats' },
					stat('دست (کل)', num(g.plays)),
					stat('۷ روز', num(g.plays7)),
					stat('بازیکن', num(g.players)),
					stat('رکورد', g.best ? num(g.best) : '—', g.champion ? g.champion : ''),
				),
				h(
					'div',
					{ class: 'op-game__toggles' },
					h('label', { class: 'op-toggle' }, h('span', null, 'منتشر شده'), sw(pub, 'انتشار ' + g.title, (v, set) => patch(g, { status: v ? 'publish' : 'draft' }, { status: v ? 'draft' : 'publish' }, v ? 'بازی منتشر شد' : 'بازی پیش‌نویس شد', () => set(!v)))),
					h('label', { class: 'op-toggle' }, h('span', null, 'ویژه آرشیو'), sw(g.featured, 'بازی ویژه ' + g.title, (v, set) => patch(g, { featured: v }, { featured: !v }, v ? 'بازی ویژه شد' : 'از حالت ویژه خارج شد', () => set(!v)))),
				),
				h(
					'div',
					{ class: 'op-actions is-tight' },
					h('button', { type: 'button', class: 'op-btn is-sm is-primary', onClick: () => edit(g) }, icon('edit', 16), 'ویرایش و اسپانسر'),
					linkBtn(g.url, 'مشاهده', 'eye', 'op-btn is-sm is-ghost'),
				),
			);
		}

		function stat(label, value, sub = '') {
			return h('div', { class: 'op-stat' }, h('dt', null, label), h('dd', null, value, sub ? h('small', null, sub) : null));
		}

		function edit(g) {
			let diff = g.difficulty;
			const tagline = h('input', { type: 'text', maxlength: '90', value: g.tagline, class: 'op-input', autocomplete: 'off' });
			const spName = h('input', { type: 'text', maxlength: '80', value: g.sponsor.name, class: 'op-input', placeholder: 'خالی = بدون اسپانسر' });
			const spUrl = h('input', { type: 'url', dir: 'ltr', value: g.sponsor.url, class: 'op-input', placeholder: 'https://', inputmode: 'url' });
			const spLogoUrl = h('input', { type: 'url', dir: 'ltr', value: g.sponsor.logoUrl, class: 'op-input', placeholder: 'https://… (یا فایل آپلود کنید)', inputmode: 'url' });
			const spFrom = h('input', { type: 'text', value: ymdToJalali(g.sponsor.from), class: 'op-input', placeholder: '۱۴۰۵/۰۸/۱۷', inputmode: 'numeric', dir: 'ltr' });
			const spTo = h('input', { type: 'text', value: ymdToJalali(g.sponsor.to), class: 'op-input', placeholder: 'خالی = بدون پایان', inputmode: 'numeric', dir: 'ltr' });
			const preview = h('div', { class: 'op-logo' }, g.sponsor.logoUrl ? h('img', { src: g.sponsor.logoUrl, alt: 'لوگوی ' + (g.sponsor.name || 'حامی'), width: '120', height: '48' }) : h('span', { class: 'op-muted' }, 'بدون لوگو'));
			const file = h('input', { type: 'file', accept: 'image/png,image/jpeg,image/webp', class: 'op-visually-hidden', id: 'op-logo-file-' + g.id });
			let logoId = g.sponsor.logo;
			let logoChanged = false;
			file.addEventListener('change', async () => {
				if (!file.files[0]) return;
				const fd = new FormData();
				fd.append('file', file.files[0]);
				fill(preview, h('span', { class: 'op-spinner' }));
				try {
					const r = await api('games/' + g.id + '/logo', { method: 'POST', form: fd });
					logoId = r.logo;
					logoChanged = false;
					spLogoUrl.value = r.logoUrl;
					g.sponsor.logo = r.logo;
					g.sponsor.logoUrl = r.logoUrl;
					fill(preview, h('img', { src: r.logoUrl, alt: 'لوگوی حامی', width: '120', height: '48' }));
					toast('لوگو آپلود شد');
				} catch (e) {
					fill(preview, h('span', { class: 'op-muted' }, 'آپلود ناموفق'));
					toast(e.message, { tone: 'err' });
				}
			});
			spLogoUrl.addEventListener('input', () => (logoChanged = true));
			const save = h('button', { type: 'submit', class: 'op-btn is-primary' }, 'ذخیره');
			const form = h(
				'form',
				{ class: 'op-form', novalidate: true },
				field('شعار کوتاه', tagline, 'حداکثر ۹۰ حرف؛ زیر عنوان بازی نمایش داده می‌شود.'),
				h('div', { class: 'op-field' }, h('span', { class: 'op-field__label' }, 'سختی'), chips(Object.entries(data.difficulties).map(([value, label]) => ({ value, label })), diff, (v) => (diff = v), 'سختی بازی')),
				h('h3', { class: 'op-form__sub' }, 'اسپانسر این بازی', data.sponsorOn ? null : h('span', { class: 'op-tag is-muted' }, 'نمایش اسپانسر خاموش است')),
				field('نام حامی', spName),
				field('لینک حامی', spUrl),
				h('div', { class: 'op-field' }, h('span', { class: 'op-field__label' }, 'لوگو'), h('div', { class: 'op-logo-row' }, preview, h('label', { for: file.id, class: 'op-btn is-sm is-ghost' }, icon('upload', 16), 'آپلود از گوشی'), file, logoId ? h('button', { type: 'button', class: 'op-btn is-sm is-ghost is-danger-text', onClick: () => { logoId = 0; logoChanged = false; spLogoUrl.value = ''; fill(preview, h('span', { class: 'op-muted' }, 'بدون لوگو')); } }, 'حذف لوگو') : null)),
				field('یا لینک لوگو', spLogoUrl, 'لینک کتابخانه رسانه یا یک تصویر بیرونی (دانلود و در رسانه‌ها ذخیره می‌شود).'),
				h('div', { class: 'op-row2' }, field('شروع نمایش (شمسی)', spFrom), field('پایان نمایش (شمسی)', spTo)),
				g.sponsor.i ? h('p', { class: 'op-muted' }, '۳۰ روز اخیر: ', num(g.sponsor.i), ' نمایش · ', num(g.sponsor.c), ' کلیک · نرخ کلیک ', pct(g.sponsor.c, g.sponsor.i)) : null,
				h('div', { class: 'op-actions' }, save, linkBtn(g.edit, 'ویرایشگر کامل', 'wp', 'op-btn is-ghost', false)),
				h(
					'div',
					{ class: 'op-danger' },
					h('h3', { class: 'op-form__sub' }, 'منطقه خطر'),
					h('p', { class: 'op-muted' }, 'صفر کردن جدول برترین‌ها: رکورد همه بازیکنان این بازی پاک می‌شود ولی تعداد دست‌ها و XP باقی می‌ماند.'),
					h('button', {
						type: 'button',
						class: 'op-btn is-danger',
						onClick: async () => {
							if (!(await confirmBox('ریست جدول برترین‌ها', `جدول برترین‌های «${g.title}» صفر شود؟ این کار برگشت‌پذیر نیست.`, { ok: 'بله، ریست کن', danger: true }))) return;
							try {
								const r = await api('games/' + g.id + '/reset', { method: 'POST' });
								g.best = 0;
								g.champion = '';
								draw();
								toast(num(r.reset) + ' رکورد پاک شد');
							} catch (e) {
								toast(e.message, { tone: 'err' });
							}
						},
					}, icon('trophy', 16), 'ریست جدول برترین‌ها'),
				),
			);
			const s = sheet('ویرایش «' + g.title + '»', form);
			form.addEventListener('submit', async (e) => {
				e.preventDefault();
				const from = jalaliToYmd(spFrom.value);
				const to = jalaliToYmd(spTo.value);
				if (from === null || to === null) {
					toast('تاریخ را به شکل ۱۴۰۵/۰۸/۱۷ وارد کنید.', { tone: 'err' });
					(from === null ? spFrom : spTo).focus();
					return;
				}
				const sponsor = { name: spName.value, url: spUrl.value.trim(), from, to };
				if (logoChanged) sponsor.logoUrl = spLogoUrl.value.trim();
				else sponsor.logo = logoId;
				save.disabled = true;
				save.textContent = 'در حال ذخیره…';
				try {
					Object.assign(g, await api('games/' + g.id, { method: 'POST', body: { tagline: tagline.value, difficulty: diff, sponsor } }));
					draw();
					s.close();
					toast('ذخیره شد');
				} catch (err) {
					toast(err.message, { tone: 'err' });
					save.disabled = false;
					save.textContent = 'ذخیره';
				}
			});
		}

		draw();
		fill(el, 
			h('p', { class: 'op-lead' }, num(data.items.length), ' بازی · ', data.mode === 'local' ? 'حالت ذخیره: فقط مرورگر (رکوردها روی سرور ثبت نمی‌شوند)' : 'رکوردها و جدول برترین‌ها روی سرور'),
			list,
		);
	},
};
