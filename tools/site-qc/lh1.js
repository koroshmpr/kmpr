// One Lighthouse mobile run with LCP breakdown + render-blocking + top opportunities. Usage: node lh1.js <path> [desktop]
(async () => {
  const { default: lighthouse } = await import('lighthouse');
  const { launch } = await import('chrome-launcher');
  const chrome = await launch({ chromePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', chromeFlags: ['--headless=new'] });
  const cfg = process.argv[3] === 'desktop' ? (await import('lighthouse/core/config/desktop-config.js')).default : undefined;
  const r = await lighthouse('http://localhost:10039' + process.argv[2], { port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: ['performance'] }, cfg);
  const a = r.lhr.audits;
  const pick = (id) => a[id] && { score: a[id].score, display: a[id].displayValue, items: (a[id].details?.items || []).slice(0, 6) };
  const out = {};
  for (const id of Object.keys(a)) if (/lcp|render-blocking|unused|largest|font|network-dependency|mainthread|bootup|long-tasks/.test(id) && a[id].score !== null && a[id].score < 1) out[id] = pick(id);
  console.log(JSON.stringify(out, (k, v) => (typeof v === 'string' && v.length > 160 ? v.slice(0, 160) : v), 1).slice(0, 9000));
  await chrome.kill();
})();
