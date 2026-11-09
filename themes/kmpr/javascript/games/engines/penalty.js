/**
 * «ضربات پنالتی» — 5 kicks each, then sudden death. Goal split into 6 zones.
 * vs AI: you shoot (tap a zone), then you keep goal (the AI striker «looks» at a corner — trust it or not, then dive).
 * Two players: P1 shoots / P2 keeps, roles swap each kick; shooter's choice is hidden.
 */
import { CATALOG } from '../catalog.js';
import { createMatchUI, playerAvatar } from '../lib/match.js';
import { fa, listeners, randInt } from '../lib/util.js';

export const achievements = CATALOG.penalty.achievements;
const KEEP = { easy: 0.17, medium: 0.28, hard: 0.4 }; // AI keeper guesses right
const TELL = { easy: 0.85, medium: 0.6, hard: 0.35 }; // AI striker looks where it shoots
let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	let mode = null, s = null, timers = [];
	const later = (fn, ms) => timers.push(setTimeout(fn, ms));
	const clear = () => { timers.forEach(clearTimeout); timers = []; };
	const ui = createMatchUI(stage, api, {
		ai: [{ id: 'easy', label: 'آسان' }, { id: 'medium', label: 'متوسط' }, { id: 'hard', label: 'حرفه‌ای' }],
		local: true,
		onStart: (m) => { mode = m; startMatch(); },
		onMenu: () => { clear(); s = null; mode = null; render(); },
	});
	const name = (seat) => (mode && mode.type === 'local' ? `بازیکن ${fa(seat + 1)}` : seat === 0 ? 'شما' : 'ربات');
	const players = () => [0, 1].map((seat) => ({ name: name(seat), avatar: seat ? playerAvatar({ player: 'pk-bot', avatar: { style: 'blocks', hue: 10 } }) : playerAvatar('me'), sub: s ? s.marks[seat].map((m) => (m === 1 ? '⚽' : '✖')).join('') || '—' : '', seat }));

	const el = document.createElement('div');
	el.className = 'pk';
	el.innerHTML = `<div class="pk__goal" data-goal>${[0, 1, 2, 3, 4, 5].map((z) => `<button type="button" class="pk__zone" data-z="${z}" aria-label="گوشه ${fa(z + 1)}"></button>`).join('')}
		<span class="pk__keeper" data-keeper aria-hidden="true">🧤</span></div>
		<span class="pk__ball" data-ball aria-hidden="true">⚽</span>
		<p class="pk__msg" data-msg aria-live="polite"></p>`;
	ui.board.appendChild(el);
	const $ = (q) => el.querySelector(q);
	const zones = [...el.querySelectorAll('.pk__zone')];
	const pos = (z) => ({ x: [-1, 0, 1][z % 3], y: z < 3 ? 0 : 1 }); // RTL-agnostic: 0 = left

	const render = () => {
		ui.players(players());
		zones.forEach((b) => (b.className = 'pk__zone'));
		$('[data-keeper]').style.transform = '';
		$('[data-ball]').style.transform = '';
		$('[data-ball]').className = 'pk__ball';
		$('[data-msg]').textContent = '';
	};
	const startMatch = () => {
		clear();
		s = { kick: 0, marks: [[], []], phase: 'aim', shot: -1, saves: 0 };
		render();
		next();
	};
	const shooter = () => s.kick % 2;
	const next = () => {
		render();
		s.shot = -1;
		s.dive = -1;
		const sh = shooter();
		if (mode.type === 'local') {
			s.phase = 'aim';
			ui.status(`${name(sh)} شوت بزن — یک گوشه را انتخاب کن (${name(1 - sh)} نگاه نکند!)`, 'turn');
		} else if (sh === 0) {
			s.phase = 'aim';
			ui.status('نوبت شوت توست — یک گوشه را بزن', 'turn');
		} else {
			s.phase = 'tell';
			s.shot = randInt(0, 5);
			const look = Math.random() < TELL[mode.level] ? s.shot : randInt(0, 5);
			ui.status('ربات آماده شوت است… چشم‌هایش کجاست؟', 'turn');
			later(() => zones[look].classList.add('is-look'), 450);
			later(() => {
				zones[look].classList.remove('is-look');
				s.phase = 'dive';
				ui.status('شیرجه بزن! (۱.۵ ثانیه)', 'turn');
				later(() => s && s.phase === 'dive' && resolve(-1), 1500);
			}, 1250);
		}
	};
	const pick = (z) => {
		if (!s) return;
		if (s.phase === 'aim') {
			s.shot = z;
			api.sfx('click');
			if (mode.type === 'local') {
				s.phase = 'dive';
				ui.status(`${name(1 - shooter())} دروازه‌بانی: شیرجه به کدام گوشه؟`, 'turn');
			} else {
				const right = Math.random() < KEEP[mode.level];
				resolve(right ? z : (z + randInt(1, 5)) % 6);
			}
		} else if (s.phase === 'dive') resolve(z);
	};
	const resolve = (dive) => {
		s.phase = 'anim';
		const z = s.shot, p = pos(z), kz = dive < 0 ? 4 : dive, kp = pos(kz);
		const saved = kz === z || (dive >= 0 && pos(dive).x === p.x && Math.random() < 0.35);
		const keeperIsMe = mode.type === 'ai' && shooter() === 1;
		const to = (node, zi, extra = '') => {
			node.style.transform = '';
			const a = node.getBoundingClientRect(), r = zones[zi].getBoundingClientRect();
			node.style.transform = `translate(${Math.round(r.left + r.width / 2 - a.left - a.width / 2)}px, ${Math.round(r.top + r.height / 2 - a.top - a.height / 2)}px)${extra}`;
		};
		to($('[data-keeper]'), kz, ` rotate(${kp.x * 35}deg)`);
		$('[data-ball]').classList.add('is-kick');
		to($('[data-ball]'), z, saved ? ' scale(.75)' : ' scale(.6)');
		zones[z].classList.add(saved ? 'is-saved' : 'is-goal');
		api.sfx('shoot');
		later(() => {
			const sh = shooter();
			s.marks[sh].push(saved ? 0 : 1);
			$('[data-msg]').textContent = saved ? 'مهار شد! 🧤' : 'گل! ⚽';
			api.sfx(saved === keeperIsMe || (mode.type === 'local' && !saved) ? 'coin' : 'hurt');
			if (keeperIsMe && saved) { s.saves++; api.unlock('save'); if (s.saves === 3) api.unlock('wall'); }
			if (mode.type === 'ai' && sh === 0 && !saved && z < 3 && z !== 1) api.unlock('top');
			ui.players(players());
			s.kick++;
			const w = winner();
			later(() => (w === null ? next() : end(w)), 1100);
		}, 650);
	};
	const winner = () => {
		const [a, b] = s.marks.map((m) => m.reduce((x, y) => x + y, 0));
		const ka = s.marks[0].length, kb = s.marks[1].length;
		if (ka <= 5 && kb <= 5) {
			if (a > b + (5 - kb)) return 0;
			if (b > a + (5 - ka)) return 1;
			if (ka === 5 && kb === 5 && a !== b) return a > b ? 0 : 1;
			return null;
		}
		return ka === kb && a !== b ? (a > b ? 0 : 1) : null;
	};
	const end = (w) => {
		s.phase = 'over';
		const [a, b] = s.marks.map((m) => m.reduce((x, y) => x + y, 0));
		if (mode.type === 'local') return ui.result({ title: `${name(w)} برد!`, text: `نتیجه پنالتی‌ها ${fa(a)} — ${fa(b)}`, tone: 'win', onPrimary: startMatch });
		if (w === 0) {
			api.unlock('first_win');
			if (mode.level === 'hard') api.unlock('pro');
			const pts = Math.min(10000, a * 400 + s.saves * 300 + { easy: 0, medium: 1200, hard: 2800 }[mode.level]);
			api.sfx('level');
			return api.over({ score: pts, meta: { mode: mode.level, result: 'win', summary: `پنالتی ${fa(a)} — ${fa(b)} · ${fa(s.saves)} مهار` } });
		}
		ui.result({ title: 'ربات برد', text: `نتیجه ${fa(a)} — ${fa(b)}. به نگاه ربات همیشه اعتماد نکن!`, tone: 'loss', onPrimary: startMatch });
	};
	L.on(el, 'click', (e) => { const z = e.target.closest('.pk__zone'); if (z) pick(+z.dataset.z); });
	L.on(window, 'keydown', (e) => {
		// Numpad-like: 7 8 9 = top row, 4 5 6 = bottom row (visual left → right)
		const map = { 7: 0, 8: 1, 9: 2, 4: 3, 5: 4, 6: 5 };
		if (s && map[e.key] !== undefined) pick(map[e.key]);
	});
	ui.players(players());
	const inst = {
		noAutoPause: true,
		start(opts = {}) { clear(); ui.start(opts); },
		pause() {},
		resume() {},
		destroy() { clear(); L.off(); ui.destroy(); },
	};
	current = inst;
	return inst;
}
export function destroy() { current && current.destroy(); current = null; }
