/**
 * Owner panel — tiny DOM/UI kit (no framework). All dynamic text goes through textContent.
 */

const SVG_NS = 'http://www.w3.org/2000/svg';

/** Hyperscript: h('div', { class, onClick, 'aria-label' }, ...children) */
export function h(tag, attrs, ...kids) {
	const el = document.createElement(tag);
	if (attrs) {
		for (const [k, v] of Object.entries(attrs)) {
			if (v === null || v === undefined || v === false) continue;
			if (k.startsWith('on') && typeof v === 'function') el.addEventListener(k.slice(2).toLowerCase(), v);
			else if (k === 'class') el.className = v;
			else if (k === 'style' && typeof v === 'object') for (const [sk, sv] of Object.entries(v)) sk.startsWith('--') ? el.style.setProperty(sk, sv) : (el.style[sk] = sv);
			else if (k === 'value') el.value = v;
			else if (k === 'checked' || k === 'disabled' || k === 'selected') el[k] = !!v;
			else el.setAttribute(k, v === true ? '' : String(v));
		}
	}
	append(el, kids);
	return el;
}

export function append(el, kids) {
	for (const k of kids.flat(Infinity)) {
		if (k === null || k === undefined || k === false || k === '') continue;
		el.append(k instanceof Node ? k : document.createTextNode(String(k)));
	}
	return el;
}

/** Replace all children; null/false/'' are skipped (unlike replaceChildren). */
export function fill(el, ...kids) {
	clear(el);
	return append(el, kids);
}

export function clear(el) {
	while (el.firstChild) el.firstChild.remove();
	return el;
}

/* ---------- icons (constant path data, rendered via createElementNS) ---------- */
const ICONS = {
	home: 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
	games: 'M6 8h12a4 4 0 0 1 4 4v2a4 4 0 0 1-7 2.6L14 15h-4l-1 1.6A4 4 0 0 1 2 14v-2a4 4 0 0 1 4-4zM8 10v4M6 12h4M15.5 11.5h.01M18 13.5h.01',
	players: 'M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 20v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8',
	online: 'M5 12.6a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 19.5h.01M1.5 9a15 15 0 0 1 21 0',
	revenue: 'M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
	requests: 'M4 4h16v12H5.2L4 17.2zM8 9h8M8 12h5',
	settings: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z',
	more: 'M5 12h.01M12 12h.01M19 12h.01',
	refresh: 'M21 12a9 9 0 1 1-2.6-6.4M21 3v6h-6',
	close: 'M18 6 6 18M6 6l12 12',
	search: 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3',
	external: 'M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
	phone: 'M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z',
	send: 'M22 2 11 13M22 2l-7 20-4-9-9-4z',
	chat: 'M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.1-5.6A8.4 8.4 0 1 1 21 11.5z',
	mail: 'M4 4h16v16H4zM4 6l8 7 8-7',
	copy: 'M9 9h11v11H9zM5 15H4V4h11v1',
	trash: 'M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6',
	edit: 'M12 20h9M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4z',
	check: 'M20 6 9 17l-5-5',
	logout: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
	wp: 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM3.5 8.5l4.6 12.4M20.5 8.5 16 20.9M7 8.5h5M14 8.5h4M9 8.5l4.5 12M15 8.5l-2.4 7',
	trophy: 'M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3',
	broom: 'M19 3 9.5 12.5M7 11l6 6-2 4-9-9 4-2zM5.5 16.5 3 19',
	download: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3',
	upload: 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12',
	chevron: 'M15 18l-6-6 6-6',
	eye: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
	alert: 'M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
	spark: 'M13 2 3 14h9l-1 8 10-12h-9z',
	plus: 'M12 5v14M5 12h14',
};

export function icon(name, size = 20, cls = '') {
	const svg = document.createElementNS(SVG_NS, 'svg');
	svg.setAttribute('width', size);
	svg.setAttribute('height', size);
	svg.setAttribute('viewBox', '0 0 24 24');
	svg.setAttribute('fill', 'none');
	svg.setAttribute('stroke', 'currentColor');
	svg.setAttribute('stroke-width', '2');
	svg.setAttribute('stroke-linecap', 'round');
	svg.setAttribute('stroke-linejoin', 'round');
	svg.setAttribute('aria-hidden', 'true');
	svg.setAttribute('focusable', 'false');
	svg.setAttribute('class', ('op-icon shrink-0 ' + cls).trim());
	const p = document.createElementNS(SVG_NS, 'path');
	p.setAttribute('d', ICONS[name] || ICONS.more);
	svg.append(p);
	return svg;
}

export function svg(tag, attrs, ...kids) {
	const el = document.createElementNS(SVG_NS, tag);
	for (const [k, v] of Object.entries(attrs || {})) if (v !== null && v !== undefined) el.setAttribute(k, v);
	for (const k of kids.flat()) if (k) el.append(k instanceof Node ? k : document.createTextNode(String(k)));
	return el;
}

/* ---------- formatting (Persian digits) ---------- */
const nf = new Intl.NumberFormat('fa-IR');
export const num = (n) => nf.format(Number(n) || 0);
export const fa = (s) => String(s ?? '').replace(/[0-9]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
export const latin = (s) => String(s ?? '').replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
export function pct(a, b, digits = 1) {
	if (!b) return '—';
	return new Intl.NumberFormat('fa-IR', { maximumFractionDigits: digits }).format((a / b) * 100) + '٪';
}
export function bytes(n) {
	n = Number(n) || 0;
	const u = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];
	let i = 0;
	while (n >= 1024 && i < u.length - 1) {
		n /= 1024;
		i++;
	}
	return new Intl.NumberFormat('fa-IR', { maximumFractionDigits: i ? 1 : 0 }).format(n) + ' ' + u[i];
}
/** Relative time from a unix timestamp (seconds). */
export function ago(ts, now = Date.now() / 1000) {
	if (!ts) return '—';
	const d = Math.max(0, Math.round(now - ts));
	if (d < 45) return 'همین الان';
	if (d < 3600) return num(Math.round(d / 60)) + ' دقیقه پیش';
	if (d < 86400) return num(Math.round(d / 3600)) + ' ساعت پیش';
	if (d < 86400 * 30) return num(Math.round(d / 86400)) + ' روز پیش';
	if (d < 86400 * 365) return num(Math.round(d / (86400 * 30))) + ' ماه پیش';
	return num(Math.round(d / (86400 * 365))) + ' سال پیش';
}
export function duration(sec) {
	sec = Math.max(0, Math.round(sec));
	if (sec < 60) return num(sec) + ' ثانیه';
	if (sec < 3600) return num(Math.floor(sec / 60)) + ' دقیقه';
	return num(Math.floor(sec / 3600)) + ' ساعت و ' + num(Math.floor((sec % 3600) / 60)) + ' دقیقه';
}

/* ---------- Jalali ⇄ Gregorian (sponsor dates are stored as Gregorian Ymd by ACF) ---------- */
function div(a, b) {
	return ~~(a / b);
}
export function g2j(gy, gm, gd) {
	const gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
	let gy2 = gm > 2 ? gy + 1 : gy;
	let days = 355666 + 365 * gy + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) + gd + gdm[gm - 1];
	let jy = -1595 + 33 * div(days, 12053);
	days %= 12053;
	jy += 4 * div(days, 1461);
	days %= 1461;
	if (days > 365) {
		jy += div(days - 1, 365);
		days = (days - 1) % 365;
	}
	const jm = days < 186 ? 1 + div(days, 31) : 7 + div(days - 186, 30);
	const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
	return [jy, jm, jd];
}
export function j2g(jy, jm, jd) {
	jy += 1595;
	let days = -355668 + 365 * jy + div(jy, 33) * 8 + div((jy % 33) + 3, 4) + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
	let gy = 400 * div(days, 146097);
	days %= 146097;
	if (days > 36524) {
		gy += 100 * div(--days, 36524);
		days %= 36524;
		if (days >= 365) days++;
	}
	gy += 4 * div(days, 1461);
	days %= 1461;
	if (days > 365) {
		gy += div(days - 1, 365);
		days = (days - 1) % 365;
	}
	let gd = days + 1;
	const sal = [0, 31, (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0 ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
	let gm = 0;
	for (gm = 0; gm < 13 && gd > sal[gm]; gm++) gd -= sal[gm];
	return [gy, gm, gd];
}
/** "20261108" → "۱۴۰۵/۰۸/۱۷" */
export function ymdToJalali(ymd) {
	const m = /^(\d{4})(\d{2})(\d{2})$/.exec(String(ymd || ''));
	if (!m) return '';
	const [y, mo, d] = g2j(+m[1], +m[2], +m[3]);
	return fa(`${y}/${String(mo).padStart(2, '0')}/${String(d).padStart(2, '0')}`);
}
/** "۱۴۰۵/۸/۱۷" → "20261108", '' for empty, null for invalid. */
export function jalaliToYmd(s) {
	s = latin(s).trim();
	if (!s) return '';
	const m = /^(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})$/.exec(s);
	if (!m) return null;
	const jy = +m[1], jm = +m[2], jd = +m[3];
	if (jm < 1 || jm > 12 || jd < 1 || jd > (jm <= 6 ? 31 : 30)) return null;
	const [gy, gm, gd] = j2g(jy, jm, jd);
	return `${gy}${String(gm).padStart(2, '0')}${String(gd).padStart(2, '0')}`;
}

/* ---------- toasts (with optional undo) ---------- */
let toastHost;
export function toast(message, { tone = 'ok', undo = null, ms = 4500 } = {}) {
	if (!toastHost) {
		toastHost = h('div', { class: 'op-toasts', role: 'status', 'aria-live': 'polite' });
		document.body.append(toastHost);
	}
	const t = h('div', { class: 'op-toast is-' + tone }, h('span', { class: 'op-toast__msg' }, message));
	let done = false;
	const close = () => {
		if (done) return;
		done = true;
		t.classList.add('is-out');
		setTimeout(() => t.remove(), 220);
	};
	if (undo) {
		t.append(
			h('button', {
				type: 'button',
				class: 'op-toast__undo',
				onClick: () => {
					close();
					undo();
				},
			}, 'برگردان'),
		);
	}
	toastHost.append(t);
	setTimeout(close, undo ? ms + 1500 : ms);
	return close;
}

/* ---------- sheet / dialog (native <dialog>: focus trap + Esc) ---------- */
export function sheet(title, body, { onClose, wide = false } = {}) {
	const closeBtn = h('button', { type: 'button', class: 'op-iconbtn', 'aria-label': 'بستن' }, icon('close', 20));
	const head = h('header', { class: 'op-sheet__head' }, h('span', { class: 'op-sheet__grip', 'aria-hidden': 'true' }), h('h2', { class: 'op-sheet__title' }, title), closeBtn);
	const content = h('div', { class: 'op-sheet__body' }, body);
	const dlg = h('dialog', { class: 'op-sheet' + (wide ? ' is-wide' : ''), 'aria-label': title }, head, content);
	document.body.append(dlg);
	let closed = false;
	const close = () => {
		if (closed) return;
		closed = true;
		dlg.classList.add('is-out');
		setTimeout(() => {
			if (dlg.open) dlg.close();
			dlg.remove();
		}, 180);
		onClose && onClose();
	};
	closeBtn.addEventListener('click', close);
	dlg.addEventListener('cancel', (e) => {
		e.preventDefault();
		close();
	});
	dlg.addEventListener('click', (e) => {
		if (e.target === dlg) close(); // backdrop
	});
	// Swipe down on the header closes the sheet (mobile).
	let y0 = null;
	head.addEventListener('touchstart', (e) => (y0 = e.touches[0].clientY), { passive: true });
	head.addEventListener('touchmove', (e) => {
		if (y0 === null) return;
		const dy = e.touches[0].clientY - y0;
		if (dy > 0) dlg.style.transform = `translateY(${dy}px)`;
	}, { passive: true });
	head.addEventListener('touchend', (e) => {
		const dy = y0 === null ? 0 : e.changedTouches[0].clientY - y0;
		y0 = null;
		dlg.style.transform = '';
		if (dy > 90) close();
	});
	dlg.showModal();
	return { close, body: content, el: dlg, setTitle: (t) => (head.querySelector('h2').textContent = t) };
}

/** Confirm dialog → Promise<boolean|object>. `extra` may add form controls; their values come back via collect(). */
export function confirmBox(title, text, { ok = 'تأیید', danger = false, extra = null, collect = null } = {}) {
	return new Promise((resolve) => {
		let result = false;
		const okBtn = h('button', { type: 'button', class: 'op-btn ' + (danger ? 'is-danger' : 'is-primary') }, ok);
		const cancel = h('button', { type: 'button', class: 'op-btn is-ghost' }, 'انصراف');
		const s = sheet(title, [h('p', { class: 'op-muted op-confirm__text' }, text), extra, h('div', { class: 'op-actions' }, okBtn, cancel)], {
			onClose: () => resolve(result),
		});
		okBtn.addEventListener('click', () => {
			result = collect ? collect() || true : true;
			s.close();
		});
		cancel.addEventListener('click', () => s.close());
		setTimeout(() => (danger ? cancel : okBtn).focus(), 30);
	});
}

/* ---------- small widgets ---------- */
export function sw(checked, label, onChange, { disabled = false } = {}) {
	const b = h('button', { type: 'button', role: 'switch', class: 'op-switch', 'aria-checked': checked ? 'true' : 'false', 'aria-label': label, disabled });
	b.addEventListener('click', () => {
		const next = b.getAttribute('aria-checked') !== 'true';
		b.setAttribute('aria-checked', next ? 'true' : 'false');
		onChange(next, (v) => b.setAttribute('aria-checked', v ? 'true' : 'false'));
	});
	return b;
}

export function chips(options, value, onPick, label) {
	const wrap = h('div', { class: 'op-chips', role: 'radiogroup', 'aria-label': label });
	const draw = (v) => {
		clear(wrap);
		for (const o of options) {
			wrap.append(
				h('button', {
					type: 'button',
					role: 'radio',
					class: 'op-chip' + (o.value === v ? ' is-on' : ''),
					'aria-checked': o.value === v ? 'true' : 'false',
					onClick: () => {
						draw(o.value);
						onPick(o.value);
					},
				}, o.label, o.count !== undefined ? h('span', { class: 'op-chip__n' }, num(o.count)) : null),
			);
		}
	};
	draw(value);
	return wrap;
}

export function skeleton(rows = 4, cls = '') {
	return h('div', { class: 'op-skel ' + cls, 'aria-hidden': 'true' }, Array.from({ length: rows }, () => h('div', { class: 'op-skel__row' })));
}

export function empty(text, sub = '') {
	return h('div', { class: 'op-empty' }, h('p', null, text), sub ? h('p', { class: 'op-muted' }, sub) : null);
}

export function errorBox(err, retry) {
	return h('div', { class: 'op-error', role: 'alert' }, icon('alert', 20), h('p', null, err?.message || 'خطا در دریافت اطلاعات.'), retry ? h('button', { type: 'button', class: 'op-btn is-ghost', onClick: retry }, 'تلاش دوباره') : null);
}

/** Generated avatar from the games module's {style, hue}. */
export function avatar(av, name, size = 40) {
	const hue = Number(av?.hue ?? 32);
	const letter = (String(name || '').trim()[0] || '؟').toUpperCase();
	return h('span', {
		class: 'op-avatar op-avatar--' + (av?.style || 'blocks'),
		style: { width: size + 'px', height: size + 'px', background: `linear-gradient(135deg, hsl(${hue} 70% 52%), hsl(${(hue + 40) % 360} 65% 36%))` },
		'aria-hidden': 'true',
	}, letter);
}

export function field(label, control, hint = '') {
	const id = control.id || 'f' + Math.random().toString(36).slice(2, 8);
	control.id = id;
	return h('div', { class: 'op-field' }, h('label', { for: id, class: 'op-field__label' }, label), control, hint ? h('p', { class: 'op-field__hint' }, hint) : null);
}

export function linkBtn(href, label, ic, cls = 'op-btn is-ghost', ext = true) {
	return h('a', { href, class: cls, target: ext ? '_blank' : null, rel: ext ? 'noopener' : null }, ic ? icon(ic, 18) : null, h('span', null, label));
}

export async function copyText(text) {
	try {
		await navigator.clipboard.writeText(text);
		toast('کپی شد');
	} catch {
		const ta = h('textarea', { style: { position: 'fixed', opacity: '0' } }, text);
		document.body.append(ta);
		ta.select();
		document.execCommand('copy');
		ta.remove();
		toast('کپی شد');
	}
}

export function debounce(fn, ms) {
	let t;
	return (...a) => {
		clearTimeout(t);
		t = setTimeout(() => fn(...a), ms);
	};
}
