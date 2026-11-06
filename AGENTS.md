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
