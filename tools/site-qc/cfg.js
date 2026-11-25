// Project-request wizard ("smart configurator") walkthrough: screenshots every step at phone/desktop sizes,
// reports whether each step fits the viewport, then (with --submit) sends a real request on LOCAL only
// (local has no Telegram token → stored as a project_request post + wp_mail to Local's Mailpit).
// Usage: NODE_PATH=../korosh-qc/node_modules node cfg.js <tag> [--submit]
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const BASE = process.env.BASE || 'http://localhost:10039';
const tag = process.argv[2] || 'run';
const submit = process.argv.includes('--submit');
const VPS = Object.fromEntries(Object.entries({ p360: [360, 640, true], p390: [390, 844, true], t768: [768, 1024, true], d1366: [1366, 900, false] }).filter(([k]) => !process.env.VP || process.env.VP.split(',').includes(k)));
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
(async () => {
  const browser = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new', args: ['--no-sandbox'], timeout: 120000, protocolTimeout: 180000 });
  const dir = `${__dirname}/out/cfg-${tag}`; fs.mkdirSync(dir, { recursive: true });
  for (const [vk, [w, h, mob]] of Object.entries(VPS)) {
    const page = await browser.newPage();
    await page.setViewport({ width: w, height: h, isMobile: mob, hasTouch: mob });
    const errs = []; page.on('pageerror', (e) => errs.push(e.message));
    await page.evaluateOnNewDocument(() => { try { localStorage.clear(); } catch (e) {} });
    await page.goto(`${BASE}/contact-us/#smart-configurator`, { waitUntil: 'networkidle2' });
    await sleep(1200);
    const info = async (n) => {
      const r = await page.evaluate(() => {
        const card = document.querySelector('#smart-configurator [x-ref="card"], #smart-configurator .rq-card');
        const vp = document.querySelector('#smart-configurator [x-ref="viewport"]');
        const c = card.getBoundingClientRect();
        const small = [...document.querySelectorAll('#smart-configurator input:not([type=radio]):not([type=hidden]), #smart-configurator textarea')].filter((i) => i.offsetParent && parseFloat(getComputedStyle(i).fontSize) < 16).length;
        const tiny = [...document.querySelectorAll('#smart-configurator button, #smart-configurator label')].filter((b) => b.offsetParent && b.getBoundingClientRect().height > 0 && b.getBoundingClientRect().height < 40).map((b) => b.textContent.trim().slice(0, 20));
        return { cardTop: Math.round(c.top), cardBottom: Math.round(c.bottom), vh: innerHeight, innerScroll: vp ? vp.scrollHeight - vp.clientHeight : null, smallInputs: small, tiny: tiny.slice(0, 5), hscroll: document.documentElement.scrollWidth - innerWidth };
      });
      await page.screenshot({ path: `${dir}/${vk}-step${n}.png` });
      console.log(vk, 'step', n, JSON.stringify(r));
    };
    await info(1);
    // step 1 → pick a type (auto-advances)
    await page.evaluate(() => document.querySelector('#smart-configurator button[aria-pressed]').click());
    await sleep(900); await info(2);
    const next = async () => { await page.evaluate(() => document.querySelector('#smart-configurator form button[type=submit]').click()); await sleep(900); };
    // pick a feature + budget if present
    await page.evaluate(() => { const s = document.querySelector('#smart-configurator'); const f = [...s.querySelectorAll('button[aria-pressed]')].find((b) => b.offsetParent && b.closest('[x-show]') && !b.querySelector('.size-10, .size-11')); if (f) f.click(); const r = [...s.querySelectorAll('input[name=budget]')].find((i) => i.offsetParent || i.closest('label').offsetParent); if (r) r.click(); });
    await next(); await info(3);
    const steps = await page.evaluate(() => window.Alpine.$data(document.getElementById('smart-configurator')).totalSteps);
    for (let s = 4; s <= steps; s++) { await next(); await info(s); }
    if (vk === 'p390') {
      await page.type('#rq-name', 'تست کیوسی');
      await page.focus('#rq-phone'); await sleep(300); await page.screenshot({ path: `${dir}/${vk}-focus-phone.png` });
      await page.type('#rq-phone', '09121234567');
      if (submit) {
        await sleep(4200); // time-trap
        const resP = page.waitForResponse((r) => r.url().includes('project-request'), { timeout: 15000 });
        await next();
        const res = await resP; console.log('submit HTTP', res.status(), await res.text());
        await sleep(2500); await page.screenshot({ path: `${dir}/${vk}-done.png` });
      }
    }
    if (errs.length) console.log(vk, 'JS errors', errs);
    await page.close();
  }
  await browser.close();
})();
