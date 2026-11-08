/**
 * «رنگ‌بازی» — modernised from the two-player "click the boxes" toy in my showcase repo.
 * A 30-second paint race on a shared grid: paint cells your colour (you may repaint the
 * opponent's), drag to paint faster (rate-capped), most cells when time runs out wins.
 * Modes: vs bot (3 speeds), local 2P (mirrored split-screen for two thumbs on one device),
 * ONLINE (shared grid, batched moves, server-timed rounds and paint-rate cap).
 */
import { CATALOG } from '../catalog.js';
import { createMatchUI, playerAvatar } from '../lib/match.js';
import { fa, listeners, pick } from '../lib/util.js';

export const achievements = CATALOG['color-race'].achievements;

const CD = 3;
const DUR = 30;
const RATE_MS = 95; // min ms between paints per player (≈10/s, server allows 12/s)
const BOT = { easy: 2.2, medium: 3.6, hard: 5.4 };
const NAMES = ['کهربایی', 'فیروزه‌ای'];

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	let mode = null;
	let cols = 6;
	let rows = 6;
	let cells = [];
	let phase = 'idle'; // idle | cd | play | end
	let t = 0; // seconds since round start (incl. countdown)
	let raf = 0;
	let lastFrame = 0;
	let lastPaint = [-1, -1];
	let botAcc = 0;
	let myPaints = 0;
	let earlyPaints = 0;
	let trailed = false;
	let lastCd = -1;
	let series = [0, 0, 0];
	let finished = false;
	// online
	let offset = 0;
	let startedAt = 0;
	let queue = [];
	let inflight = [];
	let flushT = 0;
	let tickT = 0;
	let mySeat = 0;
	let serverOver = false;

	const ui = createMatchUI(stage, api, {
		ai: [
			{ id: 'easy', label: 'آرام' },
			{ id: 'medium', label: 'سریع' },
			{ id: 'hard', label: 'برق‌آسا' },
		],
		local: true,
		online: true,
		seatLabel: (seat) => NAMES[seat],
		activeSeat: () => null,
		onStart: (m) => {
			mode = m;
			series = [0, 0, 0];
			if (m.type === 'online') startOnline(m.view);
			else startRound();
		},
		onRoomState: (view) => onlineState(view),
		resultText: (view) => {
			const c = view.state && view.state.counts;
			if (!c) return null;
			const me = view.seat;
			return { title: view.state.winner === 'draw' ? 'مساوی!' : view.state.winner === me ? 'بردید! 🎉' : 'باختید', text: `${fa(c[me])} خانه برای شما، ${fa(c[1 - me])} برای حریف` };
		},
		onMenu: () => {
			stopLoop();
			mode = null;
			phase = 'idle';
			build();
			ui.players(defaultPlayers());
		},
	});

	const defaultPlayers = () => [
		{ name: 'شما', avatar: playerAvatar('me'), sub: NAMES[0], seat: 0 },
		{ name: 'حریف', avatar: playerAvatar({ player: 'cr-bot', avatar: { style: 'blocks', hue: 172 } }), sub: NAMES[1], seat: 1 },
	];

	/* ---------- DOM ---------- */
	const wrap = document.createElement('div');
	wrap.className = 'cr';
	wrap.innerHTML = `
		<div class="cr__hud">
			<span class="cr__count cr__count--a" data-count="0" aria-label="خانه‌های کهربایی">۰</span>
			<div class="cr__mid">
				<div class="cr__tug" aria-hidden="true"><i data-tug></i></div>
				<span class="cr__timer" data-timer>${fa(DUR)}</span>
			</div>
			<span class="cr__count cr__count--b" data-count="1" aria-label="خانه‌های فیروزه‌ای">۰</span>
		</div>
		<div class="cr__grids" data-grids></div>
		<div class="cr__cd" data-cd hidden></div>`;
	ui.board.appendChild(wrap);
	const gridsEl = wrap.querySelector('[data-grids]');
	const countEls = [...wrap.querySelectorAll('[data-count]')];
	const tug = wrap.querySelector('[data-tug]');
	const timerEl = wrap.querySelector('[data-timer]');
	const cdEl = wrap.querySelector('[data-cd]');

	const isLocal = () => mode && mode.type === 'local';

	const build = () => {
		const r = ui.board.getBoundingClientRect();
		const wide = r.width > 560;
		if (mode && mode.type === 'online') {
			cols = 6;
			rows = 6;
		} else if (isLocal()) {
			cols = 6;
			rows = 5;
		} else {
			cols = wide ? 8 : 6;
			rows = 6;
		}
		cells = new Array(cols * rows).fill(-1);
		const n = isLocal() ? 2 : 1;
		gridsEl.innerHTML = Array.from({ length: n }, (_, g) => `<div class="cr__grid${isLocal() ? ' cr__grid--p' + g : ''}" data-grid="${g}" style="--cols:${cols};--rows:${rows}" role="group" aria-label="${isLocal() ? 'صفحه بازیکن ' + fa(g + 1) : 'صفحه رنگ‌آمیزی'}">
			${cells.map((_, i) => `<button type="button" class="cr__cell" data-c="${i}" aria-label="خانه ${fa(i + 1)}"></button>`).join('')}
		</div>`).join('');
		gridsEl.classList.toggle('is-local', isLocal());
		fit();
		paintAll();
	};

	const fit = () => {
		const r = ui.board.getBoundingClientRect();
		if (!r.width) return;
		const PAD = 14; // grid padding + border
		const GAP = 4;
		const hudH = 58; // hud row + gap
		let w = r.width - 24;
		let h = r.height - 24 - hudH;
		const local = isLocal();
		const sideBySide = local && r.width > r.height * 1.2;
		gridsEl.classList.toggle('is-row', sideBySide);
		if (local) {
			if (sideBySide) w = (w - 24) / 2;
			else h = (h - 12) / 2;
		}
		const cw = (w - PAD - GAP * (cols - 1)) / cols;
		const ch = (h - PAD - GAP * (rows - 1)) / rows;
		const cell = Math.max(18, Math.floor(Math.min(cw, ch, 72)));
		wrap.style.setProperty('--cell', cell + 'px');
	};
	const ro = new ResizeObserver(fit);
	ro.observe(ui.board);

	const counts = () => {
		let a = 0;
		let b = 0;
		for (const c of cells) {
			if (c === 0) a++;
			else if (c === 1) b++;
		}
		return [a, b];
	};

	const paintAll = () => {
		gridsEl.querySelectorAll('[data-grid]').forEach((g) => {
			const btns = g.children;
			for (let i = 0; i < cells.length; i++) {
				const b = btns[i];
				if (!b) continue;
				const v = cells[i];
				const cls = v === 0 ? 'is-a' : v === 1 ? 'is-b' : '';
				if (b.dataset.v !== String(v)) {
					b.dataset.v = String(v);
					b.classList.remove('is-a', 'is-b');
					if (cls) b.classList.add(cls);
				}
			}
		});
		hud();
	};

	const popCell = (i) => {
		gridsEl.querySelectorAll(`[data-c="${i}"]`).forEach((b) => {
			b.classList.remove('pop');
			void b.offsetWidth;
			b.classList.add('pop');
		});
	};

	const hud = () => {
		const [a, b] = counts();
		countEls[0].textContent = fa(a);
		countEls[1].textContent = fa(b);
		const tot = a + b;
		tug.style.setProperty('--p', tot ? a / tot : 0.5);
		const left = Math.max(0, Math.ceil(DUR - Math.max(0, t - CD)));
		timerEl.textContent = fa(phase === 'cd' ? DUR : left);
		timerEl.classList.toggle('is-low', phase === 'play' && left <= 5);
	};

	/* ---------- painting ---------- */
	const paint = (i, who, fromNet = false) => {
		if (phase !== 'play' || i < 0 || i >= cells.length) return false;
		if (!fromNet) {
			const now = performance.now();
			if (now - lastPaint[who] < RATE_MS) return false;
			if (cells[i] === who) return false;
			lastPaint[who] = now;
		}
		cells[i] = who;
		popCell(i);
		const humanSide = mode.type === 'online' ? mySeat : 0;
		if (!fromNet && (mode.type !== 'ai' || who === humanSide)) {
			if (who === humanSide || isLocal()) {
				if (who === humanSide) {
					myPaints++;
					if (t - CD <= 10) {
						earlyPaints++;
						if (earlyPaints >= 40 && !isLocal()) api.unlock('fast');
					}
				}
			}
			api.sfx([['triangle', who === 0 ? 660 : 520, who === 0 ? 990 : 780, 0.05, 0.03]]);
		}
		if (mode.type === 'online' && who === mySeat && !fromNet) queue.push(i);
		hud();
		paintAll();
		return true;
	};

	const cellAt = (x, y) => {
		const el = document.elementFromPoint(x, y);
		const b = el && el.closest && el.closest('[data-c]');
		if (!b || !gridsEl.contains(b)) return null;
		return { i: Number(b.dataset.c), g: Number(b.closest('[data-grid]').dataset.grid) };
	};
	const whoFor = (g) => (isLocal() ? g : mode.type === 'online' ? mySeat : 0);
	const pointers = new Map();
	L.on(gridsEl, 'pointerdown', (e) => {
		if (!mode || phase !== 'play') return;
		const hit = cellAt(e.clientX, e.clientY);
		if (!hit) return;
		e.preventDefault();
		pointers.set(e.pointerId, hit.g);
		paint(hit.i, whoFor(hit.g));
	});
	L.on(gridsEl, 'pointermove', (e) => {
		if (!pointers.has(e.pointerId) || phase !== 'play') return;
		const hit = cellAt(e.clientX, e.clientY);
		if (hit && hit.g === pointers.get(e.pointerId)) paint(hit.i, whoFor(hit.g));
	});
	const up = (e) => pointers.delete(e.pointerId);
	L.on(window, 'pointerup', up);
	L.on(window, 'pointercancel', up);

	/* ---------- bot ---------- */
	const botStep = (dt) => {
		botAcc += dt * BOT[mode.level] * (0.85 + Math.random() * 0.3);
		while (botAcc >= 1) {
			botAcc -= 1;
			const mine = [];
			const human = [];
			const empty = [];
			cells.forEach((c, i) => (c === 1 ? mine.push(i) : c === 0 ? human.push(i) : empty.push(i)));
			let target;
			const steal = { easy: 0.15, medium: 0.5, hard: 0.75 }[mode.level];
			if (human.length && (Math.random() < steal || !empty.length)) target = pick(human);
			else if (empty.length) target = pick(empty);
			if (target !== undefined) {
				lastPaint[1] = -1;
				cells[target] = 1;
				popCell(target);
			}
		}
		paintAll();
	};

	/* ---------- loop ---------- */
	const stopLoop = () => {
		cancelAnimationFrame(raf);
		raf = 0;
		clearInterval(flushT);
		clearInterval(tickT);
		flushT = 0;
		tickT = 0;
	};
	const loop = (now) => {
		const dt = Math.min(0.1, (now - lastFrame) / 1000 || 0);
		lastFrame = now;
		if (mode && mode.type === 'online') t = Date.now() / 1000 + offset - startedAt;
		else t += dt;
		const prev = phase;
		if (t < CD) phase = 'cd';
		else if (t < CD + DUR) phase = 'play';
		else phase = 'end';
		// countdown
		if (phase === 'cd') {
			const n = Math.ceil(CD - t);
			if (n !== lastCd) {
				lastCd = n;
				cdEl.hidden = false;
				cdEl.textContent = fa(n);
				cdEl.classList.remove('pop');
				void cdEl.offsetWidth;
				cdEl.classList.add('pop');
				api.sfx('click');
			}
		} else if (prev === 'cd' && phase === 'play') {
			cdEl.textContent = 'برو!';
			cdEl.classList.remove('pop');
			void cdEl.offsetWidth;
			cdEl.classList.add('pop');
			api.sfx('level');
			setTimeout(() => (cdEl.hidden = true), 600);
			ui.status(isLocal() ? 'هر دو بازیکن رنگ کنید!' : 'رنگ کنید! (بکشید تا سریع‌تر شود)', 'turn');
		}
		if (phase === 'play') {
			if (mode.type === 'ai') botStep(dt);
			const [a, b] = counts();
			const me = mode.type === 'online' ? mySeat : 0;
			const mine = me === 0 ? a : b;
			const theirs = me === 0 ? b : a;
			if (theirs - mine >= 5) trailed = true;
		}
		hud();
		if (phase === 'end' && prev !== 'end') endRound();
		raf = requestAnimationFrame(loop);
	};
	const startLoop = () => {
		cancelAnimationFrame(raf);
		lastFrame = performance.now();
		raf = requestAnimationFrame(loop);
	};

	const resetRoundState = () => {
		t = 0;
		phase = 'cd';
		lastCd = -1;
		botAcc = 0;
		myPaints = 0;
		earlyPaints = 0;
		trailed = false;
		finished = false;
		lastPaint = [-1, -1];
		pointers.clear();
	};

	const startRound = () => {
		resetRoundState();
		build();
		renderPlayers();
		ui.status('آماده باشید…');
		startLoop();
	};

	const renderPlayers = () => {
		if (!mode || mode.type === 'online') return;
		if (mode.type === 'ai')
			ui.players(
				[
					{ name: 'شما', avatar: playerAvatar('me'), sub: NAMES[0], seat: 0 },
					{ name: `ربات ${mode.level === 'easy' ? 'آرام' : mode.level === 'medium' ? 'سریع' : 'برق‌آسا'}`, avatar: playerAvatar({ player: 'cr-bot-' + mode.level, avatar: { style: 'bot', hue: 172 } }), sub: NAMES[1], seat: 1 },
				],
				null,
				series
			);
		else
			ui.players(
				[
					{ name: 'بازیکن ۱', avatar: playerAvatar({ player: 'cr-p1', avatar: { style: 'blocks', hue: 38 } }), sub: NAMES[0], seat: 0 },
					{ name: 'بازیکن ۲', avatar: playerAvatar({ player: 'cr-p2', avatar: { style: 'blocks', hue: 172 } }), sub: NAMES[1], seat: 1 },
				],
				null,
				series
			);
	};

	const endRound = () => {
		if (finished) return;
		finished = true;
		pointers.clear();
		const [a, b] = counts();
		const total = cells.length;
		if (mode.type === 'online') {
			ui.status('زمان تمام شد! در انتظار نتیجه…');
			flush();
			// ask the server to finalise
			clearInterval(tickT);
			tickT = setInterval(() => {
				if (serverOver || !ui.room) return clearInterval(tickT);
				ui.room.move({ cells: [] });
			}, 1000);
			setTimeout(() => ui.room && !serverOver && ui.room.move({ cells: [] }), 700);
			return;
		}
		stopLoop();
		api.sfx('over');
		if (isLocal()) {
			const w = a === b ? 2 : a > b ? 0 : 1;
			series[w]++;
			renderPlayers();
			const title = w === 2 ? 'مساوی!' : `بازیکن ${fa(w + 1)} برد!`;
			ui.status(title, 'win');
			setTimeout(() => ui.result({ title, text: `${fa(a)} خانه کهربایی — ${fa(b)} خانه فیروزه‌ای`, tone: 'win', onPrimary: startRound }), 500);
			return;
		}
		// vs bot
		if (a > b) {
			series[0]++;
			api.unlock('first_win');
			if (a > total * 0.75) api.unlock('domination');
			if (trailed) api.unlock('comeback');
			const pts = Math.min(20000, a * 100 + (a - b) * 50 + { easy: 0, medium: 800, hard: 1600 }[mode.level]);
			ui.status('بردید!', 'win');
			api.sfx('level');
			setTimeout(() => api.over({ score: pts, meta: { mode: mode.level, result: 'win', moves: myPaints, summary: `${fa(a)} خانه در برابر ${fa(b)} — ربات «${mode.level === 'easy' ? 'آرام' : mode.level === 'medium' ? 'سریع' : 'برق‌آسا'}» را بردید.` } }), 700);
		} else {
			series[a === b ? 2 : 1]++;
			renderPlayers();
			ui.status(a === b ? 'مساوی' : 'ربات برد', a === b ? '' : 'loss');
			setTimeout(() => ui.result({ title: a === b ? 'مساوی!' : 'ربات برد', text: `${fa(a)} خانه شما — ${fa(b)} خانه ربات. با کشیدن انگشت سریع‌تر رنگ کنید!`, tone: a === b ? 'accent' : 'loss', onPrimary: startRound }), 600);
		}
	};

	/* ---------- online ---------- */
	const flush = async () => {
		if (!ui.room || !queue.length || inflight.length) return;
		inflight = queue.splice(0, 16);
		const res = await ui.room.move({ cells: inflight });
		if (!res || !res.ok) {
			if (res && (res.code === 'busy' || res.code === 'early')) queue.unshift(...inflight);
		}
		inflight = [];
	};

	const startOnline = (view) => {
		resetRoundState();
		serverOver = false;
		queue = [];
		inflight = [];
		mySeat = view.seat;
		build();
		applyServer(view);
		clearInterval(flushT);
		flushT = setInterval(flush, 250);
		startLoop();
	};

	const applyServer = (view) => {
		const st = view.state;
		if (!st || st.cells === undefined) return;
		offset = (view.now || Date.now() / 1000) - Date.now() / 1000;
		startedAt = Number(st.started_at) || startedAt;
		const pending = new Set([...queue, ...inflight]);
		const str = st.cells;
		for (let i = 0; i < cells.length && i < str.length; i++) {
			const v = str[i] === '0' ? 0 : str[i] === '1' ? 1 : -1;
			if (pending.has(i)) continue;
			if (cells[i] !== v) {
				cells[i] = v;
				if (v === 1 - mySeat) popCell(i);
			}
		}
		paintAll();
	};

	const onlineState = (view) => {
		const st = view.state;
		if (!st || st.cells === undefined) return;
		mySeat = view.seat;
		// rematch → new round
		if (serverOver && !st.over) {
			startOnline(view);
			return;
		}
		applyServer(view);
		if (st.over && !serverOver) {
			serverOver = true;
			stopLoop();
			clearInterval(tickT);
			phase = 'end';
			cdEl.hidden = true;
			const c = st.counts || counts();
			const me = mySeat;
			if (st.winner === me) {
				api.unlock('first_win');
				if (c[me] > cells.length * 0.75) api.unlock('domination');
				if (trailed) api.unlock('comeback');
			}
			ui.status(st.winner === 'draw' ? 'مساوی' : st.winner === me ? 'بردید!' : 'باختید', st.winner === me ? 'win' : st.winner === 'draw' ? '' : 'loss');
			hud();
		}
	};

	build();
	ui.players(defaultPlayers());

	const inst = {
		noAutoPause: true,
		start(opts = {}) {
			stopLoop();
			ui.start(opts);
		},
		pause() {},
		resume() {},
		destroy() {
			stopLoop();
			ro.disconnect();
			L.off();
			ui.destroy();
		},
	};
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
