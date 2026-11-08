/**
 * تنظیمات — game storage (mode / keep-top-N / snapshot TTL), cleanup-now, play nudge, arcade install
 * counters, Telegram test, llms.txt regeneration, links to wp-admin.
 */
import { h, fill, icon, num, bytes, ago, sw, toast, confirmBox, skeleton, errorBox, chips, field, linkBtn, latin } from '../ui.js';
import { api } from '../api.js';

export default {
	async render(el, ctx) {
		if (!ctx.soft) el.append(skeleton(4, 'is-cards'));
		let d;
		try {
			d = await api('settings');
		} catch (e) {
			fill(el, errorBox(e, () => ctx.navigate('settings/', { replace: true })));
			return;
		}

		const usageBox = h('div');
		const pwaBox = h('div');
		const llmsBox = h('div');

		function drawUsage() {
			const max = Math.max(1, ...d.usage.tables.map((t) => t.bytes));
			fill(usageBox, 
				h('p', { class: 'op-big' }, bytes(d.usage.total)),
				h(
					'ul',
					{ class: 'op-mini' },
					d.usage.tables.map((t) =>
						h('li', null, h('span', { class: 'op-mini__t' }, { scores: 'رکوردها', players: 'بازیکنان', rooms: 'اتاق‌ها' }[t.name] || t.name), h('span', { class: 'op-mini__v' }, num(t.rows), ' ردیف'), h('span', { class: 'op-meter', 'aria-hidden': 'true' }, h('i', { style: { width: Math.max(3, (t.bytes / max) * 100) + '%' } })), h('span', { class: 'op-mini__v' }, bytes(t.bytes))),
					),
				),
			);
		}
		function drawPwa() {
			const p = d.pwa;
			fill(pwaBox, 
				h('dl', { class: 'op-stats is-5' }, [
					['پیشنهاد نصب', p.prompt],
					['پذیرفته', p.accepted],
					['رد شده', p.dismissed],
					['نصب‌شده', p.installed],
					['راهنمای iOS', p.ios_sheet],
				].map(([k, v]) => h('div', { class: 'op-stat' }, h('dt', null, k), h('dd', null, num(v))))),
			);
		}
		function drawLlms() {
			const l = d.llms;
			fill(llmsBox, 
				h('p', null, l.generated ? ['آخرین ساخت: ', l.generated.fa, h('span', { class: 'op-muted' }, ' (', ago(l.generated.ts), ')')] : 'هنوز ساخته نشده است.'),
				h('p', { class: 'op-muted' }, 'llms.txt: ', bytes(l.bytes), ' · llms-full.txt: ', bytes(l.fullBytes)),
				l.error ? h('p', { class: 'op-notice' }, icon('alert', 18), l.error) : null,
			);
		}
		drawUsage();
		drawPwa();
		drawLlms();

		// Storage form.
		let mode = d.storage.mode;
		const keep = h('input', { type: 'number', min: '10', max: '5000', step: '10', class: 'op-input', value: d.storage.keep, inputmode: 'numeric', dir: 'ltr' });
		const snap = h('input', { type: 'number', min: '0', max: '720', class: 'op-input', value: d.storage.snap, inputmode: 'numeric', dir: 'ltr' });
		const saveBtn = h('button', { type: 'submit', class: 'op-btn is-primary' }, 'ذخیره تنظیمات');
		const form = h(
			'form',
			{ class: 'op-form', novalidate: true },
			h('div', { class: 'op-field' }, h('span', { class: 'op-field__label' }, 'حالت ذخیره‌سازی'), chips([
				{ value: 'light', label: 'سبک: رکوردها روی سرور' },
				{ value: 'local', label: 'فقط مرورگر' },
			], mode, (v) => (mode = v), 'حالت ذخیره‌سازی'), h('p', { class: 'op-field__hint' }, 'در حالت «فقط مرورگر» هیچ امتیازی روی سرور ذخیره نمی‌شود؛ اتاق‌های آنلاین در هر دو حالت کار می‌کنند.')),
			h('div', { class: 'op-row2' }, field('حداکثر رکورد برای هر بازی', keep, '۱۰ تا ۵۰۰۰'), field('نگهداری بازی نیمه‌تمام (ساعت)', snap, '۰ = همیشه')),
			h('div', { class: 'op-actions' }, saveBtn),
		);
		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			saveBtn.disabled = true;
			try {
				d = await api('settings', { method: 'POST', body: { mode, keep: parseInt(latin(keep.value), 10) || 200, snap: parseInt(latin(snap.value), 10) || 0 } });
				keep.value = d.storage.keep;
				snap.value = d.storage.snap;
				toast('تنظیمات ذخیره‌سازی ذخیره شد');
			} catch (err) {
				toast(err.message, { tone: 'err' });
			} finally {
				saveBtn.disabled = false;
			}
		});

		const action = (label, ic, fn, cls = 'op-btn is-ghost') => {
			const b = h('button', { type: 'button', class: cls }, icon(ic, 18), label);
			b.addEventListener('click', async () => {
				b.disabled = true;
				b.classList.add('is-busy');
				try {
					await fn();
				} catch (e) {
					toast(e.message, { tone: 'err' });
				} finally {
					b.disabled = false;
					b.classList.remove('is-busy');
				}
			});
			return b;
		};

		const L = d.links;
		fill(el, 
			h(
				'div',
				{ class: 'op-grid2' },
				h('section', { class: 'op-card' }, h('h2', { class: 'op-card__title' }, 'ذخیره‌سازی بازی‌ها'), form),
				h(
					'section',
					{ class: 'op-card' },
					h('h2', { class: 'op-card__title' }, 'فضای اشغال‌شده'),
					usageBox,
					h(
						'div',
						{ class: 'op-actions' },
						action('پاکسازی الان', 'broom', async () => {
							if (!(await confirmBox('پاکسازی جداول', 'سیاست نگهداری اجرا شود؟ رکوردهای پایین‌تر از سقف (بازیکنان غیرفعال ۳۰+ روز)، بازیکنان کم‌فعالیت ۱۸۰+ روز و اتاق‌های تمام‌شده حذف می‌شوند.', { ok: 'پاکسازی' }))) return;
							d = await api('settings/cleanup', { method: 'POST' });
							drawUsage();
							const r = d.cleanup.rows;
							toast(`حذف شد: ${num(r.scores)} رکورد، ${num(r.players)} بازیکن، ${num(r.rooms)} اتاق · آزاد شده حدود ${bytes(d.cleanup.freed)}`, { ms: 7000 });
						}),
					),
				),
				h(
					'section',
					{ class: 'op-card' },
					h('h2', { class: 'op-card__title' }, 'بنر «وقت استراحت»'),
					h('label', { class: 'op-toggle' }, h('span', null, 'پیشنهاد بازی بعد از مطالعه در مقالات، آرشیوها، جستجو و ۴۰۴'), sw(d.nudge, 'بنر وقت استراحت', async (v, set) => {
						try {
							d = await api('settings', { method: 'POST', body: { nudge: v } });
							toast(v ? 'بنر روشن شد' : 'بنر خاموش شد', {
								undo: async () => {
									d = await api('settings', { method: 'POST', body: { nudge: !v } }).catch(() => d);
									set(d.nudge);
								},
							});
						} catch (e) {
							set(!v);
							toast(e.message, { tone: 'err' });
						}
					})),
					h('p', { class: 'op-card__note' }, 'متن و بازی‌های پیشنهادی: ', h('a', { href: L.gamesOpts }, 'تنظیمات آرشیو بازی‌ها')),
				),
				h(
					'section',
					{ class: 'op-card' },
					h('h2', { class: 'op-card__title' }, 'نصب اپ گیم‌سنتر (PWA)'),
					pwaBox,
					h('div', { class: 'op-actions' }, action('صفر کردن شمارنده‌ها', 'refresh', async () => {
						if (!(await confirmBox('صفر کردن شمارنده‌ها', 'شمارنده‌های نصب اپ گیم‌سنتر صفر شوند؟', { ok: 'صفر کن', danger: true }))) return;
						d = await api('settings/pwa-reset', { method: 'POST' });
						drawPwa();
						toast('شمارنده‌ها صفر شد');
					})),
				),
				h(
					'section',
					{ class: 'op-card' },
					h('h2', { class: 'op-card__title' }, 'اعلان تلگرام'),
					h('p', null, d.telegram ? h('span', { class: 'op-tag is-good' }, 'تنظیم شده') : h('span', { class: 'op-tag is-amber' }, 'تنظیم نشده'), ' درخواست‌های پروژه و پیش‌ثبت‌نام‌ها به تلگرام شما فرستاده می‌شوند.'),
					h(
						'div',
						{ class: 'op-actions' },
						action('ارسال پیام آزمایشی', 'send', async () => {
							await api('settings/telegram-test', { method: 'POST' });
							toast('پیام آزمایشی ارسال شد');
						}),
						linkBtn(L.telegram, 'توکن و شناسه چت', 'wp', 'op-btn is-ghost', false),
					),
				),
				h(
					'section',
					{ class: 'op-card' },
					h('h2', { class: 'op-card__title' }, 'llms.txt (هوش مصنوعی)'),
					llmsBox,
					h(
						'div',
						{ class: 'op-actions' },
						action('بازسازی الان', 'refresh', async () => {
							d = await api('settings/llms', { method: 'POST' });
							drawLlms();
							toast(d.llms.error ? 'بازسازی با خطا' : 'llms.txt بازسازی شد', { tone: d.llms.error ? 'warn' : 'ok' });
						}),
						linkBtn(L.llms, 'تنظیمات کامل', 'wp', 'op-btn is-ghost', false),
					),
				),
			),
			h(
				'section',
				{ class: 'op-card' },
				h('h2', { class: 'op-card__title' }, 'وردپرس کامل'),
				h(
					'div',
					{ class: 'op-quick' },
					linkBtn(L.admin, 'پیشخوان', 'wp', 'op-quick__btn', false),
					linkBtn(L.games, 'همه بازی‌ها', 'games', 'op-quick__btn', false),
					linkBtn(L.gamesOpts, 'تنظیمات آرشیو', 'settings', 'op-quick__btn', false),
					linkBtn(L.players, 'جدول بازیکنان', 'players', 'op-quick__btn', false),
					linkBtn(L.revenue, 'درآمد (کامل)', 'revenue', 'op-quick__btn', false),
					linkBtn(L.requests, 'درخواست‌ها', 'requests', 'op-quick__btn', false),
					linkBtn(L.comments, 'دیدگاه‌ها', 'chat', 'op-quick__btn', false),
					linkBtn(ctx.boot.logout, 'خروج از حساب', 'logout', 'op-quick__btn', false),
				),
			),
		);
	},
};
