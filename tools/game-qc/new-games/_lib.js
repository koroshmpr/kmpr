/**
 * Shared helpers for the per-game QC tests of the new games (mench, server-ping, bits).
 * Run: NODE_PATH=../../korosh-qc/node_modules node <slug>.test.js   (BASE env overrides the site URL)
 */
const pp = require('puppeteer-core');
const path = require('path');

const BASE = process.env.BASE || 'http://localhost:10039';
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const SIZES = [
	['p360', 360, 740, true],
	['p390', 390, 844, true],
	['l844', 844, 390, true],
	['t768', 768, 1024, true],
	['d1280', 1280, 800, false],
];
const OUT = path.join(__dirname, 'out');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function launch() {
	return pp.launch({ executablePath: CHROME, headless: 'new', protocolTimeout: 30000, args: ['--no-sandbox'] });
}

/** Open a game page at a size with a ready-made player profile (no nickname dialog). */
async function open(browser, slug, [name, w, h, touch]) {
	const page = await browser.newPage();
	const errors = [];
	page.on('pageerror', (e) => errors.push('pageerror: ' + e.message));
	page.on('console', (m) => m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text()) && errors.push('console: ' + m.text()));
	await page.setViewport({ width: w, height: h, isMobile: touch, hasTouch: touch, deviceScaleFactor: touch ? 2 : 1 });
	await page.evaluateOnNewDocument(() => {
		try {
			localStorage.setItem('kmpr_arcade_v1', JSON.stringify({ id: '11111111-2222-4333-8444-555555555555', nickname: 'QC', hash: '', sound: false, games: {}, avatar: { style: 'bot', hue: 200 } }));
			Object.keys(localStorage).filter((k) => k.startsWith('kmpr_snap')).forEach((k) => localStorage.removeItem(k));
		} catch (e) {}
	});
	await page.goto(`${BASE}/games/${slug}/`, { waitUntil: 'domcontentloaded', timeout: 120000 });
	await page.waitForSelector('[data-act="play"]', { timeout: 60000 });
	await page.waitForNetworkIdle({ idleTime: 400, timeout: 30000 }).catch(() => {});
	await sleep(400);
	// close a first-visit dialog if one still shows up
	await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
	return { page, errors, name, w, h, touch };
}

/** Tap (touch) or click (mouse) the centre of an element or at page coords. */
async function tapAt(ctx, x, y) {
	if (ctx.touch) await ctx.page.touchscreen.tap(x, y);
	else await ctx.page.mouse.click(x, y);
}
async function tapSel(ctx, sel) {
	await ctx.page.$eval(sel, (el) => {
		const r = el.getBoundingClientRect();
		if (r.top < 0 || r.bottom > innerHeight) el.scrollIntoView({ block: 'center', behavior: 'instant' });
	});
	await sleep(120);
	const box = await ctx.page.$eval(sel, (el) => {
		const r = el.getBoundingClientRect();
		return { x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height };
	});
	await tapAt(ctx, box.x, box.y);
	return box;
}
async function play(ctx) {
	await tapSel(ctx, '[data-act="play"]');
	try {
		await ctx.page.waitForFunction(() => { const d = document.querySelector('[data-stage]').kmprDebug; return d && document.querySelector('[data-overlay]').hidden; }, { timeout: 8000 });
	} catch (e) {
		// a missed tap (e.g. layout still settling): try once more
		await ctx.page.screenshot({ path: path.join(OUT, `_play-retry-${ctx.name}.png`) });
		if (await ctx.page.$('[data-overlay]:not([hidden]) [data-act="play"]')) await tapSel(ctx, '[data-overlay]:not([hidden]) [data-act="play"]');
		await ctx.page.waitForFunction(() => document.querySelector('[data-stage]').kmprDebug, { timeout: 8000 });
	}
	await sleep(500);
}
const engine = (page, expr) => page.evaluate(new Function(`const d = document.querySelector('[data-stage]').kmprDebug; return (${expr});`));

/** Layout problems: horizontal scroll, zoom, stage not inside viewport, small buttons in `scope`. */
async function layout(ctx, scope = '[data-stage]') {
	return ctx.page.evaluate((scope) => {
		const out = [];
		if (document.documentElement.scrollWidth > innerWidth + 1) out.push(`h-scroll ${document.documentElement.scrollWidth}>${innerWidth}`);
		if (window.visualViewport && Math.abs(visualViewport.scale - 1) > 0.001) out.push(`zoom ${visualViewport.scale}`);
		const st = document.querySelector('[data-stage]').getBoundingClientRect();
		if (st.right > innerWidth + 1 || st.left < -1) out.push('stage wider than viewport');
		if (st.bottom > innerHeight + 1) out.push(`stage taller than viewport ${Math.round(st.bottom)}>${innerHeight}`);
		document.querySelectorAll(`${scope} button`).forEach((b) => {
			const r = b.getBoundingClientRect();
			if (!r.width || getComputedStyle(b).visibility === 'hidden') return;
			if (r.height < 40 || r.width < 40) out.push(`small btn "${(b.getAttribute('aria-label') || b.textContent).trim().slice(0, 16)}" ${Math.round(r.width)}x${Math.round(r.height)}`);
			if (r.right > st.right + 1 || r.left < st.left - 1 || r.bottom > st.bottom + 1) out.push(`btn outside stage "${b.textContent.trim().slice(0, 16)}"`);
		});
		return [...new Set(out)].slice(0, 8);
	}, scope);
}
/** Several fast taps at one point, then check the page did not zoom or scroll sideways. */
async function rapidTaps(ctx, x, y, n = 6, gap = 25) {
	for (let i = 0; i < n; i++) {
		await tapAt(ctx, x, y);
		await sleep(gap);
	}
	await sleep(350);
	return ctx.page.evaluate(() => ({ scale: window.visualViewport ? visualViewport.scale : 1, sx: scrollX, hs: document.documentElement.scrollWidth > innerWidth + 1 }));
}
async function shot(ctx, slug, label) {
	const f = path.join(OUT, `${slug}-${ctx.name}-${label}.png`);
	await ctx.page.screenshot({ path: f });
	return f;
}
const AGAIN = '[data-overlay]:not([hidden]) [data-act="again"], [data-overlay]:not([hidden]) [data-act="play"]';
const overShown = (page) => page.evaluate((s) => !!document.querySelector(s) && !!document.querySelector('[data-overlay]:not([hidden]) [data-act="share"]'), AGAIN);
/** Press the game-over «دوباره» button (same mode for match games, new run for arcade games). */
const again = (page) => page.evaluate((s) => document.querySelector(s).click(), AGAIN);
async function pauseResume(ctx) {
	const r = await ctx.page.evaluate(() => {
		const b = document.querySelector('[data-act="pause"]');
		if (!b || b.disabled) return 'no pause btn';
		b.click();
		return 'ok';
	});
	if (r !== 'ok') return r;
	await sleep(300);
	const paused = await ctx.page.evaluate(() => !!document.querySelector('[data-overlay]:not([hidden]) [data-act="resume"]'));
	await ctx.page.evaluate(() => document.querySelector('[data-overlay]:not([hidden]) [data-act="resume"]')?.click());
	await sleep(300);
	return paused ? 'ok' : 'pause screen missing';
}

/** Tiny assertion collector. */
function reporter(slug) {
	const lines = [];
	let fails = 0;
	return {
		ok(cond, label, extra = '') {
			if (!cond) fails++;
			lines.push(`${cond ? 'PASS' : 'FAIL'} ${label}${extra ? ' — ' + extra : ''}`);
		},
		note(t) {
			lines.push('     ' + t);
		},
		done() {
			console.log(`\n=== ${slug}: ${fails ? fails + ' FAILED' : 'all passed'} ===\n` + lines.join('\n'));
			process.exitCode = fails ? 1 : 0;
		},
	};
}

module.exports = { BASE, SIZES, OUT, sleep, launch, open, tapAt, tapSel, play, engine, layout, rapidTaps, shot, overShown, again, pauseResume, reporter };
