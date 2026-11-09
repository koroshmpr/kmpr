# Game Center (گیم‌سنتر) — context for agents

**Before creating or changing any game, read and follow [GAME-RULES.md](GAME-RULES.md) (theme/identity, categories, characters, controls, sound, stability, performance, 6-place registration, mandatory browser test).**

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

## 2026-11-10 (story)
- `korosh-run` renamed «کوروش دیباگر» (slug kept, seeder `$ver = 7`). Comic story in `engines/korosh-story.js`: INTRO (4 panels, first play only, flag `kmpr_kr_intro`; «📖 داستان» on the map), one panel before each of the 12 stages (`STAGES`), OUTRO before the win screen. Panels = canvas scenes + DOM caption/bubble (`.kr-comic` in sport-puzzle.css).
- Hero (lib/hero.js) now: afro with round coil texture, defined face (straight nose, natural lips, proper ear), shirt-collar with shadow/top-stitch, flared sleeves, long loose shirt with curved shirt-tail hem that sways, coffee held upright with a bent elbow (cup counter-rotated).
- Story uses the owner's real projects (Kaleh «ماست سون» night/lottery, Rozha treasure map, X-Vision contest, Khanesarmaye live prices); worlds renamed: سایت کاله / سرورهای قرعه‌کشی / کویر نقشه گنج / برج قیمت‌های زنده.
- In game the hero is drawn at `HERO_FIT = 0.74` around the feet so the afro fits the 32px hitbox (no clipping into ceilings). No ear (owner's choice). `drawHero(..., tired)` 12th arg: glasses slide down, half-closed eye, droop, yawns; each stage starts sleepy for `LAZY` = 2.6 s (slower), comic panels show him tired.
- Comic = manga page (B/W action panel + reaction close-up `drawClose`, page-turn, typewriter captions, SFX lettering). Enemies: bug = side-view glitch beetle `drawBeetle` (shared with comics); new virus/turret art.
- Hacker (`s.hk`, updateHacker): warns 1.6 s, runs across, steals ≤10 coins + plants a bug; stomp/shoot = +1000 & coins back. Programmer one-liners `QUIPS` (speech bubble, cooldown); death = spin + halo ghost + `die` quip.
- Side stories (not in progress; n ≥ 100): 100 L90 drive (auto-run, 3 car HP), 101 Maedeh's street (roses refill «حوصله مائده»), 102 war mission Bumehen→Pasdaran in the cousin's 207 (brake/gas, bomb warnings). Map cards unlock at progress ≥3 / ≥6; saves `kmpr_kr_side`, `kmpr_kr_war`.
- After stage 5 (Kaleh lottery boss): Makan party cutscene `AWARD` (Negar, Shayan, Maedeh, Mohammad-Hossein); replay «🏆 جشن ماکان» on the map.
- Shayan corner cameo (`drawShayan` caricature, `cameo()`): ≤1 per stage, only at good moments (2nd power-up, 7 kills, hacker caught, perfect deploy, rarely at the sleepy start).
- TODO (owner asked): in-engine stage-intro animations (getting out of bed + bag, working from home, coffee delivery → start).
- Side 103 «زیپ کیف · منوچهری» (`buildRain`): rain + lightning, puddles slow you down, timed work tasks (📌 bug spawns ahead, 11 s to debug it, late = hurt). Unlocks at progress ≥4, save `kmpr_kr_rain`.
- Article images (blog commit c4c3339): author photo + default cover are ACF options «تصاویر مقالات» (Media Library); pack covers are deleted from the theme on the server after import.
- Chapter «پند پلاس» (104–106, save `kmpr_kr_pand` 0..3, unlocks at progress ≥3): 104 morning traffic → «پارکینگ پند» (drive, 110 s), 105 Trello QC (`buildQC`, Yousef's QC cards fly in — collect them; spawn rate rises with `qcDone`, Yousef bust + lines bottom-left), 106 boss «آقای بخشی» (`buildPandBoss`, boss type `bakhshi`, bar shows «رتبه سایت»). Cast in korosh-story.js: `drawYousef`, `drawPerson(…,'bakhshi'|'yousef'|'shayan'|'negar'|'mh')`.
- Chapter «بیورزونانس» (107–109, save `kmpr_kr_bio`, always open — chronologically first): 107 OCR puzzle overlay `showOCR` (Russian medical lines, tap look-alike chars, 5 pages, pass ≥70%), 108 «۲۵٬۰۰۰ مقاله» (`buildArticles`, Hossein walker foe, article counter, bulk-upload finale), 109 skill books + boss «آقای عزیزی» (`buildBioBoss`, bar = trust %). Cast: `shayan2` (second friend Shayan), `hossein`, `azizi`.
- Chapter «سربازی» (110–112, save `kmpr_kr_army`): 110 courier (`buildCourier`, 3 garrison gates with mailboxes, goal locked until all delivered, olive uniform form `army`), 111 Shahriar fuel bluff dialog `showDialog` (2 slips = fail), 112 bus run to «پادگان ۲۲۱ مراغه» (drive, model `bus`) → outro: teacher's 100/100 (real 130).
- MAP ORDER = owner's real chronology (renderMap `mainW()`): ۱ سربازی → ۲ بیورزونانس → ۳ پند پلاس → ۴ ماکان (Kaleh worlds 1–2 + Maedeh) → ۵ گیلدا → ۶ خانه سرمایه → ۷ جنگ و شبا (Pasdaran mission) → ۸ زیپ کیف. Chapters 2/3 unlock after the previous one; war after main progress ≥9; zip after war. Main worlds stay open from the start.
