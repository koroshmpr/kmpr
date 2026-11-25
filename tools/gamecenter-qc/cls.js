// Usage: NODE_PATH=../korosh-qc/node_modules node cls.js <path> <width> — lists layout-shift sources.
const puppeteer = require('puppeteer-core');
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const pg = await b.newPage(); const w = +process.argv[3] || 390;
  await pg.setViewport({ width: w, height: 800, isMobile: w < 800, hasTouch: w < 800 });
  await pg.evaluateOnNewDocument(() => { window.__s = []; localStorage.setItem('kmpr_arcade_v1', JSON.stringify({ id: '11111111-2222-4333-8444-555555555555', nickname: 'QC', games: {} })); localStorage.setItem('kmpr_pwa', JSON.stringify({ snooze: Date.now() + 864e5 }));
    new PerformanceObserver(l => { for (const e of l.getEntries()) window.__s.push({ v: +e.value.toFixed(3), t: Math.round(e.startTime), src: (e.sources || []).map(s => (s.node && (s.node.className || s.node.nodeName) || '?').toString().slice(0, 50) + ' ' + JSON.stringify([s.previousRect.y, s.currentRect.y, s.previousRect.height, s.currentRect.height])) }); }).observe({ type: 'layout-shift', buffered: true }); });
  await pg.goto('http://localhost:10039' + process.argv[2], { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1500));
  console.log(JSON.stringify(await pg.evaluate(() => window.__s), null, 1));
  await b.close();
})();
