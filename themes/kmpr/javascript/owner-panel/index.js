/**
 * Owner panel SPA — shell, history router, nav badges, pull-to-refresh, PWA registration.
 */
import { h, icon, clear, sheet, toast, num, errorBox } from './ui.js';
import { api, boot } from './api.js';
import home from './screens/home.js';
import games from './screens/games.js';
import players from './screens/players.js';
import online from './screens/online.js';
import revenue from './screens/revenue.js';
import requests from './screens/requests.js';
import settings from './screens/settings.js';

const SCREENS = { home, games, players, online, revenue, requests, settings };
const NAV = [
	{ key: 'home', path: '', label: 'خانه', icon: 'home' },
	{ key: 'games', path: 'games/', label: 'بازی‌ها', icon: 'games' },
	{ key: 'players', path: 'players/', label: 'بازیکنان', icon: 'players' },
	{ key: 'online', path: 'online/', label: 'آنلاین', icon: 'online', badge: 'live' },
	{ key: 'revenue', path: 'revenue/', label: 'درآمد', icon: 'revenue' },
	{ key: 'requests', path: 'requests/', label: 'درخواست‌ها', icon: 'requests', badge: 'requests' },
	{ key: 'settings', path: 'settings/', label: 'تنظیمات', icon: 'settings' },
];
const TABS = ['home', 'games', 'players', 'online'];
const MORE = ['revenue', 'requests', 'settings'];

const state = { badges: boot.badges || {}, current: null, instance: null, key: null, pushedDetail: false, installEvt: null };
const app = document.getElementById('op-app');

/* ---------------- shell ---------------- */
const view = h('main', { id: 'op-view', class: 'op-view', tabindex: '-1' });
const title = h('h1', { class: 'op-top__title' }, '');
const refreshBtn = h('button', { type: 'button', class: 'op-iconbtn', 'aria-label': 'به‌روزرسانی', onClick: () => refresh() }, icon('refresh', 20));
const ptr = h('div', { class: 'op-ptr', 'aria-hidden': 'true' }, icon('refresh', 20));
const badgeEls = {};

function badge(key) {
	const el = h('span', { class: 'op-badge is-' + key, hidden: true });
	(badgeEls[key] ||= []).push(el);
	return el;
}

function navLink(item, cls) {
	return h('a', { href: boot.base + item.path, class: cls, 'data-nav': item.key, 'data-key': item.key }, icon(item.icon, 22), h('span', { class: cls + '__label' }, item.label), item.badge ? badge(item.badge) : null);
}

const side = h(
	'aside',
	{ class: 'op-side', 'aria-label': 'منوی پنل' },
	h('a', { href: boot.base, class: 'op-brand', 'data-nav': 'home' }, h('span', { class: 'op-brand__logo', 'aria-hidden': 'true' }, 'K'), h('span', null, h('strong', null, 'پنل مدیریت'), h('small', null, 'kmpr.ir'))),
	h('nav', { class: 'op-side__nav' }, NAV.map((n) => navLink(n, 'op-side__link'))),
	h(
		'div',
		{ class: 'op-side__foot' },
		installButton('op-side__link'),
		h('a', { class: 'op-side__link', href: boot.site, target: '_blank', rel: 'noopener' }, icon('external', 20), h('span', { class: 'op-side__link__label' }, 'مشاهده سایت')),
		h('a', { class: 'op-side__link', href: boot.admin }, icon('wp', 20), h('span', { class: 'op-side__link__label' }, 'پیشخوان وردپرس')),
		h('a', { class: 'op-side__link', href: boot.logout }, icon('logout', 20), h('span', { class: 'op-side__link__label' }, 'خروج')),
	),
);

const moreItem = { key: 'more', label: 'بیشتر', icon: 'more' };
const moreBtn = h('button', { type: 'button', class: 'op-tab', 'data-key': 'more', 'aria-haspopup': 'dialog', onClick: openMore }, icon('more', 22), h('span', { class: 'op-tab__label' }, moreItem.label), badge('requests'));
const tabs = h('nav', { class: 'op-tabs', 'aria-label': 'منوی اصلی' }, NAV.filter((n) => TABS.includes(n.key)).map((n) => navLink(n, 'op-tab')), moreBtn);

const top = h(
	'header',
	{ class: 'op-top' },
	h('a', { href: boot.base, class: 'op-top__brand', 'data-nav': 'home', 'aria-label': 'خانه پنل' }, h('span', { class: 'op-brand__logo', 'aria-hidden': 'true' }, 'K')),
	title,
	refreshBtn,
	h('img', { class: 'op-top__avatar', src: boot.avatar, alt: boot.user || 'مدیر', width: '32', height: '32' }),
);

clear(app);
app.removeAttribute('aria-busy');
app.append(h('a', { href: '#op-view', class: 'op-skip' }, 'رفتن به محتوا'), side, h('div', { class: 'op-main' }, top, ptr, view), tabs);

function installButton(cls) {
	const b = h('button', { type: 'button', class: cls + ' op-install', hidden: true, onClick: install }, icon('download', 20), h('span', { class: cls + '__label' }, 'نصب اپ پنل'));
	return b;
}
async function install() {
	if (!state.installEvt) return;
	state.installEvt.prompt();
	await state.installEvt.userChoice.catch(() => null);
	state.installEvt = null;
	document.querySelectorAll('.op-install').forEach((b) => (b.hidden = true));
}
window.addEventListener('beforeinstallprompt', (e) => {
	e.preventDefault();
	state.installEvt = e;
	document.querySelectorAll('.op-install').forEach((b) => (b.hidden = false));
});

function openMore() {
	const list = h(
		'div',
		{ class: 'op-more' },
		NAV.filter((n) => MORE.includes(n.key)).map((n) =>
			h('a', { href: boot.base + n.path, class: 'op-more__item', 'data-nav': n.key }, icon(n.icon, 22), h('span', null, n.label), n.badge && state.badges[n.badge] ? h('span', { class: 'op-badge' }, num(state.badges[n.badge])) : null),
		),
		h('hr', { class: 'op-sep' }),
		state.installEvt ? h('button', { type: 'button', class: 'op-more__item', onClick: install }, icon('download', 22), h('span', null, 'نصب اپ پنل روی گوشی')) : null,
		h('a', { href: boot.site, class: 'op-more__item', target: '_blank', rel: 'noopener' }, icon('external', 22), h('span', null, 'مشاهده سایت')),
		h('a', { href: boot.admin, class: 'op-more__item' }, icon('wp', 22), h('span', null, 'پیشخوان کامل وردپرس')),
		h('a', { href: boot.logout, class: 'op-more__item' }, icon('logout', 22), h('span', null, 'خروج')),
	);
	const s = sheet('بیشتر', list);
	list.addEventListener('click', (e) => {
		if (e.target.closest('[data-nav]')) s.close();
	});
}

export function setBadges(b) {
	if (!b) return;
	state.badges = { ...state.badges, ...b };
	for (const [k, els] of Object.entries(badgeEls)) {
		const n = Number(state.badges[k] || 0);
		for (const el of els) {
			el.hidden = !n;
			el.textContent = n > 99 ? '+۹۹' : num(n);
			el.setAttribute('aria-label', num(n) + ' مورد');
		}
	}
	const reqs = Number(state.badges.requests || 0);
	document.title = (reqs ? '(' + num(reqs) + ') ' : '') + 'پنل مدیریت';
}

/* ---------------- router ---------------- */
function parse(pathname) {
	let rel = pathname.startsWith(boot.base) ? pathname.slice(boot.base.length) : '';
	const seg = rel.split('/').filter(Boolean);
	const key = SCREENS[seg[0]] ? seg[0] : 'home';
	return { key, id: seg[1] ? decodeURIComponent(seg[1]) : null };
}

export function navigate(path, { replace = false, detail = false } = {}) {
	const url = boot.base + path.replace(/^\//, '');
	if (url === location.pathname + location.search && !replace) return;
	history[replace ? 'replaceState' : 'pushState']({ detail }, '', url);
	state.pushedDetail = detail;
	route();
}

/** Close a detail sheet: go back if we pushed it, otherwise replace the URL. */
export function closeDetail(listPath) {
	if (state.pushedDetail && history.state?.detail) {
		state.pushedDetail = false;
		history.back();
	} else if (parse(location.pathname).id) {
		history.replaceState({}, '', boot.base + listPath);
	}
}

async function route({ soft = false } = {}) {
	const { key, id } = parse(location.pathname);
	const meta = NAV.find((n) => n.key === key);
	for (const a of document.querySelectorAll('[data-key]')) {
		const k = a.getAttribute('data-key');
		const on = k === key || (k === 'more' && MORE.includes(key));
		a.classList.toggle('is-active', on);
		if (a.tagName === 'A') on ? a.setAttribute('aria-current', 'page') : a.removeAttribute('aria-current');
	}
	title.textContent = meta.label;
	document.documentElement.dataset.screen = key;

	// Same screen, new params (e.g. open/close a detail sheet) → let the screen handle it.
	if (!soft && state.key === key && state.instance?.params) {
		state.instance.params({ id });
		return;
	}
	if (state.instance?.dispose) state.instance.dispose();
	state.key = key;
	if (!soft) {
		clear(view);
		window.scrollTo(0, 0);
	}
	const ctx = { id, soft, setBadges, navigate, closeDetail, boot, api };
	try {
		const host = soft ? h('div') : view;
		state.instance = (await SCREENS[key].render(host, ctx)) || null;
		if (soft) {
			clear(view);
			while (host.firstChild) view.append(host.firstChild);
		}
	} catch (e) {
		clear(view).append(errorBox(e, () => route()));
	}
}

async function refresh() {
	refreshBtn.classList.add('is-spinning');
	try {
		await route({ soft: true });
		pollBadges();
	} finally {
		refreshBtn.classList.remove('is-spinning');
	}
}

document.addEventListener('click', (e) => {
	const a = e.target.closest('a[data-nav]');
	if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
	e.preventDefault();
	navigate(a.getAttribute('href').slice(boot.base.length));
});
window.addEventListener('popstate', () => {
	state.pushedDetail = !!history.state?.detail;
	route();
});

/* ---------------- badges polling ---------------- */
async function pollBadges() {
	if (document.hidden) return;
	try {
		setBadges(await api('badges'));
	} catch {
		/* offline: keep old */
	}
}
setInterval(pollBadges, 60000);
document.addEventListener('visibilitychange', () => !document.hidden && pollBadges());

/* ---------------- pull to refresh (touch) ---------------- */
(() => {
	let y0 = null;
	let dy = 0;
	window.addEventListener('touchstart', (e) => {
		if (window.scrollY > 0 || document.querySelector('dialog[open]') || e.touches.length > 1) return;
		y0 = e.touches[0].clientY;
		dy = 0;
	}, { passive: true });
	window.addEventListener('touchmove', (e) => {
		if (y0 === null) return;
		dy = e.touches[0].clientY - y0;
		if (dy <= 0 || window.scrollY > 0) {
			ptr.style.transform = '';
			return;
		}
		const d = Math.min(90, dy * 0.5);
		ptr.style.transform = `translate(-50%, ${d}px) rotate(${d * 4}deg)`;
		ptr.classList.toggle('is-ready', d >= 60);
	}, { passive: true });
	window.addEventListener('touchend', async () => {
		if (y0 === null) return;
		const go = Math.min(90, dy * 0.5) >= 60;
		y0 = null;
		ptr.classList.remove('is-ready');
		if (go) {
			ptr.classList.add('is-loading');
			await refresh();
			toast('به‌روز شد');
		}
		ptr.classList.remove('is-loading');
		ptr.style.transform = '';
	});
})();

/* ---------------- PWA ---------------- */
if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost')) {
	navigator.serviceWorker.register(boot.sw, { scope: boot.base }).catch(() => null);
}
window.addEventListener('offline', () => toast('اتصال اینترنت قطع شد', { tone: 'warn' }));

setBadges(state.badges);
route();
