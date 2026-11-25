/**
 * Friends: panel on /account/#friends, invite toasts in the game center (piggybacks on the presence heartbeat
 * event `kmpr:presence`), and a small public API for games:
 *
 *   window.kmprFriends.invite(game, roomCode)  → friend picker → invites (game = post id or engine slug)
 *   window.dispatchEvent(new CustomEvent('kmpr:friends-invite', { detail: { game, code } }))  (same thing)
 *   window.kmprFriends.available               → true when the visitor is logged in and friends are on
 */
import { C, call } from './sync.js';
import { esc, fa, openAuth } from './ui.js';

const ok = () => !!(C.friends && C.user && C.visitor);
let data = null;

async function load() {
	const r = await call('/friends', null, { method: 'GET' });
	if (r.ok) data = r;
	return r;
}

function toast(html, actions = [], ms = 0) {
	let wrap = document.querySelector('.ka-toasts');
	if (!wrap) {
		wrap = document.createElement('div');
		wrap.className = 'ka-toasts';
		wrap.setAttribute('aria-live', 'polite');
		document.body.appendChild(wrap);
	}
	const t = document.createElement('div');
	t.className = 'ka-toast';
	t.innerHTML = `<p>${html}</p><div class="ka-toast__a">${actions.map((a, i) => `<button type="button" class="ka-btn ${a.ghost ? 'ka-btn--ghost' : ''}" data-i="${i}">${esc(a.label)}</button>`).join('')}</div>`;
	t.addEventListener('click', (e) => {
		const b = e.target.closest('[data-i]');
		if (!b) return;
		actions[Number(b.dataset.i)].run?.();
		t.remove();
	});
	wrap.appendChild(t);
	if (ms) setTimeout(() => t.remove(), ms);
	return t;
}

/* ---------- invites received (game center + account page) ---------- */
const seen = new Set();
function showInvites(list) {
	(list || []).forEach((inv) => {
		const key = inv.code + ':' + inv.from;
		if (seen.has(key)) return;
		seen.add(key);
		try {
			if (sessionStorage.getItem('ka_inv_' + key)) return;
			sessionStorage.setItem('ka_inv_' + key, '1');
		} catch (e) {
			/* ignore */
		}
		toast(`🎮 <b>${esc(inv.name)}</b> تو را به «${esc(inv.title)}» دعوت کرد.`, [
			{ label: 'بریم!', run: () => call('/friends/invite/dismiss', { code: inv.code }).finally(() => (window.location.href = inv.url)) },
			{ label: 'بعداً', ghost: true, run: () => call('/friends/invite/dismiss', { code: inv.code }) },
		]);
	});
}

/* ---------- friend picker (API) ---------- */
async function pick(game, code) {
	if (!C.user) {
		openAuth('login', 'برای دوست شدن و دعوت دوستان وارد شو.');
		return { ok: false, reason: 'login' };
	}
	if (!ok()) return { ok: false, reason: 'off' };
	const r = await load();
	if (!r.ok) return r;
	const friends = data.friends || [];
	return new Promise((resolve) => {
		const d = document.createElement('dialog');
		d.className = 'ka-dlg';
		d.innerHTML = `<form method="dialog" class="ka-sheet">
			<div class="ka-sheet__hd"><span class="ka-sheet__grab" aria-hidden="true"></span><p class="ka-sheet__t">دعوت دوستان</p></div>
			${friends.length ? `<ul class="ka-pick">${friends.map((f) => `<li><label class="ka-check"><input type="checkbox" value="${f.id}" ${f.online ? 'checked' : ''}><span class="ka-av ka-av--s">${esc(f.initials)}</span><span>${esc(f.name)}<small class="${f.online ? 'is-on' : ''}">${f.online ? '● آنلاین' : 'آفلاین'}</small></span></label></li>`).join('')}</ul>` : `<p class="ka-note">هنوز دوستی نداری. از <a href="${esc(C.page)}#friends">صفحه حساب</a> دوست اضافه کن.</p>`}
			<div class="ka-row">${friends.length ? '<button type="submit" class="ka-btn" value="send">فرستادن دعوت</button>' : ''}<button type="submit" class="ka-btn ka-btn--ghost" value="cancel">بستن</button></div>
		</form>`;
		document.body.appendChild(d);
		d.addEventListener('close', async () => {
			const to = [...d.querySelectorAll('input:checked')].map((i) => Number(i.value));
			const send = d.returnValue === 'send' && to.length;
			d.remove();
			if (!send) return resolve({ ok: false, reason: 'cancel' });
			const res = await call('/friends/invite', { to, game, code });
			toast(res.ok ? 'دعوت فرستاده شد ✓' : esc(res.message), [], 3500);
			resolve(res);
		});
		d.showModal();
	});
}

/* ---------- /account/#friends panel ---------- */
function card(f, actions) {
	const best = (f.best || []).map((b) => `<a class="ka-chip" href="${esc(b.url)}">${esc(b.title)}: <b>${fa(b.score)}</b></a>`).join('');
	return `<li class="ka-fr">
		<span class="ka-av ka-av--s ${f.online ? 'is-on' : ''}" aria-hidden="true">${esc(f.initials)}</span>
		<div class="ka-fr__b"><p class="ka-fr__n">${esc(f.name)} <small dir="ltr">@${esc(f.handle)}</small></p>
			<p class="ka-fr__s ${f.online ? 'is-on' : ''}">${f.online ? '● ' + esc(f.status) : 'آفلاین'}</p>
			${best ? `<div class="ka-chips">${best}</div>` : ''}</div>
		<div class="ka-fr__a">${actions}</div></li>`;
}

function panel(root) {
	const d = data;
	const gameOpts = (d.games || []).map((g) => `<option value="${g.id}">${esc(g.title)}</option>`).join('');
	root.innerHTML = `
	<section class="ka-card" aria-labelledby="ka-fr-t">
		<h2 id="ka-fr-t" class="ka-h2">دوستان</h2>
		${(d.invites || []).length ? `<ul class="ka-list">${d.invites.map((i) => `<li class="ka-inv">🎮 <b>${esc(i.name)}</b> دعوتت کرد به «${esc(i.title)}» <a class="ka-btn" href="${esc(i.url)}" data-join="${esc(i.code)}">بریم</a></li>`).join('')}</ul>` : ''}
		<form class="ka-form ka-add" data-add novalidate>
			<label class="ka-f"><span class="ka-f__l">افزودن دوست با شناسه</span>
				<span class="ka-row"><input class="ka-in" name="handle" dir="ltr" placeholder="@player123" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="21"><button type="submit" class="ka-btn">درخواست</button></span></label>
			<p class="ka-err" data-err role="alert"></p>
		</form>
		${(d.incoming || []).length ? `<h3 class="ka-h3">درخواست‌های دوستی</h3><ul class="ka-list">${d.incoming.map((f) => card(f, `<button type="button" class="ka-btn" data-acc="${f.id}">قبول</button><button type="button" class="ka-btn ka-btn--ghost" data-dec="${f.id}">رد</button><button type="button" class="ka-mini" data-block="${f.id}">مسدود</button>`)).join('')}</ul>` : ''}
		<h3 class="ka-h3">دوستانم (${fa((d.friends || []).length)})</h3>
		${(d.friends || []).length ? `<ul class="ka-list">${d.friends.map((f) => card(f, `${gameOpts ? `<button type="button" class="ka-btn" data-inv="${f.id}">دعوت به بازی</button>` : ''}<details class="ka-menu"><summary class="ka-mini" aria-label="گزینه‌های بیشتر">⋯</summary><button type="button" class="ka-mini" data-rm="${f.id}">حذف از دوستان</button><button type="button" class="ka-mini" data-block="${f.id}">مسدود کردن</button></details>`)).join('')}</ul>` : '<p class="ka-fine">هنوز دوستی اضافه نکرده‌ای. شناسه‌ات یا لینک دعوتت را برای دوستانت بفرست.</p>'}
		${(d.outgoing || []).length ? `<h3 class="ka-h3">در انتظار قبول</h3><ul class="ka-list">${d.outgoing.map((f) => card(f, `<button type="button" class="ka-mini" data-rm="${f.id}">لغو</button>`)).join('')}</ul>` : ''}
	</section>
	<section class="ka-card" aria-labelledby="ka-share-t">
		<h2 id="ka-share-t" class="ka-h2">لینک دعوت من</h2>
		<p class="ka-fine">شناسه‌ات: <b dir="ltr">@${esc(d.me.handle)}</b> — هر کس این لینک را باز کند می‌تواند درخواست دوستی بدهد.</p>
		<div class="ka-row"><input class="ka-in" dir="ltr" readonly value="${esc(d.me.link)}" data-link aria-label="لینک دعوت"><button type="button" class="ka-btn" data-share>اشتراک</button></div>
		<form class="ka-form" data-settings novalidate>
			<label class="ka-f"><span class="ka-f__l">تغییر شناسه</span><span class="ka-row"><input class="ka-in" name="handle" dir="ltr" value="${esc(d.me.handle)}" maxlength="20" autocapitalize="off" spellcheck="false"><button type="submit" class="ka-btn ka-btn--ghost">ذخیره</button></span></label>
			<label class="ka-check"><input type="checkbox" name="findable" ${d.me.findable ? 'checked' : ''}> <span>دیگران بتوانند مرا با شناسه پیدا کنند</span></label>
			<button type="button" class="ka-mini" data-newlink>ساخت لینک دعوت تازه (لینک قبلی باطل می‌شود)</button>
			<p class="ka-err" data-err role="alert"></p>
		</form>
		${(d.blocked || []).length ? `<details class="ka-more"><summary>مسدودشده‌ها (${fa(d.blocked.length)})</summary><ul class="ka-list">${d.blocked.map((f) => `<li class="ka-row">${esc(f.name)} <button type="button" class="ka-mini" data-unblock="${f.id}">رفع مسدودی</button></li>`).join('')}</ul></details>` : ''}
	</section>`;
}

async function mountPanel(root) {
	if (!ok()) return;
	root.innerHTML = '<div class="ka-card"><p class="ka-fine">در حال بارگذاری دوستان…</p></div>';
	const r = await load();
	if (!r.ok) {
		root.innerHTML = `<div class="ka-card"><p class="ka-note">${esc(r.message || 'دوستان بارگذاری نشد.')}</p></div>`;
		return;
	}
	const q = new URLSearchParams(window.location.search);
	if (q.get('add')) {
		const res = await call('/friends/request', { code: q.get('add') });
		history.replaceState(null, '', C.page + '#friends');
		await load();
		panel(root);
		const err = root.querySelector('[data-add] [data-err]');
		err.textContent = res.message || '';
		err.classList.toggle('is-ok', !!res.ok);
	} else panel(root);
	showInvites(data.invites);

	const act = async (path, body) => {
		const res = await call(path, body);
		if (res.ok && res.friends) data = res;
		panel(root);
		return res;
	};
	root.addEventListener('click', async (e) => {
		const b = e.target.closest('button,[data-join]');
		if (!b) return;
		const id = (k) => Number(b.dataset[k]);
		if (b.dataset.acc) act('/friends/respond', { id: id('acc'), accept: true });
		else if (b.dataset.dec) act('/friends/respond', { id: id('dec'), accept: false });
		else if (b.dataset.rm && confirm('مطمئنی؟')) act('/friends/remove', { id: id('rm') });
		else if (b.dataset.block && confirm('این بازیکن مسدود شود؟ دیگر نمی‌تواند به تو درخواست یا دعوت بدهد.')) act('/friends/block', { id: id('block') });
		else if (b.dataset.unblock) act('/friends/block', { id: id('unblock'), unblock: true });
		else if (b.dataset.join) call('/friends/invite/dismiss', { code: b.dataset.join });
		else if (b.dataset.newlink) act('/friends/settings', { new_link: true });
		else if (b.dataset.share) {
			const link = data.me.link;
			if (navigator.share) navigator.share({ title: 'با من دوست شو', text: 'در گیم‌سنتر با من دوست شو و با هم بازی کنیم:', url: link }).catch(() => {});
			else navigator.clipboard?.writeText(link).then(() => toast('لینک کپی شد ✓', [], 2500));
		} else if (b.dataset.inv) {
			const games = data.games || [];
			const f = (data.friends || []).find((x) => x.id === id('inv'));
			const d = document.createElement('dialog');
			d.className = 'ka-dlg';
			d.innerHTML = `<form method="dialog" class="ka-sheet"><div class="ka-sheet__hd"><span class="ka-sheet__grab" aria-hidden="true"></span><p class="ka-sheet__t">دعوت ${esc(f ? f.name : '')} به بازی</p></div>
				<label class="ka-f"><span class="ka-f__l">کدام بازی؟</span><select class="ka-in" name="game">${games.map((g) => `<option value="${g.id}">${esc(g.title)}</option>`).join('')}</select></label>
				<p class="ka-fine">یک اتاق آنلاین ساخته می‌شود و دعوت برای دوستت فرستاده می‌شود.</p>
				<div class="ka-row"><button class="ka-btn" value="go">ساخت اتاق و دعوت</button><button class="ka-btn ka-btn--ghost" value="no">انصراف</button></div></form>`;
			document.body.appendChild(d);
			d.addEventListener('close', async () => {
				const game = Number(d.querySelector('select').value);
				const go = d.returnValue === 'go';
				d.remove();
				if (!go) return;
				const res = await call('/friends/invite', { to: [id('inv')], game });
				if (res.ok && res.url) {
					try {
						sessionStorage.setItem('ka_inviting', '1');
					} catch (err) {
						/* ignore */
					}
					window.location.href = res.url;
				}
				else toast(esc(res.message || 'خطا'), [], 3500);
			});
			d.showModal();
		}
	});
	root.addEventListener('submit', async (e) => {
		e.preventDefault();
		const form = e.target;
		if (form.matches('[data-add]')) {
			const res = await call('/friends/request', { handle: form.elements.handle.value });
			if (res.ok) await load();
			panel(root);
			const err = root.querySelector('[data-add] [data-err]');
			err.textContent = res.message || '';
			err.classList.toggle('is-ok', !!res.ok);
		} else if (form.matches('[data-settings]')) {
			const res = await act('/friends/settings', { handle: form.elements.handle.value, findable: form.elements.findable.checked });
			const err = root.querySelector('[data-settings] [data-err]');
			err.textContent = res.ok ? 'ذخیره شد ✓' : res.message;
			err.classList.toggle('is-ok', !!res.ok);
		}
	});
	root.addEventListener('change', (e) => {
		if (e.target.name === 'findable') e.target.form.requestSubmit();
	});
	// Light poll while the page is visible (status + new requests/invites).
	setInterval(async () => {
		if (document.hidden || root.querySelector(':focus') || document.querySelector('dialog[open]')) return;
		const r = await load();
		if (r.ok) {
			panel(root);
			showInvites(r.invites);
		}
	}, 30000);
}

export function initFriends() {
	window.kmprFriends = {
		get available() {
			return ok();
		},
		invite: (game, code) => pick(game, code),
		list: () => (ok() ? load() : Promise.resolve({ ok: false })),
		open: () => (C.user ? (window.location.href = C.page + '#friends') : openAuth('login', 'برای دوست شدن وارد شو.')),
	};
	window.addEventListener('kmpr:friends-invite', (e) => pick(e.detail?.game, e.detail?.code));
	if (!ok()) return;
	window.addEventListener('kmpr:presence', (e) => {
		const d = e.detail || {};
		showInvites(d.invites);
	});
	try {
		if (sessionStorage.getItem('ka_inviting') && new URLSearchParams(window.location.search).get('create')) {
			sessionStorage.removeItem('ka_inviting');
			toast('اتاق ساخته می‌شود و دعوت خودکار برای دوستت فرستاده می‌شود؛ همین‌جا منتظرش بمان.', [], 6000);
		}
	} catch (e) {
		/* ignore */
	}
	const root = document.querySelector('[data-friends-root]');
	if (root) mountPanel(root);
}
