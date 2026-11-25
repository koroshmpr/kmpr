// Main-thread style/layout breakdown with 4x CPU throttle (phone). Usage: node trace.js <path>
const puppeteer = require('puppeteer-core');
const fs = require('fs');
(async () => {
  const b = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const p = await b.newPage();
  await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
  const c = await p.createCDPSession(); await c.send('Emulation.setCPUThrottlingRate', { rate: 4 });
  const f = __dirname + '/out/trace.json';
  await p.tracing.start({ path: f, categories: ['devtools.timeline', 'disabled-by-default-devtools.timeline'] });
  await p.goto('http://localhost:10039' + process.argv[2], { waitUntil: 'load' });
  await new Promise((r) => setTimeout(r, 5000));
  await p.tracing.stop(); await b.close();
  const ev = JSON.parse(fs.readFileSync(f)).traceEvents.filter((e) => e.ph === 'X' && e.dur);
  const t0 = Math.min(...ev.map((e) => e.ts));
  const sum = {}; ev.forEach((e) => { sum[e.name] = sum[e.name] || [0, 0]; sum[e.name][0] += e.dur / 1000; sum[e.name][1]++; });
  console.log(Object.entries(sum).filter(([n]) => /UpdateLayoutTree|Layout|RecalculateStyles|Paint|EvaluateScript|FunctionCall|ParseAuthorStyleSheet|ParseHTML|TimerFire|FireAnimationFrame/.test(n)).sort((a, b) => b[1][0] - a[1][0]).map(([n, [d, k]]) => `${n}: ${Math.round(d)}ms x${k}`).join('\n'));
  const big = ev.filter((e) => /UpdateLayoutTree|Layout/.test(e.name)).sort((a, b) => b.dur - a.dur).slice(0, 6);
  big.forEach((e) => console.log(e.name, Math.round(e.dur / 1000) + 'ms @' + Math.round((e.ts - t0) / 1000), JSON.stringify(e.args?.beginData?.stackTrace?.[0] || e.args?.data?.elementCount || '').slice(0, 120)));
})();
