/** REST client for kmpr/v1/games (URL + nonce localized by WordPress as window.kmprGames). */
const cfg = () => window.kmprGames || {};

async function req(path, { method = 'GET', body, query } = {}) {
	const base = (cfg().rest || '/wp-json/kmpr/v1/games').replace(/\/$/, '');
	const url = new URL(base + path, window.location.origin);
	if (query) Object.entries(query).forEach(([k, v]) => url.searchParams.set(k, v));
	const headers = { Accept: 'application/json' };
	if (body) headers['Content-Type'] = 'application/json';
	if (cfg().nonce) headers['X-WP-Nonce'] = cfg().nonce;
	const res = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : undefined, credentials: 'same-origin' });
	let data = null;
	try {
		data = await res.json();
	} catch (e) {
		data = { ok: false, message: 'پاسخ نامعتبر از سرور' };
	}
	if (!res.ok && data) data.ok = false;
	return data || { ok: false };
}

export const api = {
	/** Run token; also counts a play for the player (extra: { player_id, nickname, online }). */
	start: (game, extra = {}) => req('/start', { method: 'POST', body: { game, ...extra } }),
	submit: (payload) => req('/score', { method: 'POST', body: payload }),
	leaderboard: (game, period = 'all') => req('/leaderboard', { query: { game, period } }),
	player: (player_id) => req('/player', { query: { player_id } }),
	get userName() {
		return cfg().userName || '';
	},
};

/* ---------- profiles & rooms ---------- */
async function roomReq(path, { method = 'GET', body, query, token } = {}) {
	const base = (cfg().rest || '/wp-json/kmpr/v1/games').replace(/\/$/, '');
	const url = new URL(base + path, window.location.origin);
	if (query) Object.entries(query).forEach(([k, v]) => v !== undefined && url.searchParams.set(k, v));
	const headers = { Accept: 'application/json' };
	if (body) headers['Content-Type'] = 'application/json';
	if (cfg().nonce) headers['X-WP-Nonce'] = cfg().nonce;
	if (token) headers['X-Room-Token'] = token;
	const res = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : undefined, credentials: 'same-origin', cache: 'no-store' });
	let data = null;
	try {
		data = await res.json();
	} catch (e) {
		data = { ok: false, message: 'پاسخ نامعتبر از سرور' };
	}
	if (!res.ok && data) data.ok = false;
	return data || { ok: false };
}

export const online = {
	profile: (body) => roomReq('/profile', { method: 'POST', body }),
	topPlayers: () => roomReq('/top-players'),
	lobby: (game) => roomReq('/rooms/lobby', { query: { game } }),
	create: (body) => roomReq('/rooms', { method: 'POST', body }),
	quick: (body) => roomReq('/rooms/quick', { method: 'POST', body }),
	join: (code, body) => roomReq(`/rooms/${code}/join`, { method: 'POST', body }),
	state: (code, token, v) => roomReq(`/rooms/${code}`, { token, query: { v } }),
	move: (code, token, v, move) => roomReq(`/rooms/${code}/move`, { method: 'POST', token, body: { v, move } }),
	rematch: (code, token) => roomReq(`/rooms/${code}/rematch`, { method: 'POST', token, body: {} }),
	leave: (code, token) => roomReq(`/rooms/${code}/leave`, { method: 'POST', token, body: {} }),
};
