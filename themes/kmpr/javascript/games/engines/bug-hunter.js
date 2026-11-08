/**
 * «شکارچی باگ» — whack-the-bugs reaction game inside a fake code editor.
 * Bugs crawl out on numbered lines: tap/click them or press the line number (1–9 / ۱–۹).
 * Green «feature» chips must be left alone. Golden bugs pay triple. Escaped bugs break the build.
 */
import { CATALOG } from '../catalog.js';
import { fa, rand, pick, listeners, shuffle } from '../lib/util.js';

export const achievements = CATALOG['bug-hunter'].achievements;

const SNIPPETS = [
	['import { api } from "./client.js";', '', 'export async function loadUser(id) {', '  const res = await api.get(`/users/${id}`);', '  if (!res.ok) throw new Error("404");', '  const user = await res.json();', '  cache.set(id, user);', '  return normalize(user);', '}'],
	['const cart = [];', '', 'function addToCart(item, qty = 1) {', '  const found = cart.find(i => i.id === item.id);', '  if (found) found.qty += qty;', '  else cart.push({ ...item, qty });', '  render(cart);', '  return total(cart);', '}'],
	['add_action("init", function () {', '  register_post_type("game", [', '    "public"       => true,', '    "has_archive"  => "games",', '    "show_in_rest" => true,', '    "supports"     => ["title", "editor"],', '  ]);', '});', '// TODO: flush rewrite rules once'],
	['.card {', '  display: grid;', '  gap: 1rem;', '  padding: 1.5rem;', '  border-radius: 1.5rem;', '  background: rgb(15 23 42 / .8);', '  transition: transform .3s ease;', '}', '.card:hover { transform: translateY(-4px); }'],
	['export default function useDebounce(fn, ms = 300) {', '  let timer;', '  return (...args) => {', '    clearTimeout(timer);', '    timer = setTimeout(() => {', '      fn.apply(this, args);', '    }, ms);', '  };', '}'],
];

const ERRORS = [
	'Uncaught TypeError: undefined is not a function',
	'ReferenceError: user is not defined',
	'Warning: Each child in a list should have a unique "key"',
	'SyntaxError: Unexpected token }',
	'Fatal error: Allowed memory size exhausted',
	'TypeError: Cannot read properties of null',
	'CORS policy: No Access-Control-Allow-Origin',
	'Error: Maximum call stack size exceeded',
];

const BUG_SVG = (gold) => `<svg width="34" height="34" viewBox="0 0 32 32" class="shrink-0 bh-bug__svg" aria-hidden="true">
	<g stroke="${gold ? '#fde68a' : '#fecdd3'}" stroke-width="2" stroke-linecap="round" fill="none">
		<path d="M10 12 5 9M10 17H4M10 22l-5 3M22 12l5-3M22 17h6M22 22l5 3M13 7l-2-3M19 7l2-3"/>
	</g>
	<ellipse cx="16" cy="18" rx="7" ry="9" fill="${gold ? '#f59e0b' : '#e11d48'}"/>
	<circle cx="16" cy="9" r="4.5" fill="${gold ? '#fbbf24' : '#be123c'}"/>
	<path d="M16 10v17" stroke="${gold ? '#92400e' : '#881337'}" stroke-width="1.5"/>
	<circle cx="12.5" cy="15" r="1.6" fill="${gold ? '#78350f' : '#4c0519'}"/><circle cx="19.5" cy="20" r="1.6" fill="${gold ? '#78350f' : '#4c0519'}"/>
</svg>`;
const FEATURE_SVG = `<svg width="30" height="30" viewBox="0 0 24 24" class="shrink-0" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="6" fill="#059669"/><path d="m7 12.5 3.2 3.2L17 9" stroke="#ecfdf5" stroke-width="2.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

const KEY_LINE = { '1': 0, '2': 1, '3': 2, '4': 3, '5': 4, '6': 5, '7': 6, '8': 7, '9': 8, '۱': 0, '۲': 1, '۳': 2, '۴': 3, '۵': 4, '۶': 5, '۷': 6, '۸': 7, '۹': 8 };

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '4/5');
	const L = listeners();
	stage.innerHTML = `
	<div class="bh absolute inset-0 flex flex-col bg-[#0b1020] text-slate-200 select-none overflow-hidden" data-bh>
		<div class="flex items-center gap-3 px-4 h-10 shrink-0 border-b border-white/10 bg-slate-900/90">
			<span class="flex gap-1.5" aria-hidden="true"><i class="size-3 rounded-full bg-rose-500/80"></i><i class="size-3 rounded-full bg-amber-400/80"></i><i class="size-3 rounded-full bg-emerald-400/80"></i></span>
			<span class="font-mono text-xs text-slate-300 truncate" dir="ltr" data-bh-file>src/app.js</span>
			<span class="ms-auto text-xs font-bold text-slate-400 whitespace-nowrap" data-bh-hint>شماره خط یا ضربه روی باگ</span>
		</div>
		<ol class="bh-lines relative flex-1 min-h-0 grid grid-rows-9" dir="ltr" data-bh-lines></ol>
		<div class="shrink-0 border-t border-white/10 bg-slate-950/95">
			<div class="h-7 px-4 flex items-center gap-2 text-xs font-mono text-rose-300/90 truncate" dir="ltr" data-bh-console aria-live="polite">&gt; ready</div>
			<div class="h-9 px-4 flex items-center gap-4 text-sm font-bold" data-bh-status>
				<span class="inline-flex items-center gap-1.5 whitespace-nowrap" data-bh-build><i class="size-2.5 rounded-full bg-emerald-400"></i><span>بیلد سالم</span></span>
				<span class="text-slate-400 whitespace-nowrap">نسخه <b class="text-slate-100" data-bh-level>۱</b></span>
				<span class="text-slate-400 whitespace-nowrap ms-auto">زنجیره <b class="text-amber-300" data-bh-combo>×۱</b></span>
			</div>
		</div>
	</div>`;
	const root = stage.querySelector('[data-bh]');
	const linesEl = root.querySelector('[data-bh-lines]');
	const consoleEl = root.querySelector('[data-bh-console]');
	const buildEl = root.querySelector('[data-bh-build]');
	const levelEl = root.querySelector('[data-bh-level]');
	const comboEl = root.querySelector('[data-bh-combo]');
	const fileEl = root.querySelector('[data-bh-file]');

	const rows = [];
	for (let i = 0; i < 9; i++) {
		const li = document.createElement('li');
		li.className = 'bh-line relative flex items-center min-h-0 border-b border-white/[0.03]';
		li.innerHTML = `<span class="bh-gutter w-10 sm:w-12 shrink-0 h-full flex items-center justify-center font-mono text-sm text-slate-500 border-e border-white/5">${i + 1}</span><code class="bh-code flex-1 min-w-0 px-3 font-mono text-[12px] sm:text-sm whitespace-pre overflow-hidden text-ellipsis"></code>`;
		linesEl.appendChild(li);
		rows.push({ li, code: li.querySelector('code'), item: null });
	}

	const highlight = (line) =>
		line
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/("[^"]*"|`[^`]*`)/g, '<span class="text-emerald-300">$1</span>')
			.replace(/\b(const|let|return|function|export|default|import|from|async|await|if|else|throw|new|true)\b/g, '<span class="text-violet-300">$1</span>')
			.replace(/(\/\/.*)$/g, '<span class="text-slate-500">$1</span>');

	const setSnippet = (n) => {
		const snip = SNIPPETS[n % SNIPPETS.length];
		fileEl.textContent = ['src/api/user.js', 'src/cart.js', 'inc/games.php', 'css/card.css', 'hooks/useDebounce.js'][n % SNIPPETS.length];
		rows.forEach((r, i) => (r.code.innerHTML = highlight(snip[i] || '')));
	};
	setSnippet(0);

	let s = null;
	let raf = 0;
	let last = 0;
	let looping = false;

	const newRun = () => ({ t: 0, score: 0, lives: 3, level: 1, squashed: 0, combo: 0, maxCombo: 0, spawn: 0.8, featuresLeft: 0, golden: 0, escaped: 0, over: false });

	const mult = () => Math.min(2.5, 1 + Math.floor(s.combo / 5) * 0.5);
	const ttl = () => Math.max(0.95, 2.6 - (s.level - 1) * 0.17);
	const maxItems = () => Math.min(6, 1 + Math.floor((s.level + 1) / 2));

	const setCombo = () => (comboEl.textContent = '×' + mult().toLocaleString('fa-IR'));
	const log = (msg, tone = 'err') => {
		consoleEl.textContent = '> ' + msg;
		consoleEl.className = consoleEl.className.replace(/text-\S+-300\/90/, tone === 'err' ? 'text-rose-300/90' : 'text-emerald-300/90');
	};
	const setBuild = () => {
		const ok = s.lives >= 3;
		const warn = s.lives === 2;
		buildEl.innerHTML = `<i class="size-2.5 rounded-full ${ok ? 'bg-emerald-400' : warn ? 'bg-amber-400' : 'bg-rose-500 animate-pulse'}"></i><span>${ok ? 'بیلد سالم' : warn ? 'هشدار در بیلد' : 'بیلد در خطر!'}</span>`;
	};

	const addScore = (v) => {
		// Safety: never exceed a sane points/second rate (server rejects > 600/s).
		s.score = Math.min(s.score + v, Math.floor(Math.max(4, s.t) * 520));
		api.setScore(s.score);
		if (s.score >= 5000) api.unlock('s5k');
	};

	const floatText = (row, x, text, cls) => {
		const f = document.createElement('span');
		f.className = `bh-float absolute pointer-events-none font-black text-base ${cls}`;
		f.style.left = x + '%';
		f.textContent = text;
		row.li.appendChild(f);
		setTimeout(() => f.remove(), 800);
	};

	const splat = (row, x, color) => {
		const n = api.reducedMotion ? 4 : 10;
		for (let i = 0; i < n; i++) {
			const p = document.createElement('i');
			p.className = 'bh-particle absolute pointer-events-none rounded-full';
			p.style.left = x + '%';
			p.style.background = color;
			const a = rand(0, Math.PI * 2);
			const d = rand(18, 46);
			p.style.setProperty('--dx', Math.cos(a) * d + 'px');
			p.style.setProperty('--dy', Math.sin(a) * d + 'px');
			row.li.appendChild(p);
			setTimeout(() => p.remove(), 600);
		}
	};

	const shake = () => {
		if (api.reducedMotion) return;
		root.classList.remove('bh-shake');
		void root.offsetWidth;
		root.classList.add('bh-shake');
	};

	const loseLife = (reason) => {
		s.lives--;
		s.combo = 0;
		setCombo();
		api.hud({ lives: Math.max(0, s.lives) });
		setBuild();
		shake();
		api.sfx('hurt');
		if (navigator.vibrate) navigator.vibrate(60);
		log(reason);
		if (s.lives <= 0) end();
	};

	const removeItem = (row) => {
		if (!row.item) return;
		const el = row.item.el;
		el.classList.add('is-out');
		setTimeout(() => el.remove(), 220);
		row.item = null;
		row.li.classList.remove('has-bug');
	};

	const spawn = () => {
		const free = rows.filter((r) => !r.item);
		if (!free.length) return;
		const row = pick(free);
		const roll = Math.random();
		const type = s.level >= 2 && roll < 0.18 ? 'feature' : roll > 0.94 ? 'golden' : 'bug';
		const x = rand(28, 82);
		const el = document.createElement('button');
		el.type = 'button';
		el.className = `bh-item bh-item--${type} absolute top-1/2 flex items-center justify-center size-11 rounded-full`;
		el.style.left = x + '%';
		el.setAttribute('aria-label', type === 'feature' ? 'فیچر — دست نزنید' : type === 'golden' ? 'باگ طلایی' : 'باگ');
		el.innerHTML = type === 'feature' ? FEATURE_SVG : BUG_SVG(type === 'golden');
		const life = type === 'golden' ? ttl() * 0.75 : type === 'feature' ? ttl() * 1.1 : ttl();
		el.style.setProperty('--ttl', life + 's');
		row.li.appendChild(el);
		row.li.classList.toggle('has-bug', type !== 'feature');
		row.item = { type, el, x, age: 0, life };
		if (type !== 'feature') api.sfx([['square', 220 + rand(0, 80), 300, 0.04, 0.015]]);
	};

	const hit = (row) => {
		if (!s || s.over || !api.running) return;
		const it = row.item;
		if (!it) {
			// Miss: breaks the combo, no life lost.
			if (s.combo > 0) {
				s.combo = 0;
				setCombo();
				api.sfx('wrong');
			}
			return;
		}
		if (it.type === 'feature') {
			removeItem(row);
			loseLife('It’s not a bug, it’s a feature! — feature removed by mistake');
			return;
		}
		const speed = 1 - it.age / it.life; // 1 = instant
		s.combo++;
		s.maxCombo = Math.max(s.maxCombo, s.combo);
		const base = 20 + 5 * s.level + Math.round(20 * speed);
		let pts = Math.round(base * mult());
		if (it.type === 'golden') {
			pts *= 3;
			api.unlock('golden');
		}
		addScore(pts);
		floatText(row, it.x, '+' + fa(pts), it.type === 'golden' ? 'text-amber-300' : 'text-emerald-300');
		splat(row, it.x, it.type === 'golden' ? '#fbbf24' : '#fb7185');
		row.li.classList.add('bh-flash');
		setTimeout(() => row.li.classList.remove('bh-flash'), 180);
		api.sfx(it.type === 'golden' ? 'coin' : 'hit');
		removeItem(row);
		s.squashed++;
		api.unlock('first_squash');
		if (s.combo >= 10) api.unlock('combo10');
		setCombo();
		log(`fixed line ${rows.indexOf(row) + 1} ✓  (${s.squashed} bugs squashed)`, 'ok');
		if (s.squashed % 10 === 0) levelUp();
	};

	const levelUp = () => {
		s.level++;
		levelEl.textContent = fa(s.level);
		api.hud({ level: s.level });
		api.sfx('level');
		setSnippet(s.level - 1);
		api.toast(`<span class="game-toast__icon" aria-hidden="true">🚀</span><span><b>نسخه ${fa(s.level)}</b> باگ‌ها سریع‌تر شدند</span>`);
		if (s.level >= 5) api.unlock('level5');
	};

	const tick = (dt) => {
		s.t += dt;
		s.spawn -= dt;
		const active = rows.filter((r) => r.item).length;
		if (s.spawn <= 0 && active < maxItems()) {
			spawn();
			s.spawn = Math.max(0.33, 1.05 - (s.level - 1) * 0.08) * rand(0.7, 1.25);
		}
		for (const row of rows) {
			const it = row.item;
			if (!it) continue;
			it.age += dt;
			it.el.style.setProperty('--age', (it.age / it.life).toFixed(3));
			if (it.age >= it.life) {
				if (it.type === 'feature') {
					s.featuresLeft++;
					if (s.featuresLeft >= 5) api.unlock('feature');
					removeItem(row);
				} else {
					removeItem(row);
					s.escaped++;
					loseLife(pick(ERRORS) + `  (line ${rows.indexOf(row) + 1})`);
					if (s.over) return;
				}
			}
		}
	};

	const frame = (t) => {
		if (!looping) return;
		const dt = Math.min(0.05, (t - last) / 1000);
		last = t;
		if (s && !s.over) tick(dt);
		raf = requestAnimationFrame(frame);
	};
	const startLoop = () => {
		if (looping) return;
		looping = true;
		last = performance.now();
		raf = requestAnimationFrame(frame);
	};
	const stopLoop = () => {
		looping = false;
		cancelAnimationFrame(raf);
		root.classList.add('is-paused');
	};

	const end = () => {
		s.over = true;
		stopLoop();
		rows.forEach(removeItem);
		buildEl.innerHTML = '<i class="size-2.5 rounded-full bg-rose-500"></i><span>بیلد شکست خورد</span>';
		api.over({
			score: s.score,
			meta: { level: s.level, combo: s.maxCombo, summary: `${fa(s.squashed)} باگ را له کردید و تا نسخه ${fa(s.level)} پیش رفتید.` },
		});
	};

	// Input
	rows.forEach((row) => {
		L.on(row.li, 'pointerdown', (e) => {
			if (!api.running) return;
			e.preventDefault();
			// A tap must land near the item (generous on touch); elsewhere on the line = miss.
			if (row.item) {
				const r = row.item.el.getBoundingClientRect();
				const reach = Math.max(api.touch ? 46 : 34, r.width * 0.8);
				if (Math.abs(e.clientX - (r.left + r.width / 2)) > reach) {
					hit({ item: null });
					return;
				}
			}
			hit(row);
		});
	});
	L.on(window, 'keydown', (e) => {
		if (!api.running || !s || e.target.closest?.('input, textarea')) return;
		const i = KEY_LINE[e.key] ?? KEY_LINE[e.code?.replace(/^(Digit|Numpad)/, '')];
		if (i === undefined) return;
		e.preventDefault();
		const row = rows[i];
		row.li.classList.add('bh-key');
		setTimeout(() => row.li.classList.remove('bh-key'), 140);
		hit(row);
	});

	const inst = {
		start() {
			rows.forEach((r) => {
				if (r.item) r.item.el.remove();
				r.item = null;
				r.li.classList.remove('has-bug');
			});
			s = newRun();
			setSnippet(0);
			levelEl.textContent = fa(1);
			setCombo();
			setBuild();
			log('npm run dev — watching for bugs…', 'ok');
			api.hud({ level: 1, levelLabel: 'نسخه', lives: 3 });
			api.setScore(0);
			root.classList.remove('is-paused');
			looping = false;
			startLoop();
		},
		pause() {
			stopLoop();
		},
		resume() {
			if (!s || s.over) return;
			root.classList.remove('is-paused');
			startLoop();
		},
		destroy() {
			stopLoop();
			L.off();
			stage.innerHTML = '';
		},
	};
	// Attract frame: a couple of idle bugs (decorative).
	shuffle(rows).slice(0, 2).forEach((r, k) => {
		const el = document.createElement('span');
		el.className = 'bh-item bh-item--idle absolute top-1/2 size-11 flex items-center justify-center';
		el.style.left = [35, 70][k] + '%';
		el.innerHTML = BUG_SVG(k === 1);
		el.dataset.idle = '1';
		r.li.appendChild(el);
	});
	const clearIdle = inst.start;
	inst.start = () => {
		root.querySelectorAll('[data-idle]').forEach((e) => e.remove());
		clearIdle();
	};
	current = inst;
	return inst;
}

export function destroy() {
	current && current.destroy();
	current = null;
}

