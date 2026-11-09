/**
 * «کوروش دیباگر» — original 2D platformer starring the site owner (code-themed). See GAME-RULES.md.
 *
 * Hero: hand-made pixel art (lib/hero.js). Forms — one at a time, a new one replaces the old:
 *   ☕ coffee (bouncing beans) · 💻 laptop (straight </> bolts) · 🚀 jetpack (ONE extra jump + short glide).
 *   🛡️ firewall = 9 s invincibility. Hit while powered → small.
 * Enemies: bug (walker) · clock (fast) · hopper «;» (jumps) · spam (flyer) · virus (spiky: don't stomp!)
 *   · firewall turret (shoots ERR). Hazards: gaps, moving platforms, crumbling «deprecated» blocks.
 * Secrets (few, hidden): one 1UP per stage (invisible block over a pit OR behind a cracked wall),
 *   one ▼ server per stage → bonus room, multi-coin blocks.
 * Goal: jump on the big DEPLOY button. Every 3rd stage is a boss arena.
 */
import { CATALOG } from '../catalog.js';
import { createCanvas } from '../lib/canvas.js';
import { store } from '../lib/store.js';
import { fa, listeners, clamp } from '../lib/util.js';
import { HERO_H, drawHero, OL } from '../lib/hero.js';
import { INTRO, STAGES as STORY, OUTRO, CHAPTERS, GAME_NAME, showComic, drawBeetle, drawL90, drawMaedeh, SIDE_STORY, SIDE_OUTRO, SIDE_NAME, AWARD, WAR_NAME, WAR_OUTRO, RAIN_NAME, RAIN_OUTRO, drawShayan } from './korosh-story.js';

export const achievements = CATALOG['korosh-run'].achievements;

const T = 16, ROWS = 15, VH = ROWS * T, CAM_TOP = 48, VIEW_H = VH - CAM_TOP, G = 0.42, GY = 13;
const SAVE = 'kmpr_kr_progress';
const STAGES_PER = 3;
const WORLDS = [
	{ name: 'سایت کاله', sky: ['#4cc3ff', '#c8f1ff'], dirt: '#c8743a', dirtD: '#8a4a1f', top: '#5ccf3d', topD: '#2f8f23', theme: 'plains', foes: ['bug', 'bug', 'hopper', 'spam'], boss: 'megabug', bossName: 'باگ دامنه منقضی' },
	{ name: 'سرورهای قرعه‌کشی', sky: ['#05060f', '#141a3a'], dirt: '#2b4c9b', dirtD: '#172b5e', top: '#5b8cff', topD: '#2b4c9b', theme: 'under', foes: ['bug', 'clock', 'turret', 'hopper'], boss: 'server', bossName: 'سرور هنگ‌کرده زیر بار' },
	{ name: 'بازار گیلدا', sky: ['#ff9d3c', '#ffe3a1'], dirt: '#e2a65a', dirtD: '#a8692c', top: '#f4c27a', topD: '#c98a3e', theme: 'desert', foes: ['virus', 'hopper', 'clock', 'spam'], boss: 'scorpion', bossName: 'غول درگاه قطع' },
	{ name: 'برج قیمت‌های زنده', sky: ['#0b1033', '#2a2f72'], dirt: '#6b7280', dirtD: '#374151', top: '#9ca3af', topD: '#4b5563', theme: 'night', foes: ['virus', 'turret', 'clock', 'hopper', 'spam'], boss: 'deadline', bossName: 'ارباب ددلاین' },
	// side story (not counted in progress): drive the L90, then reach Maedeh on foot
	{ name: 'ترافیک تهران', sky: ['#f97316', '#fde68a'], dirt: '#3f3f46', dirtD: '#27272a', top: '#71717a', topD: '#fafafa', theme: 'drive', song: 'plains', foes: [] },
	{ name: 'کوچه مائده', sky: ['#4c1d95', '#f9a8d4'], dirt: '#78716c', dirtD: '#57534e', top: '#d6d3d1', topD: '#a8a29e', theme: 'date', song: 'night', foes: [] },
	{ name: 'بومهن تا پاسداران', sky: ['#475569', '#fdba74'], dirt: '#3f3f46', dirtD: '#27272a', top: '#78716c', topD: '#e7e5e4', theme: 'war', song: 'boss', foes: [] },
	{ name: 'باران منوچهری', sky: ['#1e293b', '#94a3b8'], dirt: '#57534e', dirtD: '#44403c', top: '#78716c', topD: '#a8a29e', theme: 'rain', song: 'under', foes: [] },
];
const MAIN_WORLDS = 4, SIDE0 = 100, WAR = 102, RAIN = 103; // side stages: 100 drive + 101 on foot (Maedeh), 102 the war-days mission
const TOTAL = MAIN_WORLDS * STAGES_PER;
const H_SMALL = 22, H_BIG = 32, P_W = 12;
// programmer one-liners Korosh (or the hacker) says during play
const QUIPS = {
	start: ['فقط یه تغییر کوچیکه… 😅', 'دیپلوی جمعه؟ چی ممکنه خراب شه؟', 'امروز بدون باگ. قول می‌دم.', 'git pull… خدا کنه کانفلیکت نده'],
	hurt: ['رو سیستم من کار می‌کرد! 😤', 'این باگ نیست، فیچره!', 'کی مستقیم روی main پوش کرده؟!', 'ارور؟ تو لوکال که نبود!'],
	respawn: ['کش رو پاک کن، رفرش کن… درست شد! 🔁', 'خاموش و روشنش کردم 😎', 'Ctrl+Z!', 'یه بار دیگه بیلد بگیر…'],
	checkpoint: ['git commit -m "fix" ✓', 'یه کامیت بزنم تا خراب نشده'],
	streak: ['console.log("کار کرد!") 🎉', 'باگ‌ها رو یکی‌یکی کامنت کردم', 'تست‌ها سبزه!'],
	idle: ['منتظر بیلدم… ⏳', 'npm install هنوز داره نصب می‌کنه…', 'Stack Overflow جواب نداد 🤔'],
	steal: ['رمزت 123456 بود؟ 😏', 'ممنون بابت سکه‌ها! 💸', 'پورتت باز بود 😈'],
	caught: ['رمز دومرحله‌ای فعال شد ✓', 'فایروال: ۱ — هکر: ۰', 'لاگت رو گرفتم!'],
	boss: ['باس رفت تو production 😎', 'ریفکتورش کردم!'],
	war: ['پسرخاله: نزدیک بود! 😰', 'فقط فایل‌ها رو برسونیم…', 'سرعت! سرعت!', 'خدا به خیر کنه…'],
	die: ['از کد، بی‌کد شدم! 💀', 'Segmentation fault (core dumped)', '۴۰۴: کوروش پیدا نشد', 'Stack Overflow شدم!', 'try کردم، catch نشدم…', 'null شدم… undefined هم شدم', 'باگ، منو دیباگ کرد!', 'کامیت آخرم بدون تست بود…', 'process.exit(1) 😵'],
};
const BEH = { ping: 'spam', mail: 'hopper' }; // side-story foes reuse movement of the main ones
// Shayan's corner cameo: max once per stage, only at good moments (≈2 s)
const SHAYAN_LINE = 'داری عشق می‌کنیا! 😎'; // his catchphrase, always the same
const pickQ = (k) => QUIPS[k][Math.floor(Math.random() * QUIPS[k].length)];
const LAZY = 2.6; // seconds of sleepy start at each stage
const HERO_FIT = 0.74; // full drawing (afro included) ≈ hitbox height, so the hair never clips into ceilings

const rng = (seed) => { let s = seed >>> 0; return () => ((s = (s * 1664525 + 1013904223) >>> 0) / 4294967296); };
const SIDE_SAVE = 'kmpr_kr_side';
const WAR_SAVE = 'kmpr_kr_war', RAIN_SAVE = 'kmpr_kr_rain';
const readRain = () => { try { return localStorage.getItem(RAIN_SAVE) === '1'; } catch (e) { return false; } };
const TASKS = ['دکمه پرداخت کار نمی‌کنه', 'ارور ۵۰۰ موقع لاگین', 'فرم تماس ایمیل نمی‌فرسته', 'سایت کش شده، تغییر دیده نمی‌شه', 'ریدایرکت بی‌نهایت', 'عکس‌ها لود نمی‌شن', 'منو توی موبایل بازه'];
const readWar = () => { try { return localStorage.getItem(WAR_SAVE) === '1'; } catch (e) { return false; } };
const readSide = () => { try { return Number(localStorage.getItem(SIDE_SAVE)) || 0; } catch (e) { return 0; } };
const writeSide = (v) => { try { if (v > readSide()) localStorage.setItem(SIDE_SAVE, String(v)); } catch (e) { /* ignore */ } };
const readProgress = () => { try { return Math.max(0, Math.min(TOTAL - 1, Number(localStorage.getItem(SAVE)) || 0)); } catch (e) { return 0; } };
const writeProgress = (n) => { if (n >= SIDE0) return; try { if (n > readProgress()) localStorage.setItem(SAVE, String(Math.min(TOTAL - 1, n))); } catch (e) { /* ignore */ } };

/* ------------------------------------------------------------------ levels */
// # ground · F cracked fake wall (walk through) · B brick · ? coin · M multi-coin · P power · * shield · H hidden 1UP
// U used · S/s server · W/w secret server · = platform · X crumbling block · o coin
function buildLevel(n) {
	const world = Math.floor(n / STAGES_PER), st = n % STAGES_PER, isBoss = st === 2;
	const wd = WORLDS[world];
	const r = rng(4242 + n * 7919);
	const W = isBoss ? 120 : 180 + st * 25 + world * 12;
	const map = Array.from({ length: ROWS }, () => Array(W).fill('.'));
	const ents = [], warps = [];
	const set = (x, y, c) => x >= 0 && x < W && y >= 0 && y < ROWS && (map[y][x] = c);
	let x = 0, calm = 0;
	const flat = (len) => { for (let i = 0; i < len && x < W; i++, x++) for (let y = GY; y < ROWS; y++) set(x, y, '#'); };
	const enemy = (t, tx, ty = GY - 1) => { if (calm <= 0) ents.push({ t, x: tx * T, y: ty * T }); };
	const foe = () => wd.foes[Math.floor(r() * wd.foes.length)];
	const diff = Math.min(1, (n + 1) / TOTAL);
	const endX = isBoss ? W - 34 : W - 30;
	let power = 0, shield = r() < 0.5, secret = false, oneUp = false, alcove = false;
	flat(14);
	set(8, GY - 4, 'P'); power++;
	set(7, GY - 4, 'B'); set(9, GY - 4, 'B');
	while (x < endX) {
		calm--;
		const k = r();
		if (k < 0.12 + diff * 0.08) {
			// plain gap (max 4) — the hidden 1UP may sit high above the middle
			const gw = 2 + Math.floor(r() * (1 + diff * 2));
			if (!oneUp && gw >= 3 && x > 40 && r() < 0.5) { set(x + Math.floor(gw / 2), GY - 6, 'H'); oneUp = true; }
			x += gw;
			calm = 1;
			flat(3);
		} else if (k < 0.22 && n > 0) {
			// wide gap with a moving platform
			const gw = 6;
			ents.push({ t: 'mover', x: (x + 1) * T, y: GY * T - 4, x0: (x + 1) * T, range: (gw - 4) * T, w: 48, h: 8, sp: 0.9 + diff * 0.6, dx: 0, ph: r() * 6 });
			x += gw;
			calm = 1;
			flat(3);
		} else if (k < 0.38) {
			const len = 3 + Math.floor(r() * 4), y = GY - 4;
			for (let i = 0; i < len; i++) set(x + 2 + i, y, r() < 0.3 ? '?' : 'B');
			const mid = x + 2 + Math.floor(len / 2);
			if (power < 2 && x > W * 0.35 && r() < 0.5) { set(mid, y, 'P'); power++; }
			else if (r() < 0.25) set(mid, y, 'M');
			if (shield && x > W * 0.5 && r() < 0.4) { set(mid, y - 4, '*'); shield = false; }
			enemy(foe(), x + 4);
			flat(len + 4);
		} else if (k < 0.48) {
			const h = 2 + Math.floor(r() * 2); // max 3 tiles: always jumpable (jump apex ≈ 3.9 tiles)
			flat(2);
			const sec = !secret && !isBoss && x > 50;
			for (let y = GY - h; y < GY; y++) { set(x, y, 'S'); set(x + 1, y, 's'); }
			const tx0 = x;
			if (sec) { set(x, GY - h, 'W'); set(x + 1, GY - h, 'w'); secret = true; }
			flat(2);
			if (sec) {
				flat(3);
				const ex = x; // exit tower: you pop out of here when leaving the bonus room
				for (let y = GY - 2; y < GY; y++) { set(ex, y, 'S'); set(ex + 1, y, 's'); }
				warps.push({ tx: tx0, ty: GY - h, back: ex });
				flat(2);
			} else enemy(foe(), x + 1);
			flat(3);
		} else if (k < 0.6) {
			// floating platforms (crumbling in later worlds) over a gap
			const y = GY - 3 - Math.floor(r() * 2), len = 3 + Math.floor(r() * 2), crumble = world >= 2 || (world === 1 && r() < 0.4);
			flat(1);
			const gx = x;
			x += len + 2;
			for (let i = 0; i < len; i++) set(gx + 1 + i, y, crumble ? 'X' : '=');
			if (r() < 0.5) set(gx + 1 + Math.floor(len / 2), y - 1, 'o');
			if (r() < 0.35 + diff * 0.3) enemy('spam', gx + 2, y - 4);
			calm = 1;
			flat(3);
		} else if (k < 0.7 && !alcove && x > 30 && !isBoss) {
			// secret alcove behind a cracked wall: walk into the left face of the block
			flat(1);
			const x0 = x;
			flat(7);
			// 3 tiles tall (climbable), 2-tile-high hidden room inside
			for (let i = 0; i < 6; i++) for (let y = GY - 3; y < GY; y++) set(x0 + i, y, '#');
			for (let y = GY - 2; y < GY; y++) set(x0, y, 'F');
			for (let i = 1; i < 5; i++) for (let y = GY - 2; y < GY; y++) set(x0 + i, y, '.');
			set(x0 + 2, GY - 1, 'o'); set(x0 + 3, GY - 1, 'o');
			if (!oneUp) { ents.push({ t: 'oneup', x: (x0 + 3) * T, y: (GY - 1) * T, vx: 0, vy: 0, w: 12, h: 14, alive: true, item: true, still: true }); oneUp = true; }
			alcove = true;
			enemy(foe(), x0 + 3, GY - 4);
		} else if (k < 0.82) {
			// pyramid (no pit between the two flights), max 3 high
			const h = 2 + Math.floor(r() * 2);
			for (let i = 0; i < h; i++) for (let j = 0; j <= i; j++) set(x + i, GY - 1 - j, '#');
			for (let j = 0; j < h; j++) set(x + h, GY - 1 - j, '#');
			for (let i = 0; i < h; i++) for (let j = 0; j < h - i; j++) set(x + h + 1 + i, GY - 1 - j, '#');
			if (r() < 0.4) enemy(foe(), x + h + 2 + h);
			flat(h * 2 + 2);
		} else {
			if (r() < 0.35) for (let i = 0; i < 3; i++) set(x + 2 + i, GY - 3, 'o');
			enemy(foe(), x + 6);
			if (n >= 3 && r() < 0.5) enemy(foe(), x + 9);
			flat(10);
		}
	}
	if (!secret && !isBoss) {
		flat(2);
		for (let y = GY - 3; y < GY; y++) { set(x, y, 'S'); set(x + 1, y, 's'); }
		set(x, GY - 3, 'W'); set(x + 1, GY - 3, 'w');
		const tx0 = x;
		flat(5);
		for (let y = GY - 2; y < GY; y++) { set(x - 1, y, 'S'); set(x, y, 's'); }
		warps.push({ tx: tx0, ty: GY - 3, back: x - 1 });
		flat(4);
	}
	let arena = null;
	if (isBoss) {
		flat(4);
		arena = { x0: x, x1: x + 26 };
		flat(W - x);
		for (let y = 3; y < GY; y++) set(arena.x1 + 1, y, '#');
		for (let i = 0; i < 3; i++) set(arena.x0 + 6 + i * 7, GY - 4, i === 1 ? 'P' : '=');
	} else {
		flat(W - x);
	}
	if (world === 1) for (let i = 0; i < W; i++) if (map[3][i] === '.') map[3][i] = '#';
	let cxT = Math.floor(isBoss ? arena.x0 - 8 : W / 2);
	while (cxT < W - 20 && map[GY][cxT] !== '#') cxT++;
	const goalX = isBoss ? (arena.x1 - 4) * T : (W - 10) * T;
	return { map, W, ents, warps, arena, goal: { x: goalX, y: GY * T }, check: cxT * T, isBoss };
}
function buildSide(n) {
	if (n === RAIN) return buildRain(n);
	const war = n === WAR, drive = n === SIDE0 || war, r = rng(9001 + n * 31), W = war ? 210 : drive ? 230 : 170;
	const map = Array.from({ length: ROWS }, () => Array(W).fill('.'));
	const ents = [];
	const set = (x, y, c) => x >= 0 && x < W && y >= 0 && y < ROWS && (map[y][x] = c);
	let x = 0;
	const flat = (len) => { for (let i = 0; i < len && x < W; i++, x++) for (let y = GY; y < ROWS; y++) set(x, y, '#'); };
	flat(16);
	while (x < W - 30) {
		const k = r();
		if (drive) {
			if (war) { if (k < 0.2) { flat(3); set(x, GY - 1, '#'); flat(8); } else if (k < 0.45) { for (let i = 0; i < 3; i++) set(x + 3 + i, GY - 3, 'o'); flat(10); } else flat(12); continue; } // open road: the danger comes from the sky
			if (k < 0.24) { x += 2; flat(5); } // pothole
			else if (k < 0.42) { flat(3); set(x, GY - 1, '#'); flat(6); } // speed bump
			else if (k < 0.66) { ents.push({ t: 'car', x: (x + 10) * T, y: (GY - 1) * T }); flat(14); } // oncoming Pride
			else if (k < 0.82) { ents.push({ t: 'cone', x: (x + 5) * T, y: (GY - 1) * T }); flat(9); }
			else { for (let i = 0; i < 4; i++) set(x + 3 + i, GY - 3, 'o'); flat(9); }
		} else {
			if (k < 0.14) { x += 2 + (r() < 0.4 ? 1 : 0); flat(4); }
			else if (k < 0.34) { const y = GY - 3 - Math.floor(r() * 2), len = 3 + Math.floor(r() * 3); flat(2); for (let i = 0; i < len; i++) { set(x + i, y, '='); if (i % 2) set(x + i, y - 1, 'o'); } ents.push({ t: 'ping', x: (x + 2) * T, y: (y - 4) * T }); flat(len + 3); }
			else if (k < 0.54) { ents.push({ t: 'call', x: (x + 6) * T, y: (GY - 1) * T }); set(x + 3, GY - 3, 'o'); flat(10); }
			else if (k < 0.7) { ents.push({ t: 'mail', x: (x + 6) * T, y: (GY - 1) * T }); flat(10); }
			else if (k < 0.82) { const h = 2; for (let i = 0; i < h; i++) for (let j = 0; j <= i; j++) set(x + 1 + i, GY - 1 - j, '#'); set(x + 2, GY - 3, 'o'); flat(5); }
			else { for (let i = 0; i < 3; i++) set(x + 2 + i, GY - 2, 'o'); flat(8); }
		}
	}
	flat(W - x);
	let cxT = Math.floor(W / 2);
	while (cxT < W - 20 && map[GY][cxT] !== '#') cxT++;
	return { map, W, ents, warps: [], arena: null, goal: { x: (W - 10) * T, y: GY * T }, check: cxT * T, isBoss: false, side: war ? 'war' : drive ? 'drive' : 'date' };
}
function buildRain(n) {
	const r = rng(5150 + n), W = 190;
	const map = Array.from({ length: ROWS }, () => Array(W).fill('.'));
	const ents = [], puddles = [];
	const set = (x, y, c) => x >= 0 && x < W && y >= 0 && y < ROWS && (map[y][x] = c);
	let x = 0;
	const flat = (len) => { for (let i = 0; i < len && x < W; i++, x++) for (let y = GY; y < ROWS; y++) set(x, y, '#'); };
	flat(16);
	while (x < W - 30) {
		const k = r();
		if (k < 0.16) { x += 2 + (r() < 0.4 ? 1 : 0); flat(4); }
		else if (k < 0.36) { puddles.push([x + 1, x + 5]); flat(8); } // puddle: slows you down
		else if (k < 0.54) { const y = GY - 3 - Math.floor(r() * 2), len = 3 + Math.floor(r() * 3); flat(2); for (let i = 0; i < len; i++) { set(x + i, y, '='); if (i % 2) set(x + i, y - 1, 'o'); } flat(len + 3); }
		else if (k < 0.72) { ents.push({ t: 'bug', x: (x + 6) * T, y: (GY - 1) * T }); flat(10); }
		else if (k < 0.84) { for (let i = 0; i < 2; i++) for (let j = 0; j <= i; j++) set(x + 1 + i, GY - 1 - j, '#'); flat(6); }
		else { for (let i = 0; i < 3; i++) set(x + 2 + i, GY - 2, 'o'); flat(8); }
	}
	flat(W - x);
	let cxT = Math.floor(W / 2);
	while (cxT < W - 20 && map[GY][cxT] !== '#') cxT++;
	return { map, W, ents, warps: [], arena: null, goal: { x: (W - 10) * T, y: GY * T }, check: cxT * T, isBoss: false, side: 'rain', puddles };
}
function buildRoom(n) {
	const W = 32;
	const map = Array.from({ length: ROWS }, () => Array(W).fill('.'));
	for (let x = 0; x < W; x++) { for (let y = GY; y < ROWS; y++) map[y][x] = '#'; map[3][x] = '#'; }
	for (let y = 3; y < GY; y++) { map[y][0] = '#'; map[y][W - 1] = '#'; }
	map[GY - 2][W - 5] = 'W'; map[GY - 2][W - 4] = 'w'; map[GY - 1][W - 5] = 'S'; map[GY - 1][W - 4] = 's';
	for (let x = 5; x < 24; x += 2) for (let y = 7; y < 11; y += 2) map[y][x + ((y + n) % 4 ? 1 : 0)] = 'o';
	map[9][12] = 'M'; map[9][19] = 'M';
	return { map, W, ents: [], warps: [{ tx: W - 5, ty: GY - 2, exit: true }], arena: null, goal: null, check: 0, isRoom: true };
}

/* ------------------------------------------------------------------ music (original, bouncy classic-platformer style) */
const midi = (name) => { if (name === '.') return -1; const m = /^([A-G])(#|b)?(\d)$/.exec(name); return 12 * (Number(m[3]) + 1) + { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 }[m[1]] + (m[2] === '#' ? 1 : m[2] === 'b' ? -1 : 0); };
const chord = (c) => { const m = /^([A-G])(#|b)?(m)?$/.exec(c); const root = { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 }[m[1]] + (m[2] === '#' ? 1 : m[2] === 'b' ? -1 : 0); return { root, tones: [0, m[3] ? 3 : 4, 7].map((t) => (root + t) % 12) }; };
const song = (bpm, lead, chords) => ({ bpm, lead: lead.trim().split(/\s+/).map(midi), chords: chords.trim().split(/\s+/).map(chord) });
const SONGS = {
	plains: song(176, 'C5 . E5 G5 . E5 A5 .  G5 . E5 . C5 D5 E5 .  F5 . A5 C6 . A5 D6 .  C6 . . B5 A5 . G5 .  A5 . F5 . A5 G5 . E5  F5 . D5 . F5 E5 . C5  D5 E5 F5 G5 A5 B5 C6 .  G5 . E5 . C5 . . .  E5 . E5 F5 G5 . C6 .  B5 . G5 . E5 . D5 .  A4 . C5 D5 E5 . A5 .  G5 . E5 . D5 . C5 .  F5 . F5 G5 A5 . F5 .  E5 . C5 . G4 . C5 .  D5 . G5 . F5 E5 D5 .  C5 . G4 . C5 . . .', 'C C F C F Dm G C C G Am C F C G C'),
	under: song(150, 'A4 . A5 . . G5 E5 .  . . C5 . D5 . E5 .  A4 . A5 . . G5 E5 .  . . D5 . C5 . B4 .  F4 . F5 . . E5 C5 .  . . A4 . B4 . C5 .  E5 . D5 . C5 . B4 .  A4 . . . E4 . . .', 'Am Am Am E F F E Am'),
	desert: song(160, 'D5 . F#5 G5 A5 . G5 F#5  G5 . Eb5 . D5 . . .  C5 . Eb5 F#5 G5 . F#5 Eb5  D5 . C5 . D5 . . .  A5 . Bb5 A5 G5 . F#5 G5  A5 . G5 F#5 Eb5 . D5 .  C5 D5 Eb5 F#5 G5 A5 Bb5 A5  D5 . . . D4 . . .', 'D Gm Cm D Gm D Cm D'),
	night: song(168, 'E5 . . B4 E5 . G5 .  F#5 . D5 . B4 . . .  C5 . E5 . G5 . C6 .  B5 . . . G5 . . .  A5 . G5 . F#5 . E5 .  D5 . E5 . F#5 . B4 .  E5 F#5 G5 A5 B5 . A5 G5  E5 . . . . . . .', 'Em D C G Am D Em Em'),
	boss: song(192, 'C5 . C5 D#5 . C5 F5 .  F#5 . F5 . D#5 . C5 .  C5 . C5 D#5 . C5 G5 .  F#5 . F5 . D#5 . D5 .  G#4 . C5 . D#5 . G#5 .  G5 . F5 . D#5 . D5 .  C5 D5 D#5 F5 G5 G#5 B5 C6  C5 . . . G4 . . .', 'Cm Cm Cm G G# G# G Cm'),
	room: song(184, 'C6 . G5 . E5 . G5 .  A5 . F5 . D5 . F5 .  E5 . C5 . E5 . G5 .  F5 E5 D5 C5 B4 . G4 .', 'C F C G'),
};
function createMusic() {
	let ac = null, master = null, p25 = null, p12 = null, timer = 0, step = 0, cur = null, next = 0, on = false, speed = 1, nbuf = null;
	const hz = (m) => 440 * Math.pow(2, (m - 69) / 12);
	const wave = (duty) => { const n = 32, re = new Float32Array(n), im = new Float32Array(n); for (let i = 1; i < n; i++) im[i] = (2 / (i * Math.PI)) * Math.sin(i * Math.PI * duty); return ac.createPeriodicWave(re, im); };
	const ensure = () => {
		if (ac) return true;
		try { ac = new (window.AudioContext || window.webkitAudioContext)(); master = ac.createGain(); master.gain.value = 0.5; master.connect(ac.destination); p25 = wave(0.25); p12 = wave(0.125); return true; } catch (e) { return false; }
	};
	const tone = (w, f, t, d, v, slide) => {
		const o = ac.createOscillator(), g = ac.createGain();
		if (w === 'p25') o.setPeriodicWave(p25); else if (w === 'p12') o.setPeriodicWave(p12); else o.type = w;
		o.frequency.setValueAtTime(f, t);
		if (slide) o.frequency.exponentialRampToValueAtTime(slide, t + d);
		g.gain.setValueAtTime(0.0001, t); g.gain.linearRampToValueAtTime(v, t + 0.005); g.gain.setValueAtTime(v, t + d * 0.7); g.gain.exponentialRampToValueAtTime(0.0008, t + d);
		o.connect(g).connect(master); o.start(t); o.stop(t + d + 0.02);
	};
	const noise = (t, d, v, hp) => {
		if (!nbuf) { nbuf = ac.createBuffer(1, ac.sampleRate * 0.3, ac.sampleRate); const dd = nbuf.getChannelData(0); for (let i = 0; i < dd.length; i++) dd[i] = Math.random() * 2 - 1; }
		const s = ac.createBufferSource(), g = ac.createGain(), f = ac.createBiquadFilter();
		f.type = 'highpass'; f.frequency.value = hp; s.buffer = nbuf; g.gain.setValueAtTime(v, t); g.gain.exponentialRampToValueAtTime(0.001, t + d);
		s.connect(f).connect(g).connect(master); s.start(t); s.stop(t + d + 0.02);
	};
	const BASS = [0, -1, -1, 7, -1, 12, 7, -1];
	const tick = () => {
		const spb = 60 / (cur.bpm * speed) / 2;
		while (next < ac.currentTime + 0.15) {
			const i = step % cur.lead.length, ch = cur.chords[Math.floor(i / 8) % cur.chords.length], t = next + (i % 2 ? spb * 0.1 : 0), m = cur.lead[i];
			if (m > 0) {
				const hold = cur.lead[(i + 1) % cur.lead.length] === -1;
				tone('p25', hz(m), t, spb * (hold ? 1.3 : 0.6), 0.07);
				for (let d = 3; d <= 9; d++) if (ch.tones.includes(((m - d) % 12 + 12) % 12)) { tone('p12', hz(m - d), t, spb * (hold ? 1.2 : 0.55), 0.03); break; }
			}
			const b = BASS[i % 8];
			if (b >= 0) tone('triangle', hz(36 + ch.root + b), t, spb * 0.85, 0.24);
			if (i % 8 === 0 || i % 8 === 5) tone('sine', 140, t, 0.09, 0.3, 50);
			if (i % 8 === 2 || i % 8 === 6) noise(t, 0.09, 0.11, 1800);
			noise(t, 0.025, 0.03, 7000);
			next += spb;
			step++;
		}
	};
	const sfx = (kind) => {
		if (!on || !ensure()) return;
		const t = ac.currentTime + 0.01;
		if (kind === 'jump') return tone('p25', 300, t, 0.15, 0.06, 700);
		if (kind === 'stomp') return tone('p12', 260, t, 0.1, 0.07, 90);
		if (kind === 'bump') return tone('triangle', 130, t, 0.08, 0.15, 90);
		if (kind === 'shot') return tone('p12', 900, t, 0.08, 0.04, 400);
		if (kind === 'hit') return noise(t, 0.2, 0.2, 400);
		const seqs = { coin: [[88, 0.05], [93, 0.2]], power: [[60, 0.05], [64, 0.05], [67, 0.05], [72, 0.05], [76, 0.05], [79, 0.05], [84, 0.05], [88, 0.2]], one: [[76, 0.08], [79, 0.08], [88, 0.08], [84, 0.08], [86, 0.08], [91, 0.22]], clear: [[67, 0.1], [72, 0.1], [76, 0.1], [79, 0.2], [76, 0.1], [79, 0.5]], die: [[71, 0.12], [70, 0.12], [69, 0.12], [64, 0.45]], boss: [[48, 0.15], [51, 0.15], [54, 0.15], [57, 0.4]], down: [[72, 0.06], [67, 0.06], [64, 0.06], [60, 0.18]] }[kind];
		if (!seqs) return;
		let tt = t;
		seqs.forEach(([mm, d]) => { tone('p25', hz(mm), tt, d * 0.95, 0.08); tt += d; });
	};
	return {
		play(name, sp = 1) { clearInterval(timer); if (!on || !ensure() || !SONGS[name]) return; if (ac.resume) ac.resume(); cur = SONGS[name]; speed = sp; step = 0; next = ac.currentTime + 0.06; timer = setInterval(tick, 40); },
		setSpeed(sp) { speed = sp; },
		stop() { clearInterval(timer); },
		sfx,
		set enabled(v) { on = v; if (!v) clearInterval(timer); },
		get enabled() { return on; },
	};
}

/* ------------------------------------------------------------------ engine */
let current = null;

export function mount(stage, api) {
	api.setRatio('16/9', '16/9');
	const L = listeners();
	const cv = createCanvas(stage, { vh: VIEW_H });
	const { ctx, view } = cv;
	const music = createMusic();
	music.enabled = !!store.player.sound;
	let s = null, raf = 0, last = 0;
	const songFor = () => (s.lv.isRoom ? 'room' : s.boss && s.boss.active && s.boss.hp > 0 ? 'boss' : (WORLDS[s.world].song || WORLDS[s.world].theme));
	L.on(window, 'kmpr:sound-changed', () => { music.enabled = !!store.player.sound; if (music.enabled && s && !s.over && s.mode === 'play' && api.running) music.play(songFor()); });

	const pad = document.createElement('div');
	pad.className = 'kr-pad';
	pad.hidden = true;
	pad.innerHTML = `<div class="kr-pad__l"><button type="button" data-k="left" aria-label="چپ">◀</button><button type="button" data-k="down" aria-label="پایین (سرور مخفی)">▼</button><button type="button" data-k="right" aria-label="راست">▶</button></div>
		<div class="kr-pad__r"><button type="button" data-k="fire" class="kr-pad__b" aria-label="قدرت">⚡</button><button type="button" data-k="jump" class="kr-pad__a" aria-label="پرش">⤒</button></div>`;
	stage.appendChild(pad);

	const mapEl = document.createElement('div');
	mapEl.className = 'kr-map';
	mapEl.hidden = true;
	stage.appendChild(mapEl);
	const renderMap = () => {
		const unlocked = readProgress();
		mapEl.innerHTML = `<div class="kr-map__box">
			<div class="kr-map__hero"><span class="kr-map__face" aria-hidden="true"></span><div><p class="kr-map__k">${GAME_NAME}</p><p class="kr-map__t">یک مرحله را انتخاب کن</p></div><button type="button" class="kr-map__story" data-story>📖 داستان</button>${unlocked >= 6 ? '<button type="button" class="kr-map__story" data-award>🏆 جشن ماکان</button>' : ''}</div>
			${WORLDS.slice(0, MAIN_WORLDS).map((w, wi) => `<div class="kr-map__world" style="--a:${w.top};--b:${w.sky[0]}"><p class="kr-map__wn">دنیای ${fa(wi + 1)} · ${w.name} <small>${CHAPTERS[wi]}</small></p><div class="kr-map__row">${Array.from({ length: STAGES_PER }, (_, si) => {
				const n = wi * STAGES_PER + si, open = n <= unlocked, boss = si === 2;
				return `<button type="button" class="kr-map__st${n === unlocked ? ' is-next' : ''}${boss ? ' is-boss' : ''}" data-st="${n}" ${open ? '' : 'disabled'} aria-label="دنیای ${fa(wi + 1)} مرحله ${fa(si + 1)}${boss ? ' (باس)' : ''}${open ? '' : ' (قفل)'}">${open ? (boss ? '👾 باس' : `مرحله ${fa(si + 1)}`) : '🔒'}</button>`;
			}).join('<i aria-hidden="true"></i>')}</div></div>`).join('')}
			${(() => { const open = unlocked >= 3, done = readSide(); return `<div class="kr-map__world kr-map__side" style="--a:#f472b6;--b:#7c3aed"><p class="kr-map__wn">💗 داستان فرعی · ${SIDE_NAME} <small>${open ? (done >= 2 ? 'مائده خوشحال است ✓' : 'مائده از اضافه‌کاری‌ها شاکی است؛ برو دیدنش!') : 'بعد از دنیای ۱ باز می‌شود'}</small></p><div class="kr-map__row"><button type="button" class="kr-map__st${open && !done ? ' is-next' : ''}" data-st="${SIDE0}" ${open ? '' : 'disabled'} aria-label="داستان فرعی: رانندگی با ال ۹۰">${open ? '🚗 ال ۹۰' : '🔒'}</button><i aria-hidden="true"></i><button type="button" class="kr-map__st" data-st="${SIDE0 + 1}" ${open && done >= 1 ? '' : 'disabled'} aria-label="داستان فرعی: کوچه مائده">${open && done >= 1 ? '🌹 کوچه مائده' : '🔒'}</button></div></div>`; })()}
			${(() => { const open = unlocked >= 4, done = readRain(); return `<div class="kr-map__world kr-map__side" style="--a:#60a5fa;--b:#334155"><p class="kr-map__wn">🎒 داستان فرعی · ${RAIN_NAME} <small>${open ? (done ? 'زیپ درست شد ✓' : 'زیپ کیف لپ‌تاپ خراب شده؛ زیر باران تا منوچهری برو') : 'بعد از مرحله ۵ باز می‌شود'}</small></p><div class="kr-map__row"><button type="button" class="kr-map__st${open && !done ? ' is-next' : ''}" data-st="${RAIN}" ${open ? '' : 'disabled'} aria-label="داستان فرعی: زیپ کیف">${open ? '☔ منوچهری' : '🔒'}</button></div></div>`; })()}
			${(() => { const open = unlocked >= 6, done = readWar(); return `<div class="kr-map__world kr-map__side" style="--a:#fb923c;--b:#475569"><p class="kr-map__wn">🛰 ماموریت فرعی · ${WAR_NAME} <small>${open ? (done ? 'فایل‌های شبا نجات پیدا کرد ✓' : 'روزهای جنگ؛ فایل‌های سایت شبا را از شرکت بیاور') : 'بعد از دنیای ۲ باز می‌شود'}</small></p><div class="kr-map__row"><button type="button" class="kr-map__st${open && !done ? ' is-next' : ''}" data-st="${WAR}" ${open ? '' : 'disabled'} aria-label="ماموریت فرعی: بومهن تا پاسداران">${open ? '🚙 ۲۰۷ پسرخاله' : '🔒'}</button></div></div>`; })()}
			<p class="kr-map__h">${api.touch ? 'گوشی را افقی کن · ◀ ▶ حرکت · ⤒ پرش · ⚡ قدرت · ▼ سرور مخفی' : '← → حرکت · Space پرش · Shift دویدن · X قدرت · ↓ ورود به سرور مخفی'}</p>
			<p class="kr-map__h">هر مرحله فقط یک جان اضافه دارد و خوب پنهان شده؛ به دیوارهای ترک‌خورده و سرورهای ▼ دقت کن 🤫</p>
		</div>`;
		const face = mapEl.querySelector('.kr-map__face');
		if (face) {
			const c = document.createElement('canvas');
			c.width = 112; c.height = 112;
			const fx = c.getContext('2d');
			fx.scale(4, 4);
			drawHero(fx, 15, 14, 'big', 'none', 'stand', 1);
			face.appendChild(c);
		}
		const nb = mapEl.querySelector('.is-next') || mapEl.querySelector('[data-st]');
		if (nb) setTimeout(() => nb.focus({ preventScroll: true }), 30);
	};
	L.on(mapEl, 'click', (e) => {
		if (e.target.closest('[data-award]') && s) { mapEl.hidden = true; comic = showComic(stage, AWARD, { worlds: WORLDS, sound: (n) => api.sfx(n), label: 'جشن ماکان 🏆', done: () => { comic = null; mapEl.hidden = false; renderMap(); } }); return; }
		if (e.target.closest('[data-story]') && s) { mapEl.hidden = true; comic = showComic(stage, INTRO, { worlds: WORLDS, sound: (n) => api.sfx(n), done: () => { mapEl.hidden = false; renderMap(); } }); return; }
		const b = e.target.closest('[data-st]');
		if (!b || b.disabled || !s) return;
		mapEl.hidden = true;
		playStage(Number(b.dataset.st));
	});
	// comic panel before every stage, then play
	let comic = null;
	const playStage = (n) => {
		s.mode = 'story';
		pad.hidden = true;
		music.stop();
		const side = n >= SIDE0, w = side ? MAIN_WORLDS + (n - SIDE0) : Math.floor(n / STAGES_PER), boss = !side && n % STAGES_PER === STAGES_PER - 1;
		comic = showComic(stage, side ? SIDE_STORY[n - SIDE0].map((pn) => ({ ...pn, world: w })) : [{ ...STORY[n], world: w, boss, tired: boss ? 0 : 0.85 }], {
			worlds: WORLDS, sound: (n) => api.sfx(n),
			label: side ? `${n === RAIN ? RAIN_NAME : n === WAR ? WAR_NAME : `داستان فرعی · ${SIDE_NAME}`} · ${WORLDS[w].name}` : `${CHAPTERS[w]} · ${boss ? 'نبرد با باس' : `مرحله ${fa((n % STAGES_PER) + 1)}`}`,
			skip: false,
			done: () => { comic = null; if (!s || s.over) return; s.mode = 'play'; pad.hidden = !api.touch; loadStage(n, false); loop(); },
		});
	};

	const keys = { left: false, right: false, jump: false, fire: false, run: false, down: false };
	let jumpPressed = 0, firePressed = false;

	const tileAt = (px, py) => {
		const tx = Math.floor(px / T), ty = Math.floor(py / T);
		if (ty < 0) return '.';
		if (ty >= ROWS || tx < 0 || tx >= s.lv.W) return tx < 0 ? '#' : '.';
		return s.lv.map[ty][tx];
	};
	const SOLID = new Set(['#', 'B', '?', 'M', 'P', '*', 'U', 'S', 's', 'W', 'w', '=', 'X']);
	const solid = (c) => SOLID.has(c);
	const BLOCKS = new Set(['?', 'M', 'P', '*', 'H']);
	const FOE_W = { car: [30, 14], cone: [12, 12], call: [12, 14], ping: [14, 12], mail: [14, 12], bug: [14, 14], clock: [14, 14], hopper: [14, 12], spam: [14, 12], virus: [14, 14], turret: [16, 16] };

	const loadStage = (n, fromCheckpoint) => {
		const lv = n >= SIDE0 ? buildSide(n) : buildLevel(n);
		s.stageN = n;
		s.world = n >= SIDE0 ? MAIN_WORLDS + (n - SIDE0) : Math.floor(n / STAGES_PER);
		Object.keys(keys).forEach((kk) => (keys[kk] = false));
		s.main = lv;
		s.lv = lv;
		s.ents = lv.ents.map((e) => {
			if (e.t === 'mover' || e.item) return { ...e, alive: true };
			const [w, h] = FOE_W[e.t];
			return { ...e, w, h, y: e.y + 16 - h, vx: e.t === 'car' ? -1.3 : e.t === 'cone' ? 0 : e.t === 'clock' ? -1.1 : e.t === 'turret' ? 0 : -0.5, vy: 0, alive: true, y0: e.y, dead: 0, cd: 1 + Math.random() };
		});
		s.beans = []; s.shots = []; s.parts = []; s.pops = []; s.multi = {}; s.crumble = {};
		s.boss = lv.isBoss ? makeBoss(WORLDS[s.world].boss, lv.arena) : null;
		s.goalOn = !lv.isBoss;
		s.camMin = 0;
		if (!fromCheckpoint) s.checkpoint = false;
		const sx = fromCheckpoint && s.checkpoint ? lv.check : 3 * T;
		s.p = { x: sx, y: GY * T - (s.big ? H_BIG : H_SMALL), vx: 0, vy: 0, w: P_W, h: s.big ? H_BIG : H_SMALL, face: 1, ground: true, coyote: 0, inv: fromCheckpoint ? 2 : 0, anim: 0, jumps: 0, fuel: 0.8 };
		// safe respawn: no enemy waiting on (or right next to) the spawn point
		if (fromCheckpoint) s.ents = s.ents.filter((e) => e.item || e.t === 'mover' || Math.abs(e.x - sx) > 6 * T);
		s.cam = clamp(sx - 80, 0, lv.W * T - view.vw);
		s.timeLeft = lv.isBoss ? 240 : 300;
		s.won = 0; s.dying = 0; s.flawless = true; s.pressed = false; s.lazy = 0;
		if (!fromCheckpoint && !lv.isRoom && !lv.side) { s.lazy = LAZY; if (Math.random() < 0.6) setTimeout(() => s && quip('start', true), 700); }
		if (fromCheckpoint) setTimeout(() => s && quip('respawn', true), 500);
		s.hk = lv.isBoss || lv.isRoom || lv.side ? null : { st: 'wait', timer: 14 + Math.random() * 16 };
		s.quip = null; s.idle = 0;
		if (!fromCheckpoint) { s.shayUsed = false; s.stageKills = 0; }
		s.shay = null;
		if (lv.side === 'drive' || lv.side === 'war') { s.p.h = 16; s.p.w = 24; s.p.y = GY * T - 16; s.carHp = 3; }
		s.bombs = []; s.bombT = 2.5; s.shake = 0;
		s.task = null; s.taskT = 3; s.tasksDone = 0;
		if (lv.side === 'date') { s.pat = 100; }
		api.hud({ level: lv.side ? 'فرعی' : `${fa(n + 1)} از ${fa(TOTAL)}`, levelLabel: 'مرحله', lives: s.lives, extra: fa(s.coins), extraLabel: 'سکه' });
		s.banner = { t: lv.side ? (lv.side === 'rain' ? '📌 تسک که رسید، باگش را قبل از تمام شدن وقت دیباگ کن!' : lv.side === 'war' ? '⚠ دایره قرمز = خطر! ← ترمز · → گاز · پرش هم داری' : lv.side === 'drive' ? '🚗 فقط بپر! ال ۹۰ خودش می‌رود' : '🌹 گل جمع کن؛ حوصله مائده دارد تمام می‌شود') : lv.isBoss ? `${WORLDS[s.world].name} · نبرد با باس` : `${WORLDS[s.world].name} · مرحله ${fa((n % STAGES_PER) + 1)}`, life: 2.6 };
		music.play(songFor());
	};
	const addScore = (v, x, y) => { s.score += v; api.setScore(s.score); if (x !== undefined) s.pops.push({ x, y, t: `+${fa(v)}`, life: 0.8 }); };
	const snd = (m, fallback) => (music.enabled ? music.sfx(m) : fallback && api.sfx(fallback));

	const setForm = (form) => {
		const p = s.p;
		if (!s.big) { s.big = true; p.y -= H_BIG - H_SMALL; p.h = H_BIG; }
		if (s.big && s.form && s.form !== form) setTimeout(() => cameo('power'), 400); // second power-up in a row
		s.formsSeen.add(form);
		s.form = form;
		if (s.formsSeen.size >= 3) api.unlock('forms');
		s.banner = { t: { coffee: '☕ قهوه: X برای پرتاب دانه', dev: '💻 لپ‌تاپ: X برای شلیک کد', jet: '🚀 جت‌پک: یک پرش اضافه در هوا' }[form], life: 1.8 };
		snd('power', 'level');
	};

	/* ---- collisions ---- */
	const moveX = (o) => {
		o.x += o.vx;
		const ys = [];
		for (let yy = o.y + 1; yy < o.y + o.h - 1; yy += 8) ys.push(yy);
		ys.push(o.y + o.h - 1);
		if (o.vx > 0) { const ex = o.x + o.w; for (const yy of ys) if (solid(tileAt(ex, yy))) { o.x = Math.floor(ex / T) * T - o.w - 0.01; o.vx = 0; return true; } }
		else if (o.vx < 0) { for (const yy of ys) if (solid(tileAt(o.x, yy))) { o.x = (Math.floor(o.x / T) + 1) * T + 0.01; o.vx = 0; return true; } }
		return false;
	};
	const moveY = (o, isPlayer) => {
		o.y += o.vy;
		o.ground = false;
		const l = o.x + 1, r = o.x + o.w - 1;
		if (o.vy > 0) {
			const by = o.y + o.h;
			for (const xx of [l, r]) if (solid(tileAt(xx, by))) { o.y = Math.floor(by / T) * T - o.h; o.vy = 0; o.ground = true; return; }
		} else if (o.vy < 0) {
			for (const xx of [o.x + o.w / 2, l + 2, r - 2]) {
				const c = tileAt(xx, o.y);
				if ((solid(c) && c !== '=') || (isPlayer && c === 'H')) {
					const tx = Math.floor(xx / T), ty = Math.floor(o.y / T);
					o.y = (ty + 1) * T; o.vy = 0.5;
					if (isPlayer) bump(tx, ty);
					return;
				}
			}
		}
	};
	const spawnItem = (t, tx, ty) => s.ents.push({ t, x: tx * T + 2, y: (ty - 1) * T, vx: t === 'shield' ? 1.6 : 0.8, vy: t === 'shield' ? -5 : -2, w: 12, h: 14, alive: true, item: true });
	const bump = (tx, ty) => {
		if (ty < 0 || ty >= ROWS) return;
		const c = s.lv.map[ty][tx];
		if (BLOCKS.has(c)) {
			const key = `${tx},${ty}`;
			if (c === 'M') {
				s.multi[key] = (s.multi[key] || 0) + 1;
				coin(tx * T + 4, (ty - 1) * T);
				s.parts.push({ k: 'coinjump', x: tx * T + 4, y: ty * T - 8, vy: -4, life: 0.45 });
				if (s.multi[key] >= 6) s.lv.map[ty][tx] = 'U';
			} else {
				s.lv.map[ty][tx] = 'U';
				if (c === 'P') spawnItem(!s.big ? 'coffee' : tx % 2 ? 'laptop' : 'jetpack', tx, ty);
				else if (c === '*') spawnItem('shield', tx, ty);
				else if (c === 'H') { spawnItem('oneup', tx, ty); api.unlock('secret'); s.banner = { t: 'بلوک مخفی پیدا شد! 💾', life: 1.4 }; }
				else { coin(tx * T + 4, (ty - 1) * T); s.parts.push({ k: 'coinjump', x: tx * T + 4, y: ty * T - 8, vy: -4, life: 0.45 }); }
				if (c !== '?') snd('one');
			}
			s.parts.push({ k: 'bump', x: tx, y: ty, life: 0.15 });
		} else if (c === 'B') {
			if (s.big) {
				s.lv.map[ty][tx] = '.';
				for (let i = 0; i < 4; i++) s.parts.push({ k: 'brick', x: tx * T + 8, y: ty * T + 8, vx: (i % 2 ? 1 : -1) * (1 + Math.random()), vy: -3 - i, life: 1 });
				addScore(50);
				api.sfx('boom');
			} else { s.parts.push({ k: 'bump', x: tx, y: ty, life: 0.15 }); snd('bump'); }
		} else snd('bump');
		s.ents.forEach((e) => { if (e.alive && !e.item && e.t !== 'mover' && Math.abs(e.x + e.w / 2 - (tx * T + 8)) < 14 && Math.abs(e.y + e.h - ty * T) < 4) kill(e, true); });
	};
	const coin = (x, y) => {
		s.coins++;
		if (s.lv.side === 'date') s.pat = Math.min(100, s.pat + 5);
		addScore(100, x, y);
		snd('coin', 'coin');
		api.hud({ extra: fa(s.coins) });
		if (s.coins === 50) api.unlock('coins50');
		if (s.coins % 100 === 0) oneUp();
	};
	const oneUp = () => { s.lives++; api.hud({ lives: s.lives }); s.banner = { t: 'یک جان اضافه! 💾', life: 1.6 }; snd('one', 'level'); };
	const kill = (e, flip) => {
		e.alive = false; e.dead = flip ? 1.2 : 0.5; e.flip = flip; e.vy = flip ? -4 : 0;
		addScore(e.t === 'turret' ? 500 : e.t === 'virus' ? 400 : 200, e.x, e.y);
		s.kills++;
		snd('stomp', 'hit');
		if (s.kills === 1) api.unlock('first_stomp');
		if (s.kills === 30) api.unlock('debugger');
		if (s.kills % 6 === 0) quip('streak');
		s.stageKills = (s.stageKills || 0) + 1;
		if (s.stageKills === 7) cameo('streak');
	};
	const hurt = () => {
		if (s.p.inv > 0 || s.dying || s.won || s.shield > 0) return;
		if (s.lv.side === 'drive' || s.lv.side === 'war') { s.carHp--; s.p.inv = 1.6; snd('down', 'hurt'); s.banner = { t: s.carHp > 0 ? `ال ۹۰ ضربه خورد! (${fa(s.carHp)} از ۳) 🚗` : 'ماشین خاموش شد… 💥', life: 1.4 }; if (s.carHp <= 0) die(); return; }
		if (s.lv.side === 'date') { s.pat = Math.max(0, s.pat - 15); s.p.inv = 1.6; snd('down', 'hurt'); s.pops.push({ x: s.p.x, y: s.p.y - 10, t: 'تماس کاری! −۱۵ 😤', life: 1.2 }); return; }
		if (s.big) {
			s.big = false; s.form = null;
			s.p.y += H_BIG - H_SMALL; s.p.h = H_SMALL; s.p.inv = 2;
			snd('down', 'hurt');
			s.flawless = false;
			quip('hurt', true);
		} else die();
	};
	const die = () => {
		if (s.dying) return;
		s.dying = 2.4; s.p.vy = -6.5; s.flawless = false; s.shield = 0;
		s.ghost = { x: s.p.x + s.p.w / 2, y: s.p.y + s.p.h };
		s.quipCd = 0; quip('die', true); s.quip.life = s.quip.max = 2.4;
		music.stop();
		snd('die', 'over');
	};

	const cameo = (k) => {
		if (!s || s.shayUsed || s.lv.isRoom || s.dying) return;
		s.shayUsed = true;
		s.shay = { t: 0, line: SHAYAN_LINE, wink: true };
		snd('coin', 'click');
	};
	// speech bubble above the hero (or the hacker); cooldown so it never spams
	const quip = (k, force, who = 'hero') => {
		if (!s || (!force && (s.quipCd || 0) > 0)) return;
		s.quip = { t: pickQ(k), life: 2.2, who };
		s.quipCd = 5;
	};

	/* ---- the hacker: shows up now and then, steals coins, plants a bug and runs; catch him for a bounty ---- */
	const HK_W = 12, HK_H = 22, HK_SPEED = 3.3;
	const groundTop = (x, fromY) => {
		let ty = Math.max(3, Math.floor(fromY / T));
		if (solid(tileAt(x, ty * T + 1))) { for (let i = 0; i < 6 && ty > 3 && solid(tileAt(x, (ty - 1) * T + 1)); i++) ty--; return ty * T; }
		for (; ty < ROWS; ty++) if (solid(tileAt(x, ty * T + 1))) return ty * T;
		return null; // pit: he glitches across
	};
	const updateHacker = (f) => {
		const h = s.hk, p = s.p, dt = f / 60;
		if (!h || s.lv.isRoom || s.lv.isBoss || s.won || s.dying) return;
		if (h.st === 'wait') {
			h.timer -= dt;
			if (h.timer <= 0) { h.st = 'warn'; h.timer = 1.6; h.dir = Math.random() < 0.5 ? 1 : -1; s.banner = { t: h.dir > 0 ? '⚠ هکر از پشت سر می‌آید!' : '⚠ هکر از روبه‌رو می‌آید!', life: 1.6 }; snd('shot', 'wrong'); }
		} else if (h.st === 'warn') {
			h.timer -= dt;
			if (h.timer <= 0) { h.st = 'run'; h.t = 0; h.stole = 0; h.x = h.dir > 0 ? s.cam - 24 : s.cam + view.vw + 24; const g = groundTop(h.x, p.y); h.y = (g === null ? GY * T : g) - HK_H; }
		} else if (h.st === 'run') {
			h.t += dt;
			h.x += h.dir * HK_SPEED * (h.stole ? 1.3 : 1) * f;
			const g = groundTop(h.x + h.dir * 8, h.y + HK_H - 2);
			if (g !== null) h.y += (g - HK_H - h.y) * Math.min(1, 0.3 * f);
			const box = { x: h.x - HK_W / 2, y: h.y, w: HK_W, h: HK_H };
			const caught = () => {
				addScore(1000, h.x, h.y - 6);
				const back = h.stole + 5; s.coins += back; api.hud({ extra: fa(s.coins) });
				s.pops.push({ x: h.x, y: h.y - 18, t: `+${fa(back)} 🪙`, life: 1.2 });
				for (let i = 0; i < 12; i++) s.parts.push({ k: 'brick', x: h.x, y: h.y + 10, vx: (Math.random() - 0.5) * 4, vy: -Math.random() * 3, life: 0.8, c: i % 2 ? '#22c55e' : '#0f172a' });
				s.banner = { t: 'هکر گیر افتاد! +۱۰۰۰ 🕵️', life: 1.8 };
				snd('stomp', 'hit');
				quip('caught', true);
				setTimeout(() => cameo('hacker'), 900);
				h.st = 'wait'; h.timer = 28 + Math.random() * 22;
			};
			if (touching(p, box)) {
				if (p.vy > 0 && p.y + p.h - h.y < 10) { p.vy = keys.jump ? -7.2 : -4.8; return caught(); }
				if (!h.stole && p.inv <= 0) {
					const n = Math.min(10, s.coins); s.coins -= n; h.stole = n || 0.001; api.hud({ extra: fa(s.coins) });
					if (n) s.pops.push({ x: p.x, y: p.y - 10, t: `-${fa(n)} 🪙`, life: 1.2 });
					s.ents.push({ t: 'bug', x: h.x - h.dir * 22, y: h.y + HK_H - 14, w: 14, h: 14, vx: -0.5 * h.dir, vy: 0, alive: true, y0: h.y, dead: 0, cd: 1 }); // plants a bug
					snd('down', 'hurt');
					quip('steal', true, 'hk');
				}
			}
			for (const b of s.beans) if (touching(b, box)) { b.life = 0; return caught(); }
			if (h.t > 1 && (h.x < s.cam - 60 || h.x > s.cam + view.vw + 60)) { h.st = 'wait'; h.timer = 22 + Math.random() * 20; if (h.stole >= 1) s.banner = { t: 'هکر با سکه‌هایت فرار کرد! 💸', life: 1.6 }; }
		}
	};

	/* ---- secret rooms ---- */
	const enterRoom = (warp) => {
		s.warp = warp;
		s.lv = buildRoom(s.stageN);
		Object.assign(s.p, { x: 3 * T, y: GY * T - s.p.h, vx: 0, vy: 0 });
		s.cam = 0;
		s.banner = { t: 'اتاق مخفی! برای خروج روی سرور ▼ بایست و ↓ بزن', life: 2.2 };
		api.unlock('secret');
		snd('down');
		music.play('room');
	};
	const exitRoom = () => {
		s.lv = s.main;
		const bx = s.warp.back;
		Object.assign(s.p, { x: bx * T + 2, y: (GY - 2) * T - s.p.h - 1, vx: 0, vy: -4, ground: false });
		s.parts.push({ k: 'puff', x: bx * T + 16, y: (GY - 2) * T, life: 0.4 });
		snd('down');
		s.cam = clamp(s.p.x - view.vw * 0.4, 0, s.lv.W * T - view.vw);
		s.warp = null;
		music.play(songFor());
	};

	/* ---- bosses ---- */
	function makeBoss(type, arena) {
		const hp = { megabug: 8, server: 10, scorpion: 10, deadline: 14 }[type];
		const dims = { megabug: [40, 34], server: [36, 44], scorpion: [48, 26], deadline: [40, 40] }[type];
		return { type, hp, max: hp, w: dims[0], h: dims[1], x: (arena.x1 - 6) * T, y: GY * T - dims[1], vx: -1, vy: 0, t: 0, inv: 0, active: false, arena, ground: false, dead: 0, dir: -1 };
	}
	const bossShot = (x, y, vx, vy, grav = 0.18) => s.shots.push({ x, y, vx, vy, w: 10, h: 10, grav, life: 4 });
	const updateBoss = (f) => {
		const b = s.boss, p = s.p, a = b.arena;
		if (!b.active) {
			if (p.x > a.x0 * T + 24) {
				b.active = true;
				s.camMin = a.x0 * T - 8;
				for (let y = 3; y < GY; y++) s.lv.map[y][a.x0 - 1] = '#';
				s.banner = { t: `👾 ${WORLDS[s.world].bossName}`, life: 2 };
				snd('boss');
				setTimeout(() => s && !s.over && b.hp > 0 && music.play('boss'), 700);
			}
			return;
		}
		if (b.hp <= 0) { b.dead -= f / 60; b.y += 1.5 * f; if (Math.random() < 0.3) s.parts.push({ k: 'spark', x: b.x + Math.random() * b.w, y: b.y + Math.random() * b.h, life: 0.4 }); return; }
		b.t += f / 60;
		if (b.inv > 0) b.inv -= f / 60;
		const toward = p.x < b.x ? -1 : 1, lx = a.x0 * T + 4, rx = a.x1 * T - b.w;
		const every = (sec) => b.t % sec < f / 60;
		if (b.type === 'server') {
			b.x += b.vx * f;
			if (b.x < lx || b.x > rx) { b.vx *= -1; b.x = clamp(b.x, lx, rx); }
			b.y = 6 * T + Math.sin(b.t * 2) * 18;
			if (every(1.4)) bossShot(b.x + b.w / 2, b.y + b.h, (p.x - b.x) / 60, -2.5);
		} else {
			if (b.type === 'megabug') {
				b.vx = toward * 0.9;
				if (b.ground && every(2.6)) b.vy = -8;
				if (every(4.2)) s.ents.push({ t: 'bug', x: b.x + b.w / 2, y: b.y, vx: toward * 0.6, vy: -3, w: 14, h: 14, alive: true, y0: b.y, dead: 0 });
			} else if (b.type === 'scorpion') {
				const phase = b.t % 3.2;
				b.vx = phase < 1.2 ? 0 : b.dir * 4;
				if (phase < f / 60) b.dir = toward;
				if (phase > 1.0 && phase < 1.0 + f / 60) bossShot(b.x + (b.dir > 0 ? b.w : 0), b.y + 4, b.dir * 3, -3.5);
			} else if (b.type === 'deadline') {
				b.vx = toward * 0.7;
				if (b.ground && every(2.2)) b.vy = -9;
				if (every(1.8)) [-2, 0, 2].forEach((d) => bossShot(b.x + b.w / 2, b.y + 10, toward * 2.6 + d * 0.4, -3 + Math.abs(d) * 0.6));
				if (every(5)) s.ents.push({ t: 'clock', x: b.x + b.w / 2, y: b.y, vx: toward * 1.1, vy: -2, w: 14, h: 14, alive: true, y0: b.y, dead: 0 });
			}
			const ovx = b.vx; b.vx = ovx * f; moveX(b); b.vx = ovx;
			b.x = clamp(b.x, lx, rx);
			b.vy = Math.min(9, b.vy + G * f);
			const ovy = b.vy; b.vy = ovy * f; moveY(b); if (b.ground) b.vy = 0; else b.vy = ovy;
		}
		if (p.x < b.x + b.w && p.x + p.w > b.x && p.y < b.y + b.h && p.y + p.h > b.y) {
			if (s.shield > 0) damageBoss(1);
			else if (p.vy > 0.4 && p.y + p.h - b.y < 14) { damageBoss(2); p.vy = keys.jump ? -8 : -6; }
			else hurt();
		}
	};
	const damageBoss = (d) => {
		const b = s.boss;
		if (!b || b.inv > 0 || b.hp <= 0) return;
		b.hp -= d; b.inv = 0.7;
		snd('hit', 'hit');
		addScore(300 * d, b.x + b.w / 2, b.y);
		if (b.hp <= 0) {
			b.dead = 2; s.goalOn = true;
			addScore(5000, b.x, b.y - 10);
			s.banner = { t: 'باس شکست خورد! دکمه دیپلوی را بزن 🟥', life: 2.4 };
			quip('boss', true);
			api.unlock('boss');
			music.stop();
			snd('clear', 'level');
			s.shots = [];
			setTimeout(() => s && !s.over && music.play(songFor()), 2200);
		}
	};

	/* ---- update ---- */
	const touching = (a, b) => a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;
	const update = (f) => {
		const p = s.p;
		if (s.banner) s.banner.life -= f / 60;
		if (s.shake > 0) s.shake -= f / 60;
		if (s.shay && (s.shay.t += f / 60) > 2.3) s.shay = null;
		if (s.quipCd > 0) s.quipCd -= f / 60;
		if (s.quip && (s.quip.life -= f / 60) <= 0) s.quip = null;
		s.pops = s.pops.filter((q) => ((q.life -= f / 60), (q.y -= 0.6 * f), q.life > 0));
		s.parts = s.parts.filter((q) => {
			q.life -= f / 60;
			if (q.k === 'brick' || q.k === 'fw') { q.x += q.vx * f; q.y += q.vy * f; q.vy += (q.k === 'fw' ? 0.08 : G) * f; }
			if (q.k === 'coinjump') { q.y += q.vy * f; q.vy += 0.35 * f; }
			return q.life > 0;
		});
		s.t += f;
		if (s.dying) {
			s.dying -= f / 60;
			if (s.dying < 2) { p.vy += G * f; p.y += p.vy * f; }
			if (s.dying <= 0) {
				s.lives--;
				api.hud({ lives: s.lives });
				if (s.lives <= 0) return finish(false);
				s.big = false; s.form = null;
				loadStage(s.stageN, true);
			}
			return;
		}
		if (s.won) {
			s.won -= f / 60;
			p.vx = 0;
			if (Math.random() < 0.25) {
				const fx = s.lv.goal.x + (Math.random() - 0.5) * 160, fy = 70 + Math.random() * 60, hue = Math.floor(Math.random() * 360);
				for (let i = 0; i < 10; i++) { const a = (i / 10) * Math.PI * 2; s.parts.push({ k: 'fw', x: fx, y: fy, vx: Math.cos(a) * 1.6, vy: Math.sin(a) * 1.6, life: 0.9, c: `hsl(${hue} 90% 60%)` }); }
			}
			if (s.won <= 0) {
				if (s.lv.side === 'rain') {
					try { localStorage.setItem(RAIN_SAVE, '1'); } catch (e) { /* ignore */ }
					s.mode = 'story'; music.stop(); pad.hidden = true; s.lives++; api.hud({ lives: s.lives });
					comic = showComic(stage, RAIN_OUTRO, { worlds: WORLDS, sound: (n) => api.sfx(n), label: `${RAIN_NAME} · ${fa(s.tasksDone)} تسک بسته شد · +۱ جان`, done: () => { comic = null; if (!s || s.over) return; s.mode = 'map'; mapEl.hidden = false; renderMap(); } });
					return;
				}
				if (s.lv.side === 'war') {
					try { localStorage.setItem(WAR_SAVE, '1'); } catch (e) { /* ignore */ }
					s.mode = 'story'; music.stop(); pad.hidden = true; s.lives++; api.hud({ lives: s.lives });
					comic = showComic(stage, WAR_OUTRO, { worlds: WORLDS, sound: (n) => api.sfx(n), label: `${WAR_NAME} · انجام شد · +۱ جان`, done: () => { comic = null; if (!s || s.over) return; s.mode = 'map'; mapEl.hidden = false; renderMap(); } });
					return;
				}
				if (s.lv.side) {
					writeSide(s.stageN - SIDE0 + 1);
					if (s.stageN === SIDE0) return playStage(SIDE0 + 1);
					s.mode = 'story'; music.stop(); pad.hidden = true; s.lives++; api.hud({ lives: s.lives });
					comic = showComic(stage, SIDE_OUTRO.map((pn) => ({ ...pn, world: MAIN_WORLDS + 1 })), { worlds: WORLDS, sound: (n) => api.sfx(n), label: `پایان داستان فرعی · +۱ جان 💗`, done: () => { comic = null; if (!s || s.over) return; s.mode = 'map'; mapEl.hidden = false; renderMap(); } });
					return;
				}
				writeProgress(s.stageN + 1);
				if (s.stageN === 5) { // end of the Kaleh chapter: the Makan party cutscene
					s.mode = 'story'; music.stop(); pad.hidden = true;
					comic = showComic(stage, AWARD, { worlds: WORLDS, sound: (n) => api.sfx(n), label: 'جشن ماکان 🏆', done: () => { comic = null; if (s && !s.over) playStage(6); } });
					return;
				}
				if (s.stageN + 1 >= TOTAL) { s.mode = 'story'; music.stop(); pad.hidden = true; comic = showComic(stage, OUTRO, { worlds: WORLDS, sound: (n) => api.sfx(n), label: 'پایان داستان', done: () => { comic = null; s && finish(true); } }); return; }
				playStage(s.stageN + 1);
			}
			return;
		}
		s.timeLeft -= f / 60;
		if (s.timeLeft <= 0) die();
		if (s.shield > 0) { s.shield -= f / 60; if (s.shield <= 0) { music.setSpeed(1); s.shield = 0; } }

		if (s.lv.side === 'drive' && !s.won && !s.dying) { keys.right = true; keys.left = false; keys.run = true; firePressed = false; }
		if (s.lv.side === 'rain' && !s.won && !s.dying) {
			const tx = Math.floor((p.x + p.w / 2) / T);
			s.inPuddle = p.ground && s.lv.puddles.some(([a, b]) => tx >= a && tx <= b);
			if (s.inPuddle) { p.vx *= 0.9; if (Math.abs(p.vx) > 0.6 && Math.random() < 0.3) s.parts.push({ k: 'puff', x: p.x + p.w / 2, y: GY * T - 3, vx: 0, vy: -0.3, life: 0.4 }); }
			if (!s.task) {
				s.taskT -= f / 60;
				if (s.taskT <= 0) {
					const ax = p.x + 170 + Math.random() * 60, g = groundTop(ax, p.y);
					if (g !== null && ax < s.lv.goal.x - 60) {
						const ent = { t: 'bug', task: true, x: ax, y: g - 14, w: 14, h: 14, vx: -0.4, vy: 0, alive: true, y0: g - 14, dead: 0, cd: 1 };
						s.ents.push(ent);
						s.task = { name: TASKS[Math.floor(Math.random() * TASKS.length)], t: 11, ent };
						s.banner = { t: `📌 تسک جدید: ${s.task.name}`, life: 1.8 };
						snd('shot', 'click');
					} else s.taskT = 1;
				}
			} else {
				s.task.t -= f / 60;
				if (!s.task.ent.alive) { s.tasksDone++; addScore(500, s.task.ent.x, s.task.ent.y - 10); s.banner = { t: `✓ تسک بسته شد: ${s.task.name}`, life: 1.6 }; s.task = null; s.taskT = 3.5; }
				else if (s.task.t <= 0) { s.task.ent.alive = false; s.task.ent.dead = 0; s.banner = { t: 'تسک دیر شد! 😬', life: 1.6 }; s.task = null; s.taskT = 4.5; hurt(); }
			}
		}
		if (s.lv.side === 'war' && !s.won && !s.dying) {
			keys.run = true; if (p.vx < 0) p.vx = 0; p.face = 1;
			s.bombT -= f / 60;
			if (s.bombT <= 0) { s.bombT = 1.1 + Math.random() * 1.3; const bx = p.x + 70 + Math.random() * 190; if (bx < s.lv.goal.x - 40) s.bombs.push({ x: bx, t: 1.25 }); }
			s.bombs = s.bombs.filter((b) => {
				b.t -= f / 60;
				if (b.t > 0) return true;
				s.shake = 0.35; snd('boom', 'boom');
				for (let i = 0; i < 14; i++) s.parts.push({ k: 'brick', x: b.x, y: GY * T - 6, vx: (Math.random() - 0.5) * 5, vy: -Math.random() * 4 - 1, life: 0.9, c: ['#f97316', '#fde047', '#57534e', '#a8a29e'][i % 4] });
				for (let i = 0; i < 5; i++) s.parts.push({ k: 'puff', x: b.x + (Math.random() - 0.5) * 30, y: GY * T - 10 - Math.random() * 20, vx: 0, vy: -0.4, life: 1 });
				if (Math.abs(p.x + p.w / 2 - b.x) < 30) hurt();
				else if (Math.abs(p.x + p.w / 2 - b.x) < 60) quip('war', true);
				return false;
			});
		}
		if (s.lv.side === 'date' && !s.won && !s.dying) { s.pat -= f / 60 * 1.7; if (s.pat <= 0) { s.pat = 0; s.banner = { t: 'مائده قهر کرد و رفت… 😤', life: 2 }; die(); } }
		if (s.lazy > 0) s.lazy = Math.max(0, s.lazy - f / 60);
		s.idle = Math.abs(p.vx) < 0.1 && p.ground ? (s.idle || 0) + f / 60 : Math.min(0, s.idle || 0);
		if (s.idle > 7) { s.idle = -20; quip('idle'); }
		updateHacker(f);
		const wake = (s.inPuddle ? 0.6 : 1) - 0.45 * Math.min(1, (s.lazy || 0) / LAZY); // sleepy start: slow for a moment, then wakes up
		const accel = (p.ground ? 0.34 : 0.24) * wake, max = (keys.run ? 2.7 : 2.15) * wake;
		if (keys.left && !keys.right) { p.vx = Math.max(-max, p.vx - accel * f); p.face = -1; }
		else if (keys.right && !keys.left) { p.vx = Math.min(max, p.vx + accel * f); p.face = 1; }
		else p.vx *= Math.pow(p.ground ? 0.76 : 0.95, f);
		if (Math.abs(p.vx) < 0.05) p.vx = 0;

		if (p.ground) { p.jumps = 0; p.fuel = 0.7; }
		p.coyote = p.ground ? 0.1 : p.coyote - f / 60;
		if (jumpPressed > 0) jumpPressed -= f / 60;
		const jet = s.big && s.form === 'jet';
		if (jumpPressed > 0 && (p.coyote > 0 || (jet && p.jumps < 1))) {
			if (p.coyote <= 0) { p.jumps++; s.parts.push({ k: 'puff', x: p.x + p.w / 2, y: p.y + p.h, life: 0.3 }); }
			p.vy = -(7.3 + Math.abs(p.vx) * 0.35) * (p.coyote > 0 ? 1 : 0.85);
			p.coyote = 0; jumpPressed = 0;
			snd('jump', 'jump');
		}
		if (!keys.jump && p.vy < -2.6) p.vy = -2.6;
		p.vy = Math.min(9, p.vy + G * f);
		if (jet && keys.jump && p.vy > 2 && p.fuel > 0) { p.vy = 2; p.fuel -= f / 60; }

		const vx = p.vx, vy = p.vy;
		p.vx = vx * f; moveX(p); p.vx = p.vx === 0 ? 0 : vx;
		p.vy = vy * f; moveY(p, true);
		if (p.ground) p.vy = 0; else if (p.vy !== 0.5) p.vy = vy;
		if (p.ground && !p.wasGround && vy > 3) { p.land = 0.15; s.parts.push({ k: 'puff', x: p.x + 2, y: p.y + p.h, life: 0.3 }, { k: 'puff', x: p.x + p.w - 2, y: p.y + p.h, life: 0.3 }); }
		if (p.land > 0) p.land -= f / 60;
		if (p.shoot > 0) p.shoot -= f / 60;
		if (p.ground && Math.abs(p.vx) > 1.8 && Math.floor(s.t) % 9 === 0) s.parts.push({ k: 'dust', x: p.x + p.w / 2 - p.face * 6, y: p.y + p.h - 2, life: 0.35 });
		p.wasGround = p.ground;
		for (const m of s.ents) {
			if (m.t !== 'mover') continue;
			const nx = m.x0 + (Math.sin((s.t / 60) * m.sp + m.ph) * 0.5 + 0.5) * m.range;
			m.dx = nx - m.x; m.x = nx;
			const bottom = p.y + p.h;
			if (p.vy >= 0 && p.x + p.w > m.x + 2 && p.x < m.x + m.w - 2 && bottom >= m.y - 2 && bottom <= m.y + 8) { p.y = m.y - p.h; p.vy = 0; p.ground = true; p.x += m.dx; }
		}
		if (p.ground) for (const xx of [p.x + 2, p.x + p.w - 2]) {
			const tx = Math.floor(xx / T), ty = Math.floor((p.y + p.h + 2) / T);
			if (ty < ROWS && s.lv.map[ty] && s.lv.map[ty][tx] === 'X') { const k = `${tx},${ty}`; if (s.crumble[k] === undefined) s.crumble[k] = 0.45; }
		}
		for (const k of Object.keys(s.crumble)) {
			s.crumble[k] -= f / 60;
			if (s.crumble[k] <= 0) { const [tx, ty] = k.split(',').map(Number); s.lv.map[ty][tx] = '.'; delete s.crumble[k]; for (let i = 0; i < 3; i++) s.parts.push({ k: 'brick', x: tx * T + 8, y: ty * T + 8, vx: (Math.random() - 0.5) * 2, vy: -1, life: 0.8, c: '#94a3b8' }); }
		}
		if (p.x < Math.max(s.cam, s.camMin)) p.x = Math.max(s.cam, s.camMin);
		p.anim += Math.abs(p.vx) * f * 0.12;
		if (p.inv > 0) p.inv -= f / 60;
		if (p.y > VH + 30) die();

		if (keys.down && p.ground) {
			const under = tileAt(p.x + p.w / 2, p.y + p.h + 2);
			if (under === 'W' || under === 'w') {
				const tx = Math.floor((p.x + p.w / 2) / T);
				const warp = s.lv.warps.find((w) => w.tx === tx || w.tx + 1 === tx);
				if (warp && s.lv.isRoom) { keys.down = false; exitRoom(); return; }
				if (warp) { keys.down = false; enterRoom(warp); return; }
			}
		}

		for (const [xx, yy] of [[p.x + 2, p.y + 2], [p.x + p.w - 2, p.y + 2], [p.x + 2, p.y + p.h / 2], [p.x + p.w - 2, p.y + p.h / 2], [p.x + 2, p.y + p.h - 2], [p.x + p.w - 2, p.y + p.h - 2]]) {
			const tx = Math.floor(xx / T), ty = Math.floor(yy / T);
			if (ty >= 0 && ty < ROWS && tx >= 0 && tx < s.lv.W) {
				if (s.lv.map[ty][tx] === 'o') { s.lv.map[ty][tx] = '.'; coin(tx * T + 4, ty * T); }
				if (s.lv.map[ty][tx] === 'F' && !s.foundWall) { s.foundWall = true; api.unlock('secret'); s.banner = { t: 'راه مخفی! 🤫', life: 1.4 }; }
			}
		}
		if (!s.lv.isRoom && !s.lv.isBoss && !s.checkpoint && p.x > s.lv.check) { s.checkpoint = true; s.banner = { t: 'نقطه ذخیره ✓', life: 1.4 }; snd('coin'); quip('checkpoint'); }

		// power attack — fired at knee height so it hits short enemies
		if (firePressed && s.big) {
			const hx = p.x + p.w / 2 + p.face * 12, hy = p.y + p.h - 15; // hand position at the end of the swing
			if (s.form === 'coffee' && s.beans.length < 2) { s.beans.push({ k: 'bean', x: hx - 3, y: p.y + p.h - 12, vx: 4.2 * p.face, vy: -1.2, w: 6, h: 6, life: 2 }); snd('shot', 'shoot'); p.shoot = 0.24; s.parts.push({ k: 'flash', x: hx, y: hy, life: 0.18, c: '#b45309' }); }
			if (s.form === 'dev' && s.beans.length < 3) { s.beans.push({ k: 'code', x: p.x + (p.face > 0 ? p.w : -12), y: p.y + p.h - 11, vx: 6 * p.face, vy: 0, w: 12, h: 8, life: 0.9, pierce: 1 }); snd('shot', 'shoot'); p.shoot = 0.2; p.vx -= p.face * 0.6; s.parts.push({ k: 'flash', x: hx, y: hy + 3, life: 0.15, c: '#22d3ee' }); }
		}
		firePressed = false;
		s.beans = s.beans.filter((b) => {
			b.life -= f / 60;
			let hit = false;
			if (b.k === 'bean') {
				b.vy = Math.min(6, b.vy + G * f);
				const ox = b.vx; b.vx = ox * f; hit = moveX(b); b.vx = hit ? 0 : ox;
				const oy = b.vy; b.vy = oy * f; moveY(b); b.vy = b.ground ? -3.3 : oy;
			} else { b.x += b.vx * f; hit = solid(tileAt(b.x + (b.vx > 0 ? b.w : 0), b.y + 4)); }
			for (const e of s.ents) if (e.alive && !e.item && e.t !== 'mover' && touching(b, e)) { kill(e, true); if (!(b.pierce-- > 0)) return false; }
			const bo = s.boss;
			if (bo && bo.active && bo.hp > 0 && touching(b, bo)) { damageBoss(1); return false; }
			for (const q of s.shots) if (touching(b, q)) { q.life = 0; return false; }
			return !hit && b.life > 0 && b.y < VH;
		});
		s.shots = s.shots.filter((q) => {
			q.life -= f / 60; q.vy += q.grav * f; q.x += q.vx * f; q.y += q.vy * f;
			if (s.shield <= 0 && touching(p, q)) { hurt(); return false; }
			return q.life > 0 && q.y < VH && !solid(tileAt(q.x + 5, q.y + 5));
		});

		if (!s.lv.isRoom) {
			for (const e of s.ents) {
				if (e.t === 'mover') continue;
				if (!e.alive) { if (e.dead > 0) { e.dead -= f / 60; if (e.flip) { e.vy += G * f; e.y += e.vy * f; } } continue; }
				if (e.x > s.cam + view.vw + 64 || e.x < s.cam - 120) continue;
				const toward = p.x < e.x ? -1 : 1, et = BEH[e.t] || e.t;
				if (et === 'spam') { e.x += e.vx * f; e.y = e.y0 + Math.sin((s.t + e.x) / 30) * 18; }
				else if (e.t === 'turret') {
					e.cd -= f / 60;
					if (e.cd <= 0 && Math.abs(p.x - e.x) < 260) { s.shots.push({ x: e.x + (toward > 0 ? e.w : -10), y: e.y + 3, vx: toward * 2.2, vy: 0, w: 10, h: 10, grav: 0, life: 4 }); e.cd = 2.4; snd('shot'); }
				} else if (!e.still) {
					if (et === 'hopper') { e.cd -= f / 60; if (e.ground && e.cd <= 0) { e.vy = -6.5; e.vx = toward * 1.2; e.cd = 1.3; } if (e.ground && e.cd < 1.1) e.vx = 0; }
					const ovx = e.vx; e.vx = ovx * f; const hit = moveX(e); e.vx = hit ? -ovx : ovx;
					e.vy = Math.min(8, (e.vy || 0) + G * f);
					const ovy = e.vy; e.vy = ovy * f; moveY(e);
					if (e.ground) e.vy = e.t === 'shield' ? -5 : 0; else e.vy = ovy;
					if ((e.t === 'bug' || e.t === 'virus' || e.t === 'call') && e.ground && !solid(tileAt(e.vx > 0 ? e.x + e.w + 1 : e.x - 1, e.y + e.h + 2))) e.vx = -e.vx;
					if (e.y > VH + 20) e.alive = false;
				}
				if (touching(p, e)) {
					if (e.item) {
						e.alive = false;
						addScore(1000, e.x, e.y);
						if (e.t === 'coffee') setForm('coffee');
						else if (e.t === 'laptop') setForm('dev');
						else if (e.t === 'jetpack') setForm('jet');
						else if (e.t === 'oneup') oneUp();
						else if (e.t === 'shield') { s.shield = 9; music.setSpeed(1.25); snd('power', 'level'); s.banner = { t: '🛡️ فایروال فعال!', life: 1.4 }; api.unlock('shield'); }
					} else if (s.shield > 0) kill(e, true);
					else if (e.t !== 'virus' && p.vy > 0.4 && p.y + p.h - e.y < 11) {
						if (e.t === 'turret') { p.vy = keys.jump ? -7.5 : -5; }
						else { kill(e, e.t === 'spam'); p.vy = keys.jump ? -7.2 : -4.6; }
					} else hurt();
				}
			}
			if (s.boss) updateBoss(f);
		}

		// goal: the DEPLOY button (step on it)
		if (s.goalOn && !s.lv.isRoom && !s.won) {
			const g = s.lv.goal, bx = g.x, by = g.y - 30;
			if (p.x + p.w > bx + 2 && p.x < bx + 30 && p.y + p.h >= by - 2) {
				s.won = 3; s.pressed = true;
				p.y = Math.min(p.y, by - p.h + 4);
				const perfect = !p.ground && p.vy > 0;
				if (perfect && !s.lv.side) cameo('perfect');
				const bonus = Math.max(0, Math.round(s.timeLeft)) * 10 + (perfect ? 2000 : 0);
				addScore(1000 + bonus, bx, by - 20);
				s.banner = { t: s.lv.side === 'rain' ? 'رسیدی منوچهری! زیپ درست می‌شه 🎒' : s.lv.side === 'war' ? 'رسیدیم شرکت! لپ‌تاپ رو بردار 💻' : s.lv.side === 'drive' ? 'رسیدی! ال ۹۰ پارک شد 🅿️' : s.lv.side === 'date' ? 'مائده خوشحال شد! 💗' : perfect ? 'دیپلوی پرشی! +۲۰۰۰ ✨' : 'دیپلوی موفق! 🚀', life: 2.6 };
				music.stop();
				snd('clear', 'level');
				api.unlock('deploy');
				if (s.flawless) api.unlock('flawless');
				if (s.stageN === TOTAL - 1) api.unlock('persepolis');
			}
		}

		const target = clamp(p.x - view.vw * 0.4, 0, s.lv.W * T - view.vw);
		if (target > s.cam) s.cam += (target - s.cam) * Math.min(1, 0.16 * f);
		if (s.boss && s.boss.active && s.boss.hp > 0) s.cam = clamp(s.cam, s.camMin, s.boss.arena.x1 * T + 2 * T - view.vw);
	};

	const finish = (win) => {
		s.over = true;
		music.stop();
		cancelAnimationFrame(raf);
		pad.hidden = true;
		if (win) addScore(10000);
		api.over({ score: s.score, meta: { result: win ? 'win' : 'loss', summary: win ? 'هر ۱۲ مرحله و ۴ باس را شکست دادی؛ دیپلوی نهایی موفق! 🚀' : `تا ${WORLDS[s.world].name}، مرحله ${fa((s.stageN % STAGES_PER) + 1)} رسیدی · ${fa(s.coins)} سکه · مرحله‌های باز شده روی نقشه می‌مانند` } });
	};

	/* ---- drawing ---- */
	const R = (x, y, w, h, c) => { ctx.fillStyle = c; ctx.fillRect(Math.round(x), Math.round(y), w, h); };
	const rr = (x, y, w, h, r, fill, stroke = OL, lw = 1.5) => { ctx.beginPath(); ctx.roundRect(x, y, w, h, r); ctx.fillStyle = fill; ctx.fill(); if (stroke) { ctx.lineWidth = lw; ctx.strokeStyle = stroke; ctx.stroke(); } };
	const circ = (x, y, r, fill, stroke = OL, lw = 1.5) => { ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2); ctx.fillStyle = fill; ctx.fill(); if (stroke) { ctx.lineWidth = lw; ctx.strokeStyle = stroke; ctx.stroke(); } };
	const eyes = (x, y, dir, angry, k = 1) => {
		circ(x, y, 2.6 * k, '#fff', OL, 1); circ(x + 5 * k, y, 2.6 * k, '#fff', OL, 1);
		circ(x + dir * 0.9 * k, y + 0.4, 1.2 * k, OL, null); circ(x + 5 * k + dir * 0.9 * k, y + 0.4, 1.2 * k, OL, null);
		if (angry) { ctx.strokeStyle = OL; ctx.lineWidth = 1.2 * k; ctx.beginPath(); ctx.moveTo(x - 2.5 * k, y - 3.5 * k); ctx.lineTo(x + 2 * k, y - 2 * k); ctx.moveTo(x + 7.5 * k, y - 3.5 * k); ctx.lineTo(x + 3 * k, y - 2 * k); ctx.stroke(); }
	};
	const text = (t, x, y, size, color, align = 'center') => { ctx.font = `900 ${size}px yekanBakh, Tahoma`; ctx.textAlign = align; ctx.lineWidth = 3; ctx.strokeStyle = OL; ctx.strokeText(t, x, y); ctx.fillStyle = color; ctx.fillText(t, x, y); };

	const heroFrame = (p, air) => (air ? 'jump' : Math.abs(p.vx) < 0.15 ? 'stand2' : 'run');
	const drawPlayer = () => {
		const p = s.p, air = !p.ground && !s.won;
		if (p.inv > 0 && Math.floor(s.t / 4) % 2) return;
		const size = s.big ? 'big' : 'small', form = s.big ? s.form || 'coffee' : 'none';
		if (s.big && s.form === 'jet') { rr(p.x + p.w / 2 - p.face * 9 - 2, p.y + 15, 5, 11, 2, '#94a3b8'); if (air && keys.jump && p.fuel > 0) circ(p.x + p.w / 2 - p.face * 9 + 0.5, p.y + 28, 2.5, '#fb923c', null); }
		// juice: squash & stretch, idle breathing, victory hops
		let sx = 1, sy = 1, oy = 0;
		if (air && p.vy < -2) { sx = 0.9; sy = 1.1; }
		else if (air && p.vy > 3) { sx = 0.94; sy = 1.06; }
		if (p.land > 0) { const k = p.land / 0.15; sx = 1 + 0.16 * k; sy = 1 - 0.14 * k; }
		if (!air && Math.abs(p.vx) < 0.15 && !s.won) oy = Math.floor(s.t / 28) % 2 ? 1 : 0;
		if (s.won) oy = -Math.abs(Math.sin(s.t / 7)) * 8;
		if (s.dying && s.ghost) {
			// ghost Korosh floats up with a halo, wobbling, while the body spins off-screen
			const u = 2.4 - s.dying, gx = s.ghost.x + Math.sin(u * 5) * 6, gy = s.ghost.y - u * 34;
			ctx.save(); ctx.globalAlpha = Math.max(0, 0.55 - u * 0.15); ctx.translate(gx, gy); ctx.scale(HERO_FIT, HERO_FIT);
			drawHero(ctx, 0, -HERO_H.big, 'big', 'none', 'stand2', 1, 1, 1, null, 0, 1);
			ctx.strokeStyle = '#fde047'; ctx.lineWidth = 2; ctx.beginPath(); ctx.ellipse(-1, -50, 7, 2.2, 0, 0, 7); ctx.stroke();
			ctx.restore();
			if (u < 0.5) for (let i = 0; i < 6; i++) circ(s.ghost.x + Math.cos(i) * u * 40, s.ghost.y - 10 + Math.sin(i * 2) * u * 30, 2, i % 2 ? '#22d3ee' : '#f43f5e', null);
		}
		if (s.lv.side === 'drive' || s.lv.side === 'war') { drawL90(ctx, p.x + p.w / 2, p.y + p.h + 1, 0.82, s.t / 60, Math.abs(p.vx) > 0.2, s.lv.side === 'war' ? '207' : 'l90'); return; }
		const fx0 = p.x + p.w / 2, fy0 = p.y + oy + p.h;
		ctx.save(); ctx.translate(fx0, fy0); ctx.scale(HERO_FIT, HERO_FIT);
		if (s.dying) { ctx.translate(0, -p.h / 2); ctx.rotate((2.4 - s.dying) * 7); ctx.translate(0, p.h / 2); }
		ctx.translate(-fx0, -fy0);
		drawHero(ctx, p.x + p.w / 2, p.y + oy + p.h - HERO_H[size], size, form, s.won ? (Math.sin(s.t / 7) > 0 ? 'jump' : 'stand') : heroFrame(p, air), p.face, sx, sy, p.anim / 4, p.shoot > 0 ? p.shoot / 0.24 : 0, Math.min(1, (s.lazy || 0) / LAZY));
		ctx.restore();
		if (s.big && s.form === 'dev') { rr(p.x + p.w / 2 + p.face * 6 - 4, p.y + 21, 9, 6, 1, '#cbd5e1', OL, 1); R(p.x + p.w / 2 + p.face * 6 - 3, p.y + 22, 7, 4, '#22d3ee'); }
		if (s.shield > 0) { ctx.strokeStyle = `hsl(${(s.t * 12) % 360} 90% 60%)`; ctx.lineWidth = 2; ctx.beginPath(); ctx.ellipse(p.x + p.w / 2, p.y + p.h / 2, 13, p.h / 2 + 4, 0, 0, 7); ctx.stroke(); }
	};
	// side-story foes
	const drawCarFoe = (e) => { // oncoming yellow Pride
		const x = e.x, y = e.y + e.h, bob = Math.abs(Math.sin(s.t / 4)) * 0.6;
		rr(x + 2, y - 12 - bob, 26, 8, 3, '#facc15', OL, 1.3);
		ctx.beginPath(); ctx.moveTo(x + 7, y - 12 - bob); ctx.lineTo(x + 10, y - 17 - bob); ctx.lineTo(x + 21, y - 17 - bob); ctx.lineTo(x + 24, y - 12 - bob); ctx.closePath(); ctx.fillStyle = '#fde047'; ctx.fill(); ctx.stroke();
		R(x + 11, y - 16 - bob, 4, 3, '#1e293b'); R(x + 17, y - 16 - bob, 4, 3, '#1e293b');
		rr(x + 12, y - 19.5 - bob, 7, 2.6, 1, '#111', OL, 0.8); // taxi sign
		circ(x + 3, y - 9 - bob, 1.2, '#fff7ae', null);
		circ(x + 8, y - 3, 3, '#111', OL, 1); circ(x + 22, y - 3, 3, '#111', OL, 1);
		ctx.strokeStyle = OL; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(x + 4, y - 11 - bob); ctx.lineTo(x + 7, y - 10 - bob); ctx.stroke(); // angry headlight brow
	};
	const drawCone = (e) => {
		const x = e.x + 6, y = e.y + e.h;
		ctx.beginPath(); ctx.moveTo(x - 5, y - 1); ctx.lineTo(x - 1.6, y - 12); ctx.lineTo(x + 1.6, y - 12); ctx.lineTo(x + 5, y - 1); ctx.closePath(); ctx.fillStyle = '#f97316'; ctx.fill(); ctx.lineWidth = 1.2; ctx.strokeStyle = OL; ctx.stroke();
		R(x - 3.4, y - 7, 6.8, 2, '#fff'); rr(x - 6.5, y - 2, 13, 2.5, 1, '#ea580c', OL, 1);
	};
	const drawCall = (e) => { // ringing work phone on little legs
		const x = e.x, y = e.y, ring = Math.sin(s.t / 2) * 1.2, dir = e.vx < 0 ? -1 : 1;
		ctx.save(); ctx.translate(x + 6, y + 7); ctx.rotate(ring * 0.08);
		rr(-5, -7, 10, 13, 2, '#0f172a', OL, 1.3); R(-3.5, -5, 7, 7, '#38bdf8'); text('!', 0, 2, 7, '#ef4444');
		ctx.restore();
		R(x + 2, y + e.h - 2, 2, 2 + (Math.floor(s.t / 6) % 2), OL); R(x + 8, y + e.h - 2, 2, 2 + ((Math.floor(s.t / 6) + 1) % 2), OL);
		if (Math.floor(s.t / 10) % 2) { ctx.strokeStyle = '#f8fafc'; ctx.lineWidth = 1; ctx.beginPath(); ctx.arc(x + 6 + dir * 8, y + 2, 3, -0.8, 0.8); ctx.stroke(); }
	};
	const drawPing = (e) => { // notification bubble
		rr(e.x, e.y, 14, 11, 4, '#f8fafc', OL, 1.2);
		ctx.beginPath(); ctx.moveTo(e.x + 3, e.y + 10); ctx.lineTo(e.x + 1, e.y + 14); ctx.lineTo(e.x + 7, e.y + 10.5); ctx.fillStyle = '#f8fafc'; ctx.fill();
		R(e.x + 3, e.y + 3, 8, 1.5, '#94a3b8'); R(e.x + 3, e.y + 6, 5, 1.5, '#94a3b8');
		circ(e.x + 13, e.y + 1, 3, '#ef4444', OL, 0.8); text('۱', e.x + 13, e.y + 3, 5, '#fff');
	};
	const drawMail = (e) => { // hopping work e-mail
		const air = !e.ground;
		rr(e.x, e.y + (air ? 0 : 2), 14, 10, 1.5, '#fef3c7', OL, 1.2);
		ctx.beginPath(); ctx.moveTo(e.x, e.y + (air ? 0 : 2)); ctx.lineTo(e.x + 7, e.y + (air ? 6 : 8)); ctx.lineTo(e.x + 14, e.y + (air ? 0 : 2)); ctx.strokeStyle = OL; ctx.stroke();
		rr(e.x + 9, e.y - 3, 7, 5, 1, '#ef4444', OL, 0.8); text('فوری', e.x + 12.5, e.y + 1, 3.5, '#fff');
	};
	// hooded hacker: dark hoodie, glowing green visor, laptop under the arm, after-image trail + glitch
	const drawHacker = () => {
		const h = s.hk;
		if (!h) return;
		if (h.st === 'warn') {
			// edge warning on the side he will come from
			const left = h.dir > 0, x = left ? s.cam + 4 : s.cam + view.vw - 22, y = s.p.y - 6, blink = Math.floor(s.t / 5) % 2;
			ctx.globalAlpha = blink ? 1 : 0.55;
			rr(x, y, 18, 16, 3, '#0b0b0e', '#22c55e', 1.5);
			text('⚠', x + 9, y + 12, 10, '#22c55e');
			ctx.globalAlpha = 1;
			return;
		}
		if (h.st !== 'run') return;
		const run = Math.sin(s.t / 2.2), x0 = h.x, y0 = h.y;
		const body = (x, y, ghost) => {
			ctx.save(); ctx.translate(x, y + HK_H); ctx.scale(h.dir, 1);
			if (!ghost) { ctx.strokeStyle = OL; ctx.lineWidth = 3; ctx.lineCap = 'round'; ctx.beginPath(); ctx.moveTo(-1, -8); ctx.lineTo(-1 - run * 4, 0); ctx.moveTo(1, -8); ctx.lineTo(1 + run * 4, 0); ctx.stroke(); }
			ctx.beginPath(); ctx.moveTo(-5, -7); ctx.lineTo(-6, -15); ctx.quadraticCurveTo(-7, -23, 0, -23); ctx.quadraticCurveTo(6, -23, 6, -16); ctx.lineTo(5, -7); ctx.closePath();
			ctx.fillStyle = ghost ? 'rgba(34,197,94,.25)' : '#111827'; ctx.fill(); if (!ghost) { ctx.lineWidth = 1.4; ctx.strokeStyle = OL; ctx.stroke(); }
			if (!ghost) {
				ctx.beginPath(); ctx.ellipse(1.6, -17.5, 3.6, 3.4, 0, 0, 7); ctx.fillStyle = '#030712'; ctx.fill(); // face in the hood's shadow
				ctx.fillStyle = '#4ade80'; ctx.fillRect(0.4, -18.6, 4.4, 1.3); // visor glow
				ctx.fillStyle = '#e5e7eb'; ctx.fillRect(1, -14.4, 3, 0.7); // grin
				ctx.fillStyle = '#374151'; ctx.fillRect(-2, -12, 7, 4.4); ctx.fillStyle = '#22c55e'; ctx.fillRect(-1.2, -11.2, 5.4, 2.6); // laptop
				ctx.strokeStyle = '#22c55e'; ctx.lineWidth = 0.6; ctx.beginPath(); ctx.moveTo(-4.5, -14); ctx.lineTo(-1, -13); ctx.stroke();
			}
			ctx.restore();
		};
		for (let i = 3; i > 0; i--) body(x0 - h.dir * i * 7, y0, true);
		const gl = Math.floor(s.t / 4) % 9 === 0 ? 2 : 0;
		body(x0 + gl, y0, false);
		if (gl) { R(x0 - 8, y0 + 6, 16, 1.5, 'rgba(34,197,94,.9)'); R(x0 - 6, y0 + 13, 12, 1.2, 'rgba(244,63,94,.8)'); }
	};
	// comic speech bubble for the one-liners
	const drawQuip = () => {
		const q = s.quip;
		if (!q) return;
		const who = q.who === 'hk' && s.hk && s.hk.st === 'run' ? { x: s.hk.x, y: s.hk.y } : s.dying && s.ghost ? { x: s.ghost.x, y: s.ghost.y - (2.4 - s.dying) * 34 - 40 } : { x: s.p.x + s.p.w / 2, y: s.p.y };
		ctx.font = '900 8px yekanBakh, Tahoma';
		const w = Math.min(170, ctx.measureText(q.t).width + 12), bx = clamp(who.x - w / 2, s.cam + 4, s.cam + view.vw - w - 4), by = who.y - 26;
		const pop = clamp(((q.max || 2.2) - q.life) * 6, 0.05, 1); // never negative (that mirrored the text)
		ctx.save(); ctx.translate(who.x, by + 16); ctx.scale(pop, pop); ctx.translate(-who.x, -(by + 16));
		ctx.globalAlpha = Math.min(1, q.life * 3);
		rr(bx, by, w, 15, 7, q.who === 'hk' ? '#052e16' : '#fff', OL, 1.5);
		ctx.beginPath(); ctx.moveTo(who.x - 3, by + 14.2); ctx.lineTo(who.x, by + 19); ctx.lineTo(who.x + 3, by + 14.2); ctx.fillStyle = q.who === 'hk' ? '#052e16' : '#fff'; ctx.fill();
		ctx.fillStyle = q.who === 'hk' ? '#4ade80' : OL; ctx.textAlign = 'center'; ctx.fillText(q.t, bx + w / 2, by + 10.5, w - 8);
		ctx.restore();
	};
	const drawBug = (e, k = 1) => {
		const dir = e.vx < 0 ? -1 : 1;
		if (e.task) { const pl = 1 + Math.sin(s.t / 4) * 0.15; ctx.beginPath(); ctx.ellipse(e.x + e.w / 2, e.y + e.h / 2, 13 * pl, 10 * pl, 0, 0, 7); ctx.strokeStyle = '#ef4444'; ctx.lineWidth = 2; ctx.stroke(); text('📌', e.x + e.w / 2, e.y - 6, 9, '#fff'); }
		drawBeetle(ctx, e.x + e.w / 2, e.y + e.h, k * 0.95, dir, s.t / 60 + e.x * 0.01, k > 1);
		// brief glitch slices now and then
		if ((Math.floor(s.t / 9) + Math.round(e.x / 16)) % 23 === 0) { R(e.x - 2, e.y + e.h * 0.35, e.w + 4, 1.5 * k, 'rgba(34,211,238,.8)'); R(e.x + 1, e.y + e.h * 0.6, e.w - 2, 1.2 * k, 'rgba(244,63,94,.8)'); }
	};
	const drawClock = (e, k = 1) => {
		const dir = e.vx < 0 ? -1 : 1, shake = Math.floor(s.t / 3) % 2, cx = e.x + e.w / 2, cy = e.y + e.h / 2;
		circ(e.x + 2 * k, e.y + (1 + shake) * k, 3 * k, '#fbbf24'); circ(e.x + e.w - 2 * k, e.y + (1 - shake) * k, 3 * k, '#fbbf24');
		circ(cx, cy, e.w / 2 + 0.5, '#f8fafc');
		eyes(cx - 4.5 * k, cy - 1 * k, dir, true, k);
		R(cx - 3 * k, cy + 3.5 * k, 6 * k, 1.5 * k, OL);
		R(e.x + 2 * k, e.y + e.h - 1, 3 * k, 3 * k, OL); R(e.x + e.w - 5 * k, e.y + e.h - 1, 3 * k, 3 * k, OL);
	};
	const drawHopper = (e) => {
		const air = !e.ground, dir = s.p.x < e.x ? -1 : 1;
		ctx.beginPath(); ctx.ellipse(e.x + 7, e.y + 7 + (air ? -1 : 1), 8, air ? 7 : 5.5, 0, 0, 7); ctx.fillStyle = '#a855f7'; ctx.fill(); ctx.lineWidth = 1.5; ctx.strokeStyle = OL; ctx.stroke();
		eyes(e.x + 2, e.y + 3, dir, false);
		text(';', e.x + 7, e.y + 13, 9, '#f5d0fe');
		if (!air) { R(e.x, e.y + e.h - 2, 4, 2, OL); R(e.x + 10, e.y + e.h - 2, 4, 2, OL); }
	};
	const drawVirus = (e) => {
		const cx = e.x + 7, cy = e.y + 7, rot = s.t / 24, pulse = 1 + Math.sin(s.t / 8) * 0.06;
		for (let i = 0; i < 9; i++) {
			const a = rot + (i / 9) * Math.PI * 2;
			ctx.strokeStyle = OL; ctx.lineWidth = 2.2; ctx.beginPath(); ctx.moveTo(cx + Math.cos(a) * 5.5, cy + Math.sin(a) * 5.5); ctx.lineTo(cx + Math.cos(a) * 10, cy + Math.sin(a) * 10); ctx.stroke();
			ctx.strokeStyle = '#a3e635'; ctx.lineWidth = 0.9; ctx.stroke();
			circ(cx + Math.cos(a) * 10.4, cy + Math.sin(a) * 10.4, 1.7, '#f43f5e', OL, 0.8);
		}
		const g = ctx.createRadialGradient(cx - 2, cy - 3, 1, cx, cy, 7.5); g.addColorStop(0, '#d9f99d'); g.addColorStop(0.5, '#65a30d'); g.addColorStop(1, '#365314');
		ctx.beginPath(); ctx.arc(cx, cy, 7 * pulse, 0, 7); ctx.fillStyle = g; ctx.fill(); ctx.lineWidth = 1.5; ctx.strokeStyle = OL; ctx.stroke();
		circ(cx + 3, cy + 3, 1.2, 'rgba(54,83,20,.7)', null); circ(cx - 3.5, cy + 3.5, 0.9, 'rgba(54,83,20,.7)', null);
		const d = s.p.x < e.x ? -1 : 1;
		ctx.fillStyle = '#fef08a'; [-2.2, 2.2].forEach((ox) => { ctx.beginPath(); ctx.ellipse(cx + ox + d, cy - 1, 1.6, 1.1, 0, 0, 7); ctx.fill(); });
		ctx.fillStyle = OL; [-2.2, 2.2].forEach((ox) => circ(cx + ox + d * 1.6, cy - 0.8, 0.7, OL, null));
		ctx.strokeStyle = OL; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(cx - 4, cy - 3.4); ctx.lineTo(cx - 1, cy - 2.2); ctx.moveTo(cx + 4, cy - 3.4); ctx.lineTo(cx + 1, cy - 2.2); ctx.stroke();
		ctx.beginPath(); ctx.moveTo(cx - 2.4, cy + 3); ctx.lineTo(cx - 1.2, cy + 2.2); ctx.lineTo(cx, cy + 3); ctx.lineTo(cx + 1.2, cy + 2.2); ctx.lineTo(cx + 2.4, cy + 3); ctx.stroke(); // jagged grin
	};
	const drawSpam = (e) => {
		const flap = Math.floor(s.t / 6) % 2;
		ctx.fillStyle = '#e0f2fe'; ctx.strokeStyle = OL; ctx.lineWidth = 1.2;
		ctx.beginPath(); ctx.ellipse(e.x - 3, e.y + (flap ? 2 : 6), 5, 3, flap ? -0.6 : 0.4, 0, 7); ctx.fill(); ctx.stroke();
		ctx.beginPath(); ctx.ellipse(e.x + 17, e.y + (flap ? 2 : 6), 5, 3, flap ? 0.6 : -0.4, 0, 7); ctx.fill(); ctx.stroke();
		rr(e.x, e.y + 1, 14, 11, 2, '#fde68a');
		ctx.beginPath(); ctx.moveTo(e.x, e.y + 1); ctx.lineTo(e.x + 7, e.y + 7); ctx.lineTo(e.x + 14, e.y + 1); ctx.stroke();
		circ(e.x + 7, e.y + 8, 2, '#ef4444', OL, 1);
	};
	const drawTurret = (e) => {
		// firewall bunker with a cannon and flickering flames
		const dir = s.p.x < e.x ? -1 : 1, fl = Math.sin(s.t / 3);
		[[3, '#f97316', 5], [8, '#fbbf24', 6.5], [13, '#f97316', 5]].forEach(([fx, col, hgt], i) => { const hh = hgt + (i % 2 ? fl : -fl) * 1.5; ctx.beginPath(); ctx.moveTo(e.x + fx - 3, e.y + 4); ctx.quadraticCurveTo(e.x + fx - 2, e.y + 4 - hh * 0.6, e.x + fx, e.y + 4 - hh); ctx.quadraticCurveTo(e.x + fx + 2, e.y + 4 - hh * 0.6, e.x + fx + 3, e.y + 4); ctx.fillStyle = col; ctx.fill(); ctx.lineWidth = 1; ctx.strokeStyle = OL; ctx.stroke(); });
		rr(e.x, e.y + 3, 16, 13, 2, '#b91c1c');
		ctx.strokeStyle = '#7f1d1d'; ctx.lineWidth = 1; ctx.beginPath();
		ctx.moveTo(e.x + 1, e.y + 7.5); ctx.lineTo(e.x + 15, e.y + 7.5); ctx.moveTo(e.x + 1, e.y + 12); ctx.lineTo(e.x + 15, e.y + 12);
		ctx.moveTo(e.x + 6, e.y + 3.5); ctx.lineTo(e.x + 6, e.y + 7.5); ctx.moveTo(e.x + 11, e.y + 7.5); ctx.lineTo(e.x + 11, e.y + 12); ctx.moveTo(e.x + 5, e.y + 12); ctx.lineTo(e.x + 5, e.y + 15.5); ctx.stroke();
		rr(dir < 0 ? e.x - 6 : e.x + 14, e.y + 6, 8, 5, 1.5, '#1f2937');
		R(dir < 0 ? e.x - 6 : e.x + 20, e.y + 7, 2, 3, e.cd < 0.4 ? '#fde047' : '#0b0b0e');
		circ(e.x + 8, e.y + 9.8, 1.6, '#fde047', OL, 0.8);
	};
	const drawItem = (e) => {
		const st = Math.floor(s.t / 10) % 2;
		if (e.t === 'coffee') { rr(e.x, e.y + 2, 12, 12, 2, '#f8fafc'); R(e.x + 1, e.y + 6, 10, 3, '#b45309'); rr(e.x - 1, e.y, 14, 3, 1, '#334155'); R(e.x + 3 + st, e.y - 4, 1.5, 3, '#fff'); R(e.x + 7 - st, e.y - 5, 1.5, 3, '#fff'); }
		else if (e.t === 'laptop') { rr(e.x, e.y + 1, 13, 9, 1.5, '#cbd5e1'); R(e.x + 1.5, e.y + 2.5, 10, 6, '#0f172a'); ctx.fillStyle = '#22d3ee'; ctx.font = '700 5px monospace'; ctx.textAlign = 'center'; ctx.fillText('</>', e.x + 6.5, e.y + 7.5); rr(e.x - 2, e.y + 10, 17, 3, 1, '#94a3b8'); }
		else if (e.t === 'jetpack') { rr(e.x + 1, e.y, 4, 12, 2, '#94a3b8'); rr(e.x + 7, e.y, 4, 12, 2, '#94a3b8'); circ(e.x + 3, e.y + 14, 2, st ? '#fb923c' : '#fde047', null); circ(e.x + 9, e.y + 14, 2, st ? '#fde047' : '#fb923c', null); }
		else if (e.t === 'shield') { ctx.beginPath(); ctx.moveTo(e.x + 6, e.y); ctx.lineTo(e.x + 12, e.y + 3); ctx.lineTo(e.x + 11, e.y + 9); ctx.lineTo(e.x + 6, e.y + 14); ctx.lineTo(e.x + 1, e.y + 9); ctx.lineTo(e.x, e.y + 3); ctx.closePath(); ctx.fillStyle = `hsl(${(s.t * 10) % 360} 85% 60%)`; ctx.fill(); ctx.strokeStyle = OL; ctx.lineWidth = 1.3; ctx.stroke(); }
		else if (e.t === 'oneup') { rr(e.x, e.y, 13, 13, 1, '#22c55e'); R(e.x + 3, e.y + 1, 7, 4, '#e2e8f0'); R(e.x + 3, e.y + 8, 7, 4, '#0f172a'); ctx.fillStyle = '#fff'; ctx.font = '900 5px monospace'; ctx.textAlign = 'center'; ctx.fillText('1UP', e.x + 6.5, e.y + 11.5); }
	};
	const drawCoin = (x, y) => {
		if (s.lv.side === 'date') { // rose
			ctx.strokeStyle = '#15803d'; ctx.lineWidth = 1.5; ctx.beginPath(); ctx.moveTo(x + 8, y + 8); ctx.lineTo(x + 8, y + 16); ctx.stroke();
			R(x + 8, y + 11, 3, 2, '#22c55e');
			circ(x + 8, y + 6, 4.2, '#e11d48', OL, 1.1); ctx.strokeStyle = '#9f1239'; ctx.lineWidth = 0.8; ctx.beginPath(); ctx.arc(x + 8, y + 6, 2, 0, 5); ctx.stroke();
			return;
		}
		if (s.lv.side === 'war') { rr(x + 3, y + 4, 11, 9, 1.5, '#fbbf24', OL, 1.1); R(x + 3, y + 3, 5, 2, '#f59e0b'); text('{}', x + 8.5, y + 11.5, 5, '#78350f'); return; } // project files
		if (s.lv.side === 'drive') { rr(x + 4, y + 3, 9, 11, 1.5, '#dc2626', OL, 1.1); R(x + 6, y + 1, 4, 2, '#111'); R(x + 6, y + 6, 5, 2, '#fde047'); return; } // fuel can
		const w = [7, 5, 2, 5][Math.floor(s.t / 7) % 4];
		ctx.beginPath(); ctx.ellipse(x + 8, y + 8, w, 6.5, 0, 0, 7); ctx.fillStyle = '#facc15'; ctx.fill(); ctx.lineWidth = 1.3; ctx.strokeStyle = '#a16207'; ctx.stroke();
		if (w > 3) R(x + 8 - w / 3, y + 4, 1.5, 8, '#fef9c3');
	};
	const drawBoss = (b) => {
		if (b.inv > 0 && Math.floor(s.t / 3) % 2) return;
		ctx.save();
		if (b.hp <= 0) ctx.globalAlpha = Math.max(0, b.dead / 2);
		const dir = s.p.x < b.x ? -1 : 1;
		if (b.type === 'megabug') drawBug({ ...b, vx: dir }, 2.5);
		else if (b.type === 'deadline') { drawClock({ ...b, vx: dir }, 2.6); text('!', b.x + b.w / 2, b.y - 4, 14, '#ef4444'); }
		else if (b.type === 'server') {
			rr(b.x, b.y, b.w, b.h, 4, '#1e293b');
			for (let i = 0; i < 4; i++) R(b.x + 5, b.y + 8 + i * 8, b.w - 10, 3, (i + Math.floor(s.t / 8)) % 2 ? '#ef4444' : '#7f1d1d');
			eyes(b.x + b.w / 2 - 7, b.y + 6, dir, true, 1.4);
			text('404', b.x + b.w / 2, b.y + b.h + 9, 8, '#fca5a5');
		} else if (b.type === 'scorpion') {
			ctx.beginPath(); ctx.ellipse(b.x + b.w / 2, b.y + b.h / 2 + 4, b.w / 2, b.h / 2 - 4, 0, 0, 7); ctx.fillStyle = '#a16207'; ctx.fill(); ctx.lineWidth = 2; ctx.strokeStyle = OL; ctx.stroke();
			const tx = b.x + (b.dir > 0 ? 4 : b.w - 4);
			ctx.beginPath(); ctx.moveTo(tx, b.y + 12); ctx.quadraticCurveTo(tx - b.dir * 14, b.y - 14, tx + b.dir * 6, b.y - 8); ctx.lineWidth = 4; ctx.strokeStyle = '#a16207'; ctx.stroke();
			circ(tx + b.dir * 6, b.y - 8, 3, '#ef4444');
			for (let i = 0; i < 3; i++) R(b.x + 8 + i * 12, b.y + b.h - 2, 3, 5, OL);
			eyes(b.x + (b.dir > 0 ? b.w - 16 : 6), b.y + 10, b.dir, true, 1.2);
			text('{ ;', b.x + b.w / 2, b.y + b.h / 2 + 8, 8, '#fde68a');
		}
		ctx.restore();
	};

	const bg = () => {
		const wd = WORLDS[s.world], th = s.lv.isRoom ? 'room' : wd.theme, cam = s.cam;
		const g = ctx.createLinearGradient(0, CAM_TOP, 0, VH);
		if (th === 'room') { g.addColorStop(0, '#0b1023'); g.addColorStop(1, '#1e1b4b'); } else { g.addColorStop(0, wd.sky[0]); g.addColorStop(1, wd.sky[1]); }
		ctx.fillStyle = g; ctx.fillRect(0, 0, view.vw, VH);
		const loopX = (par, span) => -((cam * par) % span);
		if (th === 'drive' || th === 'date' || th === 'war') {
			const night = th === 'date', war = th === 'war';
			if (war) for (let i = 0; i < 4; i++) { const sx = ((i * 260 + 120 - cam * 0.1) % (view.vw + 200) + view.vw + 200) % (view.vw + 200) - 100; for (let j = 0; j < 6; j++) circ(sx + Math.sin(s.t / 30 + j) * 6, GY * T - 120 - j * 16, 14 + j * 4, `rgba(68,64,60,${0.32 - j * 0.04})`, null); }
			if (night) { circ(view.vw * 0.82, 70, 13, '#fef9c3', null); }
			else if (!war) { const mx = ((view.vw * 0.75 - cam * 0.04) % (view.vw + 80) + view.vw + 80) % (view.vw + 80) - 40; R(mx - 2, 70, 4, 140, 'rgba(30,41,59,.35)'); ctx.fillStyle = 'rgba(30,41,59,.35)'; ctx.beginPath(); ctx.ellipse(mx, 92, 10, 7, 0, 0, 7); ctx.fill(); R(mx - 0.5, 50, 1, 20, 'rgba(30,41,59,.35)'); } // Milad tower
			for (let L = 0; L < 2; L++) {
				const par = L ? 0.4 : 0.18, col = war ? (L ? '#292524' : '#44403c') : L ? (night ? '#3b0764' : '#334155') : (night ? '#581c87' : '#64748b'), span = view.vw + 160;
				for (let i = 0; i < 12; i++) {
					const bw = 34 + ((i * 37 + L * 11) % 30), bh = 40 + ((i * 53 + L * 29) % 70) - L * 12, bx = ((i * 74 - cam * par) % span + span) % span - 80, by = GY * T - bh;
					R(bx, by, bw, bh, col);
					for (let wy = by + 6; wy < by + bh - 6; wy += 10) for (let wx = bx + 5; wx < bx + bw - 6; wx += 9) if (((wx * 7 + wy * 3 + i) | 0) % 5 < 2) R(wx, wy, 4, 5, night ? '#fde68a' : 'rgba(254,243,199,.8)');
				}
			}
			if (night) for (let i = 0; i < 20; i++) { const lx = ((i * 40 - cam * 0.6) % (view.vw + 40) + view.vw + 40) % (view.vw + 40) - 20; circ(lx, 66 + Math.sin(i * 0.9) * 5, 2, ['#f472b6', '#fde047', '#60a5fa', '#4ade80'][i % 4], null); }
			return;
		}
		if (th === 'room') {
			ctx.fillStyle = 'rgba(34,211,238,.25)'; ctx.font = '700 9px monospace'; ctx.textAlign = 'left';
			for (let i = 0; i < 14; i++) ctx.fillText(['if (secret) {', 'coins++;', 'return bonus;', '}', 'const k = "korosh";', '// shh'][i % 6], 10 + (i % 3) * 150, 70 + i * 10);
			return;
		}
		if (th === 'night') { for (let i = 0; i < 40; i++) { const sx = ((i * 97 - cam * 0.05) % (view.vw + 20) + view.vw + 20) % (view.vw + 20); R(sx, 52 + ((i * 53) % 110), i % 3 ? 1 : 2, i % 3 ? 1 : 2, '#fff'); } circ(view.vw - 60, 82, 14, '#fef9c3', null); }
		if (th === 'plains' || th === 'desert') {
			for (let i = 0; i < 5; i++) {
				const cx = ((i * 170 - cam * 0.15) % (view.vw + 160) + view.vw + 160) % (view.vw + 160) - 80, cy = 70 + (i % 3) * 14;
				ctx.fillStyle = '#fff'; ctx.strokeStyle = 'rgba(30,64,120,.35)'; ctx.lineWidth = 1.5;
				ctx.beginPath(); ctx.arc(cx, cy, 9, Math.PI * 0.5, Math.PI * 1.5); ctx.arc(cx + 12, cy - 6, 11, Math.PI, 0); ctx.arc(cx + 26, cy - 2, 9, Math.PI * 1.5, Math.PI * 0.5); ctx.closePath(); ctx.fill(); ctx.stroke();
			}
		}
		ctx.font = '700 10px monospace'; ctx.textAlign = 'center';
		ctx.fillStyle = th === 'under' || th === 'night' ? 'rgba(165,180,252,.18)' : 'rgba(255,255,255,.35)';
		['{ }', '</>', '=>', '( )', ';', '[ ]', '&&'].forEach((t, i) => ctx.fillText(t, ((i * 131 - cam * 0.12) % (view.vw + 60) + view.vw + 60) % (view.vw + 60) - 30, 100 + (i % 4) * 22));
		if (th === 'plains') {
			const mx = loopX(0.08, 900);
			for (const ox of [mx + 120, mx + 1020]) {
				ctx.fillStyle = '#7aa5d6'; ctx.beginPath(); ctx.moveTo(ox, 208); ctx.lineTo(ox + 110, 112); ctx.lineTo(ox + 220, 208); ctx.fill();
				ctx.fillStyle = '#f8fafc'; ctx.beginPath(); ctx.moveTo(ox + 82, 136); ctx.lineTo(ox + 110, 112); ctx.lineTo(ox + 138, 136); ctx.lineTo(ox + 124, 132); ctx.lineTo(ox + 112, 140); ctx.lineTo(ox + 98, 132); ctx.fill();
			}
			const hx = loopX(0.35, 260);
			for (let k = -1; k < view.vw / 260 + 2; k++) { const bx = hx + k * 260; ctx.fillStyle = '#7ee05a'; ctx.strokeStyle = '#2f8f23'; ctx.lineWidth = 2; ctx.beginPath(); ctx.ellipse(bx + 60, 212, 70, 44, 0, Math.PI, 0); ctx.fill(); ctx.stroke(); ctx.beginPath(); ctx.ellipse(bx + 180, 212, 46, 26, 0, Math.PI, 0); ctx.fill(); ctx.stroke(); circ(bx + 46, 186, 2, '#2f8f23', null); circ(bx + 74, 194, 2, '#2f8f23', null); }
		} else if (th === 'desert') {
			const dx = loopX(0.3, 320);
			for (let k = -1; k < view.vw / 320 + 2; k++) { const bx = dx + k * 320; ctx.fillStyle = '#f6c47b'; ctx.beginPath(); ctx.moveTo(bx, 214); ctx.quadraticCurveTo(bx + 90, 168, bx + 180, 214); ctx.fill(); for (let i = 0; i < 4; i++) { rr(bx + 200 + i * 26, 150 + (i % 2) * 12, 9, 64, 1, '#d8a463', '#8a5a2b', 1.2); rr(bx + 196 + i * 26, 146 + (i % 2) * 12, 17, 6, 1, '#e7b877', '#8a5a2b', 1.2); } }
		} else if (th === 'under') {
			const ux = loopX(0.4, 200);
			for (let k = -1; k < view.vw / 200 + 2; k++) { const bx = ux + k * 200; rr(bx + 20, 100, 40, 110, 3, '#111a3d', '#2b4c9b', 1.5); for (let j = 0; j < 8; j++) R(bx + 26, 108 + j * 12, 28, 3, (j + Math.floor(s.t / 20)) % 3 ? '#1e3a8a' : '#22d3ee'); ctx.strokeStyle = 'rgba(34,211,238,.35)'; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(bx + 60, 120); ctx.bezierCurveTo(bx + 100, 160, bx + 140, 90, bx + 200, 130); ctx.stroke(); }
		} else if (th === 'night') {
			const nx = loopX(0.3, 300);
			for (let k = -1; k < view.vw / 300 + 2; k++) { const bx = nx + k * 300; ctx.fillStyle = '#1e2455'; ctx.fillRect(bx + 40, 140, 60, 80); ctx.fillRect(bx + 30, 120, 20, 100); ctx.fillRect(bx + 90, 120, 20, 100); for (let i = 0; i < 3; i++) { ctx.fillRect(bx + 30 + i * 8, 114, 5, 6); ctx.fillRect(bx + 90 + i * 8, 114, 5, 6); } R(bx + 66, 170, 8, 12, '#fbbf24'); }
		}
	};

	const drawTile = (c, x, y, wd) => {
		const px = x * T;
		let py = y * T;
		if (s.parts.some((q) => q.k === 'bump' && q.x === x && q.y === y)) py -= 4;
		if (c === '#' || c === 'F') {
			R(px, py, T, T, wd.dirt);
			R(px, py, T, 1, wd.dirtD); R(px, py, 1, T, wd.dirtD);
			R(px + 3, py + 5, 3, 2, wd.dirtD); R(px + 10, py + 10, 3, 2, wd.dirtD);
			if (c === 'F') { ctx.strokeStyle = wd.dirtD; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(px + 4, py + 2); ctx.lineTo(px + 7, py + 7); ctx.lineTo(px + 5, py + 12); ctx.stroke(); }
			if (y > 0 && s.lv.map[y - 1][x] !== '#' && s.lv.map[y - 1][x] !== 'F') { R(px, py, T, 5, wd.top); R(px, py + 5, T, 1.5, wd.topD); R(px + ((x * 5) % 12), py + 1, 2, 1, 'rgba(255,255,255,.45)'); }
		} else if (c === 'B') {
			R(px, py, T, T, '#c2410c'); R(px, py, T, 1, '#fdba74');
			R(px, py + 7, T, 1.5, '#7c2d12'); R(px, py + 15, T, 1, '#7c2d12');
			R(px + 7, py, 1.5, 7, '#7c2d12'); R(px + 3, py + 8, 1.5, 7, '#7c2d12'); R(px + 11, py + 8, 1.5, 7, '#7c2d12');
		} else if (c === '?' || c === 'M' || c === 'P' || c === '*') {
			const shine = Math.floor(s.t / 10) % 4 === 0;
			rr(px + 0.5, py + 0.5, T - 1, T - 1, 2, shine ? '#fde047' : '#fbbf24', '#7c2d12', 1.2);
			R(px + 2, py + 2, 2, 2, 'rgba(255,255,255,.55)'); R(px + 12, py + 2, 2, 2, '#7c2d12'); R(px + 2, py + 12, 2, 2, '#7c2d12'); R(px + 12, py + 12, 2, 2, '#7c2d12');
			ctx.fillStyle = '#7c2d12'; ctx.font = '900 9px monospace'; ctx.textAlign = 'center'; ctx.fillText('{}', px + 8, py + 11.5);
		} else if (c === 'U') {
			rr(px + 0.5, py + 0.5, T - 1, T - 1, 2, '#a16207', '#422006', 1.2); R(px + 2, py + 2, 2, 2, '#422006'); R(px + 12, py + 12, 2, 2, '#422006');
		} else if (c === 'X') {
			const shake = s.crumble[`${x},${y}`] !== undefined ? (Math.random() - 0.5) * 2 : 0;
			rr(px + shake + 0.5, py + 0.5, T - 1, T - 1, 1, '#94a3b8', '#334155', 1.2);
			ctx.strokeStyle = '#475569'; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(px + 3 + shake, py + 3); ctx.lineTo(px + 8 + shake, py + 8); ctx.lineTo(px + 6 + shake, py + 13); ctx.moveTo(px + 8 + shake, py + 8); ctx.lineTo(px + 13 + shake, py + 6); ctx.stroke();
		} else if (c === 'S' || c === 's' || c === 'W' || c === 'w') {
			const left = c === 'S' || c === 'W';
			const top = c === 'W' || c === 'w' || y === 0 || !['S', 's', 'W', 'w'].includes(s.lv.map[y - 1][x]);
			R(px, py, T, T, '#0d9488'); R(px + (left ? 2 : 0), py, 3, T, '#5eead4'); R(px + (left ? 0 : T - 1), py, 1, T, OL);
			if (top) { R(px - (left ? 2 : 0), py, T + 2, 6, '#14b8a6'); R(px - (left ? 2 : 0), py, T + 2, 1, OL); R(px - (left ? 2 : 0), py + 6, T + 2, 1, OL); }
			R(px + (left ? 8 : 4), py + 9, 2, 2, (Math.floor(s.t / 18) + x) % 2 ? '#a3e635' : '#166534');
			if (c === 'W' && Math.floor(s.t / 20) % 2) { ctx.fillStyle = '#fde047'; ctx.font = '900 9px monospace'; ctx.textAlign = 'center'; ctx.fillText('▼', px + T, py - 3); }
		} else if (c === '=') {
			rr(px, py, T, 7, 2, '#94a3b8', '#334155', 1.2); R(px + 2, py + 1.5, T - 4, 1.5, '#e2e8f0');
		} else if (c === 'o') drawCoin(px, py);
	};

	const drawGoal = () => {
		const g = s.lv.goal, bx = g.x, by = g.y;
		if (s.lv.side === 'drive') { R(bx + 14, by - 46, 3, 46, '#94a3b8'); rr(bx - 6, by - 60, 44, 16, 3, '#1d4ed8', OL, 1.4); text('کوچه مائده', bx + 16, by - 49, 8, '#fff'); rr(bx + 2, by - 28, 16, 16, 3, '#2563eb', OL, 1.2); text('P', bx + 10, by - 16, 11, '#fff'); return; }
		if (s.lv.side === 'rain') { rr(bx - 6, by - 70, 64, 70, 3, '#78350f', OL, 1.4); R(bx, by - 54, 52, 30, '#fde68a'); rr(bx - 8, by - 84, 68, 13, 3, '#7c2d12', OL, 1.2); text('تعمیر کیف · منوچهری', bx + 26, by - 75, 7, '#fff'); rr(bx + 16, by - 20, 18, 20, 2, '#451a03', OL, 1); rr(bx + 4, by - 50, 20, 14, 3, '#1f2937', OL, 1); return; }
		if (s.lv.side === 'war') { rr(bx - 4, by - 92, 60, 92, 3, '#334155', OL, 1.4); for (let j = 0; j < 6; j++) for (let i = 0; i < 4; i++) R(bx + 2 + i * 13, by - 86 + j * 13, 8, 8, (i + j) % 3 ? '#fde68a' : '#1e293b'); rr(bx + 2, by - 106, 48, 13, 3, '#0f172a', '#fb923c', 1.2); text('شرکت · پاسداران', bx + 26, by - 97, 7, '#fff'); rr(bx + 18, by - 22, 16, 22, 2, '#78350f', OL, 1); return; }
		if (s.lv.side === 'date') { drawMaedeh(ctx, bx + 20, by, 0.82, -1, s.won ? 'happy' : 'angry', s.t / 60); if (!s.won && Math.floor(s.t / 25) % 2) text('💗', bx + 20, by - 44, 10, '#fff'); return; }
		rr(bx + 38, by - 70, 26, 70, 3, '#1e293b');
		for (let i = 0; i < 6; i++) R(bx + 42, by - 64 + i * 10, 18, 3, s.won ? `hsl(${(s.t * 8 + i * 40) % 360} 90% 60%)` : (i + Math.floor(s.t / 15)) % 3 ? '#166534' : '#22c55e');
		rr(bx - 8, by - 58, 52, 14, 3, '#0f172a', '#22c55e', 1.2);
		ctx.fillStyle = '#4ade80'; ctx.font = '700 8px monospace'; ctx.textAlign = 'center'; ctx.fillText('DEPLOY', bx + 18, by - 48);
		rr(bx + 2, by - 22, 28, 22, 3, '#64748b');
		R(bx + 4, by - 18, 24, 2, '#94a3b8');
		const press = s.pressed ? 4 : 0;
		ctx.beginPath(); ctx.ellipse(bx + 16, by - 22 + press, 11, 7 - press / 2, 0, Math.PI, 0); ctx.fillStyle = '#ef4444'; ctx.fill(); ctx.lineWidth = 1.5; ctx.strokeStyle = OL; ctx.stroke();
		if (!s.pressed) R(bx + 11, by - 27, 4, 2, 'rgba(255,255,255,.7)');
		if (!s.pressed && Math.floor(s.t / 25) % 2) text('▼', bx + 16, by - 34, 9, '#fde047');
	};

	// Styled in-game HUD: lives (pixel hearts) + coins on the left, world badge in the middle, clock on the right.
	const HEART = ['.KK.KK.', 'KrrKrrK', 'KrwrrrK', 'KrrrrrK', '.KrrrK.', '..KrK..', '...K...'];
	const pixelHeart = (x, y) => HEART.forEach((row, j) => [...row].forEach((c, i) => c !== '.' && R(x + i, y + j, 1, 1, c === 'K' ? OL : c === 'w' ? '#fecaca' : '#ef4444')));
	const panel = (x, y, w, h, border = '#fbbf24') => {
		const g = ctx.createLinearGradient(0, y, 0, y + h);
		g.addColorStop(0, 'rgba(30,27,75,.92)'); g.addColorStop(1, 'rgba(2,6,23,.92)');
		rr(x, y, w, h, h / 2, g, OL, 2);
		ctx.strokeStyle = border; ctx.globalAlpha = 0.55; ctx.lineWidth = 1; ctx.beginPath(); ctx.roundRect(x + 2, y + 2, w - 4, h - 4, (h - 4) / 2); ctx.stroke(); ctx.globalAlpha = 1;
	};
	const hud = () => {
		const y = 4, h = 17;
		// left: lives + coins (+ current power)
		const lw = s.big ? 98 : 82;
		panel(4, y, lw, h);
		pixelHeart(11, y + 5);
		text(fa(s.lives), 22, y + 13, 10, '#fecaca', 'left');
		ctx.save(); ctx.translate(40, y + 1); ctx.scale(0.8, 0.8); drawCoin(0, 1); ctx.restore();
		text(fa(s.coins), 55, y + 13, 9, '#fde68a', 'left');
		if (s.big) text({ coffee: '☕', dev: '💻', jet: '🚀' }[s.form] || '', 88, y + 13, 9, '#fff');
		// centre: world badge
		const wd = WORLDS[s.world];
		const label = s.lv.isRoom ? 'اتاق مخفی 🤫' : `${wd.name} · ${s.lv.isBoss ? 'باس' : `مرحله ${fa((s.stageN % STAGES_PER) + 1)}`}`;
		ctx.font = '900 9px yekanBakh, Tahoma';
		const tw = ctx.measureText(label).width + 30;
		panel(view.vw / 2 - tw / 2, y, tw, h, wd.top);
		circ(view.vw / 2 - tw / 2 + 11, y + h / 2, 4, wd.top, OL, 1.2);
		text(label, view.vw / 2 + 6, y + 12.5, 9, '#fff');
		// right: clock
		const low = s.timeLeft < 30, tt = fa(Math.max(0, Math.ceil(s.timeLeft)));
		panel(view.vw - 58, y, 54, h, low ? '#ef4444' : '#38bdf8');
		circ(view.vw - 47, y + h / 2, 5, '#f8fafc', OL, 1.3);
		ctx.strokeStyle = OL; ctx.lineWidth = 1.2; ctx.beginPath(); ctx.moveTo(view.vw - 47, y + h / 2); ctx.lineTo(view.vw - 47, y + h / 2 - 3); ctx.moveTo(view.vw - 47, y + h / 2); ctx.lineTo(view.vw - 45, y + h / 2 + 1); ctx.stroke();
		text(tt, view.vw - 11, y + 13, 10, low && Math.floor(s.t / 15) % 2 ? '#fca5a5' : '#e0f2fe', 'right');
		// side-story meters
		if (s.lv.side === 'date') { const bw = 120, bx = view.vw / 2 - bw / 2, by2 = y + h + 5; panel(bx, by2, bw, 12, '#f472b6'); R(bx + 3, by2 + 3, (bw - 6) * (s.pat / 100), 6, s.pat < 30 && Math.floor(s.t / 10) % 2 ? '#fb7185' : '#ec4899'); text('حوصله مائده 💗', bx + bw / 2, by2 + 9.5, 7, '#fff'); }
		if (s.lv.side === 'drive' || s.lv.side === 'war') text(`${s.lv.side === 'war' ? '🚙' : '🚗'} ${'■'.repeat(Math.max(0, s.carHp))}${'□'.repeat(Math.max(0, 3 - s.carHp))}`, view.vw / 2, y + h + 13, 9, '#fde047');
	};

	const draw = () => {
		cv.reset();
		ctx.save();
		ctx.translate(0, -CAM_TOP);
		if (s.shake > 0) ctx.translate((Math.random() - 0.5) * 8 * s.shake / 0.35, (Math.random() - 0.5) * 6 * s.shake / 0.35);
		bg();
		ctx.translate(-Math.round(s.cam), 0);
		const wd = WORLDS[s.world];
		const x0 = Math.max(0, Math.floor(s.cam / T) - 1), x1 = Math.min(s.lv.W, x0 + Math.ceil(view.vw / T) + 3);
		for (let y = 3; y < ROWS; y++) for (let x = x0; x < x1; x++) { const c = s.lv.map[y][x]; if (c !== '.' && c !== 'H') drawTile(c, x, y, wd); }
		if (!s.lv.isRoom) {
			if (!s.lv.isBoss) { const cx = s.lv.check; R(cx, 9 * T, 2, 4 * T, '#e2e8f0'); ctx.fillStyle = s.checkpoint ? '#22c55e' : '#94a3b8'; ctx.beginPath(); ctx.moveTo(cx + 2, 9 * T); ctx.lineTo(cx + 14, 9 * T + 5); ctx.lineTo(cx + 2, 9 * T + 10); ctx.fill(); }
			if (s.goalOn) drawGoal();
			for (const e of s.ents) {
				if (e.t === 'mover') { rr(e.x, e.y, e.w, e.h, 2, '#f59e0b', OL, 1.3); for (let i = 0; i < 4; i++) R(e.x + 4 + i * 11, e.y + 2, 6, 2, '#7c2d12'); continue; }
				if (!e.alive && !(e.dead > 0)) continue;
				ctx.save();
				if (!e.alive && !e.flip) { ctx.translate(e.x + 7, e.y + e.h); ctx.scale(1, 0.35); ctx.translate(-e.x - 7, -e.y - e.h); }
				else if (!e.alive && e.flip) { ctx.translate(0, e.y * 2 + e.h); ctx.scale(1, -1); }
				if (e.item) drawItem(e); else if (e.t === 'bug') drawBug(e); else if (e.t === 'clock') drawClock(e); else if (e.t === 'spam') drawSpam(e); else if (e.t === 'hopper') drawHopper(e); else if (e.t === 'virus') drawVirus(e); else if (e.t === 'turret') drawTurret(e); else if (e.t === 'car') drawCarFoe(e); else if (e.t === 'cone') drawCone(e); else if (e.t === 'call') drawCall(e); else if (e.t === 'ping') drawPing(e); else if (e.t === 'mail') drawMail(e);
				ctx.restore();
			}
			if (s.boss && (s.boss.hp > 0 || s.boss.dead > 0)) drawBoss(s.boss);
			s.shots.forEach((q) => { circ(q.x + 5, q.y + 5, 5, '#ef4444'); ctx.fillStyle = '#fff'; ctx.font = '900 5px monospace'; ctx.textAlign = 'center'; ctx.fillText('ERR', q.x + 5, q.y + 7); });
		}
		s.beans.forEach((b) => { if (b.k === 'bean') { ctx.beginPath(); ctx.ellipse(b.x + 3, b.y + 3, 3.5, 2.6, s.t / 4, 0, 7); ctx.fillStyle = '#78350f'; ctx.fill(); ctx.strokeStyle = OL; ctx.lineWidth = 1; ctx.stroke(); } else text('</>', b.x + 6, b.y + 7, 9, '#22d3ee'); });
		s.parts.forEach((q) => {
			if (q.k === 'brick') rr(q.x, q.y, 6, 6, 1, q.c || '#c2410c');
			if (q.k === 'coinjump') drawCoin(q.x - 4, q.y);
			if (q.k === 'spark') circ(q.x, q.y, 4, '#fde047', null);
			if (q.k === 'puff') circ(q.x, q.y, 6 * (1 - q.life), 'rgba(255,255,255,.7)', null);
			if (q.k === 'fw') circ(q.x, q.y, 2, q.c, null);
			if (q.k === 'flash') { const k = q.life / 0.18; ctx.save(); ctx.translate(q.x, q.y); ctx.rotate(s.t / 3); ctx.fillStyle = q.c; for (let i = 0; i < 6; i++) { ctx.rotate(Math.PI / 3); ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(1.4, 6 * k + 2); ctx.lineTo(-1.4, 6 * k + 2); ctx.fill(); } ctx.beginPath(); ctx.arc(0, 0, 2.5 * k + 1, 0, 7); ctx.fillStyle = '#fff'; ctx.fill(); ctx.restore(); }
			if (q.k === 'dust') circ(q.x, q.y, 2.5 + (0.35 - q.life) * 8, `rgba(226,232,240,${q.life})`, null);
		});
		if (s.lv.side === 'rain') s.lv.puddles.forEach(([a, b]) => { ctx.beginPath(); ctx.ellipse((a + b + 1) * T / 2, GY * T + 1, (b - a + 1) * T / 2, 3.5, 0, 0, 7); ctx.fillStyle = 'rgba(96,165,250,.55)'; ctx.fill(); ctx.strokeStyle = 'rgba(219,234,254,.7)'; ctx.lineWidth = 1; ctx.stroke(); });
		(s.bombs || []).forEach((b) => {
			const k = 1 - Math.max(0, b.t) / 1.25, gy = GY * T, pulse = 1 + Math.sin(s.t / 2) * 0.12;
			ctx.save(); ctx.globalAlpha = 0.35 + k * 0.5; ctx.beginPath(); ctx.ellipse(b.x, gy - 1, 28 * pulse, 6 * pulse, 0, 0, 7); ctx.fillStyle = 'rgba(239,68,68,.35)'; ctx.fill(); ctx.lineWidth = 2; ctx.strokeStyle = '#ef4444'; ctx.setLineDash([4, 3]); ctx.stroke(); ctx.setLineDash([]); ctx.restore();
			text('!', b.x, gy - 10, 10, '#fecaca');
			if (b.t < 0.45) { const by = CAM_TOP + (gy - 14 - CAM_TOP) * (1 - b.t / 0.45); ctx.save(); ctx.translate(b.x, by); rr(-3, -9, 6, 12, 3, '#374151', OL, 1); R(-4, -11, 8, 2, '#111827'); ctx.restore(); }
		});
		drawPlayer();
		drawHacker();
		drawQuip();
		s.pops.forEach((q) => text(q.t, q.x + 6, q.y, 9, '#fff'));
		ctx.restore();
		if (s.lv.side === 'rain') {
			ctx.strokeStyle = 'rgba(191,219,254,.5)'; ctx.lineWidth = 1;
			for (let i = 0; i < 70; i++) { const sd = (i * 7919) % 97 / 97, x = (i * 53 + s.t * 2) % (view.vw + 20) - 10, y = (sd * VIEW_H + s.t * 7) % (VIEW_H + 20) - 10; ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x - 3, y + 10); ctx.stroke(); }
			if (Math.floor(s.t) % 420 < 6) { ctx.fillStyle = 'rgba(255,255,255,.35)'; ctx.fillRect(0, 0, view.vw, VIEW_H); }
		}
		hud();
		if (s.task) { const tw = 150, tx2 = view.vw - tw - 4, ty2 = 26, low = s.task.t < 4; panel(tx2, ty2, tw, 16, low ? '#ef4444' : '#60a5fa'); text(`📌 ${s.task.name}`, tx2 + tw - 6, ty2 + 11.5, 7, '#fff', 'right'); text(`⏱ ${fa(Math.ceil(s.task.t))}`, tx2 + 18, ty2 + 11.5, 8, low && Math.floor(s.t / 8) % 2 ? '#fca5a5' : '#fde68a'); }
		if (s.shay) {
			// slides in from the bottom-right corner, talks, slides out
			const u = s.shay.t, inK = Math.min(1, u / 0.25), outK = Math.max(0, (u - 2) / 0.3), k = inK * (1 - outK), ease = 1 - Math.pow(1 - k, 3);
			const sx = view.vw - 36, sy = VIEW_H + 56 - ease * 62;
			ctx.save();
			drawShayan(ctx, sx, sy, 1.05, s.t / 60, u > 0.3 && u < 1.9 ? Math.abs(Math.sin(u * 14)) : 0, s.shay.wink && u > 1.2 && u < 1.7);
			if (u > 0.25 && u < 2) {
				ctx.font = '900 9px yekanBakh, Tahoma';
				const tw = ctx.measureText(s.shay.line).width + 16, bx = sx - 34 - tw, by = sy - 62;
				rr(bx, by, tw, 18, 9, '#fff', OL, 1.6);
				ctx.beginPath(); ctx.moveTo(bx + tw - 2, by + 8); ctx.lineTo(bx + tw + 9, by + 13); ctx.lineTo(bx + tw - 3, by + 14); ctx.fillStyle = '#fff'; ctx.fill();
				ctx.fillStyle = OL; ctx.textAlign = 'center'; ctx.fillText(s.shay.line, bx + tw / 2, by + 12.5);
				text('شایان', sx, sy - 4, 7, '#fde68a');
			}
			ctx.restore();
		}
		const b = s.boss;
		if (b && b.active && b.hp > 0) {
			const w = Math.min(180, view.vw - 40), x = view.vw / 2 - w / 2;
			rr(x, 26, w, 10, 4, '#1f2937'); rr(x + 1, 27, (w - 2) * (b.hp / b.max), 8, 3, '#ef4444', null);
			text(WORLDS[s.world].bossName, view.vw / 2, 48, 9, '#fecaca');
		}
		if (s.banner && s.banner.life > 0) {
			const by = b && b.active && b.hp > 0 ? 54 : 30;
			ctx.globalAlpha = Math.min(1, s.banner.life * 1.5);
			rr(view.vw / 2 - 110, by, 220, 26, 8, 'rgba(2,6,23,.75)', '#fbbf24', 1.5);
			text(s.banner.t, view.vw / 2, by + 18, 12, '#fde68a');
			ctx.globalAlpha = 1;
		}
	};

	const blank = () => ({ score: 0, coins: 0, lives: 5, big: false, form: null, formsSeen: new Set(), shield: 0, kills: 0, t: 0, flawless: true, over: false, world: 0, stageN: 0, lv: buildLevel(0), cam: 0, camMin: 0, ents: [], beans: [], shots: [], parts: [], pops: [], crumble: {}, checkpoint: false, timeLeft: 300, goalOn: false, boss: null, p: { x: 70, y: GY * T - H_BIG, w: P_W, h: H_BIG, vx: 0, face: 1, anim: 0, ground: true, inv: 0 } });
	const attract = () => { s = { ...blank(), big: true, form: 'coffee', mode: 'attract' }; draw(); s = null; };

	const tick = (now) => {
		if (!s || s.over || s.mode !== 'play') return;
		const f = Math.min(3, ((now - last) / 1000) * 60);
		last = now;
		update(f);
		if (s && !s.over) { draw(); raf = requestAnimationFrame(tick); }
	};
	const loop = () => { cancelAnimationFrame(raf); last = performance.now(); raf = requestAnimationFrame(tick); };

	const KEYMAP = { ArrowLeft: 'left', KeyA: 'left', ArrowRight: 'right', KeyD: 'right', ArrowUp: 'jump', KeyW: 'jump', Space: 'jump', KeyZ: 'jump', KeyX: 'fire', KeyJ: 'fire', ShiftLeft: 'run', ShiftRight: 'run', ArrowDown: 'down', KeyS: 'down' };
	const press = (k, down) => { if (k === 'jump' && down && !keys.jump) jumpPressed = 0.12; if (k === 'fire' && down && !keys.fire) firePressed = true; keys[k] = down; };
	L.on(window, 'keydown', (e) => { const k = KEYMAP[e.code]; if (!k || !api.running || !s || s.mode !== 'play') return; e.preventDefault(); press(k, true); });
	L.on(window, 'keyup', (e) => { const k = KEYMAP[e.code]; if (k) press(k, false); });
	L.on(pad, 'pointerdown', (e) => {
		const b = e.target.closest('[data-k]');
		if (!b) return;
		e.preventDefault();
		if (b.setPointerCapture) b.setPointerCapture(e.pointerId);
		b.classList.add('is-on');
		press(b.dataset.k, true);
		if (b.dataset.k === 'left' || b.dataset.k === 'right') keys.run = true;
	});
	const padUp = (e) => { const b = e.target.closest('[data-k]'); if (!b) return; b.classList.remove('is-on'); press(b.dataset.k, false); if (!keys.left && !keys.right) keys.run = false; };
	L.on(pad, 'pointerup', padUp);
	L.on(pad, 'pointercancel', padUp);
	L.on(pad, 'contextmenu', (e) => e.preventDefault());

	attract();

	const inst = {
		start() {
			cancelAnimationFrame(raf); music.stop(); comic && comic(); comic = null;
			s = { ...blank(), mode: 'map' }; api.setScore(0); draw(); pad.hidden = true;
			let seen = false;
			try { seen = localStorage.getItem('kmpr_kr_intro') === '1'; localStorage.setItem('kmpr_kr_intro', '1'); } catch (e) { /* private mode */ }
			if (seen) { mapEl.hidden = false; renderMap(); } else { mapEl.hidden = true; comic = showComic(stage, INTRO, { worlds: WORLDS, sound: (n) => api.sfx(n), done: () => { comic = null; if (s) { mapEl.hidden = false; renderMap(); } } }); }
		},
		pause() { cancelAnimationFrame(raf); music.stop(); Object.keys(keys).forEach((k) => (keys[k] = false)); },
		resume() { if (s && !s.over && s.mode === 'play') { loop(); music.play(songFor(), s.shield > 0 ? 1.25 : 1); } },
		destroy() { cancelAnimationFrame(raf); music.stop(); comic && comic(); L.off(); cv.destroy(); pad.remove(); mapEl.remove(); },
	};
	current = inst;
	stage.kmprDebug = { get s() { return s; }, playStage, cameo: (k) => cameo(k), setForm, enterRoom, damageBoss, buildLevel };
	return inst;
}

export function destroy() { current && current.destroy(); current = null; }
