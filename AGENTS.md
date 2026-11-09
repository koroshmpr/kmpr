# Deployment & Quality Control (QC) Rules

## Mandatory Quality Gate Before Any Server Deployment

Before pushing any commit to `main`, `master`, or deploying to the server, the agent MUST systematically review and verify all changes against the following 4 pillars:

### 1. ظاهر و هماهنگی بصری (Appearance & Visual Polish)
- **Buttons & CTAs:** Never allow button text to break into awkward vertical lines. Always ensure buttons have `whitespace-nowrap`, proper padding (`px-6 py-3` or `px-8 py-3.5`), and `rounded-full` or `rounded-2xl`.
- **SVG & Icons:** Always set explicit HTML `width` and `height` attributes on SVGs (e.g. `width="16" height="16"`) and add `class="shrink-0"` to prevent the browser default 300x150px expansion bug in flex containers.
- **Contrast & Legibility:** Ensure high contrast for all text (slate-100/200 on dark backgrounds, slate-800/900 on light backgrounds).
- **Layout Balance:** Prevent lopsided layouts where wide screens show massive empty voids. Use centered or well-proportioned 2-column grid structures.

### 2. سئو و ساختار استاندارد (SEO & Semantics)
- Semantic HTML tags (`<article>`, `<nav>`, `<aside>`, `<h1>`, `<h2>`, `<h3>`).
- Schema.org JSON-LD structured data on single post and archive templates.
- Crawlable standard pagination with `paginate_links()` (never JS-only traps).
- Persian Jalali dates rendered natively with `shamsi_date()` without external plugin dependencies.
- Descriptive `alt` attributes on all images.

### 3. ارور و پایداری بیلد (Error-Free Verification)
- Run `npm run production` to ensure CSS and JS build cleanly without errors.
- Ensure all Tailwind classes used in templates are scanned and compiled in `public/css/style.css`.
- In `.github/workflows/deploy.yml`, ensure any deleted or removed directories are excluded so that `FTP-Deploy-Action` does not crash with `550 Can't change directory`.

### 4. ریسپانسیو و مقیاس‌پذیری (Responsiveness)
- Verify mobile layouts (360px to 480px), tablet (768px), and desktop (1024px to 1440px+).
- Prevent horizontal scrollbars (`overflow-x-hidden` or `overflow-x-clip`).
- Mobile touch targets must be comfortably clickable (minimum 40-44px).


### 5. بودجه گیت‌هاب و دیپلوی بهینه (GitHub Actions Budget)
Every push to `main` that touches `themes/kmpr/**` runs the deploy workflow. Keep runs few, short and purposeful:
- **Batch, don't drip:** collect related changes into one or a few commits and push once per finished task — never push after every small edit. Several commits pushed together = one workflow run.
- **Separate deploys per topic:** don't mix unrelated work (e.g. games vs blog/content) in one push; commit and push only the files of the task at hand (`git add <paths>`, never `git add -A` when other work is uncommitted). If the working tree has unrelated uncommitted changes that affect the build, build and commit from a clean `git worktree` so their CSS/JS never leaks into the deploy.
- **Build locally, commit the output:** run `npm run production` before committing; `public/css` and `public/js` are committed. CI re-runs `npm ci` + the build **only** when `javascript/`, `tailwind/`, `tailwind.css`, `postcss.config.mjs` or `package*.json` changed (manual run with `force_build` to override). PHP/content-only pushes skip Node entirely.
- **Never trigger CI for nothing:** changes outside `themes/kmpr/` (`AGENTS.md`, `tools/`, docs) and `*.md` files don't deploy. For a theme commit that must not deploy yet, add `[skip ci]` to the commit message.
- **Guards stay on:** keep `concurrency` (one deploy at a time, never cancelled mid-upload; only the newest queued push runs next), `timeout-minutes: 15`, the `node_modules` cache and `log-level: minimal` in `deploy.yml`. Check run time with `gh run list -L 5` after pushing; a normal PHP/content deploy should take well under a minute.
- **Content travels as packs, not as builds:** courses/lessons/SEO meta/covers go into `themes/kmpr/content/packs/*.json` + `content/media/` (built with `tools/content/build-pack.py`, covers with `tools/covers/generate.py`) and are applied on the server once per file hash by `inc/content-packs.php`. Adding content never needs an asset rebuild.

### 6. محتوای دوره‌ها و مقالات (Course & Article Content)
- A course = a category (ACF `course_title`, `course_intro`, `course_color` ∈ amber|sky|emerald|violet|rose); a lesson = a post with `lesson_order` (0 = pillar with `is_pillar`), `difficulty`, `tldr`, `key_takeaways`, `post_faq`, Yoast title (≤ 60 chars) / description (130–155 chars) / focus keyword, primary category and a 1600×900 webp cover with descriptive alt.
- Pillar links every lesson; every lesson links its pillar, previous and next lesson and at least one relevant lesson from another course. No invented statistics, prices or quotes.
- **In-article banners are mandatory in every lesson** — use the `[kmpr_banner]` shortcode (editor button «بنر», `inc/article-banners.php`):
  - 1 banner after the 2nd section (`before_h2: 3`) pointing to the most relevant **service**, **course** or **portfolio**; lessons with 6+ `<h2>` get a 2nd banner before the last-but-one `<h2>` (a related **game**, portfolio project or another course). Max 2 per lesson, never inside a list/table, never right after the intro or inside «جمع‌بندی».
  - Choose by relevance, not rotation: speed → `speed-optimization`, SEO → `seo`, maintenance/security → `website-support`, integrations → `api-integration`, design/build → `website` / `landing-page` / `online-store`; dev-themed games (`bug-hunter`, `type-code`, `hex-hunter`, `stack-memory`, `pixel-runner`) only where the topic fits.
  - Ads only through `type="ad"` (slots in «تنظیمات سایت → بنرهای مقالات», with active flag and end date); they are labelled «تبلیغ» and get `rel="sponsored"` automatically — never hand-write ad links inside the text.
  - In packs, banners are `inject` records (`[{"html":"[kmpr_banner type=\"service\" item=\"seo\"]","before_h2":3}]`; `build-pack.py` reads a lesson's `banners` array) so they survive re-applies; existing posts get them via `content/packs/post-banners.json`.
- Packs upsert by slug: editing a lesson in wp-admin is fine, but re-deploying a changed pack file overwrites the fields it contains — make lasting changes in the pack.
