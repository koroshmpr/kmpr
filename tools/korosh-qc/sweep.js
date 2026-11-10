const pp = require('puppeteer-core');
const STAGES = (process.argv[2] || '1,3,4,6,8,10,101').split(',').map(Number);
(async () => {
  const br = await pp.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const p = await br.newPage(); const errs = []; p.on('pageerror', (e) => errs.push(e.message));
  await p.goto('http://127.0.0.1:8765/harness.html', { waitUntil: 'networkidle0' });
  await p.waitForFunction(() => window.__ready === true);
  for (const n of STAGES) {
    const r = await p.evaluate((n) => {
      const D = [...document.querySelectorAll('*')].find((x) => x.kmprDebug).kmprDebug;
      const T = 16, ROWS = 15, SOLID = new Set(['#', 'B', '?', 'M', 'P', '*', 'U', 'S', 's', 'W', 'w', '=', 'X']), WALL = new Set([...SOLID, 'F']);
      const tile = (s, x, y) => { const tx = Math.floor(x / T), ty = Math.floor(y / T); if (ty < 0) return '.'; if (ty >= ROWS) return '.'; if (tx < 0 || tx >= s.lv.W) return '#'; return s.lv.map[ty][tx]; };
      const solid = (s, x, y) => SOLID.has(tile(s, x, y));
      const standY = (s, tx) => { for (let y = 4; y < ROWS; y++) if (SOLID.has(s.lv.map[y][tx]) && !SOLID.has(s.lv.map[y - 1][tx]) && !SOLID.has(s.lv.map[y - 2][tx])) return y * T; return null; };
      const keep = (s) => { s.p.inv = Math.max(s.p.inv, 0.3); s.timeLeft = 200; if (s.lv.side === 'date') s.pat = 100; if (s.hk) s.hk.timer = 999; s.lives = 99; };
      const place = (tx) => { D.load(n); const s = D.s; keep(s); for (let d = 0; d < 6; d++) { const y = standY(s, tx - d); if (y !== null) { s.p.x = (tx - d) * T + 2; s.p.y = y - s.p.h; s.p.vy = 0; s.cam = Math.max(0, s.p.x - 150); s.checkpoint = false; return s; } } return null; };
      // greedy runner (same as qcbot) until death/goal/stuck
      const greedy = (s, frames) => {
        let stuck = 0, lx = 0, died = null;
        for (let f = 0; f < frames; f++) {
          D.sim(1, (s, keys, press) => {
            keep(s); const p = s.p, feet = p.y + p.h; keys.right = true; keys.left = false; keys.run = true;
            const edge = p.x + p.w + 2 + Math.abs(p.vx) * 3;
            const wall = WALL.has(tile(s, edge, feet - 4)) || WALL.has(tile(s, edge, feet - 20)) || WALL.has(tile(s, edge + 6, feet - 4));
            let gap = true; for (let dy = 2; dy <= 5 * T; dy += 8) if (solid(s, edge + 4, feet + dy)) { gap = false; break; }
            const want = p.ground && (wall || gap);
            if (want && !keys.jump) press('jump', true); else if (keys.jump && (p.vy > 0 || (p.ground && !want))) press('jump', false);
          });
          if (s.dying) { died = s.p.x; break; }
          if (s.won) return { won: true };
          if (f % 60 === 0) { if (Math.abs(s.p.x - lx) < 2) stuck++; else stuck = 0; lx = s.p.x; if (stuck > 6) return { stuck: s.p.x }; }
        }
        return died !== null ? { died } : { timeout: s.p.x };
      };
      // scripted attempt: wait W frames, run, jump when x >= start + d, hold until falling; success if alive past target
      const attempt = (tx, wait, d, target) => {
        const s = place(tx); if (!s) return false;
        const x0 = s.p.x; let jumped = false;
        for (let f = 0; f < 60 * 8; f++) {
          D.sim(1, (s, keys, press) => {
            keep(s); const p = s.p;
            if (f < wait) { keys.right = false; keys.left = false; return; }
            keys.right = true; keys.run = true;
            if (!jumped && p.x >= x0 + d && p.ground) { press('jump', true); jumped = true; }
            else if (keys.jump && p.vy > 0) press('jump', false);
            if (jumped && p.ground && !keys.jump && p.x < target - 8) { // keep going: small hop at walls/gaps after the first jump
              const feet = p.y + p.h, edge = p.x + p.w + 4;
              let gap = true; for (let dy = 2; dy <= 5 * T; dy += 8) if (solid(s, edge, feet + dy)) { gap = false; break; }
              if (gap || WALL.has(tile(s, edge, feet - 4))) press('jump', true);
            }
          });
          if (s.dying) return false;
          if (s.p.x > target || s.won) return true;
        }
        return false;
      };
      D.load(n); let s = D.s; keep(s);
      const report = [];
      let guard = 0, start = null;
      while (guard++ < 25) {
        if (start !== null) { s = place(start); if (!s) { report.push(`no ground @${start}`); break; } }
        const g = greedy(s, 60 * 120);
        if (g.won) { report.push('GOAL ✓'); break; }
        const at = Math.floor((g.died ?? g.stuck ?? g.timeout) / T);
        if (g.timeout !== undefined) { report.push(`timeout @${at}`); break; }
        let ok = false;
        const from = at - 7, target = (at + 7) * T;
        for (let wait = 0; wait <= 160 && !ok; wait += 20) for (let d = -8; d <= 120 && !ok; d += 4) if (attempt(from, wait, d, target)) { ok = true; report.push(`${g.died !== undefined ? 'pit' : 'wall'}@${at} ✓(w${wait},d${d})`); }
        if (!ok) { report.push(`${g.died !== undefined ? 'pit' : 'wall'}@${at} ✗ IMPOSSIBLE?`); break; }
        start = at + 7;
      }
      return `${n}: ${report.join(' → ')}`;
    }, n);
    console.log(r);
  }
  console.log('ERR', errs.join(' | ') || 'none');
  await br.close();
})();
