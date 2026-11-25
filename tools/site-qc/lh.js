// Lighthouse (simulated throttling) for key pages, mobile + desktop. Usage: node lh.js <tag> [pages...]
// Writes out/lh-<tag>.json and prints a markdown table.
const fs = require('fs');
const path = require('path');
const BASE = process.env.BASE || 'http://localhost:10039';
const PAGES = {
  home: '/', blog: '/blog/', 'art-nextjs': '/blog/nextjs-business-guide/', 'art-seo': '/blog/seo-complete-guide/',
  'art-cwv': '/blog/core-web-vitals-complete-guide/', course: '/blog/category/seo-course/', services: '/services/',
  service: '/services/api-integration/', contact: '/contact-us/',
};
(async () => {
  const { default: lighthouse } = await import('lighthouse');
  const { launch } = await import('chrome-launcher');
  const tag = process.argv[2] || 'run';
  const only = process.argv.slice(3);
  const chrome = await launch({ chromePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', chromeFlags: ['--headless=new', '--no-sandbox'] });
  const rows = [];
  for (const [name, p] of Object.entries(PAGES)) {
    if (only.length && !only.includes(name)) continue;
    for (const preset of ['mobile', 'desktop']) {
      const config = preset === 'desktop' ? (await import('lighthouse/core/config/desktop-config.js')).default : undefined;
      const r = await lighthouse(BASE + p, { port: chrome.port, output: 'json', logLevel: 'error', onlyCategories: ['performance'] }, config);
      const a = r.lhr.audits;
      const bytes = {};
      (a['resource-summary']?.details?.items || []).forEach((i) => (bytes[i.resourceType] = [i.requestCount, Math.round(i.transferSize / 1024)]));
      const row = {
        page: name, preset, score: Math.round(r.lhr.categories.performance.score * 100),
        fcp: Math.round(a['first-contentful-paint'].numericValue), lcp: Math.round(a['largest-contentful-paint'].numericValue),
        tbt: Math.round(a['total-blocking-time'].numericValue), cls: +a['cumulative-layout-shift'].numericValue.toFixed(3),
        ttfb: Math.round(a['server-response-time']?.numericValue || 0), bytes,
        lcpEl: (a['largest-contentful-paint-element']?.details?.items?.[0]?.items?.[0]?.node?.snippet || '').slice(0, 120),
      };
      rows.push(row);
      console.log(JSON.stringify(row));
    }
  }
  await chrome.kill();
  fs.mkdirSync(path.join(__dirname, 'out'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, 'out', `lh-${tag}.json`), JSON.stringify(rows, null, 1));
  console.log('\n| page | preset | score | FCP | LCP | TBT | CLS | TTFB | JS KB | CSS KB | font KB | img KB | requests |\n|---|---|---|---|---|---|---|---|---|---|---|---|---|');
  rows.forEach((r) => console.log(`| ${r.page} | ${r.preset} | ${r.score} | ${r.fcp} | ${r.lcp} | ${r.tbt} | ${r.cls} | ${r.ttfb} | ${(r.bytes.script || [0, 0])[1]} | ${(r.bytes.stylesheet || [0, 0])[1]} | ${(r.bytes.font || [0, 0])[1]} | ${(r.bytes.image || [0, 0])[1]} | ${(r.bytes.total || [0])[0]} |`));
})();
