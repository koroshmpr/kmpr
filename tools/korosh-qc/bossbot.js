const pp = require('puppeteer-core');
(async () => {
  const br = await pp.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const p = await br.newPage(); const errs = []; p.on('pageerror', (e) => errs.push(e.message));
  await p.goto('http://127.0.0.1:8765/harness.html', { waitUntil: 'networkidle0' });
  await p.waitForFunction(() => window.__ready === true);
  const rows = await p.evaluate(() => {
    const D = [...document.querySelectorAll('*')].find((x) => x.kmprDebug).kmprDebug;
    const out = [];
    for (const [n, label] of [[2, 'megabug'], [5, 'server'], [8, 'scorpion'], [11, 'deadline'], [106, 'bakhshi'], [109, 'azizi']]) {
      for (const mode of ['stomp', 'beans', 'nohit']) {
        D.load(n); const s = D.s; s.lives = 99;
        const a = s.lv.arena;
        s.p.x = a.x0 * 16 + 40; s.p.y = 13 * 16 - s.p.h; s.cam = Math.max(0, s.p.x - 200);
        if (mode === 'beans') D.setForm('coffee');
        let frames = 0, hits = 0, last = null, minX = 1e9, maxX = -1e9, hurtCount = 0, lastBig = s.big, wasD = false, deaths = 0;
        const bot = (s, keys, press) => {
          const p = s.p, b = s.boss;
          if (mode !== 'nohit') p.inv = Math.max(p.inv, 0.3);
          s.timeLeft = 200;
          if (!b || !b.active || b.hp <= 0) { const gx = s.lv.goal.x + 10; keys.right = p.x < gx; keys.left = p.x > gx + 14; return; }
          const bc = b.x + b.w / 2, pc = p.x + p.w / 2, dir = bc < pc ? -1 : 1;
          minX = Math.min(minX, b.x); maxX = Math.max(maxX, b.x + b.w);
          if (last !== null && b.hp < last) hits++;
          last = b.hp;
          if (mode === 'beans') { keys.left = false; keys.right = false; p.face = dir; if (frames % 20 === 0) press('fire', true); else if (frames % 20 === 10) press('fire', false); if (Math.abs(bc - pc) < 70) { keys.left = dir > 0; keys.right = dir < 0; } }
          else { keys.right = dir > 0; keys.left = dir < 0; keys.run = true; const want = p.ground && Math.abs(bc - pc) < 60; if (want && !keys.jump) press('jump', true); else if (keys.jump && p.vy > 0) press('jump', false); }
          if (s.dying && !wasD) deaths++; wasD = !!s.dying;
        };
        for (; frames < 60 * 150 && s.mode === 'play' && !s.over; frames++) { D.sim(1, bot); if (s.boss && s.boss.hp <= 0 && s.won) break; }
        const b = s.boss;
        out.push({ boss: label, mode, killed: b.hp <= 0, won: !!s.won, sec: Math.round(frames / 60), hp: `${b.hp}/${b.max}`, deaths, inArena: minX >= a.x0 * 16 - 2 && maxX <= (a.x1 + 1) * 16 + 2, shots: s.shots.length });
      }
    }
    return out;
  });
  console.table(rows);
  console.log('ERR', errs.join(' | ') || 'none');
  await br.close();
})();
