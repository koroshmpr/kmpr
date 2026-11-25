/**
 * Per-game input adapters for run-all.js. Any engine without an entry uses `default`
 * (random taps on the stage / its buttons, swipes and the usual keys), so new games are
 * tested automatically. Override only what a game needs:
 *   seed        localStorage keys set before the page loads
 *   play(H, ms) send inputs for ~ms while the game runs
 *   toOver(H, ms) inputs used while waiting for game-over (default: same as play)
 *   overMs / over:false / overRequired
 *   afterStart(H) extra clicks after «شروع» (intro screens, level pickers)
 *   matchStart(H) choose a mode in the match menu (default: AI, else local)
 * H = { page, size, tap, tapSel, sleep, rnd, pick, center, state, res }
 */
const KEYS = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Space', 'Enter'];

// Visible interactive things inside the stage (buttons, role=button, data-qc-target).
async function targets(page) {
	return page.evaluate(() => {
		const st = document.querySelector('[data-stage]');
		if (!st) return [];
		const sr = st.getBoundingClientRect();
		return [...st.querySelectorAll('button:not([disabled]), [role=button], [data-qc-target], [tabindex="0"]')]
			.filter((e) => !e.closest('[hidden],[inert]'))
			.map((e) => e.getBoundingClientRect())
			.filter((r) => r.width > 4 && r.height > 4 && r.top >= sr.top - 1 && r.bottom <= sr.bottom + 1 && r.top < innerHeight && r.left < innerWidth)
			.map((r) => ({ x: r.left + r.width / 2, y: r.top + r.height / 2 }));
	});
}
async function stageRect(page) {
	return page.evaluate(() => {
		const r = document.querySelector('[data-stage]').getBoundingClientRect();
		return { x: Math.max(0, r.left), y: Math.max(0, r.top), w: Math.min(r.width, innerWidth - Math.max(0, r.left)), h: Math.min(r.height, innerHeight - Math.max(0, r.top)) };
	});
}

async function swipe(H, r) {
	const { page, rnd } = H;
	const x = r.x + r.w / 2;
	const y = r.y + r.h / 2;
	const d = Math.min(r.w, r.h) * 0.3;
	const dir = H.pick([[1, 0], [-1, 0], [0, 1], [0, -1]]);
	if (H.size.touch) {
		await page.touchscreen.touchStart(x, y);
		for (let i = 1; i <= 5; i++) await page.touchscreen.touchMove(x + (dir[0] * d * i) / 5, y + (dir[1] * d * i) / 5);
		await page.touchscreen.touchEnd();
	} else {
		await page.mouse.move(x, y);
		await page.mouse.down();
		await page.mouse.move(x + dir[0] * d, y + dir[1] * d, { steps: 5 });
		await page.mouse.up();
	}
	void rnd;
}

async function fuzz(H, ms, keys = KEYS) {
	const { page, size, tap, sleep, rnd, pick } = H;
	const t0 = Date.now();
	while (Date.now() - t0 < ms) {
		const s = await H.state();
		if (s.s === 'over' || s.result || s.s === 'paused') break;
		const r = await stageRect(page);
		const roll = Math.random();
		if (!size.touch && roll < 0.45) {
			const k = pick(keys);
			await page.keyboard.down(k);
			await sleep(rnd(30, 160));
			await page.keyboard.up(k);
		} else if (roll < 0.75) {
			const t = await targets(page);
			if (t.length) {
				const p = pick(t);
				await tap(p.x, p.y);
			} else await tap(r.x + rnd(0.1, 0.9) * r.w, r.y + rnd(0.15, 0.95) * r.h);
		} else if (roll < 0.9) {
			await tap(r.x + rnd(0.1, 0.9) * r.w, r.y + rnd(0.15, 0.95) * r.h);
		} else await swipe(H, r);
		await sleep(rnd(60, 220));
	}
}

const idle = async (H, ms) => H.sleep(ms);

module.exports = {
	default: {
		playMs: 7000,
		overMs: 30000,
		play: (H, ms) => fuzz(H, ms),
		toOver: (H, ms) => fuzz(H, ms),
	},
	// games that end on their own when the player does nothing (timers / collisions)
	'math-sprint': { toOver: idle, overRequired: true, overMs: 75000 },
	'esm-famil': { toOver: idle, overRequired: true, overMs: 75000 },
	'type-code': {
		play: async (H, ms) => {
			const t0 = Date.now();
			while (Date.now() - t0 < ms) {
				await H.page.keyboard.type('const a = 1;', { delay: 40 });
			}
		},
		toOver: idle,
		overMs: 75000,
	},
	snake: { toOver: idle }, // waits on its start chooser until the first input
	'tehran-taxi': { toOver: idle, overRequired: true },
	'tehran-metro': { toOver: idle, overRequired: true },
	'pixel-runner': { toOver: idle, overRequired: true },
	'sky-shooter': { toOver: idle, overMs: 60000 },
	jota: { toOver: idle, overMs: 60000 },
	'rhythm-code': { toOver: idle, overMs: 60000 },
	'caspian-fishing': { toOver: idle, overMs: 70000 },
	qahvechi: { toOver: idle, overMs: 70000 },
	'iran-quiz': { overRequired: false },
	// puzzles have no fail state: a meaningful state (moves) is enough
	sudoku: { over: false },
	hanoi: { over: false },
	'slide-puzzle': { over: false },
	debugger: { overMs: 60000 },
	chess: { over: false },
	'korosh-run': {
		seed: { kmpr_kr_intro: '1' },
		over: false,
	},
	KEYS,
	fuzz,
	idle,
};
