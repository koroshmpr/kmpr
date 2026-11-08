/**
 * بازیکنان — searchable infinite list, detail sheet (/panel/players/<id>/), rename / reset / delete,
 * bulk delete of inactive players (dry-run count first).
 */
import { h, fill, icon, num, ago, avatar, sheet, toast, confirmBox, skeleton, errorBox, empty, chips, debounce, field, copyText } from '../ui.js';
import { api } from '../api.js';

const ORDERS = [
	{ value: 'last_seen', label: 'آخرین فعالیت' },
	{ value: 'new', label: 'جدیدترین' },
	{ value: 'xp', label: 'بیشترین XP' },
	{ value: 'plays', label: 'بیشترین بازی' },
];

export default {
	async render(el, ctx) {
		const st = { search: '', order: 'last_seen', next: 0, loading: false, total: null, gen: 0 };
		const list = h('ul', { class: 'op-list', 'aria-label': 'فهرست بازیکنان' });
		const count = h('p', { class: 'op-lead', 'aria-live': 'polite' });
		const sentinel = h('div', { class: 'op-sentinel', 'aria-hidden': 'true' });
		const searchInput = h('input', { type: 'search', class: 'op-input op-search__input', placeholder: 'جستجوی نام مستعار یا شناسه…', 'aria-label': 'جستجوی بازیکن', enterkeyhint: 'search', autocomplete: 'off' });
		let detail = null;
		let disposed = false;

		const reset = () => {
			st.next = 0;
			st.gen++;
			fill(list);
			load();
		};
		searchInput.addEventListener('input', debounce(() => {
			st.search = searchInput.value.trim();
			reset();
		}, 300));

		async function load() {
			if (st.loading || st.next === null) return;
			st.loading = true;
			const gen = st.gen;
			if (st.next === 0) fill(list, skeleton(6, 'is-list'));
			try {
				const r = await api('players', { query: { search: st.search, order: st.order, offset: st.next } });
				if (gen !== st.gen) return;
				if (st.next === 0) {
					fill(list);
					st.total = r.total;
					count.textContent = st.search ? `${num(r.total)} نتیجه برای «${st.search}»` : `${num(r.total)} بازیکن ثبت شده`;
				}
				for (const p of r.items) list.append(row(p));
				if (!list.children.length) list.append(h('li', null, empty(st.search ? 'بازیکنی با این نام پیدا نشد.' : 'هنوز بازیکنی ثبت نشده است.')));
				st.next = r.next;
			} catch (e) {
				if (st.next === 0) fill(list, errorBox(e, reset));
				else toast(e.message, { tone: 'err' });
			} finally {
				if (gen === st.gen) st.loading = false;
			}
		}

		function row(p) {
			const name = p.nickname || '(بدون نام)';
			const li = h(
				'li',
				null,
				h(
					'a',
					{
						href: ctx.boot.base + 'players/' + p.pid + '/',
						class: 'op-player',
						onClick: (e) => {
							e.preventDefault();
							ctx.navigate('players/' + p.pid + '/', { detail: true });
						},
					},
					avatar(p.avatar, name, 44),
					h(
						'span',
						{ class: 'op-player__main' },
						h('strong', { class: 'op-player__name' }, name),
						h('span', { class: 'op-player__sub' }, 'سطح ', num(p.level), ' · ', h('bdi', { dir: 'rtl' }, num(p.xp), ' XP'), ' · ', num(p.plays), ' دست'),
					),
					h('span', { class: 'op-player__side' }, h('span', { class: 'op-wld', title: 'برد / باخت / مساوی', 'aria-label': `برد ${num(p.wins)}، باخت ${num(p.losses)}، مساوی ${num(p.draws)}` }, h('b', { class: 'is-w' }, num(p.wins)), h('i', null, '/'), h('b', { class: 'is-l' }, num(p.losses)), h('i', null, '/'), h('b', null, num(p.draws))), h('small', null, ago(p.lastSeen.ts))),
				),
			);
			li.dataset.pid = p.pid;
			return li;
		}

		const io = new IntersectionObserver((ents) => ents.some((e) => e.isIntersecting) && load(), { rootMargin: '600px 0px' });
		io.observe(sentinel);

		async function openDetail(pid) {
			if (!detail) {
				detail = sheet('بازیکن', h('div'), {
					onClose: () => {
						detail = null;
						if (!disposed) ctx.closeDetail('players/');
					},
				});
			}
			const s = detail;
			const body = s.body;
			fill(body, skeleton(5));
			let p;
			try {
				p = await api('players/' + pid);
			} catch (e) {
				fill(body, errorBox(e));
				return;
			}
			const name = p.nickname || '(بدون نام)';
			s.setTitle(name);
			const pctLvl = p.next > p.floor ? Math.round(((p.xp - p.floor) / (p.next - p.floor)) * 100) : 0;
			fill(body, 
				h(
					'div',
					{ class: 'op-profile' },
					avatar(p.avatar, name, 64),
					h(
						'div',
						null,
						h('strong', { class: 'op-profile__name' }, name),
						h('button', { type: 'button', class: 'op-linkish', onClick: () => copyText(p.pid), 'aria-label': 'کپی شناسه کامل' }, h('code', { dir: 'ltr' }, p.short), icon('copy', 14)),
						h('div', { class: 'op-level' }, h('span', null, 'سطح ', num(p.level), ' · ', h('bdi', { dir: 'rtl' }, num(p.xp), ' XP')), h('span', { class: 'op-level__bar', role: 'progressbar', 'aria-valuenow': pctLvl, 'aria-valuemin': '0', 'aria-valuemax': '100', 'aria-label': 'پیشرفت تا سطح بعد' }, h('i', { style: { width: pctLvl + '%' } }))),
					),
				),
				h(
					'dl',
					{ class: 'op-stats is-4' },
					st2('کل دست‌ها', num(p.plays)),
					st2('آنلاین (ب/ب/م)', `${num(p.wins)} / ${num(p.losses)} / ${num(p.draws)}`),
					st2('دستاورد', num(p.ach)),
					st2('کاربر سایت', p.user || '—'),
					st2('آخرین فعالیت', ago(p.lastSeen.ts), p.lastSeen.fa),
					st2('اولین بازدید', ago(p.firstSeen.ts), p.firstSeen.fa),
				),
				h('h3', { class: 'op-form__sub' }, 'بازی‌ها'),
				p.games.length
					? h(
							'ul',
							{ class: 'op-mini' },
							p.games.map((g) =>
								h('li', null, h('span', { class: 'op-mini__t' }, g.title), h('span', { class: 'op-mini__v' }, num(g.plays), ' دست'), h('span', { class: 'op-mini__v' }, g.best ? 'رکورد ' + num(g.best) : 'بدون رکورد'), h('small', { class: 'op-muted' }, ago(g.last.ts))),
							),
						)
					: h('p', { class: 'op-muted' }, 'هنوز بازی‌ای ثبت نشده است.'),
				h(
					'div',
					{ class: 'op-actions' },
					h('button', { type: 'button', class: 'op-btn is-ghost', onClick: () => rename(p) }, icon('edit', 16), 'تغییر نام'),
					h('button', { type: 'button', class: 'op-btn is-ghost', onClick: () => resetScores(p) }, icon('trophy', 16), 'ریست امتیازها'),
					h('button', { type: 'button', class: 'op-btn is-danger', onClick: () => remove(p) }, icon('trash', 16), 'حذف بازیکن'),
				),
				h('p', { class: 'op-card__note' }, 'تاریخچه کامل و دستاوردها فقط در مرورگر خود بازیکن نگه‌داری می‌شود.'),
			);
		}

		function st2(label, value, sub = '') {
			return h('div', { class: 'op-stat' }, h('dt', null, label), h('dd', null, value, sub ? h('small', null, sub) : null));
		}

		function patchRow(p) {
			const old = list.querySelector(`li[data-pid="${p.pid}"]`);
			if (old) old.replaceWith(row(p));
		}

		async function rename(p) {
			const input = h('input', { type: 'text', class: 'op-input', value: p.nickname, maxlength: '24', autocomplete: 'off' });
			const v = await confirmBox('تغییر نام مستعار', 'برای نام‌های نامناسب؛ لینک و تگ حذف می‌شود.', { ok: 'ذخیره نام', extra: field('نام جدید', input), collect: () => ({ name: input.value }) });
			if (!v) return;
			try {
				const fresh = await api('players/' + p.pid, { method: 'POST', body: { action: 'rename', nickname: v.name } });
				patchRow(fresh);
				toast('نام به «' + fresh.nickname + '» تغییر کرد');
				openDetail(p.pid);
			} catch (e) {
				toast(e.message, { tone: 'err' });
			}
		}

		async function resetScores(p) {
			const xp = h('input', { type: 'checkbox', id: 'op-reset-xp' });
			const v = await confirmBox('ریست امتیازها', `رکورد «${p.nickname || 'این بازیکن'}» در همه بازی‌ها صفر شود؟ تعداد دست‌ها باقی می‌ماند.`, {
				ok: 'ریست',
				danger: true,
				extra: h('label', { class: 'op-check', for: 'op-reset-xp' }, xp, h('span', null, 'XP و برد/باخت آنلاین هم صفر شود')),
				collect: () => ({ xp: xp.checked }),
			});
			if (!v) return;
			try {
				const fresh = await api('players/' + p.pid, { method: 'POST', body: { action: 'reset', xp: !!v.xp } });
				patchRow(fresh);
				toast('امتیازها ریست شد');
				openDetail(p.pid);
			} catch (e) {
				toast(e.message, { tone: 'err' });
			}
		}

		async function remove(p) {
			if (!(await confirmBox('حذف بازیکن', `«${p.nickname || 'این بازیکن'}» و همه رکوردهایش برای همیشه حذف شود؟`, { ok: 'حذف کن', danger: true }))) return;
			try {
				await api('players/' + p.pid, { method: 'DELETE' });
				list.querySelector(`li[data-pid="${p.pid}"]`)?.remove();
				if (detail) detail.close();
				toast('بازیکن حذف شد');
			} catch (e) {
				toast(e.message, { tone: 'err' });
			}
		}

		async function prune() {
			let days = 180;
			const info = h('p', { class: 'op-muted', 'aria-live': 'polite' }, '…');
			const go = h('button', { type: 'button', class: 'op-btn is-danger', disabled: true }, 'حذف');
			const count = async () => {
				info.textContent = 'در حال شمارش…';
				go.disabled = true;
				try {
					const r = await api('players/prune', { method: 'POST', body: { days, dry: true } });
					info.textContent = r.count ? `${num(r.count)} بازیکن بیش از ${num(days)} روز است فعالیتی نداشته‌اند.` : 'بازیکن غیرفعالی با این شرط وجود ندارد.';
					go.disabled = !r.count;
					go.textContent = r.count ? `حذف ${num(r.count)} بازیکن` : 'حذف';
				} catch (e) {
					info.textContent = e.message;
				}
			};
			const s = sheet('حذف گروهی بازیکنان غیرفعال', [
				h('p', null, 'بازیکنانی که از این مدت به بعد هیچ فعالیتی نداشته‌اند همراه رکوردهایشان حذف می‌شوند:'),
				chips([30, 60, 90, 180, 365].map((d) => ({ value: d, label: num(d) + ' روز' })), days, (v) => {
					days = v;
					count();
				}, 'مدت عدم فعالیت'),
				info,
				h('div', { class: 'op-actions' }, go),
			]);
			go.addEventListener('click', async () => {
				go.disabled = true;
				try {
					const r = await api('players/prune', { method: 'POST', body: { days, dry: false } });
					s.close();
					toast(num(r.deleted) + ' بازیکن حذف شد');
					reset();
				} catch (e) {
					toast(e.message, { tone: 'err' });
					go.disabled = false;
				}
			});
			count();
		}

		fill(el, 
			h('div', { class: 'op-toolbar' }, h('label', { class: 'op-search' }, icon('search', 18), searchInput), h('button', { type: 'button', class: 'op-btn is-ghost is-sm', onClick: prune }, icon('trash', 16), 'حذف غیرفعال‌ها')),
			chips(ORDERS, st.order, (v) => {
				st.order = v;
				reset();
			}, 'مرتب‌سازی'),
			count,
			list,
			sentinel,
		);
		load();
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
				io.disconnect();
				if (detail) detail.close();
			},
		};
	},
};
