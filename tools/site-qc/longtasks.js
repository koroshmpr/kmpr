// Long-task total (TBT-like) over 8s with 4x CPU throttle, mobile viewport. Usage: node longtasks.js <path> [--no-canvas]
const puppeteer = require('puppeteer-core');
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const p = await b.newPage();
  await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  const c = await p.createCDPSession(); await c.send('Emulation.setCPUThrottlingRate', { rate: 4 });
  await p.evaluateOnNewDocument((noCanvas) => {
    window.__lt = []; new PerformanceObserver((l) => l.getEntries().forEach((e) => window.__lt.push([Math.round(e.startTime), Math.round(e.duration)]))).observe({ type: 'longtask', buffered: true });
    if (noCanvas) HTMLCanvasElement.prototype.getContext = function () { throw new Error('blocked'); };
  }, process.argv.includes('--no-canvas'));
  await p.goto('http://localhost:10039' + (process.argv[2] || '/'), { waitUntil: 'load' });
  await new Promise((r) => setTimeout(r, 8000));
  const lt = await p.evaluate(() => window.__lt);
  const tbt = lt.reduce((s, [, d]) => s + Math.max(0, d - 50), 0);
  console.log('longtasks', lt.length, 'blocking', tbt, 'first', JSON.stringify(lt.slice(0, 12)));
  await b.close();
})();
