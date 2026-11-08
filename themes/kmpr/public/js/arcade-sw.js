/* KMPR Arcade service worker — scope /games/ only. Config (self.__ARCADE) is injected by inc/games-pwa.php. */
/* eslint-disable no-restricted-globals */
const CFG = self.__ARCADE || { version: 'dev', scope: '/games/', offline: '/games/offline/', precache: [], theme: '', uploads: '' };
const SHELL = `arcade-shell-${CFG.version}`;
const PAGES = 'arcade-pages-v1';
const ASSETS = 'arcade-assets-v1';
const MAX_ASSETS = 60;
const MAX_PAGES = 30;
const NAV_TIMEOUT = 3500;

self.addEventListener('install', (event) => {
	event.waitUntil(
		caches.open(SHELL).then((cache) =>
			// Individually, so one missing file never breaks the install.
			Promise.all(CFG.precache.map((url) => cache.add(new Request(url, { cache: 'reload' })).catch(() => null)))
		)
	);
});

self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches
			.keys()
			.then((keys) => Promise.all(keys.filter((k) => k.startsWith('arcade-shell-') && k !== SHELL).map((k) => caches.delete(k))))
			.then(() => self.clients.claim())
	);
});

self.addEventListener('message', (event) => {
	if (event.data && event.data.type === 'SKIP_WAITING') self.skipWaiting();
});

const trim = async (name, max) => {
	const cache = await caches.open(name);
	const keys = await cache.keys();
	// Oldest first (insertion order) → simple LRU-ish cap.
	for (let i = 0; i < keys.length - max; i++) await cache.delete(keys[i]);
};

const fromCache = (req, opts) => caches.match(req, opts);

async function navigation(event) {
	const req = event.request;
	const cache = await caches.open(PAGES);
	const network = fetch(req)
		.then((res) => {
			if (res && res.ok && res.type === 'basic') {
				cache.put(req, res.clone()).then(() => trim(PAGES, MAX_PAGES));
			}
			return res;
		})
		.catch(() => null);
	const timeout = new Promise((resolve) => setTimeout(() => resolve(null), NAV_TIMEOUT));
	const first = await Promise.race([network, timeout]);
	if (first) return first;
	const cached = (await cache.match(req, { ignoreSearch: true })) || (await fromCache(req, { ignoreSearch: true }));
	if (cached) return cached;
	const late = await network;
	if (late) return late;
	return (await fromCache(CFG.offline)) || new Response('<h1>Offline</h1>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

async function cacheFirst(req) {
	// Versioned URLs (?ver=) must match exactly so a deploy never serves stale CSS/JS.
	const hit = await fromCache(req);
	if (hit) return hit;
	const res = await fetch(req);
	if (res && res.ok && (res.type === 'basic' || res.type === 'cors')) {
		const cache = await caches.open(ASSETS);
		cache.put(req, res.clone()).then(() => trim(ASSETS, MAX_ASSETS));
	}
	return res;
}

async function staleWhileRevalidate(req) {
	const cache = await caches.open(ASSETS);
	const hit = await cache.match(req);
	const net = fetch(req)
		.then((res) => {
			if (res && res.ok && res.type === 'basic') cache.put(req, res.clone()).then(() => trim(ASSETS, MAX_ASSETS));
			return res;
		})
		.catch(() => hit);
	return hit || net;
}

self.addEventListener('fetch', (event) => {
	const req = event.request;
	if (req.method !== 'GET') return;
	const url = new URL(req.url);
	if (url.origin !== self.location.origin) return;
	// Never cache the REST API (scores, rooms, profiles) — network only.
	if (url.pathname.includes('/wp-json/') || url.search.includes('rest_route=')) return;
	if (url.pathname.startsWith('/wp-admin') || url.pathname.endsWith('/sw.js')) return;

	if (req.mode === 'navigate') {
		if (url.pathname.startsWith(CFG.scope)) event.respondWith(navigation(event));
		return;
	}
	const isTheme = CFG.theme && url.pathname.startsWith(CFG.theme + '/public/');
	const isUpload = CFG.uploads && url.pathname.startsWith(CFG.uploads) && /\.(webp|png|jpe?g|avif|svg)$/i.test(url.pathname);
	if (isTheme || isUpload) {
		event.respondWith(cacheFirst(req));
		return;
	}
	if (/\.(css|js|woff2?)$/i.test(url.pathname)) event.respondWith(staleWhileRevalidate(req));
});
