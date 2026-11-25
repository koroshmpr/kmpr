/**
 * QC: «نقاش‌باشی» (online draw & guess) — three isolated browser contexts play a full 2-round game on the local site:
 * phone portrait (touch, host), phone landscape (touch), desktop (mouse).
 * Checks: room create + join by link, strokes reach the guessers, wrong guesses show in chat, a correct guess
 * (typed with Arabic letters / extra space) scores, the secret word never appears in a guesser's network
 * responses before they guess it, turns rotate, podium at the end, no page errors / overflow / zoom.
 * Also prints requests + bytes per minute per player.
 *
 * Run: NODE_PATH=../../korosh-qc/node_modules node naqqash.test.js
 */
const pp = require('puppeteer-core');
const path = require('path');
const fs = require('fs');

const BASE = process.env.BASE || 'http://localhost:10039';
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT = path.join(__dirname, 'out');
const SLUG = 'naqqash';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
fs.mkdirSync(OUT, { recursive: true });

const PLAYERS = [
	{ name: 'p390', w: 390, h: 844, touch: true, id: 'a1a1a1a1-1111-4111-8111-000000000001', nick: 'سارا' },
	{ name: 'l844', w: 844, h: 390, touch: true, id: 'b2b2b2b2-2222-4222-8222-000000000002', nick: 'رضا' },
	{ name: 'd1280', w: 1280, h: 800, touch: false, id: 'c3c3c3c3-3333-4333-8333-000000000003', nick: 'نگار' },
];

const lines = [];
let fails = 0;
const ok = (c, label, extra = '') => {
	if (!c) fails++;
	console.log((c ? 'PASS ' : 'FAIL ') + label + (extra ? ' — ' + extra : ''));
	lines.push(`${c ? 'PASS' : 'FAIL'} ${label}${extra ? ' — ' + extra : ''}`);
};
const note = (t) => (console.log('  ' + t), lines.push('     ' + t));

async function openPlayer(browser, P, url) {
	const context = await browser.createBrowserContext();
	const page = await context.newPage();
	const ctx = { ...P, page, errors: [], bodies: [] };
	page.on('pageerror', (e) => ctx.errors.push('pageerror: ' + e.message));
	page.on('console', (m) => m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text()) && ctx.errors.push('console: ' + m.text()));
	page.on('response', async (r) => {
		if (!r.url().includes('/games/draw')) return;
		try {
			ctx.bodies.push({ t: Date.now(), body: await r.text() });
		} catch (e) {
			/* ignore */
		}
	});
	await page.setViewport({ width: P.w, height: P.h, isMobile: P.touch, hasTouch: P.touch, deviceScaleFactor: P.touch ? 2 : 1 });
	await page.evaluateOnNewDocument((id, nick) => {
		try {
			localStorage.setItem('kmpr_arcade_v1', JSON.stringify({ id, nickname: nick, hash: '', sound: false, games: {}, avatar: { style: 'bot', hue: 200 } }));
			Object.keys(localStorage).filter((k) => k.startsWith('kmpr_snap')).forEach((k) => localStorage.removeItem(k));
		} catch (e) {}
	}, P.id, P.nick);
	await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 120000 });
	await page.waitForSelector('[data-stage]', { timeout: 60000 });
	await sleep(1500);
	await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
	return ctx;
}
const dbg = (ctx, expr) => ctx.page.evaluate(new Function(`const d = document.querySelector('[data-stage]').kmprDebug; return d ? (${expr}) : null;`));
const st = (ctx) => dbg(ctx, 'd.st');
async function tap(ctx, sel) {
	const b = await ctx.page.$eval(sel, (el) => {
		const r = el.getBoundingClientRect();
		return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
	});
	if (ctx.touch) await ctx.page.touchscreen.tap(b.x, b.y);
	else await ctx.page.mouse.click(b.x, b.y);
}
async function waitFor(ctx, expr, timeout = 20000) {
	try {
		await ctx.page.waitForFunction(new Function(`const d = document.querySelector('[data-stage]').kmprDebug; return !!(d && (${expr}));`), { timeout, polling: 200 });
	} catch (e) {
		const info = await ctx.page.evaluate(() => {
			const d = document.querySelector('[data-stage]').kmprDebug;
			return JSON.stringify({ room: d && d.room, ph: d && d.st && d.st.ph, pl: d && d.st && d.st.pl.map((p) => [p.n, p.on]), layer: (document.querySelector('[data-layer]:not([hidden])') || {}).innerText, chat: (document.querySelector('[data-chat]') || {}).innerText }).slice(0, 600);
		}).catch(() => 'n/a');
		await shot(ctx, 'timeout');
		console.log(`waitFor timeout [${ctx.name}] ${expr} → ${info} errors: ${ctx.errors.slice(0, 3).join(' | ')}`);
		throw e;
	}
}
async function layout(ctx) {
	return ctx.page.evaluate(() => {
		const out = [];
		if (document.documentElement.scrollWidth > innerWidth + 1) out.push(`h-scroll ${document.documentElement.scrollWidth}>${innerWidth}`);
		if (window.visualViewport && Math.abs(visualViewport.scale - 1) > 0.001) out.push(`zoom ${visualViewport.scale}`);
		const nq = document.querySelector('.nq');
		const st = nq.getBoundingClientRect();
		nq.querySelectorAll('button, input').forEach((b) => {
			const r = b.getBoundingClientRect();
			if (!r.width || b.closest('[hidden]')) return;
			if (b.tagName === 'BUTTON' && (r.height < 40 || r.width < 40)) out.push(`small btn "${(b.getAttribute('aria-label') || b.textContent).trim().slice(0, 16)}" ${Math.round(r.width)}x${Math.round(r.height)}`);
			if (r.right > st.right + 1 || r.left < st.left - 1 || r.bottom > st.bottom + 1) out.push(`outside "${(b.getAttribute('aria-label') || b.textContent || b.placeholder).trim().slice(0, 16)}"`);
			if (b.tagName === 'BUTTON' && b.scrollWidth > b.clientWidth + 2) out.push(`text overflow "${b.textContent.trim().slice(0, 16)}"`);
		});
		const inp = nq.querySelector('[data-input]');
		if (inp && parseFloat(getComputedStyle(inp).fontSize) < 16) out.push('input font < 16px');
		return [...new Set(out)].slice(0, 8);
	});
}
async function shot(ctx, label) {
	await ctx.page.screenshot({ path: path.join(OUT, `${SLUG}-${ctx.name}-${label}.png`) });
}
async function guess(ctx, text) {
	await ctx.page.click('[data-input]');
	await ctx.page.type('[data-input]', text, { delay: 5 });
	await ctx.page.keyboard.press('Enter');
}
/** Draw a wavy stroke + a circle on the canvas (touch events on phones, mouse on desktop). */
async function draw(ctx) {
	const r = await ctx.page.$eval('[data-canvas]', (c) => {
		const b = c.getBoundingClientRect();
		return { x: b.left, y: b.top, w: b.width, h: b.height };
	});
	const pts = [];
	for (let i = 0; i <= 30; i++) pts.push([r.x + r.w * (0.15 + 0.7 * (i / 30)), r.y + r.h * (0.5 + 0.25 * Math.sin(i / 4))]);
	const circle = [];
	for (let i = 0; i <= 24; i++) circle.push([r.x + r.w * (0.5 + 0.15 * Math.cos((i / 24) * Math.PI * 2)), r.y + r.h * (0.35 + 0.15 * Math.sin((i / 24) * Math.PI * 2))]);
	for (const line of [pts, circle]) {
		if (ctx.touch) {
			await ctx.page.touchscreen.touchStart(line[0][0], line[0][1]);
			for (const p of line.slice(1)) {
				await ctx.page.touchscreen.touchMove(p[0], p[1]);
				await sleep(16);
			}
			await ctx.page.touchscreen.touchEnd();
		} else {
			await ctx.page.mouse.move(line[0][0], line[0][1]);
			await ctx.page.mouse.down();
			for (const p of line.slice(1)) {
				await ctx.page.mouse.move(p[0], p[1]);
				await sleep(16);
			}
			await ctx.page.mouse.up();
		}
	}
}
const arabicise = (w) => {
	// Typed with Arabic ي/ك and an extra space in the middle — must still count as correct.
	const a = w.replace(/ی/g, 'ي').replace(/ک/g, 'ك');
	const chars = [...a];
	return chars.length > 2 ? ' ' + chars.slice(0, 2).join('') + ' ' + chars.slice(2).join('') + '  ' : a;
};

(async () => {
	const browser = await pp.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
	const gameUrl = `${BASE}/games/${SLUG}/`;
	const [A] = [await openPlayer(browser, PLAYERS[0], gameUrl)];
	await A.page.waitForSelector('.nq', { timeout: 60000 });
	await A.page.waitForSelector('[data-act="play"]', { timeout: 30000 });
	await tap(A, '[data-act="play"]');
	await A.page.waitForSelector('[data-l="create"]', { timeout: 30000 }).catch(async () => {
		await A.page.evaluate(() => document.querySelector('[data-act="play"]')?.click());
		await A.page.waitForSelector('[data-l="create"]', { timeout: 30000 });
	});
	await shot(A, 'menu');
	ok((await layout(A)).length === 0, '[p390] menu layout', (await layout(A)).join('; '));
	console.log('creating room');
	await sleep(800);
	await tap(A, '[data-l="create"]');
	await waitFor(A, 'd.room && d.st && d.st.ph === "lobby"', 15000).catch(async () => {
		note('touch tap on «ساخت اتاق» did not register — clicking');
		await A.page.evaluate(() => document.querySelector('[data-l="create"]').click());
		await waitFor(A, 'd.room && d.st && d.st.ph === "lobby"');
	});
	const code = (await dbg(A, 'd.room')).code;
	ok(/^[A-Z0-9]{6}$/.test(code), 'room created', code);
	const B = await openPlayer(browser, PLAYERS[1], `${gameUrl}?room=${code}`);
	const C = await openPlayer(browser, PLAYERS[2], `${gameUrl}?room=${code}`);
	const all = [A, B, C];
	for (const c of [B, C]) {
		await waitFor(c, 'd.room && d.st && d.st.pl.length >= 2', 20000);
		ok(true, `[${c.name}] joined by invite link`);
	}
	await waitFor(A, 'd.st.pl.filter((p) => p.on).length === 3', 15000);
	ok(true, 'host sees 3 players online');
	await shot(A, 'lobby');
	await shot(B, 'lobby');
	ok((await layout(A)).length === 0, '[p390] lobby layout', (await layout(A)).join('; '));
	// 2 rounds to keep the run short
	await tap(A, '[data-opt="rounds"][data-val="2"]');
	await waitFor(A, 'd.st.rounds === 2');
	await waitFor(A, 'd.st.pl.filter((p) => p.on).length === 3', 15000);
	const t0 = Date.now();
	const stats0 = await Promise.all(all.map((c) => dbg(c, '({...d.stats})')));
	await tap(A, '[data-l="start"]');

	let turns = 0;
	const drawers = [];
	let leaks = 0;
	let wrongSeen = 0;
	let hits = 0;
	let strokesSeen = 0;
	let rapidOk = null;
	for (let guard = 0; guard < 12; guard++) {
		await waitFor(A, 'd.st.ph === "pick" || d.st.ph === "over"', 40000);
		const s = await st(A);
		if (s.ph === 'over') break;
		turns++;
		const seats = await Promise.all(all.map((c) => dbg(c, 'd.room.seat')));
		const D = all[seats.indexOf(s.dr)];
		const G = all.filter((c) => c !== D);
		drawers.push(D.name);
		await waitFor(D, 'd.st.ph === "pick" && d.st.ch', 10000);
		if (turns === 1) await shot(D, 'pick');
		const mark = Object.fromEntries(G.map((g) => [g.name, g.bodies.length]));
		await tap(D, '[data-pick="0"]');
		await waitFor(D, 'd.st.ph === "draw" && d.st.word', 10000);
		const word = (await st(D)).word;
		await draw(D);
		await sleep(1800);
		for (const g of G) {
			const pts = await dbg(g, 'd.points');
			if (pts > 10) strokesSeen++;
			else note(`[${g.name}] received only ${pts} points on turn ${turns}`);
		}
		if (turns === 1) for (const c of all) await shot(c, 'draw');
		// Wrong guess → visible in the others' chat
		const wrong = `اشتباه${turns}`;
		await guess(G[0], wrong);
		await sleep(1600);
		const seen = await Promise.all([D, G[1]].map((c) => c.page.$eval('[data-chat]', (el, w) => el.textContent.includes(w), wrong)));
		if (seen.every(Boolean)) wrongSeen++;
		// Rapid taps on a guesser's canvas (touch) must not zoom
		if (rapidOk === null && G[0].touch) {
			const b = await G[0].page.$eval('[data-canvas]', (c) => {
				const r = c.getBoundingClientRect();
				return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
			});
			for (let i = 0; i < 6; i++) {
				await G[0].page.touchscreen.tap(b.x, b.y);
				await sleep(35);
			}
			await sleep(300);
			rapidOk = await G[0].page.evaluate(() => (!window.visualViewport || visualViewport.scale === 1) && document.documentElement.scrollWidth <= innerWidth + 1);
		}
		// Leak check: guessers' responses before their own guess must not contain the word
		const before = await Promise.all(G.map((g) => st(g)));
		for (const g of G) {
			const raw = g.bodies.slice(mark[g.name]).map((b) => b.body).join('\n');
			const esc = JSON.stringify(word).slice(1, -1);
			if (raw.includes(word) || raw.includes(esc)) {
				leaks++;
				note(`LEAK on ${g.name}: "${word}"`);
			}
		}
		ok(before.every((x) => !x.word && x.mask), `turn ${turns}: guessers see only the mask`, before.map((x) => x.mask).join(' / '));
		// Correct guesses
		const sc0 = (await st(G[0])).sc[(await st(G[0])).me] || 0;
		await guess(G[0], arabicise(word));
		await sleep(1500);
		const after = await st(G[0]);
		if ((after.sc[after.me] || 0) > sc0 && (after.gs.includes(after.me) || after.ph === 'reveal')) hits++;
		else note(`turn ${turns}: guess "${arabicise(word)}" for "${word}" did not score`);
		await guess(G[1], word);
		await waitFor(A, 'd.st.ph === "reveal" || d.st.ph === "over"', 15000);
		if (turns === 1) {
			await sleep(400);
			for (const c of all) await shot(c, 'reveal');
		}
	}
	await waitFor(A, 'd.st.ph === "over"', 40000).catch(() => {});
	const elapsedMin = (Date.now() - t0) / 60000;
	const sEnd = await Promise.all(all.map((c) => dbg(c, '({...d.stats})')));
	ok(turns === 6, 'six turns played (3 players × 2 rounds)', `${turns}`);
	ok(new Set(drawers).size === 3, 'turn rotates through every player', drawers.join(' → '));
	ok(strokesSeen === turns * 2, 'guessers receive the strokes every turn', `${strokesSeen}/${turns * 2}`);
	ok(wrongSeen === turns, 'wrong guesses appear in the others\' chat', `${wrongSeen}/${turns}`);
	ok(hits === turns, 'correct guess (Arabic ي/ك + extra spaces) scores', `${hits}/${turns}`);
	ok(leaks === 0, 'secret word never in a guesser\'s responses before guessing', `${leaks} leaks`);
	ok(rapidOk === true, 'rapid taps on the canvas: no zoom / no h-scroll');
	for (const c of all) {
		const s = await st(c);
		ok(s && s.ph === 'over', `[${c.name}] podium shown`);
		ok(await c.page.$('.nq-podium .nq-pod'), `[${c.name}] podium markup`);
		await shot(c, 'podium');
		const lay = await layout(c);
		ok(lay.length === 0, `[${c.name}] podium layout`, lay.join('; '));
	}
	const final = await st(A);
	note('scores: ' + final.pl.map((p) => `${p.n} ${final.sc[p.s]}`).join(' · '));
	all.forEach((c, i) => {
		const req = sEnd[i].req - stats0[i].req;
		const down = sEnd[i].down - stats0[i].down;
		const up = sEnd[i].up - stats0[i].up;
		note(`[${c.name}] ${(req / elapsedMin).toFixed(0)} req/min · ↓ ${(down / elapsedMin / 1024).toFixed(1)} KB/min · ↑ ${(up / elapsedMin / 1024).toFixed(1)} KB/min (bodies, ${elapsedMin.toFixed(2)} min)`);
	});
	// Rejoin with the same identity after a reload keeps the seat
	const seatB = (await st(B)).me;
	await B.page.reload({ waitUntil: 'domcontentloaded', timeout: 120000 });
	await waitFor(B, 'd.st', 20000).catch(() => {});
	const sb = await st(B);
	ok(sb && sb.me === seatB, '[l844] reload re-joins with the same seat', sb ? `seat ${sb.me}` : 'no state');
	for (const c of all) ok(c.errors.length === 0, `[${c.name}] no page errors`, c.errors.slice(0, 3).join(' | '));
	await browser.close();
	console.log(`\n=== ${SLUG}: ${fails ? fails + ' FAILED' : 'all passed'} ===\n` + lines.join('\n'));
	process.exitCode = fails ? 1 : 0;
})().catch((e) => {
	console.error(e);
	process.exitCode = 2;
});
