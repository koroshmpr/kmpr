# game-qc — automated play-test for every Game Center game

Headless Chrome (puppeteer-core) opens every game page, starts the game, plays it with real
taps / clicks / keys and checks it at five viewports:

| id | viewport | input |
|---|---|---|
| `360` | 360×740 | touch (mobile emulation) |
| `390` | 390×844 | touch — also plays until game-over |
| `844L` | 844×390 landscape | touch |
| `768` | 768×1024 | touch (tablet) |
| `1280` | 1280×800 | mouse + keyboard — also plays until game-over |

Games are discovered from `themes/kmpr/javascript/games/catalog.js` (`ENGINE_LOADERS`), and their
URLs from the WP REST API (`/wp-json/wp/v2/game` → `data-engine` on each page). A new game is
tested automatically once its post exists; an engine without a published post is listed as `NO POST`.

## Run

```sh
# from wp-content/ — Local site must be running (http://localhost:10039)
node tools/game-qc/run-all.js                       # all games × all sizes (~10 min with 6 jobs)
node tools/game-qc/run-all.js --jobs=6 --verbose     # + warnings / info per combo
node tools/game-qc/run-all.js --only=snake,xo --sizes=390,1280
node tools/game-qc/run-all.js --quick                # short play, no game-over phase
node tools/game-qc/run-all.js --base=https://example.com --no-shots
```

`puppeteer-core` is reused from `tools/korosh-qc/node_modules` (symlinked as `node_modules`;
if the link is missing: `ln -s ../korosh-qc/node_modules tools/game-qc/node_modules`).
Chrome path: `/Applications/Google Chrome.app/...` (override with `CHROME=/path`).

Output: a table (`ok`, `ok*` = passed with warnings, `FAIL`), the failure list, exit code
`0` all pass / `1` failures / `2` runner crash. Screenshots per step in `out/shots/`
(`<slug>-<size>-play|mid|over|error.png`) and raw results in `out/results.json` — look at them.

## What is checked (failure codes)

- `ERR` — `pageerror` or `console.error` (network failures are only warnings).
- `HS` — horizontal page scroll. `FIT` — stage outside the viewport, or a control off-screen / cut off.
- `SMALL` — a touch control under 40px (fail on touch sizes, warning on desktop). Mark intentionally
  dense grids (sudoku cells, chess squares) with `data-qc-small-ok` on a container.
- `IOSZOOM` — an `input`/`select`/`textarea` under 16px (iOS zooms on focus).
- `TA` — no `touch-action` (`manipulation`/`none`/`pan-*`) at the stage centre or the HUD bar → iOS double-tap zoom.
- `ZOOM` — `visualViewport.scale` > 1 after 3 rapid taps, a double-tap gesture and pinches on the stage / HUD.
- `START` / `STATIC` — game did not start, or nothing changed (score, state, pixels) after the inputs.
- `PAUSE` / `RESUME` — pause button and «ادامه» (arcade engines; match engines have their own menu).
- `OVER` / `AGAIN` — game-over (or match result) reached and «دوباره» restarts (only where the adapter requires it).

## Adapters

`adapters.js` maps an engine to how it is played. The default is a random mix of taps on the
stage's buttons, random taps, swipes and arrow/space/enter keys. Override `play`, `toOver`
(e.g. `idle` for games that end by themselves), `overMs`, `overRequired`, `over: false`
(puzzles without a fail state), `seed` (localStorage), `afterStart`, `matchStart`.
