// Print LCP candidates (time, element, size) for a page at phone size. Usage: node lcp.js <path> [--desktop]
const puppeteer = require('puppeteer-core');
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const p = await b.newPage();
  const d = process.argv.includes('--desktop');
  await p.setViewport(d ? { width: 1366, height: 900 } : { width: 390, height: 844, isMobile: true, hasTouch: true });
  await p.evaluateOnNewDocument(() => { window.__lcp = []; new PerformanceObserver((l) => l.getEntries().forEach((e) => { const el = e.element; window.__lcp.push([Math.round(e.startTime), e.size, el ? el.tagName + '.' + String(el.className).slice(0, 60) + ' «' + (el.textContent || '').trim().slice(0, 30) + '»' + (e.url ? ' ' + e.url.slice(-50) : '') : '?']); })).observe({ type: 'largest-contentful-paint', buffered: true }); });
  if (process.argv.includes('--reduce')) await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
  await p.goto('http://localhost:10039' + process.argv[2], { waitUntil: 'load' });
  await new Promise((r) => setTimeout(r, 3000));
  console.log(await p.evaluate(() => window.__lcp));
  await b.close();
})();
