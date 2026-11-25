/**
 * Account UI: sign-in / sign-up / reset sheet (modal <dialog>) and the /account/ page.
 * Real <form>s with correct autocomplete attributes so password managers work; inputs are 16px (no iOS zoom).
 */
import { C, call, collect, readProfile, adopt, wipe, push, state } from './sync.js';

export const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
export const fa = (n) => Number(n || 0).toLocaleString('fa-IR');

const EYE = '<svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
const HONEY = '<div class="ka-hp" aria-hidden="true"><label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';

const pwField = (name, label, ac, extra = '') => `<label class="ka-f"><span class="ka-f__l">${label}</span>
	<span class="ka-pw"><input class="ka-in" type="password" name="${name}" autocomplete="${ac}" dir="ltr" required minlength="${ac === 'current-password' ? 1 : 8}" maxlength="128" ${extra}>
	<button type="button" class="ka-pw__eye" data-eye aria-label="نمایش رمز" aria-pressed="false">${EYE}</button></span></label>`;

function forms(note = '') {
	return `<div class="ka-auth" data-auth>
		${note ? `<p class="ka-note ka-note--info">${esc(note)}</p>` : ''}
		<div class="ka-tabs" role="tablist">
			<button type="button" role="tab" class="ka-tab" data-tab="login" aria-selected="true">ورود</button>
			<button type="button" role="tab" class="ka-tab" data-tab="register" aria-selected="false">ساخت حساب</button>
		</div>
		<form class="ka-form" data-form="login" novalidate>
			<label class="ka-f"><span class="ka-f__l">ایمیل، موبایل یا نام کاربری</span>
				<input class="ka-in" type="text" name="username" autocomplete="username" dir="ltr" inputmode="email" autocapitalize="off" spellcheck="false" required maxlength="100"></label>
			${pwField('password', 'رمز عبور', 'current-password')}
			<label class="ka-check"><input type="checkbox" name="remember" checked> <span>مرا روی این دستگاه به خاطر بسپار</span></label>
			${HONEY}
			<p class="ka-err" data-err role="alert"></p>
			<button type="submit" class="ka-btn">ورود</button>
			<button type="button" class="ka-link" data-show="reset">رمز را فراموش کرده‌ام</button>
		</form>
		<form class="ka-form" data-form="register" hidden novalidate>
			<label class="ka-f"><span class="ka-f__l">نام نمایشی (در جدول برترین‌ها دیده می‌شود)</span>
				<input class="ka-in" type="text" name="name" autocomplete="nickname" required minlength="2" maxlength="24" placeholder="مثلاً: شاهین شب"></label>
			<label class="ka-f"><span class="ka-f__l">ایمیل، شماره موبایل یا نام کاربری</span>
				<input class="ka-in" type="text" name="username" autocomplete="username" dir="ltr" autocapitalize="off" spellcheck="false" required maxlength="100" data-ident></label>
			<label class="ka-f" data-email-wrap hidden><span class="ka-f__l">ایمیل برای بازیابی رمز (اختیاری)</span>
				<input class="ka-in" type="email" name="email" autocomplete="email" dir="ltr" maxlength="100"></label>
			${pwField('password', 'رمز عبور (دست‌کم ۸ کاراکتر)', 'new-password', 'data-strength')}
			<div class="ka-meter" aria-hidden="true"><span data-meter></span></div>
			${HONEY}
			<p class="ka-err" data-err role="alert"></p>
			<button type="submit" class="ka-btn">ساخت حساب</button>
			<p class="ka-fine">امتیازها و پیشرفت فعلی همین دستگاه به حسابت منتقل می‌شود.</p>
		</form>
		<form class="ka-form" data-form="reset" hidden novalidate>
			<p class="ka-fine">ایمیل، موبایل یا نام کاربری‌ات را بنویس؛ اگر برای حسابت ایمیل ثبت شده باشد، لینک تغییر رمز برایت فرستاده می‌شود.</p>
			<label class="ka-f"><span class="ka-f__l">ایمیل، موبایل یا نام کاربری</span>
				<input class="ka-in" type="text" name="username" autocomplete="username" dir="ltr" autocapitalize="off" required maxlength="100"></label>
			${HONEY}
			<p class="ka-err" data-err role="alert"></p>
			<button type="submit" class="ka-btn">فرستادن لینک</button>
			<button type="button" class="ka-link" data-show="login">بازگشت به ورود</button>
		</form>
	</div>`;
}

function score(pw) {
	let s = 0;
	if (pw.length >= 8) s++;
	if (pw.length >= 12) s++;
	if (/[a-z]/i.test(pw) && /\d/.test(pw)) s++;
	if (/[^a-z0-9]/i.test(pw)) s++;
	return s;
}

function busy(form, on) {
	const b = form.querySelector('[type=submit]');
	if (b) {
		b.disabled = on;
		b.classList.toggle('is-busy', on);
	}
}

/** Wire an auth block (modal or page). */
function wireAuth(root) {
	const show = (name) => {
		root.querySelectorAll('[data-form]').forEach((f) => (f.hidden = f.dataset.form !== name));
		root.querySelectorAll('[data-tab]').forEach((t) => t.setAttribute('aria-selected', String(t.dataset.tab === name)));
		const f = root.querySelector(`[data-form="${name}"] input:not([type=checkbox]):not([name=website])`);
		if (f && window.matchMedia('(pointer: fine)').matches) f.focus();
	};
	root.addEventListener('click', (e) => {
		const t = e.target.closest('[data-tab],[data-show]');
		if (t) show(t.dataset.tab || t.dataset.show);
		const eye = e.target.closest('[data-eye]');
		if (eye) {
			const inp = eye.parentElement.querySelector('input');
			const on = inp.type === 'password';
			inp.type = on ? 'text' : 'password';
			eye.setAttribute('aria-pressed', String(on));
		}
	});
	const ident = root.querySelector('[data-ident]');
	ident?.addEventListener('input', () => {
		root.querySelector('[data-email-wrap]').hidden = ident.value.includes('@') || ident.value.trim() === '';
	});
	root.querySelector('[data-strength]')?.addEventListener('input', (e) => {
		const m = root.querySelector('[data-meter]');
		m.style.width = `${(score(e.target.value) / 4) * 100}%`;
		m.dataset.s = String(score(e.target.value));
	});

	root.querySelectorAll('[data-form]').forEach((form) =>
		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const kind = form.dataset.form;
			const v = (n) => (form.elements[n] ? form.elements[n].value : '');
			const err = form.querySelector('[data-err]');
			err.textContent = '';
			err.classList.remove('is-ok');
			if (kind !== 'reset' && (!v('username').trim() || !v('password'))) {
				err.textContent = 'همه بخش‌ها را پر کن.';
				return;
			}
			busy(form, true);
			const p = readProfile();
			let r;
			if (kind === 'login') r = await call('/login', { login: v('username'), password: v('password'), remember: form.elements.remember.checked, website: v('website'), pid: p.id, data: collect() });
			else if (kind === 'register') r = await call('/register', { name: v('name'), login: v('username'), email: v('email'), password: v('password'), website: v('website'), remember: true, pid: p.id, data: collect() });
			else r = await call('/reset/request', { login: v('username'), website: v('website') });
			busy(form, false);
			if (!r.ok) {
				err.textContent = r.message || 'خطایی رخ داد؛ دوباره تلاش کن.';
				return;
			}
			if (kind === 'reset') {
				err.textContent = r.message;
				err.classList.add('is-ok');
				return;
			}
			if (r.user) {
				adopt(r.user, r.pid, r.data);
				err.textContent = kind === 'register' ? 'حسابت ساخته شد ✓' : 'خوش آمدی ✓';
				err.classList.add('is-ok');
				setTimeout(() => window.location.reload(), 350); // fresh nonce + server-rendered header
			} else {
				err.textContent = 'ثبت شد.';
			}
		})
	);
	return { show };
}

/* ---------- modal sheet ---------- */
let dlg = null;
export function openAuth(tab = 'login', note = '') {
	if (C.user) {
		window.location.href = C.page;
		return;
	}
	dlg?.remove();
	dlg = document.createElement('dialog');
	dlg.className = 'ka-dlg';
	dlg.setAttribute('aria-label', 'ورود یا ساخت حساب');
	dlg.innerHTML = `<div class="ka-sheet">
		<div class="ka-sheet__hd"><span class="ka-sheet__grab" aria-hidden="true"></span>
			<p class="ka-sheet__t">حساب کاربری</p>
			<button type="button" class="ka-x" data-close aria-label="بستن"><svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div>
		<p class="ka-sheet__x">با یک حساب، امتیازها، پیشرفت و اشتراکت روی همه دستگاه‌ها یکی می‌شود.</p>
		${forms(note)}
	</div>`;
	document.body.appendChild(dlg);
	const { show } = wireAuth(dlg);
	dlg.addEventListener('click', (e) => {
		if (e.target === dlg || e.target.closest('[data-close]')) dlg.close();
	});
	dlg.addEventListener('close', () => setTimeout(() => dlg && !dlg.open && dlg.remove(), 200));
	dlg.showModal();
	show(tab);
}

/* ---------- /account/ page ---------- */
function resetConfirm(root, key, uid) {
	root.innerHTML = `<form class="ka-card ka-form" data-reset novalidate>
		<h2 class="ka-h2">انتخاب رمز تازه</h2>
		<input type="text" name="username" autocomplete="username" hidden>
		${pwField('password', 'رمز تازه (دست‌کم ۸ کاراکتر)', 'new-password')}
		<p class="ka-err" data-err role="alert"></p>
		<button type="submit" class="ka-btn">ذخیره رمز</button>
	</form>`;
	const form = root.querySelector('form');
	form.addEventListener('click', (e) => {
		const eye = e.target.closest('[data-eye]');
		if (eye) {
			const inp = eye.parentElement.querySelector('input');
			inp.type = inp.type === 'password' ? 'text' : 'password';
		}
	});
	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		busy(form, true);
		const r = await call('/reset/confirm', { key, u: uid, password: form.elements.password.value });
		busy(form, false);
		const err = form.querySelector('[data-err]');
		err.textContent = r.ok ? 'رمزت عوض شد ✓ حالا وارد شو.' : r.message;
		err.classList.toggle('is-ok', !!r.ok);
		if (r.ok) {
			setTimeout(() => {
				history.replaceState(null, '', C.page);
				renderPage(root);
			}, 1200);
		}
	});
}

function gamesSummary() {
	const g = readProfile().games || {};
	const list = Object.entries(g).filter(([, x]) => x && x.best > 0).sort((a, b) => (b[1].at || 0) - (a[1].at || 0));
	const ach = Object.values(g).reduce((n, x) => n + ((x && x.ach) || []).length, 0);
	return { count: list.length, ach, plays: Object.values(g).reduce((n, x) => n + ((x && x.plays) || 0), 0) };
}

function syncLine() {
	if (state.error) return `<span class="ka-dot is-bad"></span> همگام‌سازی ناموفق: ${esc(state.error)}`;
	if (!state.last) return '<span class="ka-dot is-ok"></span> داده‌های این دستگاه با حسابت یکی است.';
	return `<span class="ka-dot is-ok"></span> همگام شد — ${new Date(state.last).toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' })}`;
}

export function renderPage(root) {
	const q = new URLSearchParams(window.location.search);
	if (q.get('reset') && q.get('u') && !C.user) return resetConfirm(root, q.get('reset'), q.get('u'));
	if (!C.user) {
		const add = q.get('add');
		root.innerHTML = `<div class="ka-card">${forms(add ? 'برای دوست شدن با کسی که لینک دعوتش را فرستاده، وارد شو یا حساب بساز.' : '')}</div>`;
		wireAuth(root).show(q.get('tab') === 'register' || add ? 'register' : 'login');
		return;
	}
	if (!C.visitor) {
		root.innerHTML = '<div class="ka-card"><p class="ka-note">با حساب مدیریت وارد شده‌ای. حساب‌های بازدیدکننده جدا هستند و مدیر از پیشخوان وردپرس استفاده می‌کند.</p></div>';
		return;
	}
	const u = C.user;
	const s = gamesSummary();
	root.innerHTML = `
	<div class="ka-grid">
		<div class="ka-col">
			<section class="ka-card ka-me" aria-labelledby="ka-me-t">
				<span class="ka-av" aria-hidden="true">${esc(u.initials)}</span>
				<div class="ka-me__b"><h2 id="ka-me-t" class="ka-h2">${esc(u.name)}</h2>
					<p class="ka-fine" dir="ltr">${u.handle ? '@' + esc(u.handle) : ''}</p></div>
				<button type="button" class="ka-btn ka-btn--ghost" data-logout>خروج</button>
			</section>
			<section class="ka-card" aria-labelledby="ka-sync-t">
				<h2 id="ka-sync-t" class="ka-h2">داده‌های من روی همه دستگاه‌ها</h2>
				<ul class="ka-stats">
					<li><b>${fa(s.count)}</b><span>بازی با رکورد</span></li>
					<li><b>${fa(s.plays)}</b><span>دست بازی</span></li>
					<li><b>${fa(s.ach)}</b><span>مدال</span></li>
				</ul>
				<p class="ka-fine" data-sync-line>${syncLine()}</p>
				<button type="button" class="ka-btn ka-btn--ghost" data-sync>همگام‌سازی حالا</button>
				<a class="ka-btn" href="${esc(C.games)}">رفتن به گیم‌سنتر</a>
			</section>
			<section class="ka-card" aria-labelledby="ka-prof-t">
				<h2 id="ka-prof-t" class="ka-h2">مشخصات</h2>
				<form class="ka-form" data-profile novalidate>
					<label class="ka-f"><span class="ka-f__l">نام نمایشی</span><input class="ka-in" name="name" autocomplete="nickname" value="${esc(u.name)}" required minlength="2" maxlength="24"></label>
					<p class="ka-err" data-err role="alert"></p>
					<button type="submit" class="ka-btn">ذخیره</button>
				</form>
				<details class="ka-more"><summary>${u.hasEmail ? 'تغییر ایمیل بازیابی' : 'افزودن ایمیل برای بازیابی رمز'}</summary>
					<form class="ka-form" data-email novalidate>
						<input type="text" name="username" autocomplete="username" value="${esc(u.login)}" hidden>
						<label class="ka-f"><span class="ka-f__l">ایمیل</span><input class="ka-in" type="email" name="email" autocomplete="email" dir="ltr" maxlength="100"></label>
						${pwField('password', 'رمز فعلی', 'current-password')}
						<p class="ka-err" data-err role="alert"></p>
						<button type="submit" class="ka-btn">ذخیره ایمیل</button>
					</form>
				</details>
			</section>
			<section class="ka-card" aria-labelledby="ka-sec-t">
				<h2 id="ka-sec-t" class="ka-h2">امنیت</h2>
				<form class="ka-form" data-password novalidate>
					<input type="text" name="username" autocomplete="username" value="${esc(u.login)}" hidden>
					${pwField('current', 'رمز فعلی', 'current-password')}
					${pwField('password', 'رمز تازه', 'new-password')}
					<p class="ka-err" data-err role="alert"></p>
					<button type="submit" class="ka-btn">تغییر رمز</button>
					<p class="ka-fine">با تغییر رمز، از همه دستگاه‌های دیگر خارج می‌شوی.</p>
				</form>
			</section>
		</div>
		<div class="ka-col" id="friends" data-friends-root></div>
	</div>`;

	root.addEventListener('click', async (e) => {
		const eye = e.target.closest('[data-eye]');
		if (eye) {
			const inp = eye.parentElement.querySelector('input');
			inp.type = inp.type === 'password' ? 'text' : 'password';
		}
		if (e.target.closest('[data-logout]')) {
			await push(); // last changes go up before this device forgets them
			await call('/logout', {});
			wipe();
			window.location.href = C.page;
		}
		if (e.target.closest('[data-sync]')) {
			await push('', true);
			const l = root.querySelector('[data-sync-line]');
			if (l) l.innerHTML = syncLine();
		}
	});
	window.addEventListener('kmpr:acct-synced', () => {
		const l = root.querySelector('[data-sync-line]');
		if (l) l.innerHTML = syncLine();
	});
	const submit = (sel, fn) => {
		const form = root.querySelector(sel);
		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			const err = form.querySelector('[data-err]');
			busy(form, true);
			const r = await fn(form);
			busy(form, false);
			err.textContent = r.ok ? 'ذخیره شد ✓' : r.message || 'خطا';
			err.classList.toggle('is-ok', !!r.ok);
			if (r.ok && r.reload) setTimeout(() => window.location.reload(), 600);
		});
	};
	submit('[data-profile]', async (f) => ({ ...(await call('/profile', { name: f.elements.name.value })), reload: true }));
	submit('[data-email]', (f) => call('/profile', { email: f.elements.email.value, password: f.elements.password.value }));
	submit('[data-password]', async (f) => ({ ...(await call('/password', { current: f.elements.current.value, password: f.elements.password.value })), reload: true }));
}
