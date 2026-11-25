# Site QC results (2026-11)

CLS with a slow cold load (`cls.js --slow`: about 1.6 Mbps, 4× CPU), desktop 1366×900 / phone 390×844:

| page | before (d) | after (d) | phone before → after |
|---|---|---|---|
| home | 0.0054 | 0 | 0 → 0 |
| services | 0.0121 | 0 | 0 → 0 |
| service | 0.0137 | 0 | 0 → 0 |
| portfolios | 0.0404 | 0.0002 | 0 → 0 |
| portfolio | 0.0162 | 0 | 0 → 0 |
| article | 0.0194 | 0.0002 | 0 → 0 |
| about | 0.0109 | 0 | 0 → 0 |
| contact / blog / course | 0 / 0 / 0.0001 | 0 | 0 → 0 |

The phone CLS was already 0, yet the visible jump was there: in the filmstrips (`out/film-film-*`) the hero heading wrapped to 3 lines in the system font, then to 2 when Yekan Bakh arrived. That reflow is gone in `out/film-film2-*`.

Lighthouse baseline (`out/lh-before.log`): mobile LCP 2.9–3.3 s, FCP 1.5–1.7 s, desktop scores 92–100. The machine was overloaded at the time (load average 60–110, TTFB up to 8 s), so the TBT and TTFB figures are noise. There was no clean "after" run.
Blocking CSS is 79 KB gzip, and the game-center CSS is about 41 KB of that.
