/** QC: «منچ» — chooser, real dice/piece taps vs AI, capture, win → score, again, pause/resume, 4-player local, zoom & layout. */
const T = require('./_lib');
const SLUG = 'mench';
const S = (page, expr) => T.engine(page, expr.replace(/\bst\b/g, 'd._s'));

async function chooseMode(ctx, type, players) {
	const { page } = ctx;
	await page.waitForSelector('.mxm', { timeout: 8000 });
	await T.tapSel(ctx, `[data-opt="players"][data-val="${players}"]`);
	const open = await page.$eval(`[data-card="${type}"]`, (c) => c.dataset.open === 'true');
	if (!open) await T.tapSel(ctx, `[data-mx-card="${type}"]`);
	await T.sleep(450);
	await T.tapSel(ctx, `[data-mx="${type}"]`);
	await T.sleep(400);
	if (await page.$('[data-mx-confirm]:not([hidden])')) await T.tapSel(ctx, '[data-mx="confirm-yes"]');
	await page.waitForFunction(() => { const d = document.querySelector('[data-stage]').kmprDebug; return d && d._s && d._s.phase; }, { timeout: 8000 });
}
const waitMyRoll = (page, seat = 0, timeout = 30000) =>
	page.waitForFunction((seat) => { const s = document.querySelector('[data-stage]').kmprDebug._s; return s && ((s.turn === seat && s.phase === 'roll') || s.phase === 'over'); }, { timeout, polling: 100 }, seat);
const roll = async (ctx, dice, key = 'Space') => (ctx.touch ? T.tapAt(ctx, dice.x, dice.y) : ctx.page.keyboard.press(key));

(async () => {
	const R = T.reporter(SLUG);
	const browser = await T.launch();
	for (const size of T.SIZES) {
		const ctx = await T.open(browser, SLUG, size);
		const { page } = ctx;
		const tag = `[${ctx.name} ${ctx.w}x${ctx.h}]`;
		try {
		await T.play(ctx).catch(() => {});
		await page.waitForSelector('.mxm', { timeout: 8000 });
		R.ok((await T.layout(ctx, '.mx-layer')).filter((x) => !/btn outside/.test(x)).length === 0, `${tag} mode chooser layout`, (await T.layout(ctx, '.mx-layer')).join('; '));
		await T.shot(ctx, SLUG, 'chooser');
		await chooseMode(ctx, 'ai', 2);
		const board = await page.$eval('.mn-board', (e) => { const r = e.getBoundingClientRect(); return { w: r.width, h: r.height }; });
		const dice = await page.$eval('.mn-dice', (e) => { const r = e.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height }; });
		R.ok(Math.abs(board.w - board.h) < 2 && board.w >= 240, `${tag} square board`, `${Math.round(board.w)}px`);
		R.ok(dice.w >= 44 && dice.h >= 44, `${tag} dice tap target`, `${Math.round(dice.w)}px`);
		const lay = await T.layout(ctx, '.mx-foot');
		R.ok(lay.length === 0, `${tag} layout in game`, lay.join('; '));
		// real turns: tap the dice, tap a highlighted piece when asked
		let rolls = 0;
		let moves = 0;
		for (let t = 0; t < 5; t++) {
			await waitMyRoll(page);
			if ((await S(page, 'st.phase')) === 'over') break;
			if (t === 0 && ctx.touch) {
				const z = await T.rapidTaps(ctx, dice.x, dice.y, 6);
				R.ok(z.scale === 1 && !z.hs, `${tag} 6 rapid taps on the dice: no zoom / no h-scroll`, JSON.stringify(z));
			} else await roll(ctx, dice, t % 2 ? 'Space' : 'Enter');
			rolls++;
			await page.waitForFunction(() => { const s = document.querySelector('[data-stage]').kmprDebug._s; return s.phase !== 'roll' && s.phase !== 'rolling'; }, { timeout: 8000 }).catch(() => {});
			await T.sleep(150);
			if ((await S(page, 'st.phase')) === 'move') {
				const tok = await page.$eval('.mn-tok.is-can', (e) => { const r = e.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2, k: +e.dataset.k }; });
				if (ctx.touch) await T.tapAt(ctx, tok.x, tok.y);
				else await page.keyboard.press(String(tok.k + 1));
				moves++;
			}
			await T.sleep(400);
		}
		R.ok(rolls >= 4 && (await S(page, 'st.rolls')) >= rolls, `${tag} dice rolled by real input`, `${rolls} rolls, ${moves} manual piece picks`);
		await T.shot(ctx, SLUG, 'play');
		// capture: own piece one square behind an opponent piece, dice forced to 1
		await waitMyRoll(page);
		await S(page, '(st.tok[0] = [10, 56, 56, 55], st.tok[2] = [37, -1, -1, -1], st.caps[0] = 0, window.__rnd = Math.random, Math.random = () => 0, true)');
		await roll(ctx, dice);
		await page.waitForFunction(() => document.querySelector('[data-stage]').kmprDebug._s.phase === 'move', { timeout: 6000 }).catch(() => {});
		const t0 = await page.$eval('.mn-tok[data-seat="0"][data-k="0"]', (e) => { const r = e.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 }; });
		if (ctx.touch) await T.tapAt(ctx, t0.x, t0.y);
		else await page.keyboard.press('1');
		await T.sleep(900);
		R.ok((await S(page, 'st.caps[0]')) === 1 && (await S(page, 'st.tok[2][0]')) === -1, `${tag} landing on an opponent sends it home`);
		R.ok((await S(page, 'st.turn')) === 0, `${tag} capture gives another roll`);
		await T.shot(ctx, SLUG, 'capture');
		R.ok((await T.pauseResume(ctx)) === 'ok', `${tag} pause/resume`);
		// win: last piece one step from home
		await waitMyRoll(page);
		await S(page, '(st.tok[0] = [55, 56, 56, 56], true)');
		await roll(ctx, dice);
		await page.waitForFunction(() => document.querySelector('[data-stage]').kmprDebug._s.phase === 'over', { timeout: 6000 }).catch(() => {});
		await page.evaluate(() => (Math.random = window.__rnd));
		await T.sleep(1800);
		R.ok(await T.overShown(page), `${tag} all four home → win → game-over screen with score`, await page.$eval('[data-hud="score"]', (e) => e.textContent.trim()));
		await T.shot(ctx, SLUG, 'win');
		await T.again(page);
		await T.sleep(900);
		R.ok((await S(page, 'st.tok[0].join()')) === '-1,-1,-1,-1' && (await S(page, 'st.phase')) !== 'over', `${tag} «دوباره» starts a new game`);
		// 4-player pass-and-play
		await page.evaluate(() => document.querySelector('[data-mx="menu"]').click());
		await T.sleep(500);
		await chooseMode(ctx, 'local', 4);
		await T.sleep(500);
		R.ok((await S(page, 'st.seats.length')) === 4 && (await page.$$('.mn-chip')).length === 4, `${tag} 4-player local game`);
		for (const seat of [0, 1, 2, 3]) {
			await waitMyRoll(page, seat, 15000).catch(() => {});
			// re-measure: the 4-player layout moves the dice
			const dice4 = await page.$eval('.mn-dice', (e) => { const r = e.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height }; });
			await roll(ctx, dice4);
			await T.sleep(700);
			if ((await S(page, 'st.phase')) === 'move') {
				const k = await page.$eval('.mn-tok.is-can', (e) => +e.dataset.k);
				await page.keyboard.press(String(k + 1));
				await T.sleep(500);
			}
		}
		R.ok((await S(page, 'st.rolls')) >= 4, `${tag} 4 players take turns`, `rolls ${await S(page, 'st.rolls')}`);
		const lay4 = await T.layout(ctx, '.mx-foot');
		R.ok(lay4.length === 0, `${tag} 4-player layout`, lay4.join('; '));
		const chips = await page.$$eval('.mn-chip', (els) => els.map((e) => { const r = e.getBoundingClientRect(); return r.right <= innerWidth + 1 && r.left >= -1; }));
		R.ok(chips.every(Boolean), `${tag} player chips fit`);
		await T.shot(ctx, SLUG, 'local4');
		} catch (e) {
			R.ok(false, `${tag} flow aborted`, e.message.split('\n')[0]);
			await T.shot(ctx, SLUG, 'ABORT').catch(() => {});
		}
		R.ok(ctx.errors.length === 0, `${tag} no page errors`, ctx.errors.slice(0, 3).join(' | '));
		await page.close();
	}
	await browser.close();
	R.done();
})().catch((e) => {
	console.error(e);
	process.exit(1);
});
