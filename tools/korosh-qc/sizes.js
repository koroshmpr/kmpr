const pp = require('puppeteer-core');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const VIEWS = [['phoneL', 844, 390, true, 52], ['phoneS-L', 667, 375, true, 52], ['tablet', 1024, 768, true, 64], ['laptop', 1280, 800, false, 64], ['desk', 1920, 1080, false, 64]];
(async () => {
  const br = await pp.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const report = [];
  for (const [name, w, h, touch, hb] of VIEWS) {
    const p = await br.newPage(); const errs = []; p.on('pageerror', (e) => errs.push(e.message));
    await p.setViewport({ width: w, height: h, isMobile: touch, hasTouch: touch });
    await p.evaluateOnNewDocument(() => { localStorage.setItem('kmpr_kr_intro', '1'); localStorage.setItem('kmpr_kr_progress', '11'); ['army', 'bio', 'pand'].forEach((k) => localStorage.setItem('kmpr_kr_' + k, '3')); localStorage.setItem('kmpr_kr_side', '2'); localStorage.setItem('kmpr_kr_war', '1'); });
    await p.goto(`http://127.0.0.1:8765/harness.html?hb=${hb}`, { waitUntil: 'networkidle0' });
    await p.waitForFunction(() => window.__ready === true); await sleep(400);
    const check = async (label) => p.evaluate((label) => {
      const st = document.getElementById('stage').getBoundingClientRect(), out = [];
      const ov = document.querySelector('.kr-comic') || document.querySelector('.kr-map:not([hidden])');
      if (ov) {
        if (ov.scrollWidth > ov.clientWidth + 1) out.push('h-overflow');
        const box = ov.querySelector('.kr-comic__box, .kr-map__box');
        if (box) { const b = box.getBoundingClientRect(); if (b.width > st.width + 1) out.push(`box wider ${Math.round(b.width)}>${Math.round(st.width)}`); if (ov.classList.contains('kr-comic') && b.height > st.height + 1) out.push(`box taller ${Math.round(b.height)}>${Math.round(st.height)}`); }
        ov.querySelectorAll('button').forEach((bt) => { const r = bt.getBoundingClientRect(); if (r.width && (r.height < 36)) out.push(`small btn "${bt.textContent.trim().slice(0, 12)}" ${Math.round(r.height)}px`); });
        const cap = ov.querySelector('.kr-comic__cap'); if (cap) { const pn = cap.closest('.kr-comic__panel').getBoundingClientRect(), c = cap.getBoundingClientRect(); if (c.bottom > pn.bottom + 1) out.push('caption overflows panel'); }
      }
      if (document.documentElement.scrollWidth > innerWidth + 1) out.push('page h-scroll');
      return `${label}: ${out.length ? [...new Set(out)].slice(0, 5).join('; ') : 'ok'}`;
    }, label);
    const shot = (f) => p.screenshot({ path: `out/sz-${name}-${f}.png` });
    report.push(`--- ${name} ${w}x${h}${touch ? ' touch' : ''}`);
    report.push(await check('map')); await shot('map');
    await p.evaluate(() => document.querySelector('.kr-map').scrollTop = 9999); await sleep(150); await shot('map-bottom');
    // comic with long caption (Bakhshi intro) + party + OCR + dialog + gameplay
    await p.evaluate(() => document.querySelector('[data-st="106"]').click()); await sleep(1600);
    await p.keyboard.press('Enter'); await sleep(200); // finish typewriter
    report.push(await check('comic')); await shot('comic');
    await p.keyboard.press('Enter'); await sleep(1200);
    report.push(await check('play-boss')); await shot('play');
    const pad = await p.evaluate(() => { const pd = document.querySelector('.kr-pad'); if (!pd || pd.hidden) return 'pad hidden'; return [...pd.querySelectorAll('button')].map((b) => Math.round(b.getBoundingClientRect().height)).join(','); });
    report.push(`pad buttons: ${pad}`);
    await p.evaluate(() => [...document.querySelectorAll('*')].find((x) => x.kmprDebug).kmprDebug.playStage(107)); await sleep(1200); await p.keyboard.press('Enter'); await sleep(200); await p.keyboard.press('Enter'); await sleep(600);
    report.push(await check('ocr-scan')); await shot('ocr');
    await p.evaluate(() => [...document.querySelectorAll('.kr-comic')].forEach((e) => e.remove()));
    await p.evaluate(() => [...document.querySelectorAll('*')].find((x) => x.kmprDebug).kmprDebug.playStage(111)); await sleep(1200); await p.keyboard.press('Enter'); await sleep(200); await p.keyboard.press('Enter'); await sleep(700);
    report.push(await check('dialog')); await shot('dialog');
    report.push(`errors: ${errs.join(' | ') || 'none'}`);
    await p.close();
  }
  console.log(report.join('\n'));
  await br.close();
})();
