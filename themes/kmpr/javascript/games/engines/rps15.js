/**
 * «سنگ کاغذ قیچی ۱۵ حالته» — 15-gesture rock-paper-scissors, arena edition.
 * Circular order; every gesture beats the next 7 and loses to the previous 7.
 * Modes: vs a small pattern-learning AI (frequency + transition model → expected-value pick),
 * local 2P with hidden picks, and ONLINE (simultaneous hidden pick, server reveals — protocol unchanged:
 * room.move({ pick }) and state { best_of, need, round, picks, score, history[{p,w,r}], over, winner }).
 *
 * Presentation: fighting-game HUD (HP + round pips + combo), ritual countdown, 3D flip reveal,
 * clash with a per-gesture attack (WAAPI on transform/opacity + a canvas that only runs while
 * particles are alive), synthesized SFX via api.sfx(voices), haptics, AI taunts, end screen
 * and a "who beats who" wheel sheet that also lists the verb for every pair.
 */
import { CATALOG } from '../catalog.js';
import { createMatchUI, playerAvatar, BTN_PRIMARY, BTN_GHOST } from '../lib/match.js';
import { avatarSvg } from '../lib/avatar.js';
import { store } from '../lib/store.js';
import { fa, listeners, esc } from '../lib/util.js';

export const achievements = CATALOG.rps15.achievements;

const S = 'fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"';
export const GESTURES = [
	{ name: 'سنگ', hue: '#94a3b8', svg: `<path ${S} d="M9 33 13 19l11-7 11 3 5 11-4 10-14 3Z"/><path ${S} d="m20 21 4 5-2 6M30 20l-1 6"/>` },
	{ name: 'آتش', hue: '#f97316', svg: `<path ${S} d="M24 5c2 8 11 11 11 22a11 11 0 0 1-22 0c0-6 4-9 5-14 3 3 3 6 5 7 1-5-1-10 1-15Z"/><path ${S} d="M24 30c-2 2-3 4-2 7"/>` },
	{ name: 'قیچی', hue: '#e2e8f0', svg: `<circle ${S} cx="13" cy="34" r="5"/><circle ${S} cx="26" cy="38" r="5"/><path ${S} d="M17 31 40 9M29 34 22 12"/>` },
	{ name: 'مار', hue: '#4ade80', svg: `<path ${S} d="M8 39c5 0 7-5 13-5s8 5 14 5c4 0 6-3 6-6s-3-6-8-6H18c-5 0-8-3-8-7s3-7 8-7h15"/><path ${S} d="m33 13 4 0 3-3M37 13l3 3"/>` },
	{ name: 'انسان', hue: '#fcd34d', svg: `<circle ${S} cx="24" cy="10" r="5"/><path ${S} d="M24 15v15M14 22h20M24 30l-7 12M24 30l7 12"/>` },
	{ name: 'درخت', hue: '#22c55e', svg: `<path ${S} d="M24 5 11 24h7l-8 11h28l-8-11h7Z"/><path ${S} d="M24 35v8"/>` },
	{ name: 'گرگ', hue: '#a1a1aa', svg: `<path ${S} d="m10 7 8 10h12l8-10 2 18-16 17L8 25Z"/><path ${S} d="M18 25h2M28 25h2M21 33l3 2 3-2"/>` },
	{ name: 'اسفنج', hue: '#facc15', svg: `<rect ${S} x="9" y="11" width="30" height="26" rx="5"/><circle cx="17" cy="19" r="2.2" fill="currentColor"/><circle cx="29" cy="17" r="1.6" fill="currentColor"/><circle cx="25" cy="27" r="2.6" fill="currentColor"/><circle cx="15" cy="30" r="1.6" fill="currentColor"/><circle cx="33" cy="30" r="1.8" fill="currentColor"/>` },
	{ name: 'کاغذ', hue: '#f8fafc', svg: `<path ${S} d="M12 5h17l8 8v30H12Z"/><path ${S} d="M29 5v8h8M17 21h14M17 28h14M17 35h9"/>` },
	{ name: 'هوا', hue: '#bae6fd', svg: `<path ${S} d="M5 18h22a5 5 0 1 0-5-5M5 26h30a5 5 0 1 1-5 5M5 34h14"/>` },
	{ name: 'آب', hue: '#38bdf8', svg: `<path ${S} d="M24 5c-6 10-12 16-12 24a12 12 0 0 0 24 0c0-8-6-14-12-24Z"/><path ${S} d="M18 31a6 6 0 0 0 6 6"/>` },
	{ name: 'اژدها', hue: '#ef4444', svg: `<path ${S} d="M7 36c7-1 11-7 13-16l6 6 4-13 4 11 9-5-6 13c-4 7-12 9-22 9"/><circle cx="33" cy="25" r="1.8" fill="currentColor"/><path ${S} d="m40 29 4 2"/>` },
	{ name: 'شیطان', hue: '#f43f5e', svg: `<path ${S} d="M13 15 9 5l9 6M35 15l4-10-9 6"/><circle ${S} cx="24" cy="27" r="13"/><path ${S} d="m17 24 5 2M31 24l-5 2M18 32c4 3 8 3 12 0"/>` },
	{ name: 'رعد', hue: '#fde047', svg: `<path ${S} d="M28 4 12 27h11l-4 17 17-25H26Z"/>` },
	{ name: 'تفنگ', hue: '#a78bfa', svg: `<path ${S} d="M5 17h29l6 5v4H25l-3 12h-8l3-12H5Z"/><path ${S} d="M34 17v-4M10 22h6"/>` },
];
const N = 15;
export const beats = (a, b) => {
	const d = (b - a + N) % N;
	return d === 0 ? 0 : d <= 7 ? 1 : -1;
};
const victims = (i) => Array.from({ length: 7 }, (_, k) => (i + 1 + k) % N);
const killers = (i) => Array.from({ length: 7 }, (_, k) => (i - 1 - k + N) % N);
const icon = (i, size = 40) => `<svg width="${size}" height="${size}" class="shrink-0" viewBox="0 0 48 48" aria-hidden="true" style="color:${GESTURES[i].hue}">${GESTURES[i].svg}</svg>`;

/**
 * Verb table: VERBS[winner][k] describes winner beating gesture (winner + 1 + k) % 15.
 * Plain entries read «W، L را …»; entries containing % are full predicates with % → loser name.
 */
const VERBS = [
	['خفه می‌کند', 'کُند می‌کند', 'له می‌کند', 'از پا درمی‌آورد', 'جلوی ریشه‌های % را می‌گیرد', 'می‌راند', 'پهن می‌کند'],
	['ذوب می‌کند', 'می‌سوزاند', 'عقب می‌راند', 'خاکستر می‌کند', 'فراری می‌دهد', 'جزغاله می‌کند', 'شعله‌ور می‌کند'],
	['سرِ % را می‌زند', 'زخمی می‌کند', 'شاخه‌های % را هرس می‌کند', 'پشم % را می‌چیند', 'تکه‌تکه می‌کند', 'می‌بُرد', 'می‌شکافد'],
	['نیش می‌زند', 'دور % می‌پیچد و خفه‌اش می‌کند', 'مسموم می‌کند', 'یک‌جا می‌بلعد', 'سوراخ‌سوراخ می‌کند', '% را در چنبره‌اش حبس می‌کند', 'سر می‌کشد'],
	['قطع می‌کند', 'رام می‌کند', 'می‌چلاند', 'مچاله می‌کند', 'آلوده می‌کند', 'می‌نوشد', 'شکار می‌کند'],
	['لای شاخه‌هایش گیر می‌اندازد', 'خشک می‌کند', 'زیر برگ‌هایش دفن می‌کند', 'تصفیه می‌کند', 'با ریشه‌هایش می‌مکد', 'در ریشه‌هایش اسیر می‌کند', 'در تنه‌اش زندانی می‌کند'],
	['می‌جَود', 'تکه‌پاره می‌کند', 'با زوزه‌اش % را می‌لرزاند', 'هورت می‌کشد', 'گاز می‌گیرد', 'می‌دَرد', 'از % سریع‌تر می‌دود'],
	['خیس می‌کند', 'در حفره‌هایش حبس می‌کند', 'جذب می‌کند', 'آتشِ % را خاموش می‌کند', 'پاک می‌کند', 'خنثی می‌کند', 'لولهٔ % را می‌بندد'],
	['بادبادک می‌شود و % را سوار می‌شود', 'قایق می‌شود و روی % می‌راند', 'با یک طلسم، % را رام می‌کند', 'پیمانِ % را باطل می‌کند', 'مهار می‌کند', 'توقیف می‌کند', 'می‌پوشاند'],
	['تبخیر می‌کند', 'سرنگون می‌کند', 'با گردباد % را می‌برد', 'پراکنده می‌کند', 'گلولهٔ % را منحرف می‌کند', 'فرسوده می‌کند', 'خاموش می‌کند'],
	['غرق می‌کند', 'می‌شوید و می‌برد', 'پخش و بی‌اثر می‌کند', 'باروتِ % را خیس می‌کند', 'می‌ساید', 'فرو می‌نشاند', 'زنگ‌زده می‌کند'],
	['به زانو درمی‌آورد', 'می‌بلعد', 'با نفسش % را ذوب می‌کند', 'خرد می‌کند', 'در اختیار می‌گیرد', 'درهم می‌کوبد', 'یک‌لقمه می‌کند'],
	['به بازی می‌گیرد', 'طلسم می‌کند', 'به آسمان پرتاب می‌کند', 'رام می‌کند', 'کج می‌کند', 'به خدمت می‌گیرد', 'وسوسه می‌کند'],
	['باروتِ % را منفجر می‌کند', 'دو نیم می‌کند', 'تحت‌الشعاع قرار می‌دهد', 'جوش می‌دهد', 'کباب می‌کند', 'غافلگیر می‌کند', 'شقه می‌کند'],
	['خاک می‌کند', 'با موج شلیک % را خاموش می‌کند', 'از کار می‌اندازد', 'هدف می‌گیرد', 'تسلیم می‌کند', 'سوراخ می‌کند', 'شکار می‌کند'],
];
export const verbFor = (w, l) => {
	const k = (l - w - 1 + N) % N;
	const v = (VERBS[w] && VERBS[w][k]) || 'را شکست می‌دهد';
	const W = GESTURES[w].name;
	const Ln = GESTURES[l].name;
	return v.includes('%') ? `${W}، ${v.replace('%', Ln)}` : `${W}، ${Ln} را ${v}`;
};

/* Attack style per winning gesture. */
const KIND = ['smash', 'burn', 'snip', 'bite', 'punch', 'roots', 'claw', 'absorb', 'wrap', 'gust', 'splash', 'breath', 'curse', 'bolt', 'shoot'];

/* Synth voices: [type, f1, f2, dur, vol, delay] (played through the shell's sound toggle). */
const SFX = {
	tick: [['square', 1800, 1650, 0.025, 0.03]],
	beat: [['sine', 150, 48, 0.22, 0.24], ['noise', 0, 0, 0.05, 0.05]],
	go: [['sine', 220, 55, 0.3, 0.26], ['triangle', 880, 440, 0.12, 0.05]],
	pick: [['triangle', 520, 780, 0.07, 0.06]],
	lock: [['square', 420, 420, 0.04, 0.04], ['square', 640, 640, 0.06, 0.04, 0.05]],
	flip: [['triangle', 380, 920, 0.09, 0.05], ['noise', 0, 0, 0.06, 0.03]],
	whoosh: [['noise', 0, 0, 0.22, 0.05], ['sine', 260, 820, 0.2, 0.04]],
	impact: [['noise', 0, 0, 0.3, 0.14], ['sine', 120, 34, 0.38, 0.28], ['square', 90, 40, 0.12, 0.05]],
	win: [['triangle', 523, 523, 0.09, 0.08], ['triangle', 659, 659, 0.09, 0.08, 0.09], ['triangle', 784, 784, 0.09, 0.08, 0.18], ['triangle', 1047, 1047, 0.32, 0.08, 0.27]],
	lose: [['sawtooth', 330, 300, 0.15, 0.04], ['sawtooth', 262, 240, 0.15, 0.04, 0.15], ['sawtooth', 196, 100, 0.45, 0.05, 0.3]],
	tie: [['square', 700, 700, 0.06, 0.04], ['square', 700, 700, 0.06, 0.04, 0.11]],
	fanfare: [['triangle', 523, 523, 0.12, 0.08], ['triangle', 659, 659, 0.12, 0.08, 0.12], ['triangle', 784, 784, 0.12, 0.08, 0.24], ['triangle', 1047, 1047, 0.2, 0.08, 0.36], ['triangle', 784, 784, 0.1, 0.07, 0.56], ['triangle', 1047, 1047, 0.5, 0.09, 0.66], ['sine', 262, 262, 0.7, 0.06, 0.66]],
	defeat: [['triangle', 392, 370, 0.25, 0.07], ['triangle', 330, 311, 0.25, 0.07, 0.25], ['triangle', 262, 130, 0.8, 0.08, 0.5]],
	smash: [['noise', 0, 0, 0.35, 0.18], ['sine', 90, 30, 0.4, 0.3]],
	burn: [['noise', 0, 0, 0.4, 0.07], ['sawtooth', 80, 55, 0.5, 0.05]],
	snip: [['square', 3200, 2600, 0.03, 0.06], ['noise', 0, 0, 0.03, 0.06], ['square', 3200, 2600, 0.03, 0.06, 0.1], ['noise', 0, 0, 0.03, 0.06, 0.1]],
	bite: [['square', 900, 260, 0.06, 0.07], ['square', 900, 260, 0.06, 0.07, 0.13]],
	punch: [['noise', 0, 0, 0.08, 0.16], ['sine', 190, 60, 0.15, 0.2], ['noise', 0, 0, 0.08, 0.16, 0.24], ['sine', 190, 60, 0.15, 0.2, 0.24]],
	roots: [['triangle', 80, 150, 0.45, 0.1], ['noise', 0, 0, 0.2, 0.03, 0.1]],
	claw: [['noise', 0, 0, 0.06, 0.12], ['noise', 0, 0, 0.06, 0.12, 0.09], ['noise', 0, 0, 0.06, 0.12, 0.18]],
	absorb: [['sine', 700, 140, 0.45, 0.08]],
	wrap: [['noise', 0, 0, 0.14, 0.06], ['triangle', 300, 520, 0.14, 0.05]],
	gust: [['noise', 0, 0, 0.4, 0.07], ['sine', 200, 620, 0.5, 0.03]],
	splash: [['sine', 820, 180, 0.25, 0.09], ['noise', 0, 0, 0.25, 0.06, 0.05]],
	breath: [['noise', 0, 0, 0.4, 0.12], ['sawtooth', 70, 48, 0.7, 0.07]],
	curse: [['sawtooth', 110, 52, 0.7, 0.06], ['sine', 233, 220, 0.7, 0.05]],
	bolt: [['sawtooth', 1700, 60, 0.35, 0.08], ['noise', 0, 0, 0.4, 0.16]],
	shoot: [['noise', 0, 0, 0.14, 0.28], ['square', 220, 50, 0.1, 0.1]],
};

const TAUNT = {
	win: ['دیدی؟ خوندمت!', 'قابل پیش‌بینی بودی…', 'یکی دیگه؟', 'این‌جوری نمی‌شه رفیق!', 'الگوت دستم اومد.'],
	read: ['می‌دونستم اینو می‌زنی!', 'ذهنت مثل کتاب بازه!'],
	repeat: 'باز هم %؟ تکراری شد!',
	lose: ['خوش‌شانس بودی!', 'دارم یاد می‌گیرم…', 'این یکی رو حساب نکن!', 'جالب شد…', 'آفرین، بد نبود.'],
	tie: ['فکرمو خوندی؟', 'هم‌فکریم!', 'دوباره!', 'عجب تصادفی!'],
	streak: ['باشه، باشه… حالا جدی می‌شم.', 'دست گرمی؟ صبر کن!'],
	match: ['یه دست دیگه و تمومه!', 'آخرشه… آماده‌ای؟'],
};
const rnd = (arr) => arr[Math.floor(Math.random() * arr.length)];

/* ---------- learning AI ---------- */
class Brain {
	constructor() {
		this.freq = new Array(N).fill(1);
		this.trans = Array.from({ length: N }, () => new Array(N).fill(0));
		this.last = -1;
	}
	learn(p) {
		this.freq[p]++;
		if (this.last >= 0) this.trans[this.last][p] += 2;
		this.last = p;
	}
	probs() {
		return this.freq.map((f, g) => f + (this.last >= 0 ? this.trans[this.last][g] * 1.5 : 0));
	}
	/** Most likely next player gesture. */
	guess() {
		const P = this.probs();
		return P.indexOf(Math.max(...P));
	}
	pick() {
		const P = this.probs();
		const sum = P.reduce((a, b) => a + b, 0);
		if (Math.random() < 0.22) return Math.floor(Math.random() * N);
		let best = -Infinity;
		let pickIdx = 0;
		for (let m = 0; m < N; m++) {
			let ev = 0;
			for (let g = 0; g < N; g++) ev += (P[g] / sum) * beats(m, g);
			ev += Math.random() * 0.04;
			if (ev > best) {
				best = ev;
				pickIdx = m;
			}
		}
		return pickIdx;
	}
}

/* ---------- attack overlays (viewBox 0 0 100 133, drawn over the losing card) ---------- */
const FLAME = '<path d="M15 60C3 60-2 48 4 38c4-8 8-12 8-26 8 10 18 18 18 32 0 10-6 16-15 16Z" fill="#f97316"/><path d="M15 60c-6 0-8-6-6-12 2-4 5-7 5-14 5 6 9 11 9 16 0 6-3 10-8 10Z" fill="#fde047"/>';
const OVER = {
	smash: '<g class="ov-a" stroke="#e2e8f0" stroke-width="2.4" fill="none" stroke-linecap="round"><path d="M50 66 30 30M50 66 78 36M50 66 88 80M50 66 62 112M50 66 20 98M50 66 12 62"/><circle cx="50" cy="66" r="9" fill="#020617" stroke="#94a3b8"/></g>',
	burn: `<rect class="ov-char" width="100" height="133" rx="10" fill="#1c0a00"/><g transform="translate(6 70)"><g class="ov-a">${FLAME}</g></g><g transform="translate(34 52) scale(1.3)"><g class="ov-a">${FLAME}</g></g><g transform="translate(64 72)"><g class="ov-a">${FLAME}</g></g>`,
	snip: '<g fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"><path class="ov-d" pathLength="1" d="M8 18 92 116"/><path class="ov-d" pathLength="1" d="M92 18 8 116"/></g>',
	bite: '<path class="ov-up" d="M8 10h84v14l-8 22-8-20-9 26-9-26-9 26-9-26-8 20-8-22Z" fill="#f8fafc" stroke="#4ade80" stroke-width="1.5"/><path class="ov-dn" d="M8 123H92v-14l-8-22-8 20-9-26-9 26-9-26-9 26-8-20-8 22Z" fill="#f8fafc" stroke="#4ade80" stroke-width="1.5"/><g class="ov-b" fill="#be123c"><circle cx="34" cy="58" r="3.4"/><circle cx="66" cy="58" r="3.4"/><circle cx="40" cy="80" r="2.6"/><circle cx="60" cy="80" r="2.6"/></g>',
	punch: '<g class="ov-a"><path d="M50 34l8 17 19-5-11 16 17 10-20 2 3 20-16-12-16 12 3-20-20-2 17-10-11-16 19 5Z" fill="#fde047" stroke="#f59e0b" stroke-width="2" stroke-linejoin="round"/></g>',
	roots: '<g fill="none" stroke="#22c55e" stroke-width="5" stroke-linecap="round"><path class="ov-d" pathLength="1" d="M18 133c6-28-12-40 6-66s14-36 4-52"/><path class="ov-d" pathLength="1" d="M52 133c4-30-12-50 4-74s14-30 6-50"/><path class="ov-d" pathLength="1" d="M84 133c-6-26 12-42-4-66"/></g><g class="ov-b" fill="#4ade80"><ellipse cx="30" cy="40" rx="6" ry="3.4" transform="rotate(-30 30 40)"/><ellipse cx="62" cy="34" rx="6" ry="3.4" transform="rotate(30 62 34)"/><ellipse cx="78" cy="70" rx="6" ry="3.4" transform="rotate(-20 78 70)"/></g>',
	claw: '<g fill="none" stroke="#fecdd3" stroke-width="4.5" stroke-linecap="round"><path class="ov-d" pathLength="1" d="M22 20 50 112"/><path class="ov-d" pathLength="1" d="M42 16 70 108"/><path class="ov-d" pathLength="1" d="M62 14 88 100"/></g>',
	absorb: '<g class="ov-a" fill="none" stroke="#facc15" stroke-width="3" stroke-dasharray="6 7"><circle cx="50" cy="66" r="42"/><circle cx="50" cy="66" r="28"/></g>',
	wrap: '<g class="ov-a"><rect x="3" y="3" width="94" height="127" rx="8" fill="#f8fafc"/><path d="M16 30h68M16 46h68M16 62h68M16 78h48" stroke="#94a3b8" stroke-width="3" stroke-linecap="round"/><path d="M70 3 97 30H70Z" fill="#cbd5e1"/></g>',
	gust: '<g fill="none" stroke="#bae6fd" stroke-width="3.5" stroke-linecap="round"><path class="ov-a" d="M-10 40c30-12 50 12 80-2 14-6 14-20 2-20"/><path class="ov-a" d="M-10 70c34-10 60 10 110-4"/><path class="ov-a" d="M-10 100c30 10 50-8 74 2 12 6 10 20-2 18"/></g>',
	splash: '<g class="ov-a"><path d="M0 0h100v46c-14 14-28-8-48 6-20 14-34-6-52 10Z" fill="#38bdf8" fill-opacity=".85"/><path d="M0 0h100v26c-16 10-30-6-50 4S16 26 0 34Z" fill="#bae6fd" fill-opacity=".7"/></g>',
	breath: `<rect class="ov-char" width="100" height="133" rx="10" fill="#1c0a00"/><g transform="translate(4 66)"><g class="ov-a">${FLAME}</g></g><g transform="translate(34 48) scale(1.35)"><g class="ov-a">${FLAME}</g></g><g transform="translate(66 68)"><g class="ov-a">${FLAME}</g></g>`,
	curse: '<g class="ov-a" fill="none" stroke-linecap="round"><circle cx="50" cy="66" r="40" stroke="#f43f5e" stroke-width="2.5" stroke-dasharray="3 8"/><path d="M50 66c0-8 12-8 12 0 0 14-24 14-24 0 0-20 36-20 36 0 0 26-48 26-48 0" stroke="#c084fc" stroke-width="3"/></g>',
	bolt: '<g class="ov-a"><path d="M60 0 30 62h20L34 133l46-84H58L72 0Z" fill="#fde047" stroke="#fff" stroke-width="2.5" stroke-linejoin="round"/></g>',
	shoot: '<g class="ov-a"><g stroke="#e2e8f0" stroke-width="1.8" fill="none" stroke-linecap="round"><path d="M50 60 34 44M50 60l20-12M50 60l22 14M50 60 40 82M50 60 26 64"/></g><circle cx="50" cy="60" r="7" fill="#020617" stroke="#a78bfa" stroke-width="2.5"/></g>',
};
/* Particle palettes per attack: [colors, shape, gravity, speed, mode] — mode: up | radial | down | away | toward */
const PARTS = {
	smash: [['#94a3b8', '#cbd5e1', '#475569'], 'sq', 1100, 420, 'up'],
	burn: [['#f97316', '#fde047', '#ef4444'], 'dot', -260, 160, 'rise'],
	snip: [['#ffffff', '#e2e8f0', '#cbd5e1'], 'spark', 0, 620, 'radial'],
	bite: [['#4ade80', '#86efac', '#be123c'], 'dot', 700, 260, 'up'],
	punch: [['#fde047', '#fcd34d', '#fff'], 'spark', 0, 600, 'radial'],
	roots: [['#22c55e', '#4ade80', '#a3e635'], 'sq', 500, 260, 'up'],
	claw: [['#fecdd3', '#fb7185', '#e2e8f0'], 'spark', 200, 520, 'away'],
	absorb: [['#facc15', '#38bdf8', '#fde68a'], 'dot', 0, 360, 'toward'],
	wrap: [['#f8fafc', '#e2e8f0', '#cbd5e1'], 'sq', 600, 300, 'up'],
	gust: [['#bae6fd', '#e0f2fe', '#7dd3fc'], 'spark', 0, 700, 'away'],
	splash: [['#38bdf8', '#7dd3fc', '#e0f2fe'], 'dot', 1300, 520, 'up'],
	breath: [['#f97316', '#fde047', '#ef4444'], 'dot', -200, 200, 'rise'],
	curse: [['#c084fc', '#f43f5e', '#a855f7'], 'dot', -160, 140, 'rise'],
	bolt: [['#fde047', '#fff', '#facc15'], 'spark', 300, 760, 'radial'],
	shoot: [['#a78bfa', '#e2e8f0', '#f59e0b'], 'spark', 400, 520, 'away'],
};
const CONFETTI = ['#f59e0b', '#fde047', '#34d399', '#38bdf8', '#f472b6', '#a78bfa', '#fb7185', '#ffffff'];

let current = null;

export function mount(stage, api) {
	api.setRatio('4/3', '3/4.8');
	const RM = !!api.reducedMotion;
	const L = listeners();
	const freshStats = () => ({ rounds: 0, ties: 0, streak: [0, 0], best: [0, 0], used: new Array(N).fill(0), usedOpp: new Array(N).fill(0), reads: 0, lost: 0 });

	let mode = null;
	let phase = 'idle'; // idle | pick | wait | anim | end
	let gen = 0; // bumps on reset → stale async sequences bail out
	let score = [0, 0];
	let need = 3;
	let bestOf = 5;
	let round = 1;
	let brain = new Brain();
	let localPicks = [null, null];
	let lastHist = 0;
	let series = [0, 0];
	let st = freshStats();
	let fighters = [];
	let hover = -1;
	let focusIdx = 0;
	let wheelSel = 0;
	let lastOpener = null;
	let matchT0 = performance.now();
	const timers = new Set();

	const ui = createMatchUI(stage, api, {
		ai: [{ id: 'learn', label: 'هوش یادگیرنده' }],
		local: true,
		online: true,
		options: [{ key: 'best_of', label: 'تعداد دست (بهترین از)', value: 5, choices: [[3, '۳'], [5, '۵'], [7, '۷']] }],
		activeSeat: () => null,
		// Online: hold match.js's result screen until the final clash + callout has played.
		onRoundResolved: () => lastOnlineRun,
		onStart: (m) => {
			mode = m;
			hardReset();
			if (m.type === 'online') {
				const s = m.view.state || {};
				bestOf = s.best_of || 5;
				need = s.need || Math.ceil(bestOf / 2);
				lastHist = (s.history || []).length;
				const me = m.view.seat;
				score = s.score ? [s.score[me], s.score[1 - me]] : [0, 0];
				round = s.round || 1;
				setOnlineFighters(m.view);
				renderHud();
				return; // onRoomState follows immediately and drives the round
			}
			bestOf = m.options.best_of || 5;
			need = Math.ceil(bestOf / 2);
			brain = new Brain();
			setFighters(
				m.type === 'ai'
					? [meFighter(), aiFighter()]
					: [
							{ name: 'بازیکن ۱', avatar: playerAvatar({ player: 'p1', avatar: { style: 'gem', hue: 32 } }, 40) },
							{ name: 'بازیکن ۲', avatar: playerAvatar({ player: 'p2', avatar: { style: 'gem', hue: 200 } }, 40) },
						]
			);
			newRound();
		},
		onRoomState: (view) => onlineState(view),
		onMenu: () => {
			mode = null;
			hardReset();
			setFighters([meFighter(), aiFighter()]);
			renderHud();
		},
	});

	const meFighter = () => ({ name: store.player.nickname || 'شما', avatar: playerAvatar('me', 40) });
	const aiFighter = () => ({ name: 'هوش یادگیرنده', avatar: playerAvatar({ player: 'rps-brain', avatar: { style: 'orbit', hue: 265 } }, 40) });

	/* ---------- DOM ---------- */
	const pl = (side) => `<div class="rps-pl" data-pl="${side}">
		<span class="rps-pl__av" data-av></span>
		<div class="rps-pl__info">
			<p class="rps-pl__name"><span data-name></span><i class="rps-pl__dot" data-dot hidden></i></p>
			<div class="rps-hp" aria-hidden="true"><i class="rps-hp__ghost" data-ghost></i><i class="rps-hp__fill" data-hp></i></div>
			<div class="rps-pl__row"><span class="rps-pips" data-pips></span><span class="rps-combo" data-combo hidden></span></div>
		</div>
	</div>`;
	const card = (side) => `<div class="rps-fighter" data-side="${side}">
		<div class="rps-card" data-card>
			<div class="rps-card__inner">
				<div class="rps-card__back"><span class="rps-card__emblem"><span class="rps-card__q">؟</span><svg class="rps-card__ok shrink-0" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span><span class="rps-card__tag" data-tag></span></div>
				<div class="rps-card__front" data-front></div>
			</div>
			<div class="rps-card__fx" data-fx></div>
			<svg class="rps-card__crack" width="100%" height="100%" viewBox="0 0 100 133" preserveAspectRatio="none" aria-hidden="true"><path d="M52 0 46 30l12 18-18 22 12 22-8 41M46 30 18 42M58 48l28-4M40 70 14 92M52 92l28 18" fill="none" stroke="rgba(255,255,255,.75)" stroke-width="1.6" stroke-linejoin="round"/></svg>
		</div>
		${side === 1 ? '<div class="rps-bubble" data-bubble hidden></div>' : ''}
	</div>`;

	const wrap = document.createElement('div');
	wrap.className = 'rps';
	wrap.dataset.phase = 'idle';
	wrap.innerHTML = `
		<div class="rps-hud">
			${pl(1)}
			<div class="rps-hud__mid">
				<p class="rps-round" data-round>دست ۱</p>
				<div class="rps-timer" data-timer hidden><svg width="44" height="44" viewBox="0 0 44 44" class="shrink-0" aria-hidden="true"><circle cx="22" cy="22" r="19" class="rps-timer__track"/><circle cx="22" cy="22" r="19" class="rps-timer__bar" data-tbar pathLength="100"/></svg><b data-tnum>۶</b></div>
				<p class="rps-bo" data-bo></p>
			</div>
			${pl(0)}
		</div>
		<div class="rps-arena" data-arena>
			<div class="rps-edge" data-edge aria-hidden="true"></div>
			${card(1)}
			<div class="rps-vs" data-vs aria-hidden="true"><span>VS</span></div>
			${card(0)}
			<div class="rps-count" aria-hidden="true"><span data-count></span></div>
			<i class="rps-tracer" data-tracer aria-hidden="true"></i>
			<div class="rps-flash" data-flash aria-hidden="true"></div>
			<div class="rps-callout" data-callout aria-live="polite"><b data-cw></b><span data-cs></span></div>
		</div>
		<div class="rps-handbar">
			<p class="rps-prompt" data-prompt></p>
			<button type="button" class="${BTN_GHOST} !min-h-11 !px-3.5 !py-2 !text-xs" data-wheel-open aria-haspopup="dialog"><svg width="16" height="16" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18"/></svg> چه کسی چه کسی را می‌برد؟</button>
		</div>
		<div class="rps-hand" role="group" aria-label="انتخاب حرکت" data-hand>
			${GESTURES.map((g, i) => `<button type="button" class="rps-hcard" data-pick="${i}" tabindex="${i === 0 ? 0 : -1}" style="--hue:${g.hue};--o:${i - 7};--o2:${(i - 7) * (i - 7)}" aria-label="${g.name}">${icon(i, 32)}<span>${g.name}</span></button>`).join('')}
		</div>`;
	ui.board.appendChild(wrap);

	const sheet = document.createElement('div');
	sheet.className = 'rps-sheet';
	sheet.hidden = true;
	sheet.setAttribute('role', 'dialog');
	sheet.setAttribute('aria-modal', 'true');
	sheet.setAttribute('aria-label', 'چرخه برد و باخت');
	sheet.innerHTML = `<div class="rps-sheet__scrim" data-wheel-close></div>
		<div class="rps-sheet__panel">
			<div class="rps-sheet__head">
				<div><p class="rps-sheet__kicker">راهنمای ۱۵ حرکت</p><p class="rps-sheet__title">چه کسی چه کسی را می‌برد؟</p></div>
				<button type="button" class="rps-sheet__x" data-wheel-close aria-label="بستن"><svg width="20" height="20" class="shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
			</div>
			<div class="rps-sheet__body">
				<svg class="rps-wheel shrink-0" width="100%" height="100%" viewBox="0 0 400 400" role="img" aria-label="چرخه ۱۵ حرکت"></svg>
				<div class="rps-sheet__info" data-wheel-text></div>
			</div>
		</div>`;
	const endEl = document.createElement('div');
	endEl.className = 'rps-end';
	endEl.hidden = true;
	const fxc = document.createElement('canvas');
	fxc.className = 'rps-fxc';
	fxc.setAttribute('aria-hidden', 'true');
	ui.root.append(endEl, fxc, sheet);

	const $ = (s, r = wrap) => r.querySelector(s);
	const arena = $('[data-arena]');
	const fEls = [...wrap.querySelectorAll('.rps-fighter')].sort((a, b) => a.dataset.side - b.dataset.side);
	const cards = fEls.map((f) => $('[data-card]', f));
	const fronts = fEls.map((f) => $('[data-front]', f));
	const fxEls = fEls.map((f) => $('[data-fx]', f));
	const tags = fEls.map((f) => $('[data-tag]', f));
	const bubble = $('[data-bubble]');
	const plEls = [0, 1].map((s) => $(`[data-pl="${s}"]`));
	const hand = $('[data-hand]');
	const hcards = [...hand.querySelectorAll('[data-pick]')];
	const promptEl = $('[data-prompt]');
	const roundEl = $('[data-round]');
	const boEl = $('[data-bo]');
	const timerEl = $('[data-timer]');
	const tbar = $('[data-tbar]');
	const tnum = $('[data-tnum]');
	const countEl = $('[data-count]');
	const callout = $('[data-callout]');
	const edge = $('[data-edge]');
	const flash = $('[data-flash]');
	const tracer = $('[data-tracer]');
	const vsEl = $('[data-vs]');
	const wheelSvg = $('.rps-wheel', sheet);
	const wheelText = $('[data-wheel-text]', sheet);

	/* ---------- helpers ---------- */
	const later = (fn, ms) => {
		const t = setTimeout(() => {
			timers.delete(t);
			fn();
		}, ms);
		timers.add(t);
		return t;
	};
	const wait = (ms) => new Promise((r) => later(r, RM ? Math.min(ms, 160) : ms));
	const anim = (el, kf, { d = 300, e = 'ease', f = 'none', delay = 0 } = {}) => {
		if (RM || !el || !el.animate) return Promise.resolve();
		return el.animate(kf, { duration: d, easing: e, fill: f, delay }).finished.catch(() => {});
	};
	const sfx = (k) => api.sfx(SFX[k]);
	const buzz = (p) => {
		if (api.touch && navigator.vibrate) {
			try {
				navigator.vibrate(p);
			} catch (e) {
				/* ignore */
			}
		}
	};
	const setPhase = (p) => {
		phase = p;
		wrap.dataset.phase = p;
		const on = p === 'pick';
		hcards.forEach((b) => (b.disabled = !on));
	};
	const relRect = (el) => {
		const r = el.getBoundingClientRect();
		const o = ui.root.getBoundingClientRect();
		return { x: r.left - o.left, y: r.top - o.top, w: r.width, h: r.height, cx: r.left - o.left + r.width / 2, cy: r.top - o.top + r.height / 2 };
	};

	/* ---------- particles (canvas only runs while particles are alive) ---------- */
	const ctx = fxc.getContext('2d');
	let parts = [];
	let raf = 0;
	let lastT = 0;
	let cw = 0;
	let chh = 0;
	let sizeDirty = true;
	const ro = new ResizeObserver(() => (sizeDirty = true));
	ro.observe(ui.root);
	const fitCanvas = () => {
		if (!sizeDirty) return;
		sizeDirty = false;
		const r = ui.root.getBoundingClientRect();
		const dpr = Math.min(window.devicePixelRatio || 1, 2);
		cw = r.width;
		chh = r.height;
		fxc.width = Math.max(1, Math.round(cw * dpr));
		fxc.height = Math.max(1, Math.round(chh * dpr));
		ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
	};
	const emit = (x, y, { n = 16, c = ['#fff'], sp = 300, life = 0.8, size = 4, g = 0, a = 0, s = Math.PI * 2, shape = 'dot', drag = 1.4 } = {}) => {
		if (RM) return;
		fitCanvas();
		for (let i = 0; i < n; i++) {
			const ang = a + (Math.random() - 0.5) * s;
			const v = sp * (0.35 + Math.random() * 0.65);
			parts.push({ x, y, vx: Math.cos(ang) * v, vy: Math.sin(ang) * v, life: life * (0.6 + Math.random() * 0.5), max: life, size: size * (0.6 + Math.random() * 0.8), c: c[i % c.length], g, shape, drag, rot: Math.random() * 6, vr: (Math.random() - 0.5) * 12, flip: Math.random() * 6 });
		}
		if (parts.length > 700) parts.splice(0, parts.length - 700);
		if (!raf) {
			lastT = performance.now();
			raf = requestAnimationFrame(step);
		}
	};
	const step = (t) => {
		const dt = Math.min(0.05, Math.max(0, (t - lastT) / 1000));
		lastT = t;
		ctx.clearRect(0, 0, cw, chh);
		for (let i = parts.length - 1; i >= 0; i--) {
			const p = parts[i];
			p.life -= dt;
			if (p.life <= 0 || p.y > chh + 40) {
				parts.splice(i, 1);
				continue;
			}
			const k = Math.max(0, 1 - p.drag * dt);
			p.vx *= k;
			p.vy = p.vy * k + p.g * dt;
			p.x += p.vx * dt;
			p.y += p.vy * dt;
			p.rot += p.vr * dt;
			p.flip += dt * 9;
			const al = Math.min(1, (p.life / p.max) * 1.6);
			ctx.globalAlpha = al;
			ctx.fillStyle = p.c;
			if (p.shape === 'spark') {
				ctx.globalCompositeOperation = 'lighter';
				ctx.strokeStyle = p.c;
				ctx.lineWidth = Math.max(1, p.size * 0.6);
				ctx.beginPath();
				ctx.moveTo(p.x, p.y);
				ctx.lineTo(p.x - p.vx * 0.035, p.y - p.vy * 0.035);
				ctx.stroke();
				ctx.globalCompositeOperation = 'source-over';
			} else if (p.shape === 'conf') {
				ctx.save();
				ctx.translate(p.x, p.y);
				ctx.rotate(p.rot);
				ctx.scale(1, Math.cos(p.flip));
				ctx.fillRect(-p.size, -p.size * 0.45, p.size * 2, p.size * 0.9);
				ctx.restore();
			} else if (p.shape === 'sq') {
				ctx.fillRect(p.x - p.size / 2, p.y - p.size / 2, p.size, p.size);
			} else {
				ctx.beginPath();
				ctx.arc(p.x, p.y, p.size / 2, 0, Math.PI * 2);
				ctx.fill();
			}
		}
		ctx.globalAlpha = 1;
		if (parts.length) raf = requestAnimationFrame(step);
		else {
			raf = 0;
			ctx.clearRect(0, 0, cw, chh);
		}
	};
	const stopParticles = () => {
		parts = [];
		if (raf) cancelAnimationFrame(raf);
		raf = 0;
		ctx.clearRect(0, 0, cw, chh);
	};
	const confetti = () => {
		if (RM) return;
		fitCanvas();
		for (let k = 0; k < 7; k++) emit(cw * ((k + 0.5) / 7), -12, { n: 16, c: CONFETTI, sp: 220, life: 3.2, size: 7, g: 420, a: Math.PI / 2, s: 1.4, shape: 'conf', drag: 0.9 });
		later(() => {
			emit(0, chh * 0.9, { n: 40, c: CONFETTI, sp: 900, life: 2.8, size: 7, g: 700, a: -Math.PI / 3, s: 0.5, shape: 'conf', drag: 1.1 });
			emit(cw, chh * 0.9, { n: 40, c: CONFETTI, sp: 900, life: 2.8, size: 7, g: 700, a: (-2 * Math.PI) / 3, s: 0.5, shape: 'conf', drag: 1.1 });
		}, 250);
	};

	/* ---------- HUD ---------- */
	const setFighters = (list) => {
		fighters = list;
		list.forEach((f, s) => {
			$('[data-av]', plEls[s]).innerHTML = f.avatar;
			$('[data-name]', plEls[s]).textContent = f.name;
			const dot = $('[data-dot]', plEls[s]);
			dot.hidden = f.online === undefined;
			dot.classList.toggle('is-on', !!f.online);
		});
	};
	const setOnlineFighters = (view) => {
		const me = view.seat;
		const mk = (seat) => {
			const p = (view.players || []).find((x) => x.seat === seat);
			if (!p) return { name: 'در انتظار…', avatar: '<span class="rps-pl__ph"></span>', online: false };
			return { name: p.nick + (seat === me ? ' (شما)' : ''), avatar: avatarSvg(p.player, p.avatar, 40), online: !!(p.online && !p.left) };
		};
		setFighters([mk(me), mk(1 - me)]);
	};
	const renderHud = () => {
		[0, 1].forEach((s) => {
			const el = plEls[s];
			const hp = Math.max(0, (need - score[1 - s]) / need);
			el.style.setProperty('--hp', hp.toFixed(3));
			el.classList.toggle('is-low', hp > 0 && hp <= 0.34);
			$('[data-pips]', el).innerHTML = Array.from({ length: need }, (_, k) => `<i class="${k < score[s] ? 'is-on' : ''}"></i>`).join('');
			$('[data-pips]', el).setAttribute('aria-label', `${fa(score[s])} برد از ${fa(need)}`);
			const c = $('[data-combo]', el);
			const sk = st.streak[s];
			c.hidden = sk < 2;
			if (sk >= 2) c.textContent = `${fa(sk)}× پیاپی`;
		});
		roundEl.textContent = mode ? `دست ${fa(round)}` : 'آماده؟';
		boEl.textContent = mode ? `بهترین از ${fa(bestOf)}` : 'سنگ کاغذ قیچی ۱۵';
	};
	const popCombo = (s) => anim($('[data-combo]', plEls[s]), [{ transform: 'scale(.4)', opacity: 0 }, { transform: 'scale(1.25)', opacity: 1, offset: 0.6 }, { transform: 'none', opacity: 1 }], { d: 420, e: 'ease-out' });
	const hitHud = (s) => anim(plEls[s], [{ transform: 'none' }, { transform: 'translateX(-5px)' }, { transform: 'translateX(5px)' }, { transform: 'translateX(-3px)' }, { transform: 'none' }], { d: 320 });

	/* ---------- AI bubble ---------- */
	let bubbleT = 0;
	const say = (text, { think = false, ms = 1900 } = {}) => {
		if (!bubble) return;
		clearTimeout(bubbleT);
		bubble.innerHTML = think ? `<span>${esc(text)}</span><span class="rps-dots" aria-hidden="true"><i></i><i></i><i></i></span>` : esc(text);
		bubble.classList.toggle('is-think', think);
		bubble.hidden = false;
		anim(bubble, [{ opacity: 0, transform: 'translateY(6px) scale(.9)' }, { opacity: 1, transform: 'none' }], { d: 220, e: 'cubic-bezier(.2,1.4,.4,1)' });
		if (ms) bubbleT = later(() => (bubble.hidden = true), ms);
	};
	const hush = () => {
		clearTimeout(bubbleT);
		if (bubble) bubble.hidden = true;
	};

	/* ---------- cards ---------- */
	const setFront = (s, g) => {
		fronts[s].style.setProperty('--hue', GESTURES[g].hue);
		fronts[s].innerHTML = `<span class="rps-card__glow" aria-hidden="true"></span><span class="rps-card__art">${icon(g, 120)}</span><span class="rps-card__name">${GESTURES[g].name}</span>`;
	};
	const lockCard = (s, on, label = '') => {
		fEls[s].classList.toggle('is-locked', !!on);
		tags[s].textContent = label;
	};
	const resetVisual = () => {
		RM || wrap.getAnimations({ subtree: true }).forEach((a) => a instanceof CSSAnimation || a instanceof CSSTransition || a.cancel());
		fEls.forEach((f, s) => {
			f.classList.remove('is-open', 'is-win', 'is-lose', 'is-locked');
			fxEls[s].innerHTML = '';
			tags[s].textContent = '';
		});
		countEl.textContent = '';
		callout.classList.remove('is-show');
		wrap.classList.remove('is-tense');
		hcards.forEach((b) => b.classList.remove('is-chosen'));
	};
	const hardReset = () => {
		gen++;
		timers.forEach((t) => clearTimeout(t));
		timers.clear();
		stopTimer();
		stopParticles();
		hideEnd();
		closeWheel();
		hush();
		resetVisual();
		score = [0, 0];
		round = 1;
		localPicks = [null, null];
		st = freshStats();
		matchT0 = performance.now();
		setPhase('idle');
		promptEl.textContent = '';
		renderHud();
	};

	/* ---------- round timer (AI only) ---------- */
	const TURN = 6000;
	let tLeft = 0;
	let tEnd = 0;
	let tInt = 0;
	let tRun = false;
	let tSec = 0;
	let tFlip = false;
	const startTimer = () => {
		stopTimer();
		if (!mode || mode.type !== 'ai') {
			timerEl.hidden = true;
			return;
		}
		timerEl.hidden = false;
		tLeft = TURN;
		tSec = 7;
		tnum.textContent = fa(6);
		tFlip = !tFlip;
		tbar.style.animation = `${tFlip ? 'rps-timer-a' : 'rps-timer-b'} ${TURN}ms linear forwards`;
		resumeTimer();
	};
	const resumeTimer = () => {
		if (tRun || tLeft <= 0 || phase !== 'pick' || !mode || mode.type !== 'ai' || !sheet.hidden || document.hidden) return;
		tRun = true;
		tEnd = performance.now() + tLeft;
		tbar.style.animationPlayState = 'running';
		tInt = setInterval(tickTimer, 200);
	};
	const pauseTimer = () => {
		if (!tRun) return;
		tRun = false;
		clearInterval(tInt);
		tLeft = Math.max(0, tEnd - performance.now());
		tbar.style.animationPlayState = 'paused';
	};
	const stopTimer = () => {
		pauseTimer();
		tLeft = 0;
		timerEl.classList.remove('is-low');
	};
	const tickTimer = () => {
		const left = tEnd - performance.now();
		const s = Math.max(0, Math.ceil(left / 1000));
		if (s !== tSec) {
			tSec = s;
			tnum.textContent = fa(s);
			if (s <= 3 && s > 0) {
				timerEl.classList.add('is-low');
				sfx('tick');
			}
		}
		if (left <= 0) {
			stopTimer();
			if (phase === 'pick' && mode && mode.type === 'ai') {
				say('دیر کردی! یه حرکت تصادفی برات زدم.', { ms: 1600 });
				choose(Math.floor(Math.random() * N), true);
			}
		}
	};

	/* ---------- round flow ---------- */
	const promptText = () => {
		if (!mode) return '';
		if (mode.type === 'local') return localPicks[0] === null ? 'بازیکن ۱ انتخاب کند — بازیکن ۲ نگاه نکند!' : 'نوبت بازیکن ۲ — بازیکن ۱ نگاه نکند!';
		return 'حرکتت را انتخاب کن';
	};
	const promptPick = () => {
		if (!mode) return;
		setPhase('pick');
		renderHud();
		promptEl.textContent = promptText();
		ui.status(mode.type === 'ai' ? `دست ${fa(round)} — ${fa(6)} ثانیه وقت داری` : promptText(), 'turn');
		if (mode.type === 'ai') {
			startTimer();
			lockCard(1, false);
			say('در حال فکر', { think: true, ms: 0 });
			const g = gen;
			later(() => {
				if (g !== gen || phase !== 'pick') return;
				lockCard(1, true, 'آماده');
				say('انتخاب کردم!', { ms: 1400 });
			}, 700 + Math.random() * 700);
		} else timerEl.hidden = true;
		if (!api.touch && stage.contains(document.activeElement) && sheet.hidden) hcards[focusIdx].focus({ preventScroll: true });
	};
	const newRound = () => {
		resetVisual();
		promptPick();
	};

	const trackUse = (i) => {
		const g = store.game('rps15');
		g.used = Array.from(new Set([...(g.used || []), i]));
		store.save();
		if (g.used.length >= N) api.unlock('all15');
	};

	/** Choose a gesture for the active side. */
	const choose = async (i, auto = false) => {
		if (!mode || phase !== 'pick' || !api.running) return;
		focusIdx = i;
		sfx('pick');
		buzz(10);
		trackUse(i);
		hcards.forEach((b, k) => b.classList.toggle('is-chosen', k === i));
		if (mode.type === 'ai') {
			stopTimer();
			setPhase('anim');
			const guess = brain.guess();
			const prev = brain.last;
			const ai = brain.pick();
			brain.learn(i);
			lockCard(0, true);
			lockCard(1, true);
			hush();
			const g = gen;
			const r = await playRound(i, ai);
			if (r === null || g !== gen) return;
			if (r === 1 && i === 11) api.unlock('dragon');
			// taunts
			if (score[0] < need && score[1] < need) {
				let t = '';
				if (r === -1) {
					if (prev === i && Math.random() < 0.6) t = TAUNT.repeat.replace('%', GESTURES[i].name);
					else if (guess === i) t = rnd(TAUNT.read);
					else if (score[1] === need - 1) t = rnd(TAUNT.match);
					else t = rnd(TAUNT.win);
				} else if (r === 1) t = st.streak[0] >= 3 ? rnd(TAUNT.streak) : rnd(TAUNT.lose);
				else t = rnd(TAUNT.tie);
				say(t, { ms: 1700 });
				if (r === -1 && guess === i) st.reads++;
			}
			afterRound(g, auto ? 300 : 0);
		} else if (mode.type === 'local') {
			if (localPicks[0] === null) {
				localPicks[0] = i;
				sfx('lock');
				lockCard(0, true, 'بازیکن ۱ آماده');
				hcards.forEach((b) => b.classList.remove('is-chosen'));
				promptPick();
				return;
			}
			localPicks[1] = i;
			setPhase('anim');
			lockCard(1, true, 'بازیکن ۲ آماده');
			hcards.forEach((b) => b.classList.remove('is-chosen'));
			const [a, b] = localPicks;
			localPicks = [null, null];
			const g = gen;
			const r = await playRound(a, b, { names: ['بازیکن ۱', 'بازیکن ۲'] });
			if (r === null || g !== gen) return;
			afterRound(g);
		} else if (mode.type === 'online') {
			const room = ui.room;
			if (!room || !room.view || room.view.state.over) return;
			setPhase('wait');
			sfx('lock');
			lockCard(0, true, 'قفل شد');
			promptEl.textContent = 'منتظر انتخاب حریف…';
			ui.status('منتظر انتخاب حریف…');
			let res;
			try {
				res = await room.move({ pick: i });
			} catch (e) {
				res = null;
			}
			if ((!res || !res.ok) && phase === 'wait') {
				lockCard(0, false);
				hcards.forEach((b) => b.classList.remove('is-chosen'));
				promptPick();
			}
		}
	};

	const afterRound = async (g, extra = 0) => {
		if (score[0] >= need || score[1] >= need) {
			await wait(650);
			if (g !== gen) return;
			matchOver(score[0] >= need);
			return;
		}
		await wait(900 + extra);
		if (g !== gen) return;
		round++;
		newRound();
		api.save && api.save();
	};

	/* ---------- the show: ritual → flip → clash → attack ---------- */
	const WORDS = ['سنگ…', 'کاغذ…', 'قیچی…'];
	const ritual = async (g, fast) => {
		const beat = fast ? 330 : 440;
		wrap.classList.add('is-tense');
		hush();
		for (let k = 0; k < 3; k++) {
			countEl.textContent = WORDS[k];
			sfx('beat');
			buzz(12);
			anim(countEl, [{ transform: 'scale(1.8)', opacity: 0 }, { transform: 'scale(1)', opacity: 1, offset: 0.35 }, { transform: 'scale(.92)', opacity: 0.9 }], { d: beat, e: 'cubic-bezier(.2,.9,.3,1)', f: 'forwards' });
			anim(edge, [{ opacity: 0.25 + k * 0.25 }, { opacity: 0.1 + k * 0.12 }], { d: beat, e: 'ease-out', f: 'forwards' });
			cards.forEach((c, s) => {
				const m = s === 0 ? 1 : -1;
				anim(c, [{ transform: 'none' }, { transform: `translateY(-14%) rotate(${-7 * m}deg)`, offset: 0.4 }, { transform: 'translateY(3%) rotate(0)', offset: 0.78 }, { transform: 'none' }], { d: beat * 0.92, e: 'ease-in-out' });
			});
			await wait(beat);
			if (g !== gen) return false;
		}
		countEl.textContent = '!';
		anim(countEl, [{ transform: 'scale(2.4)', opacity: 1 }, { transform: 'scale(3)', opacity: 0 }], { d: 380, e: 'ease-out', f: 'forwards' });
		anim(edge, [{ opacity: 1 }, { opacity: 0 }], { d: 500, f: 'forwards' });
		wrap.classList.remove('is-tense');
		sfx('go');
		return true;
	};

	const showCallout = (word, sentence, tone) => {
		$('[data-cw]', callout).textContent = word;
		$('[data-cs]', callout).textContent = sentence;
		callout.dataset.tone = tone;
		callout.classList.add('is-show');
		anim(callout, [{ opacity: 0, transform: 'translateY(12px) scale(.9)' }, { opacity: 1, transform: 'none' }], { d: 280, e: 'cubic-bezier(.2,1.3,.4,1)' });
	};

	/**
	 * Animate one round. a = side-0 gesture, b = side-1 gesture. Updates score/stats/HUD at impact.
	 * Returns 1 / -1 / 0 from side 0's view, or null if superseded.
	 */
	const playRound = async (a, b, { fast = false, skipRitual = false, names = null } = {}) => {
		const g = gen;
		setPhase('anim');
		promptEl.textContent = '';
		setFront(0, a);
		setFront(1, b);
		vsEl.classList.add('is-dim');
		if (!skipRitual) {
			const ok = await ritual(g, fast);
			if (!ok || g !== gen) return null;
		}
		// flip
		fEls.forEach((f) => f.classList.add('is-open'));
		lockCard(0, false);
		lockCard(1, false);
		sfx('flip');
		await wait(fast ? 420 : 520);
		if (g !== gen) return null;
		const r = beats(a, b);
		// measure once (rest positions), then only write transforms
		const ar = arena.getBoundingClientRect();
		const cr = cards.map((c) => c.getBoundingClientRect());
		const mid = ar.left + ar.width / 2;
		const dx = [mid + cr[0].width * 0.47 - (cr[0].left + cr[0].width / 2), mid - cr[1].width * 0.47 - (cr[1].left + cr[1].width / 2)];
		const rest = cards.map(relRect);
		const center = { x: (rest[0].cx + rest[1].cx) / 2, y: (rest[0].cy + rest[1].cy) / 2 };
		// charge
		sfx('whoosh');
		await Promise.all(fEls.map((f, s) => anim(f, [{ transform: 'none' }, { transform: `translateX(${dx[s]}px) scale(1.04)` }], { d: 230, e: 'cubic-bezier(.55,0,1,.6)', f: 'forwards' })));
		if (g !== gen) return null;
		// impact
		sfx('impact');
		buzz(r === 0 ? 25 : [30, 40, 70]);
		anim(flash, [{ opacity: 0.85 }, { opacity: 0 }], { d: 360, e: 'ease-out' });
		anim(arena, [{ transform: 'none' }, { transform: 'translate(-7px,3px)' }, { transform: 'translate(6px,-4px)' }, { transform: 'translate(-4px,2px)' }, { transform: 'translate(2px,-1px)' }, { transform: 'none' }], { d: 380 });
		emit(center.x, center.y, { n: 26, c: ['#fff', GESTURES[a].hue, GESTURES[b].hue], sp: 640, life: 0.55, size: 3, shape: 'spark', drag: 2.2 });
		st.rounds++;
		st.used[a]++;
		st.usedOpp[b]++;
		const nm = names || (mode && mode.type === 'local' ? ['بازیکن ۱', 'بازیکن ۲'] : null);
		if (r === 0) {
			st.ties++;
			st.streak = [0, 0];
			sfx('tie');
			await Promise.all(fEls.map((f, s) => anim(f, [{ transform: `translateX(${dx[s]}px) scale(1.04)` }, { transform: `translateX(${-dx[s] * 0.12}px)`, offset: 0.55 }, { transform: 'none' }], { d: 520, e: 'cubic-bezier(.2,.9,.3,1.2)', f: 'forwards' })));
			emit(center.x, center.y, { n: 18, c: ['#38bdf8', '#e0f2fe', '#fff'], sp: 380, life: 0.6, size: 3, shape: 'spark' });
			showCallout('مساوی!', `هر دو «${GESTURES[a].name}» آوردید`, 'tie');
			ui.status('این دست مساوی شد');
			renderHud();
		} else {
			const w = r === 1 ? 0 : 1;
			const l = 1 - w;
			score[w]++;
			st.streak[w]++;
			st.streak[l] = 0;
			st.best[w] = Math.max(st.best[w], st.streak[w]);
			if (w === 1) st.lost++;
			const gw = w === 0 ? a : b;
			const gl = w === 0 ? b : a;
			await attack(g, w, l, gw, dx, rest);
			if (g !== gen) return null;
			renderHud();
			hitHud(l);
			if (st.streak[w] >= 2) popCombo(w);
			const sentence = verbFor(gw, gl);
			const word = nm ? `${nm[w]} برد!` : w === 0 ? 'بُردی!' : 'باختی!';
			showCallout(word, sentence, nm ? 'win' : w === 0 ? 'win' : 'loss');
			sfx(nm || w === 0 ? 'win' : 'lose');
			ui.status(sentence, nm ? 'win' : w === 0 ? 'win' : 'loss');
		}
		vsEl.classList.remove('is-dim');
		return r;
	};

	/** Winner-specific attack. dx: charge offsets, rest: rest rects (relative to ui.root). */
	const attack = async (g, w, l, gw, dx, rest) => {
		const kind = KIND[gw];
		const dirW = Math.sign(dx[w]) || 1; // winner travels this way toward the loser
		const holdW = dx[w] * 0.55;
		const knock = dirW * Math.min(26, rest[l].w * 0.09);
		const fw = fEls[w];
		const fl = fEls[l];
		const cwEl = cards[w];
		const clEl = cards[l];
		const lc = { x: rest[l].cx + knock, y: rest[l].cy };
		const wEdge = { x: rest[w].cx + holdW + (dirW * rest[w].w) / 2, y: rest[w].cy - rest[w].h * 0.08 };
		anim(fw, [{ transform: `translateX(${dx[w]}px) scale(1.04)` }, { transform: `translateX(${holdW}px) scale(1.06)` }], { d: 260, e: 'cubic-bezier(.2,.8,.3,1)', f: 'forwards' });
		anim(fl, [{ transform: `translateX(${dx[l]}px) scale(1.04)` }, { transform: `translateX(${knock}px) rotate(${dirW * 5}deg) scale(.96)` }], { d: 340, e: 'cubic-bezier(.2,.9,.3,1.25)', f: 'forwards' });
		fxEls[l].innerHTML = `<svg width="100%" height="100%" viewBox="0 0 100 133" preserveAspectRatio="xMidYMid meet" aria-hidden="true">${OVER[kind] || ''}</svg>`;
		const q = (s) => [...fxEls[l].querySelectorAll(s)];
		const [pc, shape, grav, speed, pmode] = PARTS[kind];
		const angAway = dirW > 0 ? 0 : Math.PI;
		const burst = (n = 22, at = lc, extra = {}) => {
			const a = pmode === 'up' ? -Math.PI / 2 : pmode === 'rise' ? -Math.PI / 2 : pmode === 'away' ? angAway : pmode === 'toward' ? angAway + Math.PI : 0;
			const s = pmode === 'radial' ? Math.PI * 2 : pmode === 'rise' ? 0.9 : pmode === 'up' ? 1.9 : 1.1;
			emit(at.x, at.y, { n, c: pc, sp: speed, life: pmode === 'rise' ? 1.1 : 0.8, size: shape === 'sq' ? 6 : 4, g: grav, a, s, shape, ...extra });
		};
		const shakeL = (d = 320, delay = 0) => anim(clEl, [{ transform: 'none' }, { transform: 'translateX(-6px) rotate(-2deg)' }, { transform: 'translateX(6px) rotate(2deg)' }, { transform: 'translateX(-3px)' }, { transform: 'none' }], { d, delay });
		const pop = (els, d = 420, delay = 0) => els.forEach((el, k) => anim(el, [{ opacity: 0, transform: 'scale(.2)' }, { opacity: 1, transform: 'scale(1.15)', offset: 0.5 }, { opacity: 1, transform: 'scale(1)' }], { d, delay: delay + k * 90, e: 'cubic-bezier(.2,1.3,.4,1)', f: 'both' }));
		const draw = (els, d = 260, delay = 0, gap = 90) => els.forEach((el, k) => anim(el, [{ strokeDasharray: '1 1', strokeDashoffset: 1, opacity: 1 }, { strokeDasharray: '1 1', strokeDashoffset: 0, opacity: 1 }], { d, delay: delay + k * gap, e: 'ease-out', f: 'both' }));
		const lunge = (n = 1, dist = 26) => {
			const kf = [{ transform: 'none' }];
			for (let k = 0; k < n; k++) {
				kf.push({ transform: `translateX(${dirW * dist}px) rotate(${dirW * 5}deg) scale(1.05)`, offset: (k + 0.4) / n });
				kf.push({ transform: 'none', offset: (k + 1) / n });
			}
			return anim(cwEl, kf, { d: 300 * n, e: 'ease-in-out' });
		};
		sfx(kind);
		let dur = 760;
		switch (kind) {
			case 'smash':
				anim(cwEl, [{ transform: 'none' }, { transform: `translate(${-dirW * 8}px,-30%) rotate(${-dirW * 14}deg)`, offset: 0.45 }, { transform: `translate(${dirW * 24}px,3%) rotate(${dirW * 4}deg)`, offset: 0.72 }, { transform: 'none' }], { d: 640, e: 'ease-in-out' });
				anim(clEl, [{ transform: 'none' }, { transform: 'scale(1.1,.78)', offset: 0.35 }, { transform: 'none' }], { d: 380, delay: 440 });
				pop(q('.ov-a'), 300, 450);
				later(() => {
					burst(28);
					anim(arena, [{ transform: 'none' }, { transform: 'translateY(6px)' }, { transform: 'translateY(-3px)' }, { transform: 'none' }], { d: 300 });
				}, 460);
				dur = 900;
				break;
			case 'burn':
			case 'breath': {
				if (kind === 'breath') {
					anim(cwEl, [{ transform: 'none' }, { transform: `rotate(${-dirW * 8}deg) scale(1.06)`, offset: 0.2 }, { transform: `rotate(${-dirW * 8}deg) scale(1.06)`, offset: 0.8 }, { transform: 'none' }], { d: 760 });
					for (let k = 0; k < 9; k++) later(() => emit(wEdge.x, wEdge.y, { n: 9, c: pc, sp: 760, life: 0.5, size: 6, g: -80, a: angAway, s: 0.42, drag: 0.6 }), 60 + k * 50);
				}
				const d0 = kind === 'breath' ? 300 : 80;
				q('.ov-char').forEach((el) => anim(el, [{ opacity: 0 }, { opacity: 0.55 }], { d: 600, delay: d0, f: 'both' }));
				q('.ov-a').forEach((el, k) => anim(el, [{ opacity: 0, transform: 'translateY(30px) scale(.3)' }, { opacity: 1, transform: 'translateY(0) scale(1.1)', offset: 0.35 }, { opacity: 0.95, transform: 'translateY(-4px) scale(.95)', offset: 0.7 }, { opacity: 0, transform: 'translateY(-16px) scale(.6)' }], { d: 900, delay: d0 + k * 110, f: 'both' }));
				later(() => burst(18, { x: lc.x, y: rest[l].y + rest[l].h * 0.8 }, { s: 1.2 }), d0 + 80);
				later(() => burst(14, { x: lc.x, y: rest[l].y + rest[l].h * 0.7 }, { s: 1.4 }), d0 + 320);
				dur = d0 + 900;
				break;
			}
			case 'snip':
				lunge(1, 20);
				draw(q('.ov-d'), 180, 120, 120);
				later(() => burst(18), 160);
				later(() => burst(14), 280);
				later(() => q('.ov-d').forEach((el) => anim(el, [{ opacity: 1 }, { opacity: 0 }], { d: 400, f: 'forwards' })), 520);
				shakeL(300, 260);
				break;
			case 'bite':
				lunge(2, 30);
				q('.ov-up').forEach((el) => anim(el, [{ opacity: 0, transform: 'translateY(-50px)' }, { opacity: 1, transform: 'translateY(0)', offset: 0.6 }, { opacity: 1, transform: 'translateY(4px)', offset: 0.75 }, { opacity: 0, transform: 'translateY(-10px)' }], { d: 640, delay: 80, f: 'both' }));
				q('.ov-dn').forEach((el) => anim(el, [{ opacity: 0, transform: 'translateY(50px)' }, { opacity: 1, transform: 'translateY(0)', offset: 0.6 }, { opacity: 1, transform: 'translateY(-4px)', offset: 0.75 }, { opacity: 0, transform: 'translateY(10px)' }], { d: 640, delay: 80, f: 'both' }));
				pop(q('.ov-b'), 300, 460);
				later(() => burst(20), 420);
				shakeL(320, 420);
				break;
			case 'punch':
				lunge(2, 36);
				pop(q('.ov-a'), 260, 110);
				later(() => burst(16), 120);
				later(() => {
					burst(22);
					q('.ov-a').forEach((el) => anim(el, [{ transform: 'scale(1)' }, { transform: 'scale(1.35)' }, { transform: 'scale(1)' }], { d: 220 }));
				}, 420);
				shakeL(300, 420);
				break;
			case 'roots':
				anim(cwEl, [{ transform: 'none' }, { transform: 'scale(1.08)', offset: 0.5 }, { transform: 'none' }], { d: 700 });
				draw(q('.ov-d'), 520, 60, 110);
				pop(q('.ov-b'), 300, 520);
				later(() => burst(16, { x: lc.x, y: rest[l].y + rest[l].h * 0.9 }), 120);
				later(() => burst(12), 560);
				anim(clEl, [{ transform: 'none' }, { transform: 'scale(.94)' }], { d: 600, delay: 200, f: 'forwards' });
				dur = 900;
				break;
			case 'claw':
				lunge(1, 30);
				draw(q('.ov-d'), 150, 140, 90);
				later(() => burst(20), 180);
				later(() => q('.ov-d').forEach((el) => anim(el, [{ opacity: 1 }, { opacity: 0.55 }], { d: 300, f: 'forwards' })), 600);
				shakeL(300, 200);
				break;
			case 'absorb':
				anim(cwEl, [{ transform: 'none' }, { transform: 'scale(1.12,.92)', offset: 0.5 }, { transform: 'none' }], { d: 700 });
				q('.ov-a').forEach((el) => anim(el, [{ opacity: 0, transform: 'rotate(0) scale(1.3)' }, { opacity: 1, transform: 'rotate(120deg) scale(.9)', offset: 0.6 }, { opacity: 0, transform: 'rotate(200deg) scale(.4)' }], { d: 800, f: 'both' }));
				anim(clEl, [{ transform: 'none' }, { transform: `translateX(${-dirW * 10}px) scale(.82)` }], { d: 700, e: 'ease-in', f: 'forwards' });
				for (let k = 0; k < 4; k++) later(() => burst(10), 80 + k * 140);
				dur = 860;
				break;
			case 'wrap':
				lunge(1, 18);
				q('.ov-a').forEach((el) => anim(el, [{ opacity: 0, transform: 'translateY(-120%) rotate(-8deg)' }, { opacity: 1, transform: 'translateY(4%) rotate(2deg)', offset: 0.7 }, { opacity: 0.92, transform: 'none' }], { d: 520, delay: 80, e: 'cubic-bezier(.3,1.2,.5,1)', f: 'both' }));
				later(() => burst(14), 480);
				anim(clEl, [{ transform: 'none' }, { transform: 'scale(.95)' }], { d: 300, delay: 450, f: 'forwards' });
				break;
			case 'gust':
				anim(cwEl, [{ transform: 'none' }, { transform: `scale(1.06) translateX(${-dirW * 6}px)`, offset: 0.3 }, { transform: 'none' }], { d: 640 });
				q('.ov-a').forEach((el, k) => anim(el, [{ opacity: 0, transform: `translateX(${-dirW * 40}px)` }, { opacity: 1, transform: 'translateX(0)', offset: 0.4 }, { opacity: 0, transform: `translateX(${dirW * 60}px)` }], { d: 620, delay: k * 90, f: 'both' }));
				anim(fl, [{ transform: `translateX(${knock}px) rotate(${dirW * 5}deg) scale(.96)` }, { transform: `translateX(${knock * 2.4}px) rotate(${dirW * 14}deg) scale(.92)` }], { d: 520, delay: 160, e: 'cubic-bezier(.2,.9,.3,1.1)', f: 'forwards' });
				for (let k = 0; k < 3; k++) later(() => burst(12, { x: lc.x - dirW * rest[l].w * 0.4, y: lc.y + (k - 1) * rest[l].h * 0.25 }), 60 + k * 110);
				break;
			case 'splash':
				lunge(1, 18);
				q('.ov-a').forEach((el) => anim(el, [{ opacity: 0, transform: 'translateY(-70px)' }, { opacity: 1, transform: 'translateY(10px)', offset: 0.45 }, { opacity: 0.8, transform: 'translateY(60px)', offset: 0.8 }, { opacity: 0, transform: 'translateY(90px)' }], { d: 760, delay: 60, f: 'both' }));
				later(() => burst(30, { x: lc.x, y: rest[l].y + rest[l].h * 0.25 }), 260);
				later(() => burst(18, { x: lc.x, y: rest[l].y + rest[l].h * 0.6 }), 440);
				shakeL(280, 300);
				break;
			case 'curse':
				anim(cwEl, [{ transform: 'none' }, { transform: 'translateY(-8%) scale(1.06)', offset: 0.5 }, { transform: 'none' }], { d: 760 });
				q('.ov-a').forEach((el) => anim(el, [{ opacity: 0, transform: 'rotate(0) scale(.3)' }, { opacity: 1, transform: 'rotate(220deg) scale(1)', offset: 0.55 }, { opacity: 0, transform: 'rotate(360deg) scale(1.3)' }], { d: 900, f: 'both' }));
				for (let k = 0; k < 4; k++) later(() => burst(10, { x: lc.x, y: rest[l].y + rest[l].h * (0.85 - k * 0.12) }), 80 + k * 140);
				shakeL(480, 280);
				dur = 900;
				break;
			case 'bolt':
				anim(cwEl, [{ transform: 'none' }, { transform: 'translateY(-6%) scale(1.05)', offset: 0.4 }, { transform: 'none' }], { d: 520 });
				q('.ov-a').forEach((el) => anim(el, [{ opacity: 0 }, { opacity: 1, offset: 0.08 }, { opacity: 0.1, offset: 0.18 }, { opacity: 1, offset: 0.28 }, { opacity: 0.2, offset: 0.4 }, { opacity: 1, offset: 0.5 }, { opacity: 0 }], { d: 620, delay: 120, f: 'both' }));
				later(() => {
					anim(flash, [{ opacity: 0.9 }, { opacity: 0 }], { d: 300 });
					burst(30, { x: lc.x, y: rest[l].y + rest[l].h * 0.55 });
				}, 160);
				shakeL(360, 160);
				break;
			case 'shoot': {
				anim(cwEl, [{ transform: 'none' }, { transform: `translateX(${-dirW * 16}px) rotate(${-dirW * 6}deg)`, offset: 0.15 }, { transform: 'none' }], { d: 420, delay: 100, e: 'ease-out' });
				const tx = Math.min(wEdge.x, lc.x);
				const tw = Math.abs(lc.x - wEdge.x);
				const ar = relRect(arena);
				tracer.style.left = `${tx - ar.x}px`;
				tracer.style.top = `${wEdge.y - ar.y}px`;
				tracer.style.width = `${tw}px`;
				tracer.style.transformOrigin = dirW > 0 ? 'left center' : 'right center';
				anim(tracer, [{ opacity: 1, transform: 'scaleX(0)' }, { opacity: 1, transform: 'scaleX(1)', offset: 0.4 }, { opacity: 0, transform: 'scaleX(1)' }], { d: 260, delay: 100 });
				later(() => emit(wEdge.x, wEdge.y, { n: 16, c: ['#fde047', '#f59e0b', '#fff'], sp: 420, life: 0.3, size: 4, a: angAway, s: 0.9, shape: 'spark' }), 100);
				pop(q('.ov-a'), 240, 200);
				later(() => burst(22, { x: lc.x, y: rest[l].y + rest[l].h * 0.45 }), 210);
				shakeL(300, 210);
				break;
			}
			default:
				lunge(1);
		}
		await wait(Math.round(dur * 0.55));
		if (g !== gen) return;
		fl.classList.add('is-lose');
		fw.classList.add('is-win');
		await wait(Math.round(dur * 0.45));
		if (g !== gen) return;
		anim(fw, [{ transform: `translateX(${holdW}px) scale(1.06)` }, { transform: 'none' }], { d: 420, e: 'cubic-bezier(.3,1.3,.5,1)', f: 'forwards' });
	};

	/* ---------- match end ---------- */
	const favorite = (arr) => {
		const m = Math.max(...arr);
		return m > 0 ? arr.indexOf(m) : -1;
	};
	let endTimer = 0;
	const hideEnd = () => {
		clearInterval(endTimer);
		endEl.hidden = true;
		endEl.innerHTML = '';
	};
	const statTile = (label, value, extra = '') => `<div class="rps-end__stat"><dt>${label}</dt><dd>${extra}${value}</dd></div>`;
	const showEnd = ({ kicker, title, tone, sub, stats, actions }) => {
		const f = fighters;
		endEl.innerHTML = `<div class="rps-end__box" data-tone="${tone}">
			<p class="rps-end__kicker">${kicker}</p>
			<p class="rps-end__title">${esc(title)}</p>
			<div class="rps-end__vs">
				<span class="rps-end__who">${f[0] ? f[0].avatar : ''}<b>${esc(f[0] ? f[0].name : '')}</b></span>
				<span class="rps-end__score"><b>${fa(score[0])}</b><i>:</i><b>${fa(score[1])}</b></span>
				<span class="rps-end__who">${f[1] ? f[1].avatar : ''}<b>${esc(f[1] ? f[1].name : '')}</b></span>
			</div>
			${sub ? `<p class="rps-end__sub">${esc(sub)}</p>` : ''}
			<dl class="rps-end__stats">${stats.join('')}</dl>
			<div class="rps-end__actions">${actions}</div>
			<div data-kmpr-slot="game-over"></div>
		</div>`;
		endEl.hidden = false;
		anim(endEl.firstElementChild, [{ opacity: 0, transform: 'translateY(16px) scale(.94)' }, { opacity: 1, transform: 'none' }], { d: 420, e: 'cubic-bezier(.2,1.2,.4,1)' });
		const af = endEl.querySelector('[data-autofocus]');
		if (af && !api.touch) af.focus({ preventScroll: true });
	};
	const gamesUrl = (() => {
		try {
			return new URL('../', api.gameUrl).href;
		} catch (e) {
			return '/games/';
		}
	})();
	const backLink = `<a class="${BTN_GHOST}" href="${esc(gamesUrl)}">همه بازی‌ها</a>`;

	/** For endings that don't go through api.over() (AI loss, local) — same event the shell emits. */
	const emitOver = (won) => {
		try {
			const slug = (api.gameUrl.match(/\/([^/?#]+)\/?(?:[?#].*)?$/) || [])[1] || 'rps-15';
			window.dispatchEvent(new CustomEvent('kmpr:game-over', { detail: { slug, mode: mode ? mode.type : null, score: 0, won: !!won, durationMs: Math.round(performance.now() - matchT0) } }));
		} catch (e) {
			/* ignore */
		}
	};
	const matchOver = (myWin) => {
		setPhase('end');
		hush();
		stopTimer();
		timerEl.hidden = true;
		promptEl.textContent = '';
		const fav = favorite(st.used);
		const favHtml = fav >= 0 ? `${icon(fav, 22)} ` : '';
		const base = [statTile('تعداد دست', fa(st.rounds)), statTile('بهترین برد پیاپی', fa(st.best[0]))];
		if (mode.type === 'ai') {
			const stats = [...base, statTile('حرکت محبوب شما', fav >= 0 ? GESTURES[fav].name : '—', favHtml), statTile('ذهن‌خوانی هوش', `${fa(st.rounds ? Math.round((st.reads / st.rounds) * 100) : 0)}٪`)];
			if (myWin) {
				api.unlock('first_win');
				if (st.lost === 0) api.unlock('sweep');
				if (bestOf === 7) api.unlock('beat_ai');
				const pts = 100 * bestOf + 60 * (score[0] - score[1]);
				sfx('fanfare');
				buzz([40, 60, 40, 60, 120]);
				confetti();
				const submit = () => {
					hideEnd();
					api.over({ score: pts, meta: { mode: 'ai', result: 'win', round, summary: `هوش یادگیرنده را ${fa(score[0])} بر ${fa(score[1])} در بهترین از ${fa(bestOf)} بردید.` } });
				};
				let left = 6;
				showEnd({
					kicker: 'پایان مسابقه',
					title: 'پیروزی!',
					tone: 'win',
					sub: `هوش یادگیرنده را شکست دادی — +${fa(pts)} امتیاز`,
					stats,
					actions: `<button type="button" class="${BTN_PRIMARY} rps-end__main" data-end="submit" data-autofocus>ثبت امتیاز و ادامه <span class="rps-end__cd" data-cd>${fa(left)}</span></button>`,
				});
				endEl.querySelector('[data-end="submit"]').addEventListener('click', submit, { once: true });
				endTimer = setInterval(() => {
					left--;
					const cd = endEl.querySelector('[data-cd]');
					if (cd) cd.textContent = fa(Math.max(0, left));
					if (left <= 0) {
						clearInterval(endTimer);
						if (!endEl.hidden) submit();
					}
				}, 1000);
			} else {
				sfx('defeat');
				buzz(160);
				emitOver(false);
				say('بازی تمومه. الگوت رو یاد گرفتم!', { ms: 2600 });
				showEnd({
					kicker: 'پایان مسابقه',
					title: 'هوش یادگیرنده برد',
					tone: 'loss',
					sub: 'الگوی شما را یاد گرفت! غیرقابل‌پیش‌بینی‌تر بازی کنید.',
					stats,
					actions: `<button type="button" class="${BTN_PRIMARY}" data-end="again" data-autofocus>انتقام! بازی دوباره</button><button type="button" class="${BTN_GHOST}" data-end="menu">منوی بازی</button>${backLink}`,
				});
			}
		} else {
			const w = myWin ? 0 : 1;
			series[w]++;
			emitOver(true);
			const favW = favorite(w === 0 ? st.used : st.usedOpp);
			sfx('fanfare');
			confetti();
			showEnd({
				kicker: `مجموع این جلسه: ${fa(series[0])} — ${fa(series[1])}`,
				title: `بازیکن ${fa(w + 1)} برد!`,
				tone: 'win',
				sub: `بهترین از ${fa(bestOf)}`,
				stats: [statTile('تعداد دست', fa(st.rounds)), statTile('بهترین برد پیاپی', fa(st.best[w])), statTile('حرکت محبوب برنده', favW >= 0 ? GESTURES[favW].name : '—', favW >= 0 ? `${icon(favW, 22)} ` : ''), statTile('مساوی‌ها', fa(st.ties))],
				actions: `<button type="button" class="${BTN_PRIMARY}" data-end="again" data-autofocus>بازی دوباره</button><button type="button" class="${BTN_GHOST}" data-end="menu">منوی بازی</button>${backLink}`,
			});
		}
	};
	const restartSame = () => {
		hideEnd();
		gen++;
		timers.forEach((t) => clearTimeout(t));
		timers.clear();
		stopParticles();
		score = [0, 0];
		round = 1;
		localPicks = [null, null];
		st = freshStats();
		matchT0 = performance.now();
		renderHud();
		newRound();
	};

	/* ---------- online ---------- */
	const onlineIdle = (view) => {
		const s = view.state;
		if (!s || !s.score || s.over) return;
		const me = view.seat;
		const opp = 1 - me;
		round = s.round || round;
		score = [s.score[me], s.score[opp]];
		const mine = s.picks ? s.picks[me] : null;
		const oppLocked = !!(s.picks && s.picks[opp]);
		if (mine === null || mine === undefined) {
			if (phase !== 'pick') newRound();
			else renderHud();
		} else {
			setPhase('wait');
			renderHud();
			hcards.forEach((b, k) => b.classList.toggle('is-chosen', k === mine));
			lockCard(0, true, 'قفل شد');
			promptEl.textContent = 'منتظر انتخاب حریف…';
			ui.status('منتظر انتخاب حریف…');
		}
		lockCard(1, oppLocked, oppLocked ? 'حریف آماده' : '');
		if (oppLocked && bubble && bubble.hidden) say('انتخاب کردم!', { ms: 1400 });
	};
	let lastOnlineRun = null;
	const onlineState = (view) => {
		const s = view.state;
		if (!s || !s.score) return;
		const me = view.seat;
		const opp = 1 - me;
		bestOf = s.best_of;
		need = s.need;
		setOnlineFighters(view);
		const hist = s.history || [];
		if (hist.length < lastHist) {
			// rematch started
			lastHist = hist.length;
			gen++;
			timers.forEach((t) => clearTimeout(t));
			timers.clear();
			stopParticles();
			st = freshStats();
			setPhase('idle');
		}
		if (hist.length > lastHist) {
			const h = hist[hist.length - 1];
			lastHist = hist.length;
			const r0 = h.w === 'draw' ? 0 : h.w === me ? 1 : -1;
			score = [s.score[me] - (r0 === 1 ? 1 : 0), s.score[opp] - (r0 === -1 ? 1 : 0)];
			round = h.r || round;
			stopTimer();
			lastOnlineRun = runOnline(h.p[me], h.p[opp], !!s.over, s.winner === me);
			return;
		}
		if (phase === 'anim' || phase === 'end') return;
		onlineIdle(view);
	};
	const runOnline = async (a, b, over, iWon) => {
		const g = gen;
		const r = await playRound(a, b, { fast: true, skipRitual: over });
		if (r === null || g !== gen) return;
		if (r === 1 && a === 11) api.unlock('dragon');
		if (over) {
			setPhase('end');
			if (iWon) {
				api.unlock('first_win');
				if (st.lost === 0 && bestOf >= 3) api.unlock('sweep');
				confetti();
			}
			return;
		}
		await wait(700);
		if (g !== gen) return;
		setPhase('idle');
		const v = ui.room && ui.room.view;
		if (v) onlineIdle(v);
	};
	// presence changes re-render match.js' (hidden) players bar → mirror them into our HUD
	const barEl = ui.root.querySelector('[data-bar]');
	const mo = new MutationObserver(() => {
		if (mode && mode.type === 'online' && ui.room && ui.room.view) setOnlineFighters(ui.room.view);
	});
	if (barEl) mo.observe(barEl, { childList: true });

	/* ---------- wheel sheet ---------- */
	const drawWheel = (sel) => {
		wheelSel = sel;
		const cx = 200;
		const cy = 200;
		const R = 158;
		const pos = (i) => {
			const a = (i / N) * Math.PI * 2 - Math.PI / 2;
			return [cx + Math.cos(a) * R, cy + Math.sin(a) * R];
		};
		const [sx, sy] = pos(sel);
		const vs = victims(sel);
		let lines = '';
		killers(sel).forEach((v) => {
			const [x, y] = pos(v);
			lines += `<line x1="${sx}" y1="${sy}" x2="${x}" y2="${y}" stroke="#fb7185" stroke-width="1.6" stroke-opacity=".5" stroke-dasharray="5 6"/>`;
		});
		vs.forEach((v) => {
			const [x, y] = pos(v);
			lines += `<line x1="${sx}" y1="${sy}" x2="${x}" y2="${y}" stroke="#34d399" stroke-width="2.6" stroke-opacity=".85"/>`;
		});
		const nodes = GESTURES.map((g, i) => {
			const [x, y] = pos(i);
			const role = i === sel ? 'sel' : vs.includes(i) ? 'win' : 'lose';
			const ring = role === 'sel' ? '#f59e0b' : role === 'win' ? '#34d399' : '#fb7185';
			return `<g class="rps-node${role === 'sel' ? ' is-sel' : ''}" data-node="${i}" tabindex="0" role="button" aria-label="${g.name}" transform="translate(${x - 24} ${y - 24})">
				<circle cx="24" cy="24" r="24" fill="#0b1220" stroke="${ring}" stroke-width="${role === 'sel' ? 4 : 2.2}"/>
				<g transform="translate(7 7) scale(.71)" style="color:${g.hue}">${g.svg}</g>
			</g>`;
		}).join('');
		wheelSvg.innerHTML = `<circle cx="200" cy="200" r="158" fill="none" stroke="rgba(255,255,255,.07)"/>${lines}<circle cx="200" cy="200" r="54" fill="#020617" stroke="rgba(245,158,11,.45)" stroke-width="2"/><g transform="translate(176 160) scale(1)" style="color:${GESTURES[sel].hue}">${GESTURES[sel].svg}</g><text x="200" y="230" text-anchor="middle" fill="#fff" font-size="17" font-weight="900">${GESTURES[sel].name}</text>${nodes}`;
		wheelText.innerHTML = `<p class="rps-sheet__h rps-sheet__h--win">«${esc(GESTURES[sel].name)}» این ۷ حرکت را می‌برد</p>
			<ul>${vs.map((v) => `<li>${icon(v, 22)}<span>${esc(verbFor(sel, v))}</span></li>`).join('')}</ul>
			<p class="rps-sheet__h rps-sheet__h--lose">و به این ۷ حرکت می‌بازد</p>
			<ul>${killers(sel).map((k) => `<li>${icon(k, 22)}<span>${esc(verbFor(k, sel))}</span></li>`).join('')}</ul>`;
	};
	const openWheel = (opener) => {
		lastOpener = opener || document.activeElement;
		pauseTimer();
		drawWheel(hover >= 0 ? hover : focusIdx);
		sheet.hidden = false;
		anim(sheet.querySelector('.rps-sheet__panel'), [{ opacity: 0, transform: 'translateY(24px)' }, { opacity: 1, transform: 'none' }], { d: 300, e: 'cubic-bezier(.2,1.1,.4,1)' });
		anim(sheet.querySelector('.rps-sheet__scrim'), [{ opacity: 0 }, { opacity: 1 }], { d: 240 });
		sheet.querySelector('.rps-sheet__x').focus({ preventScroll: true });
	};
	function closeWheel() {
		if (sheet.hidden) return;
		sheet.hidden = true;
		if (lastOpener && lastOpener.focus) lastOpener.focus({ preventScroll: true });
		lastOpener = null;
		resumeTimer();
	}

	/* ---------- events ---------- */
	const onClick = (e) => {
		const p = e.target.closest('[data-pick]');
		if (p && !p.disabled) return choose(Number(p.dataset.pick));
		const o = e.target.closest('[data-wheel-open]');
		if (o) return openWheel(o);
		if (e.target.closest('[data-wheel-close]')) return closeWheel();
		const n = e.target.closest('[data-node]');
		if (n) {
			drawWheel(Number(n.dataset.node));
			const nn = sheet.querySelector(`[data-node="${wheelSel}"]`);
			return nn && nn.focus({ preventScroll: true });
		}
		const en = e.target.closest('[data-end]');
		if (en) {
			api.sfx('click');
			if (en.dataset.end === 'again') restartSame();
			else if (en.dataset.end === 'menu') {
				hideEnd();
				ui.menu();
			}
		}
	};
	L.on(ui.root, 'click', onClick);
	const hint = (i) => {
		hover = i;
		hcards.forEach((b, k) => {
			b.classList.toggle('is-prey', i >= 0 && beats(i, k) === 1);
			b.classList.toggle('is-threat', i >= 0 && beats(i, k) === -1);
		});
		if (i >= 0 && phase === 'pick' && mode) ui.status(`${GESTURES[i].name} می‌برد از: ${victims(i).map((v) => GESTURES[v].name).join('، ')}`);
	};
	L.on(hand, 'pointerover', (e) => {
		const p = e.target.closest('[data-pick]');
		if (p && e.pointerType !== 'touch') hint(Number(p.dataset.pick));
	});
	L.on(hand, 'pointerleave', () => hint(-1));
	L.on(hand, 'focusin', (e) => {
		const p = e.target.closest('[data-pick]');
		if (!p) return;
		focusIdx = Number(p.dataset.pick);
		hcards.forEach((b, k) => (b.tabIndex = k === focusIdx ? 0 : -1));
		if (!api.touch) hint(focusIdx);
	});
	L.on(hand, 'focusout', (e) => {
		if (!hand.contains(e.relatedTarget)) hint(-1);
	});
	const cols = () => {
		const top = hcards[0].offsetTop;
		let c = 0;
		while (c < hcards.length && hcards[c].offsetTop === top) c++;
		return Math.max(1, c);
	};
	L.on(stage, 'keydown', (e) => {
		if (!sheet.hidden) {
			if (e.key === 'Escape') {
				e.preventDefault();
				closeWheel();
			} else if (e.key === 'Tab') {
				// keep focus inside the sheet
				const f = [...sheet.querySelectorAll('button,[data-node]')];
				const i = f.indexOf(document.activeElement);
				e.preventDefault();
				f[(i + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus({ preventScroll: true });
			} else if ((e.key === 'Enter' || e.key === ' ') && e.target.closest && e.target.closest('[data-node]')) {
				e.preventDefault();
				drawWheel(Number(e.target.closest('[data-node]').dataset.node));
				const nn = sheet.querySelector(`[data-node="${wheelSel}"]`);
				nn && nn.focus({ preventScroll: true });
			}
			return;
		}
		if ((e.key === '?' || e.key === '؟') && mode) {
			e.preventDefault();
			openWheel();
			return;
		}
		if (phase !== 'pick' || !endEl.hidden) return;
		const inHand = hand.contains(document.activeElement);
		const keys = { ArrowLeft: 1, ArrowRight: -1, ArrowUp: 'up', ArrowDown: 'down', Home: 'home', End: 'end' };
		if (!(e.key in keys)) return;
		e.preventDefault();
		let i = focusIdx;
		const k = keys[e.key];
		if (inHand) {
			if (k === 'home') i = 0;
			else if (k === 'end') i = N - 1;
			else if (k === 'up' || k === 'down') i = Math.min(N - 1, Math.max(0, i + (k === 'up' ? -1 : 1) * cols()));
			else i = (i + k + N) % N;
		}
		hcards[i].focus({ preventScroll: true });
	});
	// Esc closes the sheet before the shell's document-level Esc (which would leave focus mode).
	L.on(
		window,
		'keydown',
		(e) => {
			if (e.key !== 'Escape' || sheet.hidden) return;
			e.preventDefault();
			e.stopPropagation();
			closeWheel();
		},
		true
	);
	L.on(document, 'visibilitychange', () => (document.hidden ? pauseTimer() : resumeTimer()));

	setFighters([meFighter(), aiFighter()]);
	renderHud();
	setPhase('idle');

	const inst = {
		noAutoPause: true,
		/** Shell resume hook — AI / local match snapshot (online resumes by re-joining the room). */
		serialize() {
			if (!mode || mode.type === 'online' || phase === 'end' || phase === 'idle') return null;
			return { v: 1, s: score, r: round, bo: bestOf, se: series, st, b: { f: brain.freq, t: brain.trans, l: brain.last } };
		},
		restore(d) {
			if (!d || d.v !== 1 || !mode || mode.type === 'online' || !Array.isArray(d.s)) return;
			const num = (v, a, b) => Math.min(b, Math.max(a, Number(v) || 0));
			bestOf = [3, 5, 7].includes(Number(d.bo)) ? Number(d.bo) : bestOf;
			need = Math.ceil(bestOf / 2);
			score = [num(d.s[0], 0, need - 1), num(d.s[1], 0, need - 1)];
			round = num(d.r, 1, 99);
			if (Array.isArray(d.se)) series = [num(d.se[0], 0, 999), num(d.se[1], 0, 999)];
			const f = freshStats();
			if (d.st && typeof d.st === 'object') {
				['rounds', 'ties', 'reads', 'lost'].forEach((k) => (f[k] = num(d.st[k], 0, 999)));
				['streak', 'best'].forEach((k) => Array.isArray(d.st[k]) && (f[k] = [num(d.st[k][0], 0, 99), num(d.st[k][1], 0, 99)]));
				['used', 'usedOpp'].forEach((k) => Array.isArray(d.st[k]) && d.st[k].length === N && (f[k] = d.st[k].map((v) => num(v, 0, 999))));
			}
			st = f;
			if (mode.type === 'ai' && d.b && Array.isArray(d.b.f) && d.b.f.length === N && Array.isArray(d.b.t) && d.b.t.length === N) {
				brain = new Brain();
				brain.freq = d.b.f.map((v) => num(v, 0, 1e4));
				brain.trans = d.b.t.map((row) => (Array.isArray(row) && row.length === N ? row.map((v) => num(v, 0, 1e4)) : new Array(N).fill(0)));
				brain.last = Number.isInteger(d.b.l) && d.b.l >= -1 && d.b.l < N ? d.b.l : -1;
			}
			gen++;
			timers.forEach((t) => clearTimeout(t));
			timers.clear();
			localPicks = [null, null];
			newRound();
		},
		start(opts = {}) {
			hardReset();
			ui.start(opts);
		},
		pause() {
			pauseTimer();
		},
		resume() {
			resumeTimer();
		},
		destroy() {
			gen++;
			timers.forEach((t) => clearTimeout(t));
			timers.clear();
			clearInterval(tInt);
			clearInterval(endTimer);
			clearTimeout(bubbleT);
			stopParticles();
			ro.disconnect();
			mo.disconnect();
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
