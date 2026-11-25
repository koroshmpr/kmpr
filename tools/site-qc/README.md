# site-qc — main-site QC / speed / layout-shift tools

Headless Chrome scripts for the non-game site (local `http://localhost:10039`, override with `BASE=`).
puppeteer-core comes from `../korosh-qc/node_modules`; Lighthouse is installed here (`npm install`).
Output goes to `out/` (git-ignored).

| script | what it does | run |
|---|---|---|
| `cls.js` | CLS + every layout-shift source per page at 390×844 / 1366×900. `--slow` = cold cache, ~1.6 Mbps, 4× CPU; `--film` = screencast filmstrip of the first ~1.5 s | `NODE_PATH=../korosh-qc/node_modules node cls.js <tag> [--slow] [--film] [--pages=home,article]` |
| `cfg.js` | Project-request wizard walkthrough at 360/390/768/1366: screenshot per step, card vs viewport, inner scroll, inputs < 16px. `--submit` sends a real request (local only: no Telegram token → stored as `project_request` + mail to Local's Mailpit) | `NODE_PATH=../korosh-qc/node_modules node cfg.js <tag> [--submit]` |
| `qc.js` | AGENTS.md sweep at 360/390/768/1024/1440: full-page screenshots, horizontal overflow (+ culprit), console errors, `<img>` without alt, `<svg>` without width/height, wrapped CTA text, tap targets < 32px | `NODE_PATH=../korosh-qc/node_modules node qc.js <tag> [--pages=…] [--widths=…] [--noshot]` |
| `lh.js` | Lighthouse performance (mobile + desktop presets, simulated throttling) for the key pages → markdown table | `node lh.js <tag> [page…]` |
| `lh1.js` | One Lighthouse run with LCP breakdown, render-blocking / unused bytes | `node lh1.js /blog/seo-complete-guide/ [desktop]` |
| `lcp.js`, `trace.js`, `anims.js`, `longtasks.js` | LCP candidates, main-thread style/layout breakdown (4× CPU), running CSS animations, long tasks | `NODE_PATH=../korosh-qc/node_modules node <script> <path>` |

Results of the 2026-11 pass: `RESULTS.md`.
