// Support card: click → thank-you state; html.is-sub → member line. Usage: NODE_PATH=../korosh-qc/node_modules node thanks.js
const puppeteer = require('puppeteer-core');
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const pg = await b.newPage(); pg.on('pageerror', e => console.log('ERR', e.message)); pg.on('console', m => console.log('LOG', m.text().slice(0,150)));
  await pg.setViewport({ width: 390, height: 900, isMobile: true, hasTouch: true });
  await pg.evaluateOnNewDocument(() => { localStorage.setItem('kmpr_pwa', JSON.stringify({ snooze: Date.now() + 864e5 })); });
  await pg.goto('http://localhost:10039/games/', { waitUntil: 'networkidle2' });
  const card = await pg.$('.am-hub--after_rails .am-sup');
  await card.evaluate(e => e.scrollIntoView({ block: 'center' }));
  const a = await pg.$('.am-hub--after_rails .am-sup .am-btn--gold');
  console.log('href', await a.evaluate(e => e.href + ' ' + e.target));
  console.log('kmprAM', await pg.evaluate(() => JSON.stringify(window.kmprAM)));
  await a.evaluate(e => { e.addEventListener("click", ev => ev.preventDefault(), { once: true }); e.click(); });
  console.log('ls', await pg.evaluate(() => localStorage.getItem('kmpr_am_thx')));
  await new Promise(r => setTimeout(r, 1200));
  console.log('thanks visible', await pg.$eval('.am-hub--after_rails [data-am-thanks]', e => !e.hidden));
  await card.screenshot({ path: __dirname + '/out/sup-thanks-390.png' });
  await pg.evaluate(() => document.documentElement.classList.add('is-sub'));
  await card.screenshot({ path: __dirname + '/out/sup-member-390.png' });
  await pg.reload({ waitUntil: 'networkidle2' });
  console.log('thanks after reload', await pg.$eval('.am-hub--after_rails [data-am-thanks]', e => !e.hidden));
  await b.close();
})();
