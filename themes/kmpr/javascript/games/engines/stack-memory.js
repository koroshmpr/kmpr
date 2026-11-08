/**
 * «حافظه استک» — memory pairs for developers.
 * Levels 1–2: identical tech pairs. Level 3+: match a tool with its file extension / command.
 * Countdown per level; leftover time becomes bonus; the run ends when the clock hits zero.
 */
import { CATALOG } from '../catalog.js';
import { fa, esc, listeners, shuffle } from '../lib/util.js';

export const achievements = CATALOG['stack-memory'].achievements;

// [name, monogram, color, extension/command]
const TECH = [
	['JavaScript', 'JS', '#f7df1e', '.js'],
	['TypeScript', 'TS', '#3178c6', '.ts'],
	['Python', 'Py', '#3776ab', '.py'],
	['PHP', 'php', '#777bb4', '.php'],
	['HTML', '<>', '#e34f26', '<html>'],
	['CSS', '{}', '#2965f1', '.css'],
	['Sass', 'Ss', '#cd6799', '.scss'],
	['Git', 'Git', '#f05032', 'git commit'],
	['Node.js', 'N', '#5fa04e', 'npm install'],
	['React', 'Re', '#61dafb', '.jsx'],
	['Vue', 'V', '#42b883', '.vue'],
	['Docker', 'Dk', '#2496ed', 'Dockerfile'],
	['SQL', 'SQL', '#e38c00', 'SELECT *'],
	['Markdown', 'MD', '#94a3b8', '.md'],
	['JSON', '{ }', '#a3e635', '.json'],
	['WordPress', 'WP', '#21759b', 'wp-config.php'],
	['Tailwind', 'tw', '#38bdf8', '@apply'],
	['Linux', '$_', '#fcc624', 'sudo'],
	['Go', 'Go', '#00add8', '.go'],
	['Rust', 'Rs', '#dea584', '.rs'],
];

const LEVELS = [
	{ pairs: 6, mode: 'same' },
	{ pairs: 8, mode: 'same' },
	{ pairs: 8, mode: 'ext' },
	{ pairs: 10, mode: 'ext' },
	{ pairs: 12, mode: 'ext' },
];

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '3/4');
	const L = listeners();
	const reduced = api.reducedMotion;
	stage.innerHTML = `
	<div class="sm absolute inset-0 flex flex-col gap-3 p-3 sm:p-5 bg-[radial-gradient(ellipse_at_bottom,rgba(56,189,248,.10),transparent_60%)] bg-slate-950 text-slate-200${reduced ? ' sm-reduced' : ''}" data-sm>
		<div class="flex items-center gap-3">
			<p class="text-sm sm:text-base font-black text-white whitespace-nowrap" data-sm-title>مرحله ۱</p>
			<p class="text-xs sm:text-sm text-slate-400 font-bold truncate" data-sm-sub>جفت‌های یکسان را پیدا کنید</p>
			<p class="ms-auto text-lg sm:text-xl font-black tabular-nums text-white whitespace-nowrap" data-sm-time aria-label="زمان باقی‌مانده">۰</p>
		</div>
		<div class="h-1.5 rounded-full bg-white/10 overflow-hidden"><div class="h-full rounded-full bg-sky-400 origin-right transition-[background-color]" style="transform:scaleX(1)" data-sm-bar></div></div>
		<div class="sm-grid flex-1 min-h-0 grid gap-2 sm:gap-3" role="grid" aria-label="کارت‌های حافظه" data-sm-grid></div>
	</div>`;
	const $ = (q) => stage.querySelector(q);
	const grid = $('[data-sm-grid]');
	const titleEl = $('[data-sm-title]');
	const subEl = $('[data-sm-sub]');
	const timeEl = $('[data-sm-time]');
	const bar = $('[data-sm-bar]');

	let s = null;
	let cards = [];
	let cols = 4;
	let raf = 0;
	let last = 0;
	let looping = false;

	const isMobile = () => stage.clientWidth < 560;
	const layout = () => {
		const n = cards.length || 12;
		const maxCols = isMobile() ? 4 : n >= 24 ? 6 : n >= 20 ? 5 : 4;
		cols = Math.min(maxCols, n);
		const rows = Math.ceil(n / cols);
		grid.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
		grid.style.gridTemplateRows = `repeat(${rows}, minmax(0, 1fr))`;
		grid.classList.toggle('is-dense', rows >= 5);
	};
	const ro = new ResizeObserver(layout);
	ro.observe(stage);

	const levelConf = (lv) => LEVELS[Math.min(lv - 1, LEVELS.length - 1)];
	const levelTime = (lv) => {
		const c = levelConf(lv);
		return Math.round(c.pairs * Math.max(3.2, 6.2 - lv * 0.45) + 8);
	};

	const face = (c) => {
		if (c.kind === 'ext') {
			return `<span class="sm-ext font-mono font-black text-sm sm:text-lg text-white break-all" dir="ltr">${esc(c.t[3])}</span><span class="text-xs text-slate-400 font-bold">پسوند / دستور</span>`;
		}
		return `<span class="sm-mono shrink-0 inline-flex items-center justify-center rounded-xl font-black text-sm sm:text-base" dir="ltr" style="background:${c.t[2]}22;color:${c.t[2]};border:1px solid ${c.t[2]}55">${esc(c.t[1])}</span><span class="font-black text-sm sm:text-base text-white truncate max-w-full" dir="ltr">${esc(c.t[0])}</span>`;
	};

	const buildLevel = (lv) => {
		const conf = levelConf(lv);
		const techs = shuffle(TECH).slice(0, conf.pairs);
		const deck = [];
		techs.forEach((t, i) => {
			deck.push({ pair: i, t, kind: 'name' });
			deck.push({ pair: i, t, kind: conf.mode === 'ext' ? 'ext' : 'name' });
		});
		cards = shuffle(deck).map((c, idx) => ({ ...c, idx, open: false, done: false, el: null }));
		grid.innerHTML = '';
		cards.forEach((c, idx) => {
			const b = document.createElement('button');
			b.type = 'button';
			b.className = 'sm-card relative min-h-0 min-w-0 rounded-2xl';
			b.setAttribute('role', 'gridcell');
			b.setAttribute('aria-label', `کارت ${fa(idx + 1)}`);
			b.tabIndex = idx === 0 ? 0 : -1;
			b.innerHTML = `<span class="sm-inner absolute inset-0">
				<span class="sm-back absolute inset-0 rounded-2xl border border-white/10 flex items-center justify-center" aria-hidden="true"><span class="font-mono font-black text-slate-500 text-base sm:text-xl" dir="ltr">&lt;/&gt;</span></span>
				<span class="sm-front absolute inset-0 rounded-2xl border flex flex-col items-center justify-center gap-1.5 p-2 text-center" style="--c:${c.t[2]}">${face(c)}</span>
			</span>`;
			c.el = b;
			grid.appendChild(b);
		});
		layout();
		titleEl.textContent = 'مرحله ' + fa(lv);
		subEl.textContent = conf.mode === 'ext' ? 'هر ابزار را به پسوند یا دستورش وصل کنید' : 'جفت‌های یکسان را پیدا کنید';
	};

	const setOpen = (c, on) => {
		c.open = on;
		c.el.classList.toggle('is-open', on);
		c.el.setAttribute('aria-label', on || c.done ? `${c.kind === 'ext' ? c.t[3] : c.t[0]}` : `کارت ${fa(c.idx + 1)}`);
	};

	const updateTime = () => {
		const left = Math.max(0, s.left);
		timeEl.textContent = fa(Math.ceil(left));
		const k = left / s.total;
		bar.style.transform = `scaleX(${k})`;
		bar.style.backgroundColor = k < 0.25 ? '#fb7185' : k < 0.5 ? '#f59e0b' : '#38bdf8';
	};

	const addScore = (v) => {
		s.score = Math.min(s.score + v, Math.floor(Math.max(5, s.t) * 380));
		api.setScore(s.score);
		if (s.score >= 5000) api.unlock('s5k');
	};

	const startLevel = (lv) => {
		s.level = lv;
		s.mistakes = 0;
		s.first = null;
		s.lock = true;
		s.total = levelTime(lv);
		s.left = s.total;
		s.found = 0;
		buildLevel(lv);
		updateTime();
		api.hud({ level: lv, levelLabel: 'مرحله' });
		if (lv >= 5) api.unlock('level5');
		// Peek
		s.peek = Math.max(0.7, 1.8 - lv * 0.2);
		cards.forEach((c) => setOpen(c, true));
		grid.classList.add('is-peek');
	};

	const endPeek = () => {
		cards.forEach((c) => setOpen(c, false));
		grid.classList.remove('is-peek');
		s.lock = false;
		s.peek = 0;
		api.sfx('flip');
	};

	const flip = (c) => {
		if (!s || s.over || s.lock || !api.running || c.done || c.open) return;
		setOpen(c, true);
		api.sfx('flip');
		if (!s.first) {
			s.first = c;
			return;
		}
		const a = s.first;
		s.first = null;
		s.moves++;
		if (a.pair === c.pair) {
			a.done = c.done = true;
			a.el.classList.add('is-done');
			c.el.classList.add('is-done');
			s.combo++;
			s.found++;
			s.maxCombo = Math.max(s.maxCombo, s.combo);
			api.unlock('first_pair');
			if (s.combo >= 4) api.unlock('combo4');
			addScore(40 + 10 * s.level + Math.min(4, s.combo - 1) * 20);
			api.sfx('coin');
			if (s.found === cards.length / 2) levelClear();
		} else {
			s.combo = 0;
			s.mistakes++;
			s.lock = true;
			a.el.classList.add('is-wrong');
			c.el.classList.add('is-wrong');
			api.sfx('wrong');
			s.unflip = { t: 0.75, a, c };
		}
	};

	const levelClear = () => {
		const bonus = Math.round(s.left * 10) + (s.mistakes === 0 ? 300 : 0);
		if (s.mistakes === 0) api.unlock('perfect');
		if (s.left > s.total / 2) api.unlock('speed');
		addScore(bonus);
		api.sfx('level');
		api.toast(`<span class="game-toast__icon" aria-hidden="true">🧩</span><span><b>مرحله ${fa(s.level)} تمام شد</b> پاداش زمان: ${fa(bonus)}</span>`);
		s.lock = true;
		s.between = 1.1;
	};

	const tick = (dt) => {
		s.t += dt;
		if (s.peek > 0) {
			s.peek -= dt;
			if (s.peek <= 0) endPeek();
			return;
		}
		if (s.between > 0) {
			s.between -= dt;
			if (s.between <= 0) startLevel(s.level + 1);
			return;
		}
		if (s.unflip) {
			s.unflip.t -= dt;
			if (s.unflip.t <= 0) {
				const { a, c } = s.unflip;
				a.el.classList.remove('is-wrong');
				c.el.classList.remove('is-wrong');
				setOpen(a, false);
				setOpen(c, false);
				s.unflip = null;
				s.lock = false;
			}
		}
		s.left -= dt;
		updateTime();
		if (s.left <= 0) end();
	};

	const end = () => {
		s.over = true;
		stopLoop();
		cards.forEach((c) => setOpen(c, true));
		api.over({ score: s.score, meta: { level: s.level, moves: s.moves, combo: s.maxCombo, summary: `تا مرحله ${fa(s.level)} رسیدید و در مجموع ${fa(s.moves)} حرکت انجام دادید.` } });
	};

	const frame = (t) => {
		if (!looping) return;
		const dt = Math.min(0.05, (t - last) / 1000);
		last = t;
		if (s && !s.over) tick(dt);
		if (looping) raf = requestAnimationFrame(frame);
	};
	const startLoop = () => {
		if (looping) return;
		looping = true;
		last = performance.now();
		raf = requestAnimationFrame(frame);
	};
	function stopLoop() {
		looping = false;
		cancelAnimationFrame(raf);
	}

	// Input: click + roving-tabindex arrow navigation (RTL aware).
	L.on(grid, 'click', (e) => {
		const b = e.target.closest('.sm-card');
		if (!b) return;
		const c = cards.find((x) => x.el === b);
		if (c) flip(c);
	});
	L.on(grid, 'keydown', (e) => {
		const b = e.target.closest('.sm-card');
		if (!b) return;
		const i = cards.findIndex((x) => x.el === b);
		const rtl = getComputedStyle(grid).direction === 'rtl';
		const step = { ArrowLeft: rtl ? 1 : -1, ArrowRight: rtl ? -1 : 1, ArrowUp: -cols, ArrowDown: cols }[e.key];
		if (step === undefined) return;
		e.preventDefault();
		const j = Math.max(0, Math.min(cards.length - 1, i + step));
		cards.forEach((c) => (c.el.tabIndex = -1));
		cards[j].el.tabIndex = 0;
		cards[j].el.focus({ preventScroll: true });
	});

	// Attract frame
	s = { level: 1 };
	buildLevel(1);
	cards.slice(0, 3).forEach((c) => setOpen(c, true));
	timeEl.textContent = fa(levelTime(1));
	s = null;

	const inst = {
		start() {
			s = { t: 0, score: 0, level: 1, moves: 0, combo: 0, maxCombo: 0, over: false, peek: 0, between: 0, unflip: null };
			api.setScore(0);
			startLevel(1);
			startLoop();
			if (!api.touch) setTimeout(() => cards[0] && cards[0].el.focus({ preventScroll: true }), 50);
		},
		pause() {
			stopLoop();
		},
		resume() {
			if (s && !s.over) startLoop();
		},
		destroy() {
			stopLoop();
			ro.disconnect();
			L.off();
			stage.innerHTML = '';
		},
	};
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}
