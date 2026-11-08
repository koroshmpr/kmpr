/**
 * «تایپ کد» — 60-second developer typing test (JS snippets, Persian sentences, or mixed).
 * A real (transparent) <input> captures typing so mobile keyboards and Persian IMEs work.
 * Score = round(netWPM × accuracy² × 10), capped at 3000.
 */
import { CATALOG } from '../catalog.js';
import { fa, esc, listeners, shuffle } from '../lib/util.js';

export const achievements = CATALOG['type-code'].achievements;

const DURATION = 60;

const JS = [
	['let x = 42;', "const name = 'kmpr';", 'count += 1;', 'return true;', 'list.push(item);', 'el.hidden = false;'],
	['const sum = (a, b) => a + b;', 'if (user) greet(user.name);', "document.querySelector('#app');", 'for (const p of posts) render(p);', 'export default function App() {}'],
	["const res = await fetch('/wp-json/kmpr/v1/games');", 'items.filter((i) => i.done).map((i) => i.id);', "btn.addEventListener('click', (e) => e.preventDefault());", 'const { data, error } = await api.get(url);', 'localStorage.setItem(key, JSON.stringify(value));'],
	['Object.entries(scores).sort(([, a], [, b]) => b - a).slice(0, 10);', 'const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };', 'new IntersectionObserver((entries) => entries.forEach((e) => e.isIntersecting && show(e.target)));', "wp_enqueue_script('kmpr-games', $src, [], $ver, ['strategy' => 'defer']);"],
];

const FA = [
	['کد تمیز بنویس', 'سایت سریع است', 'طراحی وب', 'رنگ و فونت', 'دکمه را بزن', 'سلام دنیا'],
	['هر روز یک خط کد بهتر از دیروز', 'سرعت سایت روی سئو اثر دارد', 'اول موبایل را طراحی کن', 'کاربر حوصله صبر کردن ندارد'],
	['اول مسئله را بفهم و بعد کد بنویس', 'کاربر باید در سه ثانیه بفهمد سایت درباره چیست', 'تست نوشتن یعنی خواب راحت در شب انتشار', 'یک دکمه خوب از صد پاراگراف بهتر است'],
	['طراحی خوب دیده نمی شود اما وقتی نباشد همه متوجه نبودنش می شوند', 'سئو یعنی ساختار درست و محتوای مفید برای آدم ها نه فقط برای ربات ها', 'هر کامپوننت باید یک کار را انجام دهد و آن را خیلی خوب انجام دهد'],
];

const MODES = [
	{ id: 'js', label: 'JS' },
	{ id: 'fa', label: 'فارسی' },
	{ id: 'mix', label: 'ترکیبی' },
];

const norm = (s) =>
	s
		.replace(/ي/g, 'ی')
		.replace(/ك/g, 'ک')
		.replace(/[‌ ]/g, ' ')
		.replace(/[ً-ْ]/g, '');

let current = null;

export function mount(stage, api) {
	api.setRatio('16/10', '4/5');
	const L = listeners();
	stage.innerHTML = `
	<div class="tc absolute inset-0 flex flex-col gap-3 sm:gap-4 p-4 sm:p-6 bg-[radial-gradient(ellipse_at_top,rgba(99,102,241,.12),transparent_60%)] bg-slate-950 text-slate-200" data-tc>
		<div class="flex items-center justify-between gap-3 flex-wrap">
			<div class="flex gap-2" role="radiogroup" aria-label="نوع متن" data-tc-modes>
				${MODES.map((m) => `<button type="button" role="radio" data-mode="${m.id}" class="tc-chip min-h-11 px-4 rounded-full border border-white/10 bg-white/[0.04] text-sm font-black whitespace-nowrap transition-colors">${m.label}</button>`).join('')}
			</div>
			<div class="flex items-center gap-4 sm:gap-6" aria-live="off">
				<div class="text-center"><p class="text-xs text-slate-400 font-bold">کلمه/دقیقه</p><p class="text-2xl sm:text-3xl font-black text-white tabular-nums" data-tc-wpm>۰</p></div>
				<div class="text-center"><p class="text-xs text-slate-400 font-bold">دقت</p><p class="text-2xl sm:text-3xl font-black text-white tabular-nums" data-tc-acc>۱۰۰٪</p></div>
				<div class="relative size-14 sm:size-16 shrink-0" aria-label="زمان باقی‌مانده">
					<svg width="64" height="64" viewBox="0 0 64 64" class="shrink-0 size-full -rotate-90" aria-hidden="true"><circle cx="32" cy="32" r="28" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="5"/><circle cx="32" cy="32" r="28" fill="none" stroke="#f59e0b" stroke-width="5" stroke-linecap="round" stroke-dasharray="175.9" stroke-dashoffset="0" data-tc-ring/></svg>
					<span class="absolute inset-0 flex items-center justify-center text-lg font-black text-white tabular-nums" data-tc-time>۶۰</span>
				</div>
			</div>
		</div>
		<div class="tc-card relative flex-1 min-h-0 rounded-3xl border border-white/10 bg-slate-900/70 p-5 sm:p-8 flex flex-col justify-center gap-5 overflow-hidden cursor-text" data-tc-card>
			<p class="tc-target text-xl sm:text-2xl lg:text-3xl leading-loose break-words" data-tc-target></p>
			<p class="tc-next text-sm sm:text-base text-slate-500 truncate" data-tc-next></p>
			<input type="text" class="tc-input absolute inset-0 w-full h-full opacity-0 text-base" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" enterkeyhint="next" aria-label="محل تایپ" data-tc-input>
		</div>
		<div class="flex items-center justify-between gap-3 text-sm">
			<p class="text-slate-400 font-bold" data-tc-hint>شروع به تایپ کنید؛ زمان با اولین کلید شروع می‌شود.</p>
			<p class="text-slate-400 font-bold whitespace-nowrap">قطعه‌ها: <b class="text-white" data-tc-lines>۰</b></p>
		</div>
	</div>`;

	const $ = (s) => stage.querySelector(s);
	const card = $('[data-tc-card]');
	const targetEl = $('[data-tc-target]');
	const nextEl = $('[data-tc-next]');
	const input = $('[data-tc-input]');
	const wpmEl = $('[data-tc-wpm]');
	const accEl = $('[data-tc-acc]');
	const timeEl = $('[data-tc-time]');
	const ring = $('[data-tc-ring]');
	const linesEl = $('[data-tc-lines]');
	const hintEl = $('[data-tc-hint]');
	const chips = [...stage.querySelectorAll('[data-mode]')];

	let mode = 'mix';
	let s = null;
	let raf = 0;
	let last = 0;
	let looping = false;
	const pools = {};

	const newRun = () => ({ started: false, over: false, t: 0, total: 0, correct: 0, errors: 0, committed: 0, lines: 0, lineErr: 0, prev: '', target: null, next: null, n: 0 });

	const draw = (m, tier) => {
		const src = m === 'js' ? JS : FA;
		const key = m + tier;
		if (!pools[key] || !pools[key].length) pools[key] = shuffle(src[Math.min(tier, src.length - 1)]);
		return { text: norm(pools[key].pop()), lang: m };
	};
	const nextSnippet = () => {
		const tier = Math.min(3, Math.floor(s.n / 3));
		const lang = mode === 'mix' ? (s.n % 2 ? 'fa' : 'js') : mode;
		s.n++;
		return draw(lang, tier);
	};

	const setDir = (el, lang) => {
		el.dir = lang === 'js' ? 'ltr' : 'rtl';
		el.classList.toggle('font-mono', lang === 'js');
		el.classList.toggle('tc-fa', lang !== 'js');
	};

	const render = () => {
		const tgt = s.target.text;
		const v = norm(input.value);
		// Merge consecutive chars with the same state into one span (keeps Persian letters joined).
		const runs = [];
		for (let i = 0; i < tgt.length; i++) {
			const st = i < v.length ? (v[i] === tgt[i] ? 'ok' : 'bad') : i === v.length ? 'cur' : 'todo';
			const lastRun = runs[runs.length - 1];
			if (lastRun && lastRun.st === st && st !== 'cur') lastRun.t += tgt[i];
			else runs.push({ st, t: tgt[i] });
		}
		targetEl.innerHTML = runs.map((r) => `<span class="tc-${r.st}">${esc(r.t)}</span>`).join('') + (v.length >= tgt.length ? '<span class="tc-cur tc-end"> </span>' : '');
		setDir(targetEl, s.target.lang);
		setDir(nextEl, s.next.lang);
		nextEl.textContent = s.next.text;
	};

	const stats = () => {
		const tgt = s.target.text;
		const v = norm(input.value);
		let prefix = 0;
		while (prefix < v.length && v[prefix] === tgt[prefix]) prefix++;
		const minutes = Math.max(s.t, 3) / 60;
		const wpm = (s.committed + prefix) / 5 / minutes;
		const acc = s.total ? s.correct / s.total : 1;
		return { wpm, acc };
	};

	const updateStats = () => {
		const { wpm, acc } = stats();
		wpmEl.textContent = fa(s.started ? wpm : 0);
		accEl.textContent = fa(Math.round(acc * 100)) + '٪';
		const left = Math.max(0, DURATION - s.t);
		timeEl.textContent = fa(Math.ceil(left));
		ring.style.strokeDashoffset = String(175.9 * (1 - left / DURATION));
		ring.setAttribute('stroke', left < 10 ? '#fb7185' : '#f59e0b');
		const live = Math.round(Math.min(3000, wpm * acc * acc * 10));
		if (s.started) api.setScore(live);
	};

	const commit = () => {
		s.committed += s.target.text.length + 1;
		s.lines++;
		if (s.lineErr === 0 && s.target.text.length >= 15) api.unlock('clean_line');
		if (s.lines >= 10) api.unlock('lines10');
		linesEl.textContent = fa(s.lines);
		s.lineErr = 0;
		s.target = s.next;
		s.next = nextSnippet();
		input.value = '';
		s.prev = '';
		input.dir = s.target.lang === 'js' ? 'ltr' : 'rtl';
		card.classList.remove('tc-pop');
		void card.offsetWidth;
		card.classList.add('tc-pop');
		api.sfx('coin');
		render();
	};

	const onInput = () => {
		if (!s || s.over || !api.running) {
			if (s) input.value = s.prev;
			return;
		}
		if (!s.started) {
			s.started = true;
			chips.forEach((c) => (c.disabled = true));
			hintEl.textContent = 'ادامه دهید! هر قطعه که کامل شود، بعدی می‌آید.';
		}
		const tgt = s.target.text;
		let v = norm(input.value);
		if (v.length > tgt.length + 1) v = v.slice(0, tgt.length + 1);
		// Count newly typed characters (keystrokes), not deletions.
		if (v.length > s.prev.length) {
			for (let i = s.prev.length; i < v.length; i++) {
				s.total++;
				if (v[i] === tgt[i]) s.correct++;
				else {
					s.errors++;
					s.lineErr++;
					card.classList.remove('tc-err');
					void card.offsetWidth;
					card.classList.add('tc-err');
					api.sfx('wrong');
				}
			}
			if (v[v.length - 1] === tgt[v.length - 1]) api.sfx('type');
		}
		s.prev = v;
		if (v === tgt || (v.length === tgt.length + 1 && v.startsWith(tgt) && v.endsWith(' '))) {
			commit();
		} else {
			if (input.value !== v) input.value = v;
			render();
		}
		updateStats();
	};

	const end = () => {
		s.over = true;
		stopLoop();
		input.blur();
		const { wpm, acc } = stats();
		const w = Math.round(wpm);
		const a = Math.round(acc * 100);
		if (w >= 30) api.unlock('wpm30');
		if (w >= 50) api.unlock('wpm50');
		if (w >= 70) api.unlock('wpm70');
		if (acc >= 0.98 && s.total >= 50) api.unlock('acc98');
		const score = Math.round(Math.min(3000, wpm * acc * acc * 10));
		api.over({ score, meta: { wpm: w, accuracy: a, summary: `سرعت شما ${fa(w)} کلمه در دقیقه با دقت ${fa(a)}٪ بود و ${fa(s.lines)} قطعه را کامل کردید.` } });
	};

	const frame = (t) => {
		if (!looping) return;
		const dt = Math.min(0.1, (t - last) / 1000);
		last = t;
		if (s && s.started && !s.over) {
			s.t += dt;
			updateStats();
			if (s.t >= DURATION) {
				end();
				return;
			}
		}
		raf = requestAnimationFrame(frame);
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

	const setMode = (m) => {
		mode = m;
		chips.forEach((c) => {
			const on = c.dataset.mode === m;
			c.setAttribute('aria-checked', String(on));
			c.classList.toggle('is-on', on);
		});
	};

	const reset = () => {
		s = newRun();
		s.target = nextSnippet();
		s.next = nextSnippet();
		input.value = '';
		input.dir = s.target.lang === 'js' ? 'ltr' : 'rtl';
		linesEl.textContent = fa(0);
		hintEl.textContent = 'شروع به تایپ کنید؛ زمان با اولین کلید شروع می‌شود.';
		chips.forEach((c) => (c.disabled = false));
		render();
		updateStats();
	};

	chips.forEach((c) =>
		L.on(c, 'click', () => {
			if (s && s.started) return;
			setMode(c.dataset.mode);
			reset();
			if (api.running) api.hud({ level: c.textContent.trim() });
			if (api.running) setTimeout(() => input.focus({ preventScroll: true }), 0);
		})
	);
	L.on(input, 'input', onInput);
	L.on(input, 'keydown', (e) => {
		if (e.key === 'Enter') {
			e.preventDefault();
			if (s && norm(input.value).trim() === s.target.text.trim()) commit();
		}
	});
	L.on(input, 'focus', () => card.classList.add('is-focus'));
	L.on(input, 'blur', () => card.classList.remove('is-focus'));
	L.on(input, 'paste', (e) => e.preventDefault());
	L.on(input, 'drop', (e) => e.preventDefault());

	setMode('mix');
	reset();
	input.disabled = true;

	const inst = {
		start() {
			pools.js0 = null;
			Object.keys(pools).forEach((k) => (pools[k] = null));
			reset();
			api.setScore(0);
			api.hud({ level: MODES.find((m) => m.id === mode).label, levelLabel: 'حالت' });
			input.disabled = false;
			startLoop();
			setTimeout(() => input.focus({ preventScroll: true }), 30);
		},
		pause() {
			stopLoop();
			input.disabled = true;
		},
		resume() {
			if (!s || s.over) return;
			input.disabled = false;
			startLoop();
			setTimeout(() => input.focus({ preventScroll: true }), 30);
		},
		destroy() {
			stopLoop();
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
