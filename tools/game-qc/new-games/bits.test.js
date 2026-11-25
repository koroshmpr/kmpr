/** QC: «صفر و یک» — solve a real board by tapping (keyboard on desktop), hint, pause/resume, time-out, restart, zoom & layout. */
const T = require('./_lib');
const SLUG = 'bits';

(async () => {
	const R = T.reporter(SLUG);
	const browser = await T.launch();
	for (const size of T.SIZES) {
		const ctx = await T.open(browser, SLUG, size);
		const { page } = ctx;
		const tag = `[${ctx.name} ${ctx.w}x${ctx.h}]`;
		try {
		await T.play(ctx);
		const n = await T.engine(page, 'd._s.n');
		const cells = await page.$$eval('.bt__cell', (els) => els.map((e) => { const r = e.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height }; }));
		R.ok(cells.length === n * n, `${tag} board ${n}x${n} rendered`);
		const minSide = Math.min(...cells.map((c) => Math.min(c.w, c.h)));
		R.ok(minSide >= 40, `${tag} cell tap targets`, `${Math.round(minSide)}px`);
		R.ok((await T.layout(ctx)).length === 0, `${tag} layout`, (await T.layout(ctx)).join('; '));
		await T.shot(ctx, SLUG, 'start');
		const sol = await T.engine(page, '[...d._s.sol]');
		const given = await T.engine(page, 'd._s.given');
		const empty = given.map((g, i) => (g ? -1 : i)).filter((i) => i >= 0);
		// rapid taps on one empty cell: 6 taps = full cycle back to empty; no zoom allowed
		if (ctx.touch) {
			const c = cells[empty[0]];
			const z = await T.rapidTaps(ctx, c.x, c.y, 6);
			R.ok(z.scale === 1 && !z.hs, `${tag} 6 rapid taps on a cell: no zoom / no h-scroll`, JSON.stringify(z));
			R.ok((await T.engine(page, `d._s.g[${empty[0]}]`)) === -1, `${tag} cell cycles empty→0→1→empty`);
		}
		// play: fill every empty cell with its bit
		if (ctx.touch) {
			for (const i of empty) for (let k = 0; k <= sol[i]; k++) await T.tapAt(ctx, cells[i].x, cells[i].y);
		} else {
			await page.evaluate(() => document.querySelector('[data-stage]').focus());
			let cur = 0;
			for (const i of empty) {
				const [r0, c0] = [Math.floor(cur / n), cur % n];
				const [r1, c1] = [Math.floor(i / n), i % n];
				for (let k = 0; k < Math.abs(r1 - r0); k++) await page.keyboard.press(r1 > r0 ? 'ArrowDown' : 'ArrowUp');
				for (let k = 0; k < Math.abs(c1 - c0); k++) await page.keyboard.press(c1 > c0 ? 'ArrowRight' : 'ArrowLeft');
				await page.keyboard.press(String(sol[i]));
				cur = i;
			}
		}
		await T.sleep(250);
		await T.shot(ctx, SLUG, 'solved');
		await T.sleep(1100);
		const b = await T.engine(page, 'd._s.board');
		const score = await T.engine(page, 'd._s.score');
		R.ok(b === 1 && score > 0, `${tag} board 1 solved by real input → board 2`, `score ${score}`);
		// hint costs time and fills a correct cell
		const before = await T.engine(page, '({ left: d._s.left, filled: [...d._s.g].filter((v) => v >= 0).length })');
		await T.tapSel(ctx, '[data-bt-hint]');
		await T.sleep(200);
		const after = await T.engine(page, '({ left: d._s.left, filled: [...d._s.g].filter((v) => v >= 0).length })');
		R.ok(after.filled === before.filled + 1 && after.left < before.left - 15, `${tag} hint fills a cell and costs time`);
		R.ok((await T.pauseResume(ctx)) === 'ok', `${tag} pause/resume`);
		await T.shot(ctx, SLUG, 'board2');
		// time-out → game over
		await T.engine(page, '(d._s.left = 0.4, true)');
		await T.sleep(1200);
		R.ok(await T.overShown(page), `${tag} clock runs out → game-over screen`);
		await T.shot(ctx, SLUG, 'over');
		await T.again(page);
		await T.sleep(800);
		R.ok((await T.engine(page, 'd._s.board')) === 0 && (await T.engine(page, 'd._s.score')) === 0, `${tag} «دوباره» starts a fresh run`);
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
