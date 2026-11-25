#!/usr/bin/env node
/**
 * Game Center QC — plays every game from catalog.js at several viewport sizes in headless Chrome.
 *
 *   node tools/game-qc/run-all.js [--only=snake,xo] [--sizes=390,1280] [--jobs=4] [--base=http://localhost:10039] [--no-shots] [--quick]
 *
 * Exit code: 0 = all pass, 1 = at least one FAIL, 2 = runner error.
 * Screenshots: tools/game-qc/out/shots/<slug>-<size>-<step>.png, raw results: out/results.json.
 */
const fs = require('fs');
const path = require('path');
let pp;
try {
	pp = require('puppeteer-core');
} catch (e) {
	pp = require(path.join(__dirname, '../korosh-qc/node_modules/puppeteer-core'));
}
const ADAPTERS = require('./adapters');

const ARGS = Object.fromEntries(
	process.argv.slice(2).map((a) => {
		const [k, v] = a.replace(/^--/, '').split('=');
		return [k, v === undefined ? true : v];
	})
);
const BASE = (ARGS.base || 'http://localhost:10039').replace(/\/$/, '');
const JOBS = Number(ARGS.jobs || 4);
const SHOTS = !ARGS['no-shots'];
const QUICK = !!ARGS.quick;
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const THEME = path.resolve(__dirname, '../../themes/kmpr');
const OUT = path.join(__dirname, 'out');
const SHOT_DIR = path.join(OUT, 'shots');

const SIZES = [
	{ id: '360', w: 360, h: 740, touch: true },
	{ id: '390', w: 390, h: 844, touch: true, over: true },
	{ id: '844L', w: 844, h: 390, touch: true },
	{ id: '768', w: 768, h: 1024, touch: true },
	{ id: '1280', w: 1280, h: 800, touch: false, over: true },
].filter((s) => !ARGS.sizes || String(ARGS.sizes).split(',').includes(s.id));

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const rnd = (a, b) => a + Math.random() * (b - a);
const pick = (arr) => arr[Math.floor(Math.random() * arr.length)];

/* ---------- discovery: catalog.js → engines, WP REST → game URLs ---------- */
function readCatalog() {
	const src = fs.readFileSync(path.join(THEME, 'javascript/games/catalog.js'), 'utf8');
	const loaders = src.slice(src.indexOf('ENGINE_LOADERS'));
	const engines = [...loaders.matchAll(/^\s*'?([a-z0-9-]+)'?\s*:\s*\(\)\s*=>\s*import\(/gm)].map((m) => m[1]);
	const cat = src.slice(src.indexOf('export const CATALOG'), src.indexOf('ENGINE_LOADERS'));
	const meta = {};
	// top-level entries are indented by exactly one tab
	const parts = cat.split(/\n\t(?=['a-z0-9-]+'?\s*:\s*\{)/);
	for (const p of parts) {
		const m = p.match(/^'?([a-z0-9-]+)'?\s*:\s*\{/);
		if (!m) continue;
		meta[m[1]] = { kind: (p.match(/kind:\s*'(\w+)'/) || [])[1] || 'arcade', landscape: /landscape:\s*true/.test(p) };
	}
	return engines.map((e) => ({ engine: e, ...(meta[e] || { kind: 'arcade', landscape: false }) }));
}

async function gameUrls() {
	const map = {};
	for (let page = 1; page < 5; page++) {
		const r = await fetch(`${BASE}/wp-json/wp/v2/game?per_page=100&page=${page}&_fields=link`);
		if (!r.ok) break;
		const list = await r.json();
		if (!list.length) break;
		await Promise.all(
			list.map(async ({ link }) => {
				const html = await (await fetch(link)).text();
				const m = html.match(/data-game-shell[^>]*data-engine="([^"]+)"/);
				if (m && !map[m[1]]) map[m[1]] = link;
			})
		);
		if (list.length < 100) break;
	}
	return map;
}

/* ---------- in-page probes ---------- */
// Layout: horizontal scroll, stage inside viewport, interactive controls visible + big enough.
function pageLayout(minTarget) {
	const vw = innerWidth;
	const vh = innerHeight;
	const out = { issues: [], warn: [] };
	const root = document.querySelector('[data-game-shell]');
	const stage = root && root.querySelector('[data-stage]');
	const de = document.documentElement;
	if (de.scrollWidth > vw + 1) out.issues.push(`HS page scrollWidth ${de.scrollWidth}>${vw}`);
	if (!stage) return out;
	const frame = root.querySelector('[data-frame]');
	const fr = frame.getBoundingClientRect();
	const sr = stage.getBoundingClientRect();
	out.stage = [Math.round(sr.left), Math.round(sr.top), Math.round(sr.width), Math.round(sr.height)];
	if (sr.width < 50 || sr.height < 50) out.issues.push(`FIT stage tiny ${Math.round(sr.width)}x${Math.round(sr.height)}`);
	// in focus mode the frame must fill the screen; outside it must not overflow horizontally
	if (sr.left < -2 || sr.right > vw + 2) out.issues.push(`FIT stage x ${Math.round(sr.left)}..${Math.round(sr.right)} vw ${vw}`);
	if (de.classList.contains('game-focus') && (sr.top < -2 || sr.bottom > vh + 2)) out.issues.push(`FIT stage y ${Math.round(sr.top)}..${Math.round(sr.bottom)} vh ${vh}`);
	if (stage.scrollWidth > stage.clientWidth + 2) out.warn.push(`stage inner h-overflow ${stage.scrollWidth}>${stage.clientWidth}`);
	const vis = (el) => {
		const cs = getComputedStyle(el);
		if (cs.visibility === 'hidden' || cs.display === 'none' || Number(cs.opacity) === 0) return false;
		const r = el.getBoundingClientRect();
		return r.width > 0 && r.height > 0;
	};
	const scroller = (el) => {
		for (let p = el.parentElement; p && p !== frame; p = p.parentElement) {
			const cs = getComputedStyle(p);
			if (/(auto|scroll)/.test(cs.overflowY + cs.overflowX) && (p.scrollHeight > p.clientHeight + 1 || p.scrollWidth > p.clientWidth + 1)) return p;
		}
		return null;
	};
	const label = (el) => (el.getAttribute('aria-label') || el.textContent || el.className || el.tagName).toString().trim().replace(/\s+/g, ' ').slice(0, 18);
	const nodes = frame.querySelectorAll('button, a[href], input:not([type=hidden]), select, textarea, [role=button], [data-qc-target]');
	const small = [];
	const off = [];
	for (const el of nodes) {
		if (!vis(el) || el.closest('[hidden],[inert]') || el.classList.contains('game-hp')) continue;
		const r = el.getBoundingClientRect();
		const sc = scroller(el);
		// a control inside a scrolling list may legitimately be off-screen
		if (!sc) {
			const clipR = de.classList.contains('game-focus') || frame.matches(':fullscreen, .is-pseudo-fs') ? { left: 0, top: 0, right: vw, bottom: vh } : { left: 0, top: -1e9, right: vw, bottom: 1e9 };
			const box = el.closest('[data-stage], [data-overlay]') ? sr : fr;
			const L = Math.max(clipR.left, box.left) - 2;
			const T = Math.max(clipR.top, box.top) - 2;
			const R = Math.min(clipR.right, box.right) + 2;
			const B = Math.min(clipR.bottom, box.bottom) + 2;
			if (r.right < L + 8 || r.left > R - 8 || r.bottom < T + 8 || r.top > B - 8) off.push(`${label(el)}@${Math.round(r.left)},${Math.round(r.top)}`);
			else if (r.left < L - 4 || r.right > R + 4 || r.top < T - 4 || r.bottom > B + 4) off.push(`cut:${label(el)}`);
		}
		if (el.matches('input, select, textarea')) {
			const fz = parseFloat(getComputedStyle(el).fontSize);
			if (fz < 16 && el.type !== 'range' && getComputedStyle(el).opacity !== '0') out.issues.push(`IOSZOOM input font ${fz}px (${label(el)})`);
			else if (fz < 16 && el.type !== 'range') out.issues.push(`IOSZOOM hidden input font ${fz}px`);
		}
		// layout size (ignores pop/scale animations and the CSS rotation of forced-landscape games)
		const ow = el.offsetWidth || r.width;
		const oh = el.offsetHeight || r.height;
		if (Math.min(ow, oh) < minTarget - 0.5 && !el.closest('[data-qc-small-ok]')) small.push(`${label(el)} ${Math.round(ow)}x${Math.round(oh)}`);
	}
	if (off.length) out.issues.push(`FIT controls off-screen: ${[...new Set(off)].slice(0, 4).join(' | ')}`);
	if (small.length) out.small = [...new Set(small)];
	return out;
}

// Effective touch-action at a point: the intersection over the element and its ancestors.
function touchActionAt(x, y) {
	let el = document.elementFromPoint(x, y);
	const tag = el ? el.tagName.toLowerCase() + (el.className && typeof el.className === 'string' ? '.' + el.className.split(' ')[0] : '') : 'none';
	let ok = false;
	for (; el; el = el.parentElement) {
		const ta = getComputedStyle(el).touchAction;
		if (ta === 'none' || ta === 'manipulation' || /^pan-/.test(ta)) {
			ok = true;
			break;
		}
	}
	return { ok, tag };
}

const state = (page) => page.evaluate(() => {
	const r = document.querySelector('[data-game-shell]');
	const s = r ? r.dataset.state : 'none';
	const result = !!document.querySelector('[data-mx="r-primary"]');
	const score = (document.querySelector('[data-hud="score"]') || {}).textContent || '';
	return { s, result, score };
});

async function center(page, sel) {
	return page.evaluate((sel) => {
		const els = [...document.querySelectorAll(sel)].filter((e) => {
			const r = e.getBoundingClientRect();
			return r.width > 0 && r.height > 0 && !e.disabled && !e.closest('[hidden],[inert]');
		});
		if (!els.length) return null;
		let r = els[0].getBoundingClientRect();
		// a real user scrolls to a control below the fold first
		if (r.bottom > innerHeight || r.top < 0) {
			els[0].scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'instant' });
			r = els[0].getBoundingClientRect();
		}
		return { x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height };
	}, sel);
}

/* ---------- one game × one size ---------- */
async function runCombo(browser, game, size) {
	const ad = { ...ADAPTERS.default, ...(ADAPTERS[game.engine] || {}) };
	const res = { engine: game.engine, size: size.id, fail: [], warn: [], info: [] };
	const ctx = await browser.createBrowserContext();
	const page = await ctx.newPage();
	const errs = [];
	page.on('pageerror', (e) => errs.push('pageerror: ' + String(e.message).split('\n')[0]));
	page.on('console', (m) => {
		if (m.type() !== 'error') return;
		const t = m.text();
		if (/Failed to load resource|net::ERR|favicon/i.test(t)) return res.warn.push('net: ' + t.slice(0, 80));
		errs.push('console: ' + t.slice(0, 140));
	});
	page.on('dialog', (d) => d.dismiss().catch(() => {}));
	// 4xx/5xx from our own REST API (429 = local rate limit from parallel runs → ignored)
	page.on('response', (r) => {
		const u = r.url();
		if (r.status() >= 400 && r.status() !== 429 && u.startsWith(BASE) && /wp-json/.test(u)) res.warn.push(`http ${r.status()} ${u.replace(BASE, '').split('?')[0]}`);
	});
	const tap = async (x, y) => (size.touch ? page.touchscreen.tap(x, y) : page.mouse.click(x, y));
	const tapSel = async (sel) => {
		const c = await center(page, sel);
		if (!c) return false;
		await tap(c.x, c.y);
		return true;
	};
	const shot = async (step) => {
		if (!SHOTS) return;
		try {
			await page.screenshot({ path: path.join(SHOT_DIR, `${game.engine}-${size.id}-${step}.png`) });
		} catch (e) {
			/* ignore */
		}
	};
	const H = { page, size, tap, tapSel, sleep, rnd, pick, center, state: () => state(page), res };
	try {
		await page.setViewport({ width: size.w, height: size.h, isMobile: size.touch, hasTouch: size.touch, deviceScaleFactor: size.touch ? 2 : 1 });
		await page.evaluateOnNewDocument((seed) => {
			try {
				localStorage.setItem('kmpr_arcade_v1', JSON.stringify({ id: '0c0c0c0c-1111-4111-8111-' + Math.random().toString(16).slice(2, 14).padEnd(12, '0'), nickname: 'QC Bot', hash: '', sound: false, games: {} }));
				for (const [k, v] of Object.entries(seed || {})) localStorage.setItem(k, v);
			} catch (e) {
				/* ignore */
			}
			navigator.share = undefined;
			// random taps must not leave the game page (e.g. «بازی‌های دیگر», header links)
			document.addEventListener('click', (e) => {
				const a = e.target.closest && e.target.closest('a[href]');
				if (a && !a.getAttribute('href').startsWith('#')) e.preventDefault();
			}, true);
		}, ad.seed || {});
		await page.goto(game.url, { waitUntil: 'domcontentloaded', timeout: 30000 });
		await page.waitForSelector('[data-game-shell][data-state="idle"], [data-game-shell][data-state="error"]', { timeout: 45000 });
		if ((await state(page)).s === 'error') throw new Error('RUNNER engine chunk failed to load (build in progress?)');
		await sleep(500);
		const pre = await page.evaluate(pageLayout, 40);
		pre.issues.filter((i) => i.startsWith('HS')).forEach((i) => res.fail.push('pre ' + i));

		// --- start
		if (!(await tapSel('[data-overlay] [data-act="play"]'))) throw new Error('START no play button');
		await sleep(900);
		if (game.kind === 'match') {
			if (ad.matchStart) await ad.matchStart(H);
			else {
				const mode = (await center(page, '[data-mx-card="ai"]')) ? 'ai' : 'local';
				await tapSel(`[data-mx-card="${mode}"]`);
				await sleep(450);
				if (!(await tapSel(`[data-mx="${mode}"]`))) res.fail.push('START match mode button not found');
				await sleep(700);
			}
		}
		if (ad.afterStart) await ad.afterStart(H);
		let st = await state(page);
		if (st.s !== 'running') res.fail.push(`START state=${st.s}`);

		// --- layout while playing
		await sleep(300);
		const lay = await page.evaluate(pageLayout, 40);
		res.fail.push(...lay.issues);
		res.warn.push(...lay.warn);
		if (lay.small) (size.touch ? res.fail : res.warn).push(`SMALL <40px: ${lay.small.slice(0, 4).join(' | ')}${lay.small.length > 4 ? ` (+${lay.small.length - 4})` : ''}`);
		res.info.push(`stage ${lay.stage ? lay.stage.join(',') : '-'}`);
		await shot('play');

		// --- touch-action + zoom
		if (size.touch && lay.stage) {
			const [sx, sy, sw, sh] = lay.stage;
			const cx = sx + sw / 2;
			const cy = sy + sh / 2;
			const ta = await page.evaluate(touchActionAt, cx, cy);
			if (!ta.ok) res.fail.push(`TA no touch-action at stage centre (${ta.tag})`);
			const bar = await center(page, '.game-shell__bar');
			const barOnTop = bar && (await page.evaluate((x, y) => !!(document.elementFromPoint(x, y) || document.body).closest('.game-shell__bar'), bar.x, bar.y));
			if (barOnTop) {
				const tb = await page.evaluate(touchActionAt, bar.x, bar.y);
				if (!tb.ok) res.fail.push(`TA no touch-action on HUD bar (${tb.tag})`);
			}
		}

		// --- play with real inputs
		const before = await state(page);
		const img0 = lay.stage ? await page.screenshot({ clip: { x: Math.max(0, lay.stage[0]), y: Math.max(0, lay.stage[1]), width: Math.max(1, Math.min(lay.stage[2], size.w)), height: Math.max(1, Math.min(lay.stage[3], size.h - Math.max(0, lay.stage[1]))) } }).catch(() => null) : null;
		await ad.play(H, QUICK ? 3000 : ad.playMs);
		st = await state(page);
		const img1 = lay.stage ? await page.screenshot({ clip: { x: Math.max(0, lay.stage[0]), y: Math.max(0, lay.stage[1]), width: Math.max(1, Math.min(lay.stage[2], size.w)), height: Math.max(1, Math.min(lay.stage[3], size.h - Math.max(0, lay.stage[1]))) } }).catch(() => null) : null;
		const changed = st.score !== before.score || st.s !== before.s || st.result || (img0 && img1 && !Buffer.from(img0).equals(Buffer.from(img1)));
		if (before.s === 'over' || before.result) res.warn.push('game ended before the input phase');
		else if (!changed) res.fail.push('STATIC nothing changed after inputs');
		res.info.push(`score ${before.score}→${st.score} ${st.s}${st.result ? ' result' : ''}`);
		await shot('mid');

		// --- zoom: rapid taps, double-tap gesture and a pinch inside the game
		if (size.touch && lay.stage && (st.s === 'running' || st.s === 'paused')) {
			const [sx, sy, sw, sh] = lay.stage;
			const cx = Math.round(sx + sw / 2);
			const cy = Math.round(sy + sh * 0.55);
			for (let i = 0; i < 3; i++) {
				await page.touchscreen.tap(cx, cy);
				await sleep(45);
			}
			const cdp = await page.createCDPSession();
			await cdp.send('Input.synthesizeTapGesture', { x: cx, y: cy, tapCount: 2, gestureSourceType: 'touch' }).catch(() => {});
			await cdp.send('Input.synthesizePinchGesture', { x: cx, y: cy, scaleFactor: 2, gestureSourceType: 'touch' }).catch(() => {});
			const bar = await center(page, '.game-shell__bar');
			if (bar) await cdp.send('Input.synthesizePinchGesture', { x: Math.round(bar.x), y: Math.round(bar.y), scaleFactor: 2, gestureSourceType: 'touch' }).catch(() => {});
			await sleep(400);
			const scale = await page.evaluate(() => (window.visualViewport ? visualViewport.scale : 1));
			if (scale > 1.01) res.fail.push(`ZOOM scale ${scale.toFixed(2)} after taps/pinch`);
			await cdp.detach().catch(() => {});
			st = await state(page);
		}

		// --- pause / resume (arcade engines; match engines keep their own menu)
		if (st.s === 'running') {
			const pb = await center(page, '[data-act="pause"]:not([disabled])');
			if (pb) {
				await tap(pb.x, pb.y);
				await sleep(350);
				const p1 = await state(page);
				if (p1.s !== 'paused') res.fail.push(`PAUSE state=${p1.s}`);
				else {
					const pl = await page.evaluate(pageLayout, 40);
					pl.issues.filter((i) => i.startsWith('FIT')).forEach((i) => res.fail.push('pause ' + i));
					await tapSel('[data-overlay] [data-act="resume"]');
					await sleep(350);
					const p2 = await state(page);
					if (p2.s !== 'running' && p2.s !== 'over') res.fail.push(`RESUME state=${p2.s}`);
				}
			} else if (game.kind !== 'match') res.warn.push('pause button unavailable');
			else res.info.push('pause n/a (match)');
		}

		// --- reach game-over (or a match result) on selected sizes
		if (size.over && ad.over !== false && !QUICK) {
			st = await state(page);
			const t0 = Date.now();
			while (Date.now() - t0 < ad.overMs && st.s !== 'over' && !st.result) {
				await ad.toOver(H, 1500);
				st = await state(page);
			}
			if (st.s === 'over' || st.result) {
				res.info.push(`over in ${((Date.now() - t0) / 1000).toFixed(0)}s`);
				await sleep(700);
				const ol = await page.evaluate(pageLayout, 40);
				ol.issues.filter((i) => !i.startsWith('IOSZOOM hidden')).forEach((i) => res.fail.push('over ' + i));
				if (ol.small && size.touch) res.fail.push(`over SMALL: ${ol.small.slice(0, 3).join(' | ')}`);
				await shot('over');
				const again = st.result ? '[data-mx="r-primary"]' : '[data-overlay] [data-act="again"], [data-overlay] [data-act="play"]';
				const vis = await page.evaluate((sel) => {
					const e = document.querySelector(sel);
					if (!e) return false;
					const r = e.getBoundingClientRect();
					return r.right > 0 && r.bottom > 0 && r.left < innerWidth && r.top < innerHeight && r.width > 0;
				}, again);
				if (!vis) res.fail.push('OVER «again» button not on screen without scrolling');
				if (!(await tapSel(again))) res.fail.push('OVER no «again» button');
				else {
					await sleep(900);
					if (ad.afterStart) await ad.afterStart(H);
					const a = await state(page);
					if (a.s !== 'running' || a.result) res.fail.push(`AGAIN state=${a.s}`);
				}
			} else if (ad.overRequired) res.fail.push(`OVER not reached in ${ad.overMs / 1000}s`);
			else res.warn.push(`over not reached in ${ad.overMs / 1000}s`);
		}
		await sleep(200);
	} catch (e) {
		res.fail.push('RUNNER ' + String(e.message).split('\n')[0]);
		await shot('error');
	}
	if (errs.length) res.fail.push(...[...new Set(errs)].slice(0, 4).map((e) => 'ERR ' + e));
	res.warn = [...new Set(res.warn)];
	await ctx.close().catch(() => {});
	return res;
}

/* ---------- main ---------- */
// puppeteer can throw from event handlers (e.g. a page swapped by navigation); keep the run going
process.on('uncaughtException', (e) => console.error('[runner] ' + String(e.message).split('\n')[0]));
process.on('unhandledRejection', (e) => console.error('[runner] ' + String(e && e.message).split('\n')[0]));
(async () => {
	fs.mkdirSync(SHOT_DIR, { recursive: true });
	const urls = await gameUrls();
	let games = readCatalog().map((g) => ({ ...g, url: urls[g.engine] }));
	if (ARGS.only) games = games.filter((g) => String(ARGS.only).split(',').includes(g.engine));
	const missing = games.filter((g) => !g.url);
	games = games.filter((g) => g.url);
	const browser = await pp.launch({ executablePath: CHROME, headless: 'new', protocolTimeout: 120000, args: ['--disable-features=Prerender2', '--autoplay-policy=no-user-gesture-required', '--disable-renderer-backgrounding', '--disable-background-timer-throttling', '--disable-backgrounding-occluded-windows'] });
	const queue = [];
	for (const g of games) for (const s of SIZES) queue.push([g, s]);
	const results = [];
	const total = queue.length;
	const worker = async () => {
		while (queue.length) {
			const [g, s] = queue.shift();
			let r = await runCombo(browser, g, s);
			// a slow local server can time out a load: retry runner-level failures once
			for (let k = 0; k < 2 && r.fail.some((f) => f.startsWith('RUNNER')); k++) {
				await sleep(8000);
				r = await runCombo(browser, g, s);
			}
			results.push(r);
			process.stderr.write(`[${results.length}/${total}] ${g.engine} ${s.id} ${r.fail.length ? 'FAIL  ' + r.fail.join(' ; ').slice(0, 300) : 'ok'}\n`);
		}
	};
	await Promise.all(Array.from({ length: JOBS }, worker));
	await browser.close();

	// --- report
	const pad = (s, n) => String(s).padEnd(n);
	const w = Math.max(16, ...games.map((g) => g.engine.length + 1));
	const lines = [pad('game', w) + SIZES.map((s) => pad(s.id, 7)).join('')];
	let fails = 0;
	for (const g of games) {
		lines.push(
			pad(g.engine, w) +
				SIZES.map((s) => {
					const r = results.find((x) => x.engine === g.engine && x.size === s.id);
					if (!r) return pad('-', 7);
					if (r.fail.length) fails++;
					return pad(r.fail.length ? 'FAIL' : r.warn.length ? 'ok*' : 'ok', 7);
				}).join('')
		);
	}
	for (const m of missing) lines.push(pad(m.engine, w) + 'NO POST (engine in catalog.js but no published game page)');
	console.log('\n' + lines.join('\n'));
	console.log('\nFailures:');
	for (const g of games)
		for (const s of SIZES) {
			const r = results.find((x) => x.engine === g.engine && x.size === s.id);
			if (r && r.fail.length) console.log(`  ${g.engine} @${s.id}: ${r.fail.join(' ; ')}`);
		}
	if (ARGS.verbose) {
		console.log('\nWarnings / info:');
		for (const r of results) console.log(`  ${r.engine} @${r.size}: ${[...r.warn, ...r.info].join(' ; ')}`);
	}
	fs.writeFileSync(path.join(OUT, 'results.json'), JSON.stringify({ at: new Date().toISOString(), base: BASE, sizes: SIZES, missing: missing.map((m) => m.engine), results }, null, 1));
	console.log(`\n${games.length} games × ${SIZES.length} sizes, ${fails} failing combos${missing.length ? `, ${missing.length} without a game page` : ''}. Details: tools/game-qc/out/results.json`);
	process.exit(fails ? 1 : 0);
})().catch((e) => {
	console.error(e);
	process.exit(2);
});
