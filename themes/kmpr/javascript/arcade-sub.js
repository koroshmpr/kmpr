/**
 * «پاس آرکید» subscription dialog (inc/arcade-subscription.php).
 * Opens from any [data-am-go="sub"] button, shows the member state (html.is-sub), places manual / gateway orders.
 */
(() => {
	const C = window.kmprSub;
	const d = document;
	const modal = d.querySelector('[data-sub-modal]');
	if (!C || !modal) return;
	const $ = (s) => modal.querySelector(s);
	const step = (name) => modal.querySelectorAll('[data-sub-step]').forEach((el) => (el.hidden = el.dataset.subStep !== name));
	const profile = () => {
		try {
			return JSON.parse(localStorage.getItem('kmpr_arcade_v1') || 'null') || {};
		} catch (e) {
			return {};
		}
	};
	const form = $('[data-sub-step="form"]');
	const say = (t, tone = '') => {
		const s = $('[data-sub-status]');
		s.textContent = t;
		s.dataset.tone = tone;
	};
	let me = { active: false };

	const apply = () => {
		d.documentElement.classList.toggle('is-sub', !!me.active);
		d.querySelectorAll('[data-am-go="sub"] span').forEach((s) => {
			if (!s.dataset.orig) s.dataset.orig = s.textContent;
			s.textContent = me.active ? 'پاس فعال · تمدید' : s.dataset.orig;
		});
	};
	const load = () => {
		const pid = profile().id;
		if (!pid) return Promise.resolve();
		return fetch(`${C.rest}/me?player_id=${encodeURIComponent(pid)}`, { credentials: 'same-origin' })
			.then((r) => r.json())
			.then((j) => {
				me = j || { active: false };
				apply();
			})
			.catch(() => {});
	};

	const open = (forceForm = false) => {
		if (typeof modal.showModal !== 'function') return;
		say('');
		if (me.active && !forceForm) {
			$('[data-sub-until]').textContent = me.until;
			$('[data-sub-days]').textContent = Number(me.days).toLocaleString('fa-IR');
			$('[data-sub-pid]').textContent = profile().id || '';
			step('active');
		} else {
			step('form');
			if (me.pending) say(`درخواست ${me.pending} در انتظار تأیید است؛ رسیدت را در تلگرام/واتساپ بفرست.`, 'ok');
			const p = profile();
			if (!form.nickname.value) form.nickname.value = p.nickname || '';
		}
		if (!modal.open) modal.showModal();
	};

	d.addEventListener('click', (e) => {
		const b = e.target.closest('[data-am-go="sub"]');
		if (b) {
			e.preventDefault();
			open();
		}
	});
	modal.addEventListener('click', (e) => {
		if (e.target === modal || e.target.closest('[data-sub-close]')) modal.close();
		if (e.target.closest('[data-sub-renew]')) open(true);
		if (e.target.closest('[data-sub-copypid]') && navigator.clipboard) {
			navigator.clipboard.writeText(profile().id || '').then(() => (e.target.closest('[data-sub-copypid]').textContent = 'کپی شد ✓'));
		}
		if (e.target.closest('[data-sub-restore]')) {
			const code = ($('[data-sub-restore-in]').value || '').trim().toLowerCase();
			if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/.test(code)) {
				say('کد بازیابی درست نیست؛ همان کدی را که در دستگاه قبلی دیدی کامل وارد کن.', 'warn');
				return;
			}
			fetch(`${C.rest}/me?player_id=${code}`, { credentials: 'same-origin' })
				.then((r) => r.json())
				.then((j) => {
					if (!j.active) return say('برای این کد اشتراک فعالی پیدا نشد.', 'warn');
					const p = profile();
					p.id = code;
					localStorage.setItem('kmpr_arcade_v1', JSON.stringify(p));
					location.reload();
				})
				.catch(() => say('ارتباط برقرار نشد؛ دوباره امتحان کن.', 'warn'));
		}
		if (e.target.closest('[data-sub-copy]')) {
			navigator.clipboard && navigator.clipboard.writeText(modal.dataset.msg || '').then(() => (e.target.closest('[data-sub-copy]').querySelector('span').textContent = 'کپی شد ✓'));
		}
	});

	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		const method = (e.submitter && e.submitter.value) || 'manual';
		const pid = profile().id;
		if (!pid) {
			say('اول یک دست بازی کن تا شناسه بازیکنت ساخته شود، بعد دوباره امتحان کن.', 'warn');
			return;
		}
		if (method === 'manual' && !form.contact.value.trim()) {
			say('برای تأیید دستی، شماره یا آیدی تلگرامت را بنویس.', 'warn');
			form.contact.focus();
			return;
		}
		const btns = form.querySelectorAll('button[type="submit"]');
		btns.forEach((b) => (b.disabled = true));
		say('در حال ثبت…');
		try {
			const r = await fetch(`${C.rest}/order`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				credentials: 'same-origin',
				body: JSON.stringify({ player_id: pid, plan: Number(form.plan.value), method, nickname: form.nickname.value.trim(), contact: form.contact.value.trim(), website: form.website.value }),
			});
			const j = await r.json();
			if (!r.ok || !j.ok) throw new Error(j.message || 'ثبت نشد؛ دوباره امتحان کن.');
			if (j.redirect) {
				say('در حال انتقال به درگاه…', 'ok');
				location.href = j.redirect;
				return;
			}
			modal.dataset.msg = j.msg || '';
			$('[data-sub-code]').textContent = j.code;
			const tg = $('[data-sub-tg]');
			const wa = $('[data-sub-wa]');
			tg.hidden = !j.tg;
			wa.hidden = !j.wa;
			if (j.tg) tg.href = j.tg;
			if (j.wa) wa.href = j.wa;
			$('[data-sub-cardbox]').hidden = !j.card;
			$('[data-sub-card]').textContent = j.card || '';
			$('[data-sub-cardn]').textContent = j.cardn || '';
			me.pending = j.code;
			step('manual');
			$('[data-sub-step="manual"] h2').focus();
		} catch (err) {
			say(err.message, 'warn');
		} finally {
			btns.forEach((b) => (b.disabled = false));
		}
	});

	// Renewal nudge: 3 days before expiry and after it ends (once a day, never mid-game).
	const nudge = () => {
		if (!(me.active && me.days <= 3) && !me.expired) return;
		const day = new Date().toISOString().slice(0, 10);
		try {
			if (localStorage.getItem('kmpr_sub_nudge') === day) return;
			localStorage.setItem('kmpr_sub_nudge', day);
		} catch (e) {
			return;
		}
		const b = d.createElement('div');
		b.className = 'sub-nudge';
		b.setAttribute('role', 'status');
		b.innerHTML = `<span>👑 ${me.active ? `پاس آرکیدت ${Number(me.days).toLocaleString('fa-IR')} روز دیگر تمام می‌شود.` : 'پاس آرکیدت تمام شده؛ نشان «مشترک ویژه» را از دست نده.'}</span><button type="button" data-sub-nudge-go>تمدید</button><button type="button" data-sub-nudge-x aria-label="بستن">✕</button>`;
		d.body.appendChild(b);
		b.addEventListener('click', (e) => {
			if (e.target.closest('[data-sub-nudge-go]')) open(true);
			if (e.target.closest('button')) b.remove();
		});
	};

	load().then(() => {
		if (!d.documentElement.classList.contains('game-focus')) nudge();
		const q = new URLSearchParams(location.search).get('sub');
		const ref = new URLSearchParams(location.search).get('ref');
		if (!q) return;
		history.replaceState(null, '', location.pathname + location.hash);
		if (q === 'ok') load(); // the callback has just activated it
		if (q === 'ok') {
			open();
			const el = $('[data-sub-ref]');
			if (ref && el) {
				el.textContent = `پرداخت موفق بود. کد پیگیری بانک: ${ref}`;
				el.hidden = false;
			}
		}
		else {
			open(true);
			say('پرداخت انجام نشد یا لغو شد. اگر مبلغ کم شده، کد پیگیری را برایم بفرست.', 'warn');
		}
	});
})();
