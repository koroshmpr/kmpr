/**
 * Local player profile (localStorage): random uuid, nickname, per-engine stats,
 * achievements and preferences. Everything is wrapped in try/catch so private
 * windows still work (in-memory fallback).
 */
const KEY = 'kmpr_arcade_v1';
let memory = null;

const uuid = () => {
	if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
	const b = new Uint8Array(16);
	(window.crypto || { getRandomValues: (a) => a.map(() => Math.floor(Math.random() * 256)) }).getRandomValues(b);
	b[6] = (b[6] & 0x0f) | 0x40;
	b[8] = (b[8] & 0x3f) | 0x80;
	const x = [...b].map((v) => v.toString(16).padStart(2, '0')).join('');
	return `${x.slice(0, 8)}-${x.slice(8, 12)}-${x.slice(12, 16)}-${x.slice(16, 20)}-${x.slice(20)}`;
};

function read() {
	if (memory) return memory;
	let data = null;
	try {
		data = JSON.parse(localStorage.getItem(KEY) || 'null');
	} catch (e) {
		data = null;
	}
	if (!data || typeof data !== 'object' || !/^[0-9a-f-]{36}$/i.test(data.id || '')) {
		data = { id: uuid(), nickname: '', hash: '', sound: false, games: {} };
	}
	data.games = data.games || {};
	memory = data;
	write();
	return data;
}

function write() {
	try {
		localStorage.setItem(KEY, JSON.stringify(memory));
	} catch (e) {
		/* storage blocked — keep in memory */
	}
}

export const store = {
	get player() {
		return read();
	},
	set(key, value) {
		read()[key] = value;
		write();
	},
	game(engine) {
		const d = read();
		if (!d.games[engine]) d.games[engine] = { best: 0, plays: 0, time: 0, ach: [], last: 0 };
		return d.games[engine];
	},
	/** Record a finished run. Returns { best, isBest }. */
	record(engine, score, ms) {
		const g = this.game(engine);
		const isBest = score > g.best;
		g.plays += 1;
		g.time += Math.round(ms / 1000);
		g.last = score;
		g.at = Date.now();
		if (isBest) g.best = score;
		write();
		return { best: g.best, isBest };
	},
	unlock(engine, id) {
		const g = this.game(engine);
		if (g.ach.includes(id)) return false;
		g.ach.push(id);
		write();
		return true;
	},
	/** Merge server stats (best/plays) without ever lowering local values. */
	merge(engine, { best = 0, plays = 0 }) {
		const g = this.game(engine);
		g.best = Math.max(g.best, best);
		g.plays = Math.max(g.plays, plays);
		write();
	},
	/** Local history (runs + online matches); server keeps none. */
	pushRecent(entry) {
		const d = read();
		d.recent = [{ ...entry, t: Date.now() }, ...(d.recent || [])].slice(0, 12);
		write();
	},
	save: write,
};
