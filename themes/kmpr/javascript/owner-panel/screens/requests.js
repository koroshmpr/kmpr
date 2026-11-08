/**
 * درخواست‌ها — project-request inbox: status filter, detail sheet (/panel/requests/<id>/),
 * status change, internal note, call / Telegram / WhatsApp / email buttons. Opening marks it as seen.
 */
import { h, fill, icon, num, ago, sheet, toast, skeleton, errorBox, empty, chips, copyText, linkBtn } from '../ui.js';
import { api } from '../api.js';

export default {
	async render(el, ctx) {
		const st = { status: 'all', next: 0, items: new Map(), statuses: [], tg: true };
		const list = h('ul', { class: 'op-list' });
		const filters = h('div');
		const warn = h('div');
		const more = h('button', { type: 'button', class: 'op-btn is-ghost', hidden: true, onClick: () => load() }, 'بیشتر');
		let detail = null;
		let disposed = false;

		const color = (k) => st.statuses.find((s) => s.key === k)?.color || '#64748b';
		const label = (k) => st.statuses.find((s) => s.key === k)?.label || k;

		async function load(reset = false) {
			if (reset) {
				st.next = 0;
				fill(list, skeleton(4, 'is-list'));
			}
			if (st.next === null) return;
			try {
				const r = await api('requests', { query: { status: st.status, offset: st.next } });
				st.statuses = r.statuses;
				st.tg = r.tg;
				if (st.next === 0) {
					fill(list);
					drawFilters(r.total);
				}
				for (const it of r.items) {
					st.items.set(it.id, it);
					list.append(row(it));
				}
				if (!list.children.length) list.append(h('li', null, empty('درخواستی در این وضعیت نیست.')));
				st.next = r.next;
				more.hidden = r.next === null;
				fill(warn, st.tg ? '' : h('p', { class: 'op-notice' }, icon('alert', 18), 'اعلان تلگرام تنظیم نشده؛ درخواست‌های جدید فقط اینجا و در ایمیل دیده می‌شوند. ', h('a', { href: ctx.boot.base + 'settings/', 'data-nav': '' }, 'تنظیمات')));
			} catch (e) {
				fill(list, errorBox(e, () => load(true)));
			}
		}

		function drawFilters(total) {
			fill(filters, 
				chips([{ value: 'all', label: 'همه', count: total }, ...st.statuses.map((s) => ({ value: s.key, label: s.label, count: s.count }))], st.status, (v) => {
					st.status = v;
					load(true);
				}, 'فیلتر وضعیت'),
			);
		}

		function row(it) {
			const li = h(
				'li',
				{ 'data-id': it.id },
				h(
					'a',
					{
						href: ctx.boot.base + 'requests/' + it.id + '/',
						class: 'op-req' + (it.seen ? '' : ' is-unseen'),
						onClick: (e) => {
							e.preventDefault();
							ctx.navigate('requests/' + it.id + '/', { detail: true });
						},
					},
					h('span', { class: 'op-req__dot', style: { background: color(it.status) }, 'aria-hidden': 'true' }),
					h(
						'span',
						{ class: 'op-req__main' },
						h('strong', null, it.name, it.business ? h('span', { class: 'op-muted' }, ' · ', it.business) : null),
						h('span', { class: 'op-req__sub' }, it.type, it.budget ? ' · ' + it.budget : ''),
					),
					h('span', { class: 'op-req__side' }, h('span', { class: 'op-tag', style: { '--tag': color(it.status) } }, label(it.status)), h('small', null, ago(it.date.ts))),
				),
			);
			return li;
		}

		function redrawRow(it) {
			list.querySelector(`li[data-id="${it.id}"]`)?.replaceWith(row(it));
		}

		async function openDetail(id) {
			id = Number(id);
			let it = st.items.get(id);
			if (!detail) {
				detail = sheet('درخواست', h('div'), {
					onClose: () => {
						detail = null;
						if (!disposed) ctx.closeDetail('requests/');
					},
					wide: true,
				});
			}
			const s = detail;
			if (!it || !it.seen) {
				fill(s.body, skeleton(5));
				try {
					it = await api('requests/' + id, { method: 'POST', body: { seen: true } });
					ctx.setBadges(it.badges);
					st.items.set(id, it);
					redrawRow(it);
				} catch (e) {
					fill(s.body, errorBox(e));
					return;
				}
			}
			s.setTitle(it.name);
			const note = h('textarea', { class: 'op-input', rows: '4', 'aria-label': 'یادداشت داخلی', placeholder: 'یادداشت داخلی (فقط برای شما)…' }, it.note);
			const saveNote = h('button', { type: 'button', class: 'op-btn is-sm is-primary' }, 'ذخیره یادداشت');
			saveNote.addEventListener('click', async () => {
				saveNote.disabled = true;
				try {
					const r = await api('requests/' + id, { method: 'POST', body: { note: note.value } });
					st.items.set(id, r);
					toast('یادداشت ذخیره شد');
				} catch (e) {
					toast(e.message, { tone: 'err' });
				} finally {
					saveNote.disabled = false;
				}
			});
			const L = it.links;
			const info = [
				['کد پیگیری', it.code && h('bdi', { dir: 'ltr' }, it.code)],
				['نوع پروژه', it.type],
				['بودجه', it.budget],
				['زمان‌بندی', it.timeline],
				['ترجیح ارتباط', it.channel],
				['امکانات', it.features.join('، ')],
				['صفحه خدمت', it.service],
				['ثبت از صفحه', it.source && h('bdi', { dir: 'ltr' }, it.source)],
				['تاریخ', it.date.fa],
			].filter(([, v]) => v);
			fill(s.body, 
				h(
					'div',
					{ class: 'op-contact' },
					L.tel ? h('a', { class: 'op-btn is-primary', href: L.tel }, icon('phone', 18), 'تماس') : null,
					L.tg ? h('a', { class: 'op-btn is-sky', href: L.tg, target: '_blank', rel: 'noopener' }, icon('send', 18), 'تلگرام') : null,
					L.wa ? h('a', { class: 'op-btn is-good', href: L.wa, target: '_blank', rel: 'noopener' }, icon('chat', 18), 'واتساپ') : null,
					L.mail ? h('a', { class: 'op-btn is-ghost', href: L.mail }, icon('mail', 18), 'ایمیل') : null,
					it.phone ? h('button', { type: 'button', class: 'op-btn is-ghost', onClick: () => copyText(it.phone) }, icon('copy', 18), h('span', { dir: 'ltr' }, it.phone)) : null,
				),
				h('div', { class: 'op-field' }, h('span', { class: 'op-field__label' }, 'وضعیت پیگیری'), chips(st.statuses.map((x) => ({ value: x.key, label: x.label })), it.status, async (v) => {
					const prev = it.status;
					try {
						const r = await api('requests/' + id, { method: 'POST', body: { status: v } });
						st.items.set(id, r);
						it = r;
						ctx.setBadges(r.badges);
						redrawRow(r);
						toast('وضعیت: ' + label(v), {
							undo: async () => {
								const u = await api('requests/' + id, { method: 'POST', body: { status: prev } }).catch(() => null);
								if (u) {
									st.items.set(id, u);
									redrawRow(u);
									ctx.setBadges(u.badges);
									if (detail) openDetail(id);
								}
							},
						});
					} catch (e) {
						toast(e.message, { tone: 'err' });
					}
				}, 'وضعیت')),
				it.message ? h('blockquote', { class: 'op-quote' }, it.message) : null,
				h('dl', { class: 'op-dl' }, info.map(([k, v]) => [h('dt', null, k), h('dd', null, v)])),
				it.reference ? h('p', null, 'سایت مرجع: ', h('a', { href: it.reference, target: '_blank', rel: 'noopener noreferrer', dir: 'ltr' }, it.reference)) : null,
				h('div', { class: 'op-field' }, h('span', { class: 'op-field__label' }, 'یادداشت داخلی'), note, h('div', { class: 'op-actions is-tight' }, saveNote)),
				h('div', { class: 'op-actions' }, linkBtn(it.edit, 'باز کردن در وردپرس', 'wp', 'op-btn is-ghost is-sm', false), it.tgSent ? null : h('span', { class: 'op-tag is-muted' }, 'اعلان تلگرام ارسال نشد')),
			);
		}

		fill(el, warn, filters, list, more);
		await load(true);
		if (ctx.id) openDetail(ctx.id);
		return {
			params: ({ id }) => {
				if (id) openDetail(id);
				else if (detail) {
					const d = detail;
					detail = null;
					d.close();
				}
			},
			dispose: () => {
				disposed = true;
				if (detail) detail.close();
			},
		};
	},
};
