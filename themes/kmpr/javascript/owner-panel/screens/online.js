/**
 * آنلاین — live rooms (auto-refresh every 5 s while visible), lobby status, close a room.
 */
import { h, fill, icon, num, ago, duration, avatar, toast, confirmBox, skeleton, errorBox, empty, linkBtn } from '../ui.js';
import { api } from '../api.js';

const STATUS = {
	waiting: ['در انتظار حریف', 'is-amber'],
	playing: ['در حال بازی', 'is-good'],
	over: ['تمام شده', 'is-muted'],
	finished: ['تمام شده', 'is-muted'],
	closed: ['بسته شده', 'is-muted'],
	expired: ['منقضی', 'is-muted'],
};

export default {
	async render(el, ctx) {
		if (!ctx.soft) el.append(skeleton(2, 'is-grid'), skeleton(4, 'is-cards'));
		const lobby = h('section', { class: 'op-kpis is-3', 'aria-label': 'وضعیت لابی' });
		const list = h('div', { class: 'op-cards', 'aria-live': 'polite' });
		const stamp = h('p', { class: 'op-card__note' });
		let data = null;
		let timer = null;
		let alive = true;

		async function load() {
			try {
				data = await api('online');
				draw();
			} catch (e) {
				if (!data) fill(el, errorBox(e, load));
			}
		}

		function draw() {
			const l = data.lobby;
			fill(lobby, 
				tile('در لابی عمومی', l.waiting, 'منتظر حریف (قابل دیدن در لابی)'),
				tile('مسابقه در جریان', l.active, 'هر دو بازیکن متصل'),
				tile('بازیکن آنلاین', l.online, 'امروز ' + num(l.today) + ' مسابقه · ۷ روز ' + num(l.week)),
			);
			const live = data.rooms.filter((r) => r.live && (r.status === 'waiting' || r.status === 'playing'));
			const recent = data.rooms.filter((r) => !live.includes(r));
			fill(list, 
				...(live.length ? live.map(room) : [empty('الان اتاق فعالی وجود ندارد.', 'وقتی کسی بازی آنلاین بسازد اینجا ظاهر می‌شود.')]),
				recent.length ? h('h2', { class: 'op-section-title' }, 'اتاق‌های اخیر (۳۰ دقیقه)') : null,
				...recent.map(room),
			);
			stamp.textContent = 'به‌روزرسانی خودکار هر ۵ ثانیه · آخرین: ' + new Date().toLocaleTimeString('fa-IR');
		}

		function tile(label, value, sub) {
			return h('div', { class: 'op-kpi is-arcade' }, h('span', { class: 'op-kpi__label' }, label), h('strong', { class: 'op-kpi__value' }, num(value)), h('span', { class: 'op-kpi__sub' }, sub));
		}

		function room(r) {
			const [label, tone] = STATUS[r.status] || [r.status, 'is-muted'];
			const age = data.now - r.created;
			return h(
				'article',
				{ class: 'op-room' + (r.live ? ' is-live' : '') },
				h(
					'div',
					{ class: 'op-room__head' },
					h('div', null, h('h3', { class: 'op-room__game' }, r.game), h('span', { class: 'op-muted' }, 'کد ', h('code', { dir: 'ltr' }, r.code), ' · ', r.public ? 'عمومی' : 'خصوصی (لینک دعوت)')),
					h('span', { class: 'op-tag ' + tone }, r.live && r.status !== 'over' ? h('i', { class: 'op-dot', 'aria-hidden': 'true' }) : null, label),
				),
				h(
					'ul',
					{ class: 'op-room__players' },
					r.players.map((p) =>
						h(
							'li',
							{ class: p.online ? 'is-on' : '' },
							avatar(p.avatar, p.nick, 32),
							h('span', null, p.nick),
							h('small', { class: 'op-muted' }, p.left ? 'ترک کرد' : p.online ? 'متصل' : r.live ? 'قطع' : '—'),
							p.pid ? h('a', { href: ctx.boot.base + 'players/' + p.pid + '/', class: 'op-linkish', 'data-nav': '', 'aria-label': 'پروفایل ' + p.nick }, icon('chevron', 16)) : null,
						),
					),
					r.players.length < 2 ? h('li', { class: 'op-muted' }, h('span', { class: 'op-avatar is-empty', style: { width: '32px', height: '32px' }, 'aria-hidden': 'true' }, '?'), 'جای خالی') : null,
				),
				h('p', { class: 'op-muted op-room__age' }, 'ساخته شده ', ago(r.created, data.now), ' (', duration(age), ')', r.beat ? ' · آخرین ضربان ' + ago(Math.min(r.beat, data.now), data.now) : ''),
				h(
					'div',
					{ class: 'op-actions is-tight' },
					linkBtn(r.url, 'تماشا / ورود', 'eye', 'op-btn is-sm is-ghost'),
					r.status === 'waiting' || r.status === 'playing'
						? h('button', {
								type: 'button',
								class: 'op-btn is-sm is-danger',
								onClick: async () => {
									if (!(await confirmBox('بستن اتاق', `اتاق ${r.code} (${r.game}) بسته شود؟ نتیجه‌ای ثبت نمی‌شود.`, { ok: 'ببند', danger: true }))) return;
									try {
										await api('online/' + r.code + '/close', { method: 'POST' });
										toast('اتاق بسته شد');
										load();
									} catch (e) {
										toast(e.message, { tone: 'err' });
									}
								},
							}, icon('close', 16), 'بستن اتاق')
						: null,
				),
			);
		}

		const tick = () => {
			if (!alive) return;
			if (!document.hidden && !document.querySelector('dialog[open]')) load();
			timer = setTimeout(tick, 5000);
		};

		await load();
		if (!data) return;
		fill(el, lobby, list, stamp);
		timer = setTimeout(tick, 5000);
		return {
			dispose: () => {
				alive = false;
				clearTimeout(timer);
			},
		};
	},
};
