/**
 * Owner panel service worker (served at /panel/sw.js with self.__PANEL prepended).
 * - Static panel assets (JS/CSS/fonts/icons under the theme's /public/): stale-while-revalidate.
 * - Navigations: always network (the shell carries a fresh nonce); offline → tiny offline page.
 * - REST API and everything else: untouched (never cached).
 */
/* global self, caches */
const CFG = self.__PANEL || { v: '0', scope: '/panel/', assets: '/' };
const CACHE = 'kmpr-panel-' + CFG.v;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => {
	e.waitUntil(
		caches
			.keys()
			.then((keys) => Promise.all(keys.filter((k) => k.startsWith('kmpr-panel-') && k !== CACHE).map((k) => caches.delete(k))))
			.then(() => self.clients.claim()),
	);
});

const OFFLINE = `<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#020617"><title>آفلاین</title><body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#020617;color:#e2e8f0;font-family:yekanBakh,system-ui,sans-serif;text-align:center;padding:24px"><div><p style="font-size:18px;font-weight:700">اینترنت در دسترس نیست</p><p style="color:#94a3b8">پنل برای نمایش اطلاعات زنده به اتصال نیاز دارد.</p><button onclick="location.reload()" style="min-height:44px;padding:10px 24px;border-radius:999px;border:0;background:#fbbf24;color:#0f172a;font:inherit;font-weight:700">تلاش دوباره</button></div></body></html>`;

function isAsset(url) {
	return url.origin === self.location.origin && url.pathname.startsWith(CFG.assets) && /(owner-panel|\/fonts\/yekanBakh\/|\/images\/panel\/)/.test(url.pathname);
}

self.addEventListener('fetch', (e) => {
	const req = e.request;
	if (req.method !== 'GET') return;
	const url = new URL(req.url);

	if (req.mode === 'navigate' && url.pathname.startsWith(CFG.scope)) {
		e.respondWith(fetch(req).catch(() => new Response(OFFLINE, { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
		return;
	}
	if (isAsset(url)) {
		e.respondWith(
			caches.open(CACHE).then(async (c) => {
				const hit = await c.match(req);
				const net = fetch(req)
					.then((res) => {
						if (res.ok) c.put(req, res.clone());
						return res;
					})
					.catch(() => hit);
				return hit || net;
			}),
		);
	}
});
