// Which element sits on top of a selector's center. Usage: NODE_PATH=../korosh-qc/node_modules node hit.js <path> <selector> <width>
const puppeteer = require('puppeteer-core');
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const pg = await b.newPage(); const w = +process.argv[4] || 390;
  await pg.setViewport({ width: w, height: 900, isMobile: w < 800, hasTouch: w < 800 });
  await pg.evaluateOnNewDocument(() => localStorage.setItem('kmpr_pwa', JSON.stringify({ snooze: Date.now() + 864e5 })));
  await pg.goto('http://localhost:10039' + process.argv[2], { waitUntil: 'networkidle2' });
  console.log(await pg.$eval(process.argv[3], (e) => { e.scrollIntoView({ block: 'center' }); const r = e.getBoundingClientRect(); const h = document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2); return JSON.stringify(r) + ' -> ' + (h && (h.outerHTML || '').slice(0, 200)); }));
  await b.close();
})();
