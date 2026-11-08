/**
 * خانه — today / 7-day KPIs, top games (bars), 14-day play trend, pending comments, quick actions.
 */
import { h, fill, icon, num, pct, ago, bytes, skeleton, svg, toast, confirmBox, errorBox, linkBtn } from '../ui.js';
import { api } from '../api.js';

function delta(cur, prev) {
	if (!prev && !cur) return null;
	if (!prev) return h('span', { class: 'op-delta is-up' }, 'جدید');
	const d = Math.round(((cur - prev) / prev) * 100);
	if (d === 0) return h('span', { class: 'op-delta' }, '±۰٪');
	return h('span', { class: 'op-delta ' + (d > 0 ? 'is-up' : 'is-down'), title: 'نسبت به ۷ روز قبل' }, (d > 0 ? '▲ ' : '▼ ') + num(Math.abs(d)) + '٪');
}

function kpi({ label, value, sub, extra, href, tone = '', ic, alert = false }) {
	const body = [
		h('span', { class: 'op-kpi__label' }, ic ? icon(ic, 16) : null, label),
		h('strong', { class: 'op-kpi__value' }, value),
		h('span', { class: 'op-kpi__sub' }, sub, extra),
	];
	const cls = 'op-kpi' + (tone ? ' is-' + tone : '') + (alert ? ' has-alert' : '');
	return href ? h('a', { href, class: cls, 'data-nav': '' }, body) : h('div', { class: cls }, body);
}

/** Horizontal bars (one series → one colour, value labels at the bar end, native tooltip on hover). */
function topBars(rows) {
	if (!rows.length) return h('p', { class: 'op-muted' }, 'این هفته هنوز بازی‌ای ثبت نشده است.');
	const max = Math.max(1, ...rows.map((r) => r.plays || r.players));
	return h(
		'ol',
		{ class: 'op-bars' },
		rows.map((r) => {
			const v = r.plays || r.players;
			const w = Math.max(2, (v / max) * 100);
			const tip = `${r.title}: ${num(r.plays)} دست بازی · ${num(r.players)} بازیکن فعال`;
			return h(
				'li',
				{ class: 'op-bars__row', title: tip },
				h('span', { class: 'op-bars__label' }, r.title),
				svg('svg', { class: 'op-bars__svg', width: '100%', height: '12', role: 'img', 'aria-label': tip, preserveAspectRatio: 'none' }, svg('rect', { x: (100 - w) + '%', y: '0', width: w + '%', height: '12', rx: '4', class: 'op-bars__bar' }, svg('title', null, tip))),
				h('span', { class: 'op-bars__val' }, num(v)),
			);
		}),
	);
}

/** 14 daily columns; newest at the left (RTL reading order puts the oldest first on the right). */
function trend(points) {
	const W = 14 * 20;
	const H = 72;
	const max = Math.max(1, ...points.map((p) => p.n));
	const bars = points.map((p, i) => {
		const bh = Math.max(p.n ? 3 : 1, (p.n / max) * (H - 4));
		const x = W - (i + 1) * 20 + 3;
		return svg('rect', { x, y: H - bh, width: 14, height: bh, rx: 3, class: 'op-trend__bar' + (i === points.length - 1 ? ' is-today' : '') }, svg('title', null, `${p.d}: ${num(p.n)} دست`));
	});
	const total = points.reduce((a, p) => a + p.n, 0);
	return h(
		'figure',
		{ class: 'op-trend' },
		svg('svg', { viewBox: `0 0 ${W} ${H}`, width: '100%', role: 'img', 'aria-label': `دست‌های بازی در ۱۴ روز اخیر، مجموع ${num(total)}` }, svg('line', { x1: 0, x2: W, y1: H - 0.5, y2: H - 0.5, class: 'op-trend__base' }), bars),
		h('figcaption', { class: 'op-trend__axis' }, h('span', null, points[0]?.d), h('span', null, 'امروز')),
	);
}

function commentRow(c, onDone) {
	const act = async (action, label) => {
		row.classList.add('is-busy');
		try {
			const r = await api('comments/' + c.id, { method: 'POST', body: { action } });
			row.remove();
			onDone(r.badges);
			toast(label);
		} catch (e) {
			row.classList.remove('is-busy');
			toast(e.message, { tone: 'err' });
		}
	};
	const row = h(
		'li',
		{ class: 'op-comment' },
		h('div', { class: 'op-comment__head' }, h('strong', null, c.author || 'ناشناس'), h('span', { class: 'op-muted' }, ' · ', ago(c.date.ts))),
		h('p', { class: 'op-comment__text' }, c.text),
		h('a', { class: 'op-comment__post', href: c.url, target: '_blank', rel: 'noopener' }, 'در: ', c.post),
		h(
			'div',
			{ class: 'op-actions is-tight' },
			h('button', { type: 'button', class: 'op-btn is-sm is-primary', onClick: () => act('approve', 'دیدگاه تأیید شد') }, icon('check', 16), 'تأیید'),
			h('button', { type: 'button', class: 'op-btn is-sm is-ghost', onClick: () => act('spam', 'به اسپم منتقل شد') }, 'اسپم'),
			h('button', { type: 'button', class: 'op-btn is-sm is-ghost is-danger-text', 'aria-label': 'حذف دیدگاه', onClick: () => act('trash', 'به زباله‌دان رفت') }, icon('trash', 16)),
		),
	);
	return row;
}

export default {
	async render(el, ctx) {
		if (!ctx.soft) el.append(skeleton(3, 'is-grid'), skeleton(5));
		let d;
		try {
			d = await api('dashboard');
		} catch (e) {
			fill(el, errorBox(e, () => ctx.navigate('', { replace: true })));
			return;
		}
		ctx.setBadges(d.badges);
		const c = d.cards;
		const b = ctx.boot.base;
		const funnelRate = c.funnel.i ? pct(c.funnel.s, c.funnel.i) : '—';

		fill(el, 
			h('p', { class: 'op-hello' }, 'سلام ', ctx.boot.user || 'کوروش', h('span', { class: 'op-muted' }, ' · ', d.now)),
			h(
				'section',
				{ class: 'op-kpis', 'aria-label': 'خلاصه امروز و ۷ روز' },
				kpi({ label: 'بازیکن جدید', ic: 'players', value: num(c.players.today), sub: 'امروز · ۷ روز: ' + num(c.players.week) + ' ', extra: delta(c.players.week, c.players.prev), href: b + 'players/' }),
				kpi({ label: 'دست بازی', ic: 'games', value: num(c.plays.today), sub: 'امروز · ۷ روز: ' + num(c.plays.week) + ' ', extra: delta(c.plays.week, c.plays.prev), href: b + 'games/', tone: 'arcade' }),
				kpi({ label: 'مسابقه آنلاین', ic: 'online', value: num(c.matches.today), sub: '۷ روز: ' + num(c.matches.week) + ' · زنده: ' + num(c.matches.live), href: b + 'online/', tone: 'arcade' }),
				kpi({ label: 'درخواست پروژه', ic: 'requests', value: num(c.requests.new), sub: 'خوانده‌نشده · امروز: ' + num(c.requests.today) + ' · ۷ روز: ' + num(c.requests.week), href: b + 'requests/', tone: 'amber', alert: c.requests.new > 0 }),
				kpi({ label: 'دیدگاه در انتظار', ic: 'chat', value: num(c.comments.pending), sub: c.comments.pending ? 'نیاز به بررسی' : 'همه بررسی شده‌اند', alert: c.comments.pending > 0, href: null }),
				kpi({ label: 'پیش‌ثبت‌نام پاس', ic: 'trophy', value: num(c.signups.today), sub: 'امروز · ۷ روز: ' + num(c.signups.week) + ' · کل: ' + num(c.signups.total), href: b + 'revenue/' }),
				kpi({ label: 'تبدیل پاس (۷ روز)', ic: 'revenue', value: funnelRate, sub: num(c.funnel.i) + ' نمایش ← ' + num(c.funnel.c) + ' کلیک ← ' + num(c.funnel.s) + ' ثبت', href: b + 'revenue/', tone: c.funnel.tone === 'win' ? 'good' : '' }),
				kpi({ label: 'فضای جداول بازی', ic: 'settings', value: bytes(d.storage.total), sub: d.storage.mode === 'local' ? 'حالت: فقط مرورگر' : 'حالت: سبک (سرور)', href: b + 'settings/' }),
			),
			h(
				'div',
				{ class: 'op-grid2' },
				h('section', { class: 'op-card' }, h('h2', { class: 'op-card__title' }, 'پرطرفدارترین بازی‌های این هفته'), topBars(d.top), h('p', { class: 'op-card__note' }, 'بر اساس شروع دست در ۷ روز اخیر (بازدید ادمین و ربات شمرده نمی‌شود).')),
				h('section', { class: 'op-card' }, h('h2', { class: 'op-card__title' }, 'روند دست‌های بازی'), trend(d.trend), h('p', { class: 'op-card__note' }, 'هر ستون یک روز؛ ستون روشن‌تر = امروز.')),
			),
			h(
				'section',
				{ class: 'op-card' },
				h('h2', { class: 'op-card__title' }, 'کارهای سریع'),
				h(
					'div',
					{ class: 'op-quick' },
					h('a', { class: 'op-quick__btn', href: b + 'online/', 'data-nav': '' }, icon('online', 22), 'اتاق‌های زنده'),
					h('a', { class: 'op-quick__btn', href: b + 'players/', 'data-nav': '' }, icon('search', 22), 'جستجوی بازیکن'),
					h('button', {
						type: 'button',
						class: 'op-quick__btn',
						onClick: async () => {
							if (!(await confirmBox('پاکسازی جداول بازی', 'سیاست نگهداری همین حالا اجرا شود؟ (رکوردهای قدیمی پایین جدول، بازیکنان غیرفعال کم‌امتیاز و اتاق‌های تمام‌شده حذف می‌شوند.)', { ok: 'پاکسازی' }))) return;
							try {
								const r = await api('settings/cleanup', { method: 'POST' });
								const rows = r.cleanup.rows;
								toast(`پاکسازی شد: ${num(rows.scores + rows.players + rows.rooms)} ردیف · حدود ${bytes(r.cleanup.freed)}`);
							} catch (e) {
								toast(e.message, { tone: 'err' });
							}
						},
					}, icon('broom', 22), 'پاکسازی الان'),
					linkBtn(d.links.arcade, 'گیم‌سنتر', 'games', 'op-quick__btn'),
					linkBtn(d.links.newPost, 'نوشته جدید', 'plus', 'op-quick__btn', false),
					h('a', { class: 'op-quick__btn', href: b + 'settings/', 'data-nav': '' }, icon('settings', 22), 'تنظیمات'),
				),
			),
			d.pending.length
				? h(
						'section',
						{ class: 'op-card' },
						h('div', { class: 'op-card__head' }, h('h2', { class: 'op-card__title' }, 'دیدگاه‌های در انتظار تأیید'), linkBtn(d.links.comments, 'همه', 'external', 'op-btn is-sm is-ghost', false)),
						h('ul', { class: 'op-comments' }, d.pending.map((cm) => commentRow(cm, ctx.setBadges))),
					)
				: null,
		);
	},
};
