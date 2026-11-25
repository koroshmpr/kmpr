// Element screenshots. Usage: NODE_PATH=../korosh-qc/node_modules node shot.js <tag> <width> <path> <selector> [js-before]
const puppeteer = require('puppeteer-core');
const [tag, w, p, sel, pre] = process.argv.slice(2);
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const pg = await b.newPage();
  await pg.setViewport({ width: +w, height: 900, isMobile: +w < 800, hasTouch: +w < 800 });
  await pg.evaluateOnNewDocument(() => { localStorage.setItem('kmpr_arcade_v1', JSON.stringify({ id: '11111111-2222-4333-8444-555555555555', nickname: 'QC', games: {} })); localStorage.setItem('kmpr_pwa', JSON.stringify({ snooze: Date.now() + 864e5 })); });
  const errs = []; pg.on('pageerror', e => errs.push(e.message));
  await pg.goto('http://localhost:10039' + p, { waitUntil: 'networkidle2', timeout: 90000 });
  if (pre) await pg.evaluate(pre);
  const el = await pg.$(sel);
  if (!el) { console.log('no element', sel); await b.close(); return; }
  await el.evaluate(e => e.scrollIntoView({ block: 'center' }));
  await new Promise(r => setTimeout(r, 900));
  await el.screenshot({ path: `${__dirname}/out/${tag}-${w}.png` });
  console.log('ok', tag, errs);
  await b.close();
})();
