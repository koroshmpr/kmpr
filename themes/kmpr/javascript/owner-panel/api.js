/**
 * Owner panel — REST client (cookie auth + wp_rest nonce, refreshed transparently when it expires).
 */
export const boot = JSON.parse(document.getElementById('op-boot')?.textContent || '{}');

let nonce = boot.nonce;

let refreshing = null;

/**
 * Fetch a fresh nonce from /panel/nonce. That request also carries the /panel/-scoped trusted-device
 * cookie, so if the WP login cookie was dropped (app killed, 2-day session ended) the server signs the
 * owner back in transparently and the retried call succeeds. Only a real sign-out ends at the login page.
 * Concurrent callers share one in-flight refresh.
 */
function refreshNonce() {
	refreshing ||= (async () => {
		let r;
		try {
			r = await fetch(boot.nonceUrl, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
		} catch {
			throw new Error(navigator.onLine ? 'ارتباط با سرور برقرار نشد.' : 'اینترنت قطع است.');
		}
		let j = null;
		try {
			j = await r.json();
		} catch {
			/* 404 HTML etc. */
		}
		if (!r.ok || !j?.nonce) {
			if (r.status === 401 || r.status === 403 || r.status === 404) {
				location.href = boot.base; // signed out → the panel shows its login screen
				throw new Error('نشست شما منقضی شده است.');
			}
			throw new Error('خطای سرور (' + r.status + ')');
		}
		nonce = j.nonce;
	})().finally(() => {
		refreshing = null;
	});
	return refreshing;
}

/**
 * api('games') · api('games/12', { method: 'POST', body: {...} }) · api('x', { form: FormData })
 */
export async function api(path, { method = 'GET', body = null, form = null, query = null, retried = false } = {}) {
	let url = boot.rest + path;
	if (query) {
		const q = new URLSearchParams();
		for (const [k, v] of Object.entries(query)) if (v !== null && v !== undefined && v !== '') q.set(k, v);
		const s = q.toString();
		if (s) url += (url.includes('?') ? '&' : '?') + s;
	}
	const headers = { 'X-WP-Nonce': nonce, Accept: 'application/json' };
	let payload;
	if (form) payload = form;
	else if (body) {
		headers['Content-Type'] = 'application/json';
		payload = JSON.stringify(body);
	}
	const ctrl = new AbortController();
	const timer = setTimeout(() => ctrl.abort(), 30000);
	let res;
	try {
		res = await fetch(url, { method, headers, body: payload, credentials: 'same-origin', cache: 'no-store', signal: ctrl.signal });
	} catch (e) {
		throw new Error(navigator.onLine ? 'ارتباط با سرور برقرار نشد.' : 'اینترنت قطع است.');
	} finally {
		clearTimeout(timer);
	}
	let data = null;
	try {
		data = await res.json();
	} catch {
		/* empty body */
	}
	if (!res.ok) {
		if (!retried && (data?.code === 'rest_cookie_invalid_nonce' || data?.code === 'rest_forbidden' || res.status === 401)) {
			await refreshNonce();
			return api(path, { method, body, form, query, retried: true });
		}
		const err = new Error(data?.message || 'خطای سرور (' + res.status + ')');
		err.status = res.status;
		err.code = data?.code;
		throw err;
	}
	return data;
}
