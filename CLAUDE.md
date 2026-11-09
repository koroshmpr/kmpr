# KMPR — personal site + Game Center (WordPress, wp-content repo)

@AGENTS.md

## Fast orientation (read this instead of exploring)
- Repo root = `wp-content/`. Everything custom lives in the theme `themes/kmpr/` (Tailwind v4 + esbuild, no page builder). Plugins: ACF Pro, ACF Extended, Gravity Forms, Yoast, Perfmatters, Classic Editor. **WooCommerce is NOT installed** — don't add it.
- Language/UI: Persian, RTL. Talk to the owner in Persian; code comments in English.
- PHP entry: `themes/kmpr/functions.php` → `require_once KMPR_INC . '*.php'` (one file per feature in `inc/`).
- Settings are ACF options pages; helpers `kmpr_field()`, `kmpr_acf()`, `kmpr_acf_tab()`, `kmpr_lines()`, `kmpr_fa_num()`, `kmpr_latin_digits()`, `shamsi_date()`.
- Owner Telegram notifications: `kmpr_tg_send($html)` (guard with `function_exists('kmpr_tg_token') && kmpr_tg_token()`).
- Owner mobile panel: `inc/owner-panel*` + `javascript/owner-panel/`.

## Build / deploy
- `cd themes/kmpr && npm run production` → `public/css/style.css`, `public/js/*` (games bundle: `public/js/games/`, hashed chunks are rebuilt — commit deletions too).
- Separate IIFE bundles: `arcade-monetize.js`, `arcade-sub.js`, `owner-panel.js` (scripts `*:monetize`, `*:panel` in package.json).
- Deploy = push to `main` → `.github/workflows/deploy.yml` (FTP). Commit only when asked; follow the QC gate in AGENTS.md.
- Local site: Local by Flywheel `kmpr.local` (often not running; then verify with `php -l` + build).

## Game Center
See `themes/kmpr/javascript/games/CLAUDE.md` (architecture, engine contract, subscription, status, backlog).

## Working preferences (owner)
- Token budget is tight: read targeted line ranges, don't re-explore; batch edits; verify with build + `php -l`.
- Everything new must be switchable from the dashboard and degrade gracefully.
