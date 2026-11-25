/** QC: «پینگ سرور» — real timed taps (touch) / Space (desktop), pause/resume, timeout, restart, zoom & layout. */
const T = require('./_lib');
const SLUG = 'server-ping';

(async () => {
	const R = T.reporter(SLUG);
	const browser = await T.launch();
	for (const size of T.SIZES) {
		const ctx = await T.open(browser, SLUG, size);
		const { page } = ctx;
		const tag = `[${ctx.name} ${ctx.w}x${ctx.h}]`;
		try {
		await T.play(ctx);
		const st = await page.$eval('[data-stage]', (e) => { const r = e.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2, w: r.width, h: r.height }; });
		R.ok(st.w > 200 && st.h > 200, `${tag} stage size`, `${Math.round(st.w)}x${Math.round(st.h)}`);
		R.ok(await page.$('canvas.sp-canvas'), `${tag} canvas mounted`);
		const ta = await page.$eval('canvas.sp-canvas', (c) => getComputedStyle(c).touchAction);
		R.ok(ta === 'none', `${tag} canvas touch-action`, ta);
		const press = async () => (ctx.touch ? T.tapAt(ctx, st.x, st.y + st.h * 0.3) : page.keyboard.press('Space'));
		await press(); // starts the packet
		await T.sleep(150);
		R.ok(await T.engine(page, 'd._s.started'), `${tag} first tap starts the run`);
		let hits = 0;
		for (let i = 0; i < 12; i++) {
			try {
				await page.waitForFunction(() => { const p = document.querySelector('[data-stage]').kmprDebug._probe(); return p && (p.dead || (p.d < p.hw * 0.6 && p.d > -p.hw * 0.2)); }, { polling: 'raf', timeout: 6000 });
			} catch (e) {
				break;
			}
			if (await T.engine(page, 'd._s.dead')) break;
			await press();
			await T.sleep(60);
			hits = await T.engine(page, 'd._s.hits');
		}
		R.ok(hits >= 8, `${tag} timed taps land pings`, `${hits} hits, score ${await T.engine(page, 'd._s.score')}`);
		const hud = await page.$eval('[data-hud="score"]', (e) => e.textContent.trim());
		R.ok(hud !== '۰', `${tag} HUD score updates`, hud);
		R.ok((await T.layout(ctx)).length === 0, `${tag} layout during play`, (await T.layout(ctx)).join('; '));
		await T.shot(ctx, SLUG, 'play');
		R.ok((await T.pauseResume(ctx)) === 'ok', `${tag} pause/resume`);
		const alive = !(await T.engine(page, 'd._s.dead'));
		// rapid taps: a mistimed tap ends the run; page must not zoom
		const z = ctx.touch ? await T.rapidTaps(ctx, st.x, st.y, 5) : null;
		if (z) R.ok(z.scale === 1 && !z.hs, `${tag} 5 rapid taps: no zoom / no h-scroll`, JSON.stringify(z));
		else {
			for (let i = 0; i < 3; i++) await page.keyboard.press('Space');
		}
		await T.sleep(1600);
		R.ok(await T.overShown(page), `${tag} mistimed tap / missed port → game-over screen`, alive ? 'ended by early tap' : 'ended by timeout');
		await T.shot(ctx, SLUG, 'over');
		R.ok((await T.layout(ctx, '[data-overlay]')).filter((x) => !/small btn|btn outside stage/.test(x)).length === 0, `${tag} game-over layout`, (await T.layout(ctx, '[data-overlay]')).join('; '));
		await T.again(page);
		await T.sleep(700);
		R.ok((await T.engine(page, 'd._s.hits')) === 0 && !(await T.engine(page, 'd._s.dead')), `${tag} «دوباره» restarts a fresh run`);
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
