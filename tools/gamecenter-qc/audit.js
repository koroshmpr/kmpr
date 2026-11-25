// Game Center audit: screenshots + console errors + overflow + CLS at several widths.
// Usage: NODE_PATH=../korosh-qc/node_modules node audit.js <tag> [path ...]
const puppeteer = require('puppeteer-core');
const path = require('path');
const BASE = process.env.BASE || 'http://localhost:10039';
const tag = process.argv[2] || 'before';
const paths = process.argv.slice(3).length ? process.argv.slice(3) : ['/games/', '/games/#lobby', '/games/#ranks', '/games/neon-snake/', '/no-such-page-xyz/'];
const widths = (process.env.W || '360,390,768,1280,1440').split(',').map(Number);
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new', args: ['--no-sandbox'] });
  for (const p of paths) for (const w of widths) {
    const pg = await b.newPage();
    const errs = [];
    pg.on('console', m => { if (m.type() === 'error') errs.push(m.text().slice(0, 160)); });
    pg.on('pageerror', e => errs.push('PAGEERR ' + e.message.slice(0, 160)));
    await pg.setViewport({ width: w, height: w < 800 ? 780 : 900, isMobile: w < 800, hasTouch: w < 800, deviceScaleFactor: 1 });
    const now = Date.now(), D = 864e5;
    const seed = process.env.FRESH ? { id: '11111111-2222-4333-8444-555555555555', nickname: 'QC', games: {} } : { id: '11111111-2222-4333-8444-555555555555', nickname: 'QC', games: { snake: { plays: 4, best: 320, ach: [], at: now - 3600e3 }, xo: { plays: 2, best: 0, ach: [], at: now - D } }, recent: [{ e: 'snake', s: 320, t: now - 3600e3 }, { e: 'snake', s: 120, t: now - D }, { e: 'xo', type: 'match', r: 'win', t: now - 2 * D }] };
    await pg.evaluateOnNewDocument((process_seed) => {
      window.__cls = 0;
      new PerformanceObserver(l => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__cls += e.value; }).observe({ type: 'layout-shift', buffered: true });
      try { if (true || !localStorage.getItem('kmpr_arcade_v1')) localStorage.setItem('kmpr_arcade_v1', JSON.stringify(process_seed)); localStorage.setItem('kmpr_pwa', JSON.stringify({ snooze: Date.now() + 864e5 })); } catch (e) {}
    }, seed);
    const t0 = Date.now();
    await pg.goto(BASE + p, { waitUntil: 'networkidle2', timeout: 45000 }).catch(e => errs.push('GOTO ' + e.message));
    const load = Date.now() - t0;
    await new Promise(r => setTimeout(r, 1200));
    const info = await pg.evaluate(() => {
      const de = document.documentElement;
      const wide = [];
      document.querySelectorAll('body *').forEach(el => { const r = el.getBoundingClientRect(); if (r.width && (r.right > de.clientWidth + 1 || r.left < -1) && getComputedStyle(el).position !== 'fixed' && !el.closest('.gc-noscroll,.gc-rail__track,[aria-hidden="true"],.gs-mq')) wide.push((el.className && el.className.toString().slice(0, 40)) || el.tagName); });
      const small = [];
      document.querySelectorAll('a,button').forEach(el => { const r = el.getBoundingClientRect(); const cs = getComputedStyle(el); if (r.width && r.height && cs.visibility !== 'hidden' && (r.height < 32 || r.width < 32) && el.offsetParent && !el.closest('p,li.text-sm,nav[aria-label="مسیر صفحه"],footer')) small.push((el.textContent || el.getAttribute('aria-label') || '').trim().slice(0, 20) + '@' + Math.round(r.width) + 'x' + Math.round(r.height)); });
      return { sw: de.scrollWidth, cw: de.clientWidth, h: de.scrollHeight, cls: +window.__cls.toFixed(3), wide: [...new Set(wide)].slice(0, 6), small: [...new Set(small)].slice(0, 8) };
    });
    const name = `${tag}-${p.replace(/[^a-z0-9]+/gi, '_').replace(/^_|_$/g, '') || 'home'}-${w}.png`;
    const shots = +(process.env.SHOTS || 3);
    for (let i = 0; i < shots; i++) {
      const vh = await pg.evaluate(() => innerHeight);
      await pg.evaluate((y) => window.scrollTo(0, y), i * vh * 0.9);
      await new Promise(r => setTimeout(r, 700));
      await pg.screenshot({ path: path.join(__dirname, 'out', name.replace('.png', '-' + i + '.png')) }).catch(() => {});
    }
    console.log(JSON.stringify({ p, w, load, ...info, errs: errs.slice(0, 5) }));
    await pg.close();
  }
  await b.close();
})();
