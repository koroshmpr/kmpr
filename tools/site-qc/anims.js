// List running CSS animations after load (infinite ones on non-compositor properties cost main-thread style/paint every frame).
const puppeteer = require('puppeteer-core');
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const p = await b.newPage();
  await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  await p.goto('http://localhost:10039' + process.argv[2], { waitUntil: 'load' });
  await new Promise((r) => setTimeout(r, 2500));
  const r = await p.evaluate(() => {
    const m = {};
    document.getAnimations().forEach((a) => {
      const el = a.effect && a.effect.target; const kf = a.effect.getKeyframes(); const props = [...new Set(kf.flatMap((k) => Object.keys(k).filter((x) => !['offset', 'easing', 'composite', 'computedOffset'].includes(x))))];
      const key = (a.animationName || a.transitionProperty || '?') + ' [' + props.join(',') + '] ' + (a.effect.getTiming().iterations === Infinity ? 'INF' : '');
      const vis = el && el.getBoundingClientRect && (() => { const r = el.getBoundingClientRect(); return r.bottom > 0 && r.top < innerHeight; })();
      m[key] = m[key] || { n: 0, onscreen: 0, sample: '' }; m[key].n++; if (vis) m[key].onscreen++; m[key].sample = el ? (el.tagName + '.' + String(el.className).slice(0, 50)) : '';
    });
    return m;
  });
  console.log(r);
  await b.close();
})();
