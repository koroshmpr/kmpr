# Game Center (گیم‌سنتر) — context for agents

**Before creating or changing any game, read and follow [GAME-RULES.md](GAME-RULES.md) (theme/identity, categories, characters, controls, sound, stability, performance, 6-place registration, mandatory browser test). For «کوروش دیباگر» also read [KOROSH-DEBUGGER.md](KOROSH-DEBUGGER.md).**

## Map
- PHP: `inc/games.php` (CPT `game`, engine registry `kmpr_game_engines()`, scores REST `kmpr/v1`, anti-cheat caps, one-time post seeder `kmpr_games_seed`), `games-online.php`/`games-rooms-extra.php` (online rooms), `games-pwa.php`, `games-admin-players.php`, `play-nudge.php`.
- Monetisation: `inc/arcade-monetize.php` (pass card, support, sponsor, advergame CTA, A/B demand-test stats page) + `inc/arcade-subscription.php` (real subscription). JS: `javascript/arcade-monetize.js`, `javascript/arcade-sub.js`. CSS: `tailwind/custom/arcade-monetize.css`.
- Templates: `template-parts/games/*` (`player.php` = shell markup with `data-game-shell data-engine data-url data-hub`).
- JS: `index.js` (boot) → `shell.js` (lifecycle, focus/fullscreen, pause, snapshots, game-over screen, leaderboard) + `catalog.js` (CATALOG metadata, achievements, ENGINE_LOADERS) + `engines/*.js` + `lib/` (`canvas.js`, `match.js` = AI/local/online chooser for duel games, `store.js` = localStorage profile `kmpr_arcade_v1` {id uuid, nickname,…}).
- CSS: `tailwind/custom/games.css` imports `games/*.css` (one per engine; `iranian.css` for iran-quiz + haft-sin).

## Engine contract
`export function mount(stage, api)` → returns `{ start(opts), pause(), resume(), destroy(), serialize?, restore?, noAutoPause?, orientation? }`.
api: `setRatio(desk, mobile)`, `setScore`, `hud({level,lives,extra})`, `sfx(name)` (click shoot hit boom coin jump hurt level over type wrong flip ach), `toast(html)`, `unlock(achId)`, `over({score, meta:{summary, result}})`, `running`, `touch`, `reducedMotion`, `begin()` (match engines).

## Add a new game (4 places + seed)
1. `engines/<slug>.js` 2. `catalog.js` CATALOG entry (+ `landscape: true` if wide) and ENGINE_LOADERS 3. `inc/games.php` `kmpr_game_engines()` caps (max, pps, min_ms) 4. CSS file + import in `games.css` 5. add to the seeder array in `inc/games.php` and bump `$ver` (creates the post on prod at next admin visit).

## Shell features (current)
- Game-over: «دوباره با همین حالت/سختی» (act `again`), «سخت‌تر» (next AI level from `api._matchUI.levels`), «تغییر حالت» (M key), «اشتراک امتیاز» (Web Share / clipboard), «بازی‌های دیگر», gap-to-record line.
- Forced landscape: `CATALOG[x].landscape` → orientation lock on fullscreen; fallback CSS rotation `html.game-rotate` (portrait phone in focus mode); `canvas.toWorld()` maps pointer for the rotation and `fit()` uses clientWidth/Height. Only canvas engines are safe for this (jota, pixel-runner).

## Subscription («پاس آرکید») — inc/arcade-subscription.php
- Master switch ACF `sub_on` (بازی‌ها › تنظیمات آرشیو › تب اشتراک). Off → `kmpr_am_pass_on()` false, no modal/REST/JS.
- Plans textarea `months|price_toman|label|bonus_days`. Identity = arcade player uuid (no login). Members in option `kmpr_sub_members` {pid: until, since, nick}. Requests = private CPT `arcade_sub_req` (meta `_s_*`, code `KM-####`).
- Pay: manual (Telegram/WhatsApp deep link + card; owner approves in بازی‌ها › اشتراک‌ها, extends months*30+bonus days) and Zarinpal v4 (`sub_zp_merchant`, sandbox flag; callback `kmpr/v1/arcade/sub/back` → `/games/?sub=ok|fail`).
- Perks implemented: «مشترک ویژه» leaderboard badge, no game-over upsells (`html.is-sub`), gift days, renew dialog. Games stay free for everyone.

## Hub v2 / lobby (2026-11-09)
- Hub (`template-parts/games/hub.php`) = tabs `#games` / `#lobby` / `#ranks` (`data-pane`), pixel-wipe switch in `hub.js tabs()`; header (`header-bar.php` `data-gh-tab`) + dock switch tabs in place. Rails = one per `game_category` term + «آنلاین با دوستان».
- Device nickname: `pwa.js devicePlayer()` asks once (dialog `.gc-hello`), stores in `kmpr_arcade_v1`, then presence heartbeat every 25s → `POST kmpr/v1/games/presence` (`inc/games-lobby.php`, transient, 70s TTL) → event `kmpr:presence`.
- Lobby «ساخت اتاق» → `game-url?create=1` → shell calls `api._matchUI.create()`.
- No cover image → `kmpr_game_art_html()` generated 2D art (glyph per engine in `kmpr_game_glyph()`).
- Single game header = arcade marquee `.gs-mq` (chasing bulbs, neon title, «الان اینجا», how-to popover `#gs-how`).
- Speed: `games-pwa.php` speculation rules (prerender moderate) + view transitions + modulepreload; `index.js` loads engine chunk in parallel; 2D loaders `lib/loader.js` (only after 150ms).
- New games: sport `air-hockey` `penalty` (match.js, AI/local) `basket` (1–4 players); puzzle `slide-puzzle` `sudoku` `hanoi`. Seeder `$ver = 2`.

## 2026-11-10 (later)
- 30 games. New topics/categories: music (`rhythm-code`), education (`math-sprint`), racing (`tehran-taxi`), casual (`caspian-fishing`). Seeder `$ver = 6`.
- Hero (lib/hero.js) final look: long black curly hair with a faint grey streak, rectangular black sunglasses, light stubble with a faint chin streak, open BLUE overshirt (flaps sway: frames stand/stand2/run*) over a dark-blue tee with yellow «{}», navy trousers. Forms: blue / dev cyan / jet indigo. drawHero(..., sx, sy) supports squash & stretch.
- korosh-run: safe respawn (2 s invincible, no enemy within 6 tiles of the checkpoint); styled canvas HUD (pixel heart, coin, world badge, clock).
- Hub order: (SEO-only h1) → spotlight (animated pixel Korosh when no cover) → category chips → continue → 🆕 new → 🔥 popular → online → pass card → one horizontal snap-scroll row per category (online-only category row skipped as duplicate).

## 2026-11-10
- 26 games. New: `tehran-metro` (runner), `qahvechi` (coffee bar), `debugger` (rotate code-flow), `foosball` (match AI/local), `esm-famil` (Iranian word game). `korosh-run` renamed «کوروش و ارتش باگ‌ها» (slug kept).
- Hero = hand-made pixel art in `lib/hero.js` (`drawHero(ctx, cx, top, size, form, frame, face)`): aviators, beard, open overshirt (colour = form) over black tee with yellow «{}». Used by korosh-run + tehran-metro.
- korosh-run: 6 enemy types (bug, clock, hopper, spam, virus=don't stomp, turret), moving + crumbling platforms, 1 hidden 1UP per stage (block over a pit or cracked-wall alcove `F`), ▼ server → bonus room with ▼ exit server → you pop out of an exit tower; DEPLOY button goal; jetpack = 1 extra jump + 0.7 s glide; shots at knee height. Walls ≤ 3 tiles (jump apex ≈ 3.9).
- Cards: vertical `.gc-card` with cartoon SVG icons `kmpr_game_icon_svg()` (inc/games-lobby.php); badges = SVG medals grouped per game (hub.js `badges()`).
- Archive query shows up to 100 games (no pagination).

## Later the same day
- `korosh-run` «کوروش ران»: featured first game, original platformer (owner as pixel hero, coffee power-up, 3 worlds × 2 stages, seeded levels, WebAudio chiptune in a Hijaz-like scale; only plays when the arcade sound is on). Camera crops the top 3 rows (`CAM_TOP`).
- Hub cards = compact `.gc-card` (icon glyph from `kmpr_game_glyph()`); single page shows the cover/art as `.gs-banner` backdrop.
- Navigation: header menu (≥1024px) and bottom dock (<1024px) switch hub tabs; the in-page `.gc-tabs` bar is hidden (kept for a11y). Tab switch = View Transitions (`::view-transition-*(gc-pane)`).
- Subscription automation: provider `zarinpal` (built-in) or `woocommerce` (bridge: hidden virtual product per plan, order-pay link, auto-activate on payment_complete/processing/completed, auto-revoke on refund/cancel, guest e-mail verification skipped). Hourly cron `kmpr_sub_reconcile` re-verifies Zarinpal payments whose buyer closed the tab. Admin page shows revenue. Recovery code (= player uuid) in the subscription dialog; renewal nudge ≤3 days / after expiry.
- Tested: Zarinpal sandbox end-to-end (real redirect + verify + activation), WooCommerce bridge with WC temporarily installed locally (order, payment_complete, duplicate trigger, refund).

## Status (2026-11-09)
Done, tested on local (`http://localhost:10039`, headless Chrome via puppeteer-core in scratchpad), NOT committed yet:
subscription (order → approve → 97 days for 3m+7d, badge, double-approve guard, master switch off → no UI + REST 404),
game-over quick actions (arcade shell + match result screen «بازی دوباره (سطح)» / «سخت‌تر»), jota/pixel-runner landscape
(lock refused → exits fullscreen → CSS rotation; fullscreen elements ignore transforms), iran-quiz + haft-sin (posts seeded: /games/iran-gard/, /games/haft-sin/).
Match engines (xo, chess, rps15, color-race) end via `ui.result()` in lib/match.js, not shell.finish().
Local testing: WP CLI-less — run PHP with Local's binary `~/Library/Application Support/Local/lightning-services/php-8.2.30+1/bin/darwin-arm64/bin/php -d mysqli.default_socket=~/Library/Application Support/Local/run/sooecj4yr/mysql/mysqld.sock script.php` (script requires wp-load.php).

## Backlog ideas
- Subscriber-only cosmetic: gold nickname/avatar frame in leaderboard & arcade bar chip; monthly tournament page.
- Restore subscription on another device (show player id/«کد بازیابی» in profile + import).
- More Iranian games: «اسم‌فامیل» (needs word lists), «منچ» (match engine), «حافظ‌خوان» daily, «پرسپولیس/استقلال پنالتی».
- Optional WooCommerce bridge only if WooCommerce ever gets installed.

## «کوروش دیباگر» (korosh-run)
**Single source of truth: [KOROSH-DEBUGGER.md](KOROSH-DEBUGGER.md)** — real-life chronology of chapters/stages, cast (from the owner's photos), systems & code map, the «add a chapter» checklist and the QC bots in `tools/korosh-qc/`. Read it before touching the game; update it with every new chapter.

## 2026-11-24 — hub «الان چی بازی کنم؟», support card v2, games across the site
- Hub games tab: `.gc-now` strip under the spotlight (daily challenge card · «یک بازی شانسی» prefers unplayed games · «پیشرفت من» play-streak from localStorage `kmpr_gc_days` + tried/records meter). JS: `hub.js daily()/quickPlay()/streak()`; CSS `tailwind/custom/games/page.css`.
- Hash tabs (#lobby/#ranks) are chosen pre-paint by an inline script in hub.php; online list + top players are fixed-height scroll boxes (no CLS).
- Support card (`kmpr_am_render_support_card`): kicker, «حمایت صرف این‌ها می‌شود» (`am_support_uses`), optional suggested amounts (`am_support_tiers` amount|label, `{amount}` in the URL), pass link (sub on), thank-you state 30 days after a click (`kmpr_am_thx`), subscribers (`html.is-sub`) see a member thank-you instead of asks. Hub: wide card at the end of the games tab (`after_rails`); single game: after the scores in the sidebar.
- `inc/games-promo.php` + `template-parts/games/promo.php`: «یک دست بازی» strip on 404 + end of articles + `[kmpr_games count title games]`; settings «بازی‌ها › تنظیمات آرشیو › گیم‌سنتر در سایت» (master + per-place switches, games, categories). The 404 nudge popup is skipped when the strip is shown.

## 2026-11-24 — 3 new games (seeder `$ver = 8`)
- `mench` «منچ» (iranian, `kind: 'match'`, AI easy/medium/«زرنگ» + local, option 2/4 players; no online — the server has no move validator for it). DOM board: SVG background (`boardSvg()`), pieces = absolutely positioned `.mn-tok`, dice in the centre (≥44px). Tap anywhere near a piece = nearest movable one; keys Space/Enter + 1–4. Rules: 6 = exit + extra roll, capture (not on ★/start squares) = extra roll, finishing = extra roll, three 6s burn the turn. AI timers hold while paused. Score only vs AI (400/900/1600 × 1.5 for 4P + captures).
- `server-ping` «پینگ سرور» (arcade, canvas): one-thumb timing ring — tap while the packet is in the port; perfect = centre third; ports shrink, golden ports, drift from level 4.
- `bits` «صفر و یک» (puzzle): binary logic grid (Takuzu-style), generator + unique-solution solver in the engine, 8 boards 6×6 → 8×8, per-board clock, hint (−20 s).
- Each engine sets `stage.kmprDebug = inst` (read-only QC hook). Tests: `tools/game-qc/new-games/<slug>.test.js` (`NODE_PATH=../../korosh-qc/node_modules node mench.test.js`), screenshots in `new-games/out/`.

## 2026-11-24 — «نقاش‌باشی» `naqqash` (online draw & guess, 2–8 players, seeder `$ver = 9`)
- Files: `engines/naqqash.js` (DOM + one canvas, own lobby/podium; `kind: 'match'`, `online: true`, `noAutoPause`, `api._matchUI.create/open` for `?create=1` + mode button, `?room=CODE` joins), `tailwind/custom/games/naqqash.css` (container queries: portrait stack vs wide board|tools|side), `inc/games-draw.php` (REST + rules), `inc/data/naqqash-words.json` (280 words, 4 categories × easy/medium/hard, `a|b` = accepted variants). Category `casual`.
- Storage: room meta/state in the shared `kmpr_game_rooms` table (engine `naqqash`, so the hub lobby lists public rooms and the hourly cleanup deletes them) but NOT in `kmpr_game_room_handlers` (generic join/move refuse it). Own tables `kmpr_draw_ev` (strokes/chat/system events, since-id cursor) + `kmpr_draw_seen` (per-seat heartbeat); old rows removed on the `kmpr_games_cleanup` cron, strokes deleted at game end.
- REST `kmpr/v1/games/draw/{create|quick}`, `/{CODE}/join`, `GET|POST /{CODE}` (poll + every action `a=draw|guess|pick|start|opts|again|leave`; header `X-Room-Token`; params `since`, `v`). Reply `{v, now, cur, ev:[[id,k,seat,d,vis?]], on, st?}` — `st` only when the version changed. Timers, hints (45/65/82 %), drawer-left skip, host hand-over are lazy "ticks" on any request.
- Anti-cheat: words chosen + checked server-side; view has `word` only for the drawer / players who guessed / after the turn, `mask` (`_` hidden, `|` half-space) otherwise; near misses answered privately (`hit:'near'`), never broadcast; guessed players' chat is private (vis=1) and can't contain the word; drawer can't chat while drawing. Normalisation: ي/ك/ة/آ, diacritics, half-space, spaces, punctuation, Persian digits.
- Settings: «بازی‌ها › تنظیمات آرشیو» group «نقاش‌باشی»: `nq_on` (DEFAULT OFF until the full 3-browser test passes; off → REST 503 + in-game message), `nq_public` (quick match), `nq_time`, `nq_rounds`, `nq_max`. Friends picker: filter `kmpr_friends_invite_engines`; in-game «دعوت دوستان» when `window.kmprFriends.available`.
- Test: `tools/game-qc/new-games/naqqash.test.js` (3 browser contexts, full 2-round game, leak check, req/bytes per minute).

## 2026-11-25 — hub on phones = game app (< 1024px; desktop unchanged)
- `tailwind/custom/games/hub-mobile.css`: short spotlight, «الان چی بازی کنم؟» as one swipe row, sticky glass genre chips (`.gc-chipbar[data-gc-chips]`), only «ادامه بازی» + «تازه‌ها» rails (app-icon size; rails get `.gc-rail--{new|hot|online|genre|term}`), then ONE app-icon grid `.gc-all` (all game links in HTML; `li[data-cats="slugs [online]"]`). HUD shows only in «رتبه‌ها»; one money block (`after_hero` pass hidden when `after_rails` exists); B2B CTA = one line `.gc-cta__line`.
- `hub.js gridFilter()`: chips filter the grid on phones only (desktop chips stay genre-page links), hash `#cat-<slug>`; dock/header «پروفایل» on phones opens the profile editor.
- Dock (`template-parts/games/dock.php`): glass pill بازی‌ها/لابی/رتبه‌ها/پروفایل, auto-hide on scroll down (inline script, `.is-away` = transform only). `.gc-pane` gets its view-transition-name only during a tab switch (`html.gc-vt`) — a permanent name kills the chips' backdrop blur.
