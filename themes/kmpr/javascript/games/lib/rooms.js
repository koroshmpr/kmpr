/**
 * Online room client — REST polling with a version number (server answers
 * { changed:false } quickly when nothing moved). Token per seat kept in sessionStorage
 * so a reload re-attaches to the same seat.
 */
import { online } from './api.js';
import { store } from './store.js';

const SKEY = 'kmpr_room_tokens';
const tokens = () => {
	try {
		return JSON.parse(sessionStorage.getItem(SKEY) || '{}');
	} catch (e) {
		return {};
	}
};
const saveToken = (code, token) => {
	try {
		const t = tokens();
		t[code] = token;
		sessionStorage.setItem(SKEY, JSON.stringify(t));
	} catch (e) {
		/* ignore */
	}
};

export class Room {
	constructor({ gameId, onState, onPresence, onError }) {
		this.gameId = gameId;
		this.onState = onState || (() => {});
		this.onPresence = onPresence || (() => {});
		this.onError = onError || (() => {});
		this.code = '';
		this.token = '';
		this.seat = null;
		this.view = null;
		this.v = 0;
		this.timer = 0;
		this.alive = false;
		this.busy = false;
		this.fails = 0;
		this._vis = () => {
			if (!document.hidden && this.alive) this.poll(true);
		};
		document.addEventListener('visibilitychange', this._vis);
	}

	me() {
		const p = store.player;
		return { player_id: p.id, nickname: p.nickname || '', avatar: p.avatar || { style: 'blocks', hue: 32 } };
	}

	async create(isPublic, options = {}) {
		return this._adopt(await online.create({ game: this.gameId, public: isPublic, options, ...this.me() }));
	}
	async quick() {
		return this._adopt(await online.quick({ game: this.gameId, ...this.me() }));
	}
	async join(code) {
		return this._adopt(await online.join(code, this.me()));
	}

	_adopt(res) {
		if (!res || !res.ok) return res || { ok: false };
		this.code = res.code;
		this.token = res.token || this.token;
		this.seat = res.seat;
		if (this.token) saveToken(this.code, this.token);
		this._apply(res);
		this.alive = true;
		this._schedule();
		return res;
	}

	_apply(res) {
		if (!res || !res.ok) return;
		if (res.changed === false) {
			if (this.view && res.online) {
				let changed = false;
				this.view.players.forEach((p) => {
					const on = !!res.online[p.seat];
					if (p.online !== on) changed = true;
					p.online = on;
				});
				if (changed) this.onPresence(this.view);
			}
			return;
		}
		this.v = res.v;
		this.view = res;
		this.seat = res.seat ?? this.seat;
		this.onState(res);
	}

	_schedule() {
		clearTimeout(this.timer);
		if (!this.alive) return;
		// Hidden tab: slow heartbeat only (keeps the seat from being treated as abandoned).
		const base = document.hidden ? 15000 : this.view && this.view.status === 'waiting' ? 1500 : 850;
		this.timer = setTimeout(() => this.poll(), base + Math.min(8000, this.fails * 1500));
	}

	async poll(now = false) {
		if (!this.alive || !this.code) return;
		if (now) clearTimeout(this.timer);
		try {
			const res = await online.state(this.code, this.token, this.v);
			if (res && res.ok) {
				this.fails = 0;
				this._apply(res);
			} else {
				this.fails++;
				if (res && res.code === 'no_room') {
					this.alive = false;
					this.onError(res);
					return;
				}
			}
		} catch (e) {
			this.fails++;
		}
		this._schedule();
	}

	async move(move) {
		if (this.busy) return { ok: false, code: 'busy' };
		this.busy = true;
		try {
			const res = await online.move(this.code, this.token, this.v, move);
			if (res && res.ok) this._apply(res);
			else {
				this.onError(res);
				this.poll(true);
			}
			return res;
		} finally {
			this.busy = false;
		}
	}
	async rematch() {
		const res = await online.rematch(this.code, this.token);
		if (res && res.ok) this._apply(res);
		return res;
	}
	async leave() {
		this.alive = false;
		clearTimeout(this.timer);
		if (!this.code || !this.token) return;
		try {
			await online.leave(this.code, this.token);
		} catch (e) {
			/* ignore */
		}
	}
	/** Re-attach with a token saved in this tab (reload). */
	static savedToken(code) {
		return tokens()[code] || '';
	}
	destroy() {
		this.alive = false;
		clearTimeout(this.timer);
		document.removeEventListener('visibilitychange', this._vis);
	}
}
