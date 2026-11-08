/**
 * Owner panel — REST client (cookie auth + wp_rest nonce, refreshed transparently when it expires).
 */
export const boot = JSON.parse(document.getElementById('op-boot')?.textContent || '{}');

let nonce = boot.nonce;

async function refreshNonce() {
	const r = await fetch(boot.nonceUrl, { credentials: 'same-origin', cache: 'no-store' });
	if (r.status === 401) {
		location.href = boot.base; // session gone → server redirects to wp-login with redirect_to
		throw new Error('نشست شما منقضی شده است.');
	}
	const j = await r.json();
	nonce = j.nonce;
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
		if (!retried && (data?.code === 'rest_cookie_invalid_nonce' || res.status === 403 || res.status === 401)) {
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
