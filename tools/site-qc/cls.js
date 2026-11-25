// CLS + early filmstrip + shift sources. Usage: NODE_PATH=../korosh-qc/node_modules node cls.js [tag] [--film]
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const BASE = process.env.BASE || 'http://localhost:10039';
const PAGES = {
  home: '/', services: '/services/', service: '/services/api-integration/',
  portfolios: '/portfolios/', portfolio: '/portfolios/traders-dastyar/',
  blog: '/blog/', article: '/blog/telegram-mini-app-guide/', about: '/about-us/',
  contact: '/contact-us/', course: '/blog/category/seo-course/',
};
const VPS = { m: { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 1 }, d: { width: 1366, height: 900, deviceScaleFactor: 1 } };
const tag = process.argv[2] || 'run';
const film = process.argv.includes('--film');
const only = (process.argv.find((a) => a.startsWith('--pages=')) || '').slice(8).split(',').filter(Boolean);
(async () => {
  const browser = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new', args: ['--no-sandbox'], timeout: 120000, protocolTimeout: 180000 });
  const rows = [];
  for (const [name, path] of Object.entries(PAGES)) {
    if (only.length && !only.includes(name)) continue;
    for (const [vk, vp] of Object.entries(VPS)) {
      const page = await browser.newPage();
      await page.setViewport(vp);
      if (vp.isMobile) await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
      const errors = [];
      page.on('pageerror', (e) => errors.push(e.message));
      page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
      await page.evaluateOnNewDocument(() => {
        window.__cls = 0; window.__shifts = [];
        new PerformanceObserver((l) => l.getEntries().forEach((e) => {
          if (e.hadRecentInput) return;
          window.__cls += e.value;
          window.__shifts.push({ t: Math.round(e.startTime), v: +e.value.toFixed(4), src: (e.sources || []).map((s) => {
            const n = s.node; if (!n) return '?';
            const el = n.nodeType === 1 ? n : n.parentElement;
            return (el ? el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).slice(0, 3).join('.') : '') : '?') + ` ${s.previousRect.y}->${s.currentRect.y} h${s.previousRect.height}->${s.currentRect.height}`;
          }) });
        })).observe({ type: 'layout-shift', buffered: true });
      });
      if (process.argv.includes('--slow')) {
        const c2 = await page.createCDPSession();
        await c2.send('Network.enable'); await c2.send('Network.setCacheDisabled', { cacheDisabled: true });
        await c2.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 1.6e6 / 8 * 1024 / 1000 * 1000, uploadThroughput: 750e3 / 8 });
        await c2.send('Emulation.setCPUThrottlingRate', { rate: 4 });
      }
      const frames = [];
      if (film) {
        const cdp = await page.createCDPSession();
        await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 50, everyNthFrame: 1 });
        cdp.on('Page.screencastFrame', async (f) => { frames.push(f.data); cdp.send('Page.screencastFrameAck', { sessionId: f.sessionId }).catch(() => {}); });
      }
      await page.goto(BASE + path, { waitUntil: 'load', timeout: 60000 }).catch((e) => errors.push('goto ' + e.message));
      await new Promise((r) => setTimeout(r, process.argv.includes('--slow') ? 6000 : 2500));
      const res = await page.evaluate(() => ({ cls: window.__cls, shifts: window.__shifts, sx: document.documentElement.scrollWidth - innerWidth }));
      if (film) {
        const dir = `${__dirname}/out/film-${tag}-${name}-${vk}`; fs.mkdirSync(dir, { recursive: true });
        frames.slice(0, 40).forEach((d, i) => fs.writeFileSync(`${dir}/${String(i).padStart(2, '0')}.jpg`, Buffer.from(d, 'base64')));
      }
      rows.push({ page: name, vp: vk, cls: +res.cls.toFixed(4), hscroll: res.sx, errors: errors.slice(0, 3), shifts: res.shifts.slice(0, 6) });
      await page.close();
    }
  }
  await browser.close();
  fs.writeFileSync(`${__dirname}/out/cls-${tag}.json`, JSON.stringify(rows, null, 1));
  rows.forEach((r) => console.log(`${r.page.padEnd(11)} ${r.vp} CLS=${r.cls} hscroll=${r.hscroll} ${r.errors.length ? 'ERR ' + r.errors.join(' | ').slice(0, 200) : ''}\n   ${r.shifts.map((s) => `@${s.t} ${s.v} ${s.src.join(', ')}`).join('\n   ')}`));
})();
