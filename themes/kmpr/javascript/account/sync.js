/**
 * Local-first sync of the visitor's browser data with their account.
 *
 * Synced: the arcade profile `kmpr_arcade_v1` (id, nickname, avatar, per-game best/plays/time/achievements)
 * + whitelisted localStorage keys (window.kmprAccount.keys: e.g. kmpr_kr_* story progress, kmpr_lessons_done).
 * Merge = monotonic (numbers max, lists union, maps per key) — same rules as the server (inc/accounts.php),
 * so syncing in any order on any device converges and never lowers a value.
 */
export const C = window.kmprAccount || {};
const PROFILE = 'kmpr_arcade_v1';
const LS = (() => {
	try {
		return window.localStorage;
	} catch (e) {
		return null;
	}
})();
const rawSet = LS ? Storage.prototype.setItem : null;

export const uuid = () => {
	if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
	const b = new Uint8Array(16);
	crypto.getRandomValues(b);
	b[6] = (b[6] & 0x0f) | 0x40;
	b[8] = (b[8] & 0x3f) | 0x80;
	const x = [...b].map((v) => v.toString(16).padStart(2, '0')).join('');
	return `${x.slice(0, 8)}-${x.slice(8, 12)}-${x.slice(12, 16)}-${x.slice(16, 20)}-${x.slice(20)}`;
};

const get = (k) => {
	try {
		return LS ? LS.getItem(k) : null;
	} catch (e) {
		return null;
	}
};
const put = (k, v) => {
	try {
		if (LS) rawSet.call(LS, k, v);
	} catch (e) {
		/* quota / private mode */
	}
};

export function isSyncKey(k) {
	const s = C.keys || { keys: [], prefixes: [] };
	return s.keys.includes(k) || s.prefixes.some((p) => k.startsWith(p));
}

export function readProfile() {
	try {
		const p = JSON.parse(get(PROFILE) || 'null');
		if (p && typeof p === 'object' && /^[0-9a-f-]{36}$/i.test(p.id || '')) {
			p.games = p.games || {};
			return p;
		}
	} catch (e) {
		/* broken JSON → fresh */
	}
	return { id: uuid(), nickname: '', hash: '', sound: false, games: {} };
}
export const writeProfile = (p) => put(PROFILE, JSON.stringify(p));

/* ---------- merge (mirror of kmpr_acct_merge_val) ---------- */
function mergeVal(a, b) {
	if (a === null || a === undefined) return b;
	if (b === null || b === undefined) return a;
	if (typeof a === 'boolean' || typeof b === 'boolean') return !!a || !!b;
	if (typeof a === 'number' && typeof b === 'number') return Math.max(a, b);
	if (Array.isArray(a) && Array.isArray(b)) {
		const seen = new Set();
		return [...a, ...b].filter((x) => {
			const h = typeof x === 'object' ? JSON.stringify(x) : typeof x + ':' + x;
			if (seen.has(h)) return false;
			seen.add(h);
			return true;
		}).slice(0, 500);
	}
	if (a && b && typeof a === 'object' && typeof b === 'object' && !Array.isArray(a) && !Array.isArray(b)) {
		const out = { ...a };
		Object.keys(b).forEach((k) => (out[k] = mergeVal(a[k], b[k])));
		return out;
	}
	return a;
}
function mergeKv(a, b) {
	if (a === null || a === undefined) return b;
	if (b === null || b === undefined) return a;
	let da;
	let db;
	try {
		da = JSON.parse(a);
		db = JSON.parse(b);
	} catch (e) {
		return a !== '' ? a : b;
	}
	const m = mergeVal(da, db);
	return typeof m === 'string' ? a : JSON.stringify(m);
}
function mergeGame(x, g) {
	if (!x) return { ...g };
	return {
		...x,
		best: Math.max(x.best || 0, g.best || 0),
		plays: Math.max(x.plays || 0, g.plays || 0),
		time: Math.max(x.time || 0, g.time || 0),
		ach: [...new Set([...(x.ach || []), ...(g.ach || [])])],
		last: (g.at || 0) > (x.at || 0) ? g.last || 0 : x.last || 0,
		at: Math.max(x.at || 0, g.at || 0),
	};
}

/** Everything this device would send. */
export function collect() {
	const p = readProfile();
	const games = {};
	Object.entries(p.games || {}).forEach(([eng, g]) => {
		if (g && typeof g === 'object') games[eng] = { best: g.best || 0, plays: g.plays || 0, time: g.time || 0, ach: g.ach || [], last: g.last || 0, at: g.at || 0 };
	});
	const kv = {};
	if (LS) {
		for (let i = 0; i < LS.length; i++) {
			const k = LS.key(i);
			if (k && isSyncKey(k)) kv[k] = get(k);
		}
	}
	const out = { v: 1, games, kv };
	if (p.avatar && typeof p.avatar === 'object') out.avatar = p.avatar;
	return out;
}

/** Merge a server blob into this device. Returns true when something local changed. */
export function apply(blob) {
	if (!blob || typeof blob !== 'object') return false;
	let changed = false;
	const p = readProfile();
	Object.entries(blob.games || {}).forEach(([eng, g]) => {
		const m = mergeGame(p.games[eng], g);
		if (JSON.stringify(m) !== JSON.stringify(p.games[eng])) {
			p.games[eng] = m;
			changed = true;
		}
	});
	if (blob.avatar && JSON.stringify(blob.avatar) !== JSON.stringify(p.avatar)) {
		p.avatar = blob.avatar;
		changed = true;
	}
	if (changed) writeProfile(p);
	Object.entries(blob.kv || {}).forEach(([k, v]) => {
		if (!isSyncKey(k)) return;
		const cur = get(k);
		const m = mergeKv(v, cur);
		if (m !== cur) {
			put(k, m);
			changed = true;
		}
	});
	if (changed) window.dispatchEvent(new CustomEvent('kmpr:profile-reload'));
	return changed;
}

/** Remove the account's data from this device (explicit log-out). */
export function wipe() {
	if (!LS) return;
	const del = [PROFILE];
	for (let i = 0; i < LS.length; i++) {
		const k = LS.key(i);
		if (k && isSyncKey(k)) del.push(k);
	}
	del.forEach((k) => {
		try {
			LS.removeItem(k);
		} catch (e) {
			/* ignore */
		}
	});
	window.dispatchEvent(new CustomEvent('kmpr:profile-reload'));
}

/** Adopt the account identity on this device (after login / on every logged-in page load). */
export function adopt(user, pid, data) {
	let p = readProfile();
	if (p.acct && p.acct !== user.id) {
		wipe(); // another account used this browser before
		p = readProfile();
	}
	const device = p.id;
	p.id = pid || p.id;
	p.nickname = user.name;
	p.acct = user.id;
	writeProfile(p);
	apply(data);
	return device;
}

/* ---------- REST ---------- */
let loadedAt = Date.now();
export async function refreshNonce() {
	try {
		const r = await fetch(`${C.ajax}?action=rest-nonce`, { credentials: 'same-origin' });
		const n = (await r.text()).trim();
		if (r.ok && /^[a-f0-9]{6,20}$/i.test(n)) {
			C.nonce = n;
			if (window.kmprGames) window.kmprGames.nonce = n;
			loadedAt = Date.now();
			return true;
		}
	} catch (e) {
		/* offline */
	}
	return false;
}

export async function call(path, body, { method = 'POST', keepalive = false, retry = true } = {}) {
	const headers = { Accept: 'application/json', 'X-KMPR-Acct': '1' };
	if (body) headers['Content-Type'] = 'application/json';
	if (C.user && C.nonce) headers['X-WP-Nonce'] = C.nonce;
	let res;
	try {
		res = await fetch(C.rest + path, { method, headers, body: body ? JSON.stringify(body) : undefined, credentials: 'same-origin', cache: 'no-store', keepalive });
	} catch (e) {
		return { ok: false, offline: true, message: 'اتصال اینترنت برقرار نیست.' };
	}
	let data = null;
	try {
		data = await res.json();
	} catch (e) {
		data = null;
	}
	if (res.status === 403 && data && data.code === 'rest_cookie_invalid_nonce' && retry && (await refreshNonce())) {
		return call(path, body, { method, keepalive, retry: false });
	}
	if (!data) data = { ok: false, message: 'پاسخ نامعتبر از سرور؛ دوباره تلاش کن.' };
	if (!res.ok) data.ok = false;
	data.status = res.status;
	return data;
}

/* ---------- background sync for a logged-in visitor ---------- */
let timer = 0;
let busy = false;
let again = false;
export const state = { last: 0, error: '' };
let lastSent = '';
const sortKeys = (o) => (Array.isArray(o) ? o.map(sortKeys) : o && typeof o === 'object' ? Object.keys(o).sort().reduce((a, k) => ((a[k] = sortKeys(o[k])), a), {}) : o);
const canon = (o) => JSON.stringify(sortKeys(o));

/** Send this device's data, get the merged copy back. force = also when nothing changed locally (pull). */
export async function push(devicePid, force = false) {
	const data = collect();
	const json = canon(data);
	if (!devicePid && !force && json === lastSent) return { ok: true, skipped: true };
	if (busy) {
		again = true;
		return;
	}
	busy = true;
	clearTimeout(timer);
	const r = await call('/sync', { pid: devicePid || readProfile().id, data });
	busy = false;
	if (r.ok) {
		if (r.pid) {
			const p = readProfile();
			if (p.id !== r.pid) {
				p.id = r.pid;
				writeProfile(p);
				window.dispatchEvent(new CustomEvent('kmpr:profile-reload'));
			}
		}
		apply(r.data);
		lastSent = canon(collect());
		state.last = Date.now();
		state.error = '';
	} else {
		state.error = r.message || '';
	}
	window.dispatchEvent(new CustomEvent('kmpr:acct-synced', { detail: { ok: !!r.ok } }));
	if (again) {
		again = false;
		schedule(1500);
	}
	return r;
}

export function schedule(ms = 3000, force = false) {
	clearTimeout(timer);
	timer = setTimeout(() => push('', force), ms);
}

function flushOnLeave() {
	clearTimeout(timer);
	const headers = { 'Content-Type': 'application/json', 'X-KMPR-Acct': '1', 'X-WP-Nonce': C.nonce || '' };
	try {
		fetch(C.rest + '/sync', { method: 'POST', headers, body: JSON.stringify({ pid: readProfile().id, data: collect() }), credentials: 'same-origin', keepalive: true });
	} catch (e) {
		/* ignore */
	}
}

export function bootSync() {
	if (C.user && C.visitor && C.pid) {
		const before = canon({ v: 1, games: {}, kv: {}, ...(C.data || {}) });
		const device = adopt(C.user, C.pid, C.data);
		if (device !== C.pid) push(device); // fold this device's previous id into the account
		else if (canon(collect()) !== before) schedule(1500);
		else lastSent = before;

		let dirty = false;
		if (LS) {
			Storage.prototype.setItem = function (k, v) {
				rawSet.call(this, k, v);
				if (this === LS && (k === PROFILE || isSyncKey(k))) {
					dirty = true;
					schedule(4000);
				}
			};
		}
		window.addEventListener('kmpr:game-over', () => schedule(1200));
		document.addEventListener('visibilitychange', () => {
			if (document.hidden) {
				if (dirty) flushOnLeave();
				dirty = false;
			} else {
				if (Date.now() - loadedAt > 6 * 3600 * 1000) refreshNonce();
				schedule(800, true); // pull what other devices changed meanwhile
			}
		});
		window.addEventListener('pagehide', () => dirty && flushOnLeave());
		return;
	}
	if (!C.user) {
		// Session ended (expired or logged out elsewhere): keep this device's own copy, but as a new anonymous player.
		const p = readProfile();
		if (p.acct) {
			delete p.acct;
			p.id = uuid();
			writeProfile(p);
		}
	}
}
