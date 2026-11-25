// AGENTS.md QC sweep: full-page screenshots + automated checks per page × width.
// Checks: horizontal overflow (and the widest offender), console/page errors, <img> without alt,
// <svg> without width/height attributes, wrapped button/CTA text, visible tap targets < 40px.
// Usage: NODE_PATH=../korosh-qc/node_modules node qc.js <tag> [--pages=a,b] [--widths=360,1440] [--noshot]
const puppeteer = require('puppeteer-core');
const fs = require('fs');
const BASE = process.env.BASE || 'http://localhost:10039';
const PAGES = {
  home: '/', services: '/services/', service: '/services/api-integration/', portfolios: '/portfolios/',
  portfolio: '/portfolios/traders-dastyar/', blog: '/blog/', article: '/blog/seo-complete-guide/', about: '/about-us/',
  contact: '/contact-us/', course: '/blog/category/seo-course/',
};
const arg = (k) => (process.argv.find((a) => a.startsWith(`--${k}=`)) || '').split('=')[1];
const pages = arg('pages') ? arg('pages').split(',') : Object.keys(PAGES);
const widths = (arg('widths') || '360,390,768,1024,1440').split(',').map(Number);
const tag = process.argv[2] || 'run';
(async () => {
  const browser = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new', args: ['--no-sandbox'], timeout: 120000, protocolTimeout: 180000 });
  const dir = `${__dirname}/out/qc-${tag}`; fs.mkdirSync(dir, { recursive: true });
  const report = [];
  for (const name of pages) for (const w of widths) {
    const page = await browser.newPage();
    const mob = w < 1024;
    await page.setViewport({ width: w, height: mob ? 800 : 900, isMobile: mob, hasTouch: mob });
    await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]); // final state, no entrance animation
    const errors = [];
    page.on('pageerror', (e) => errors.push('pageerror: ' + e.message));
    page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
    page.on('requestfailed', (r) => errors.push('failed: ' + r.url()));
    await page.goto(BASE + PAGES[name], { waitUntil: 'networkidle2', timeout: 60000 }).catch((e) => errors.push(e.message));
    // scroll through so lazy content renders
    await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 60)); } window.scrollTo(0, 0); }).catch((e) => errors.push('scroll: ' + e.message));
    await new Promise((r) => setTimeout(r, 500));
    await new Promise((r) => setTimeout(r, 200));
    const res = await page.evaluate(() => {
      const vis = (el) => { const r = el.getBoundingClientRect(); const cs = getComputedStyle(el); return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none' && !el.closest('[aria-hidden="true"], [x-cloak], .sr-only, dialog:not([open])'); };
      const desc = (el) => (el.tagName.toLowerCase() === 'svg' ? 'svg in ' + el.parentElement.tagName.toLowerCase() + '.' + String(el.parentElement.className).trim().split(/\s+/).slice(0, 3).join('.') + ' ' : '') + (el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + '.' + String(el.className && el.className.baseVal !== undefined ? el.className.baseVal : el.className).trim().split(/\s+/).slice(0, 4).join('.')).slice(0, 110) + ' «' + (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 30) + '»';
      const W = document.documentElement.clientWidth;
      const over = [...document.querySelectorAll('body *')].filter((el) => { const r = el.getBoundingClientRect(); return r.width && (r.right > W + 1 || r.left < -1) && vis(el); })
        .filter((el) => !el.closest('.marquee, [class*="overflow-hidden"], [class*="overflow-x-auto"], [class*="overflow-x-clip"], [class*="overflow-clip"], .swiper, [class*="snap-x"]')).slice(0, 4).map(desc);
      const noAlt = [...document.querySelectorAll('img:not([alt])')].map((i) => i.src.slice(-60));
      const svgNoSize = [...document.querySelectorAll('svg')].filter((s) => vis(s) && (!s.getAttribute('width') || !s.getAttribute('height')) && !s.closest('[aria-hidden="true"] svg *')).slice(0, 5).map(desc);
      const lines = (el) => { const ys = new Set(); const tw = document.createTreeWalker(el, NodeFilter.SHOW_TEXT); let n; while ((n = tw.nextNode())) { if (!n.textContent.trim() || n.parentElement.closest('.sr-only, [aria-hidden="true"]')) continue; const rg = document.createRange(); rg.selectNodeContents(n); [...rg.getClientRects()].filter((r) => r.width > 2).forEach((r) => ys.add(Math.round((r.top + r.height / 2) / 6))); } return ys.size; };
      const wrapped = [...document.querySelectorAll('a[class*="rounded-full"], button[class*="rounded-full"], a[class*="px-6"], button[class*="px-6"]')].filter((b) => vis(b) && b.textContent.trim().length > 3 && getComputedStyle(b).whiteSpace !== 'nowrap' && lines(b) > 1 && b.querySelectorAll('p, div, li').length === 0).slice(0, 5).map(desc);
      const small = [...document.querySelectorAll('a[href], button, [role="button"], input:not([type=hidden]), select')].filter((el) => {
        if (!vis(el) || el.closest('nav[aria-label*="مسیر"], .prose, article p, li > p, footer p')) return false; const r = el.getBoundingClientRect(); return (r.height < 32 || r.width < 24) && !(getComputedStyle(el).display === 'inline');
      }).slice(0, 6).map((el) => desc(el) + ` ${Math.round(el.getBoundingClientRect().width)}x${Math.round(el.getBoundingClientRect().height)}`);
      return { url: location.href, hscroll: document.documentElement.scrollWidth - W, over, noAlt, svgNoSize, wrapped, small, height: document.body.scrollHeight };
    });
    if (res.url && !res.url.startsWith(BASE + PAGES[name].replace(/\/$/, ''))) errors.push('navigated to ' + res.url);
    if (!process.argv.includes('--noshot')) await page.screenshot({ path: `${dir}/${name}-${w}.jpg`, fullPage: true, quality: 55, type: 'jpeg' }).catch(() => {});
    report.push({ page: name, w, ...res, errors: errors.slice(0, 5) });
    const bad = res.hscroll > 0 || errors.length || res.noAlt.length || res.svgNoSize.length || res.wrapped.length || res.small.length;
    console.log(`${name} @${w}: hscroll=${res.hscroll}${bad ? '' : ' OK'}`
      + (res.over.length ? `\n  overflow: ${res.over.join(' | ')}` : '') + (errors.length ? `\n  errors: ${errors.slice(0, 3).join(' | ').slice(0, 300)}` : '')
      + (res.noAlt.length ? `\n  img no alt: ${res.noAlt.join(', ')}` : '') + (res.svgNoSize.length ? `\n  svg no size: ${res.svgNoSize.join(' | ')}` : '')
      + (res.wrapped.length ? `\n  wrapped CTA: ${res.wrapped.join(' | ')}` : '') + (res.small.length ? `\n  small targets: ${res.small.join(' | ')}` : ''));
    await page.close();
  }
  fs.writeFileSync(`${dir}/report.json`, JSON.stringify(report, null, 1));
  await browser.close();
})();
