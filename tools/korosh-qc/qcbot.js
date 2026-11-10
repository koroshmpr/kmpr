const pp = require('puppeteer-core');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const STAGES = (process.env.ST || "0,1,2,3,4,5,6,7,8,9,10,11,100,101,102,103,104,105,106,108,109,110,112").split(",").map(Number);
const SEEDS = Number(process.argv[2] || 1);
(async () => {
  const br = await pp.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
  const p = await br.newPage(); const errs = []; p.on('pageerror', (e) => errs.push(e.message));
  await p.setViewport({ width: 1280, height: 800 });
  await p.evaluateOnNewDocument(() => { localStorage.setItem('kmpr_arcade_v1', JSON.stringify({ id: '19191919-8888-4888-8888-888888888888', nickname: 'تستر', games: {} })); localStorage.setItem('kmpr_kr_intro', '1'); });
  await p.goto('http://127.0.0.1:8765/harness.html', { waitUntil: 'networkidle0' });
  await p.waitForFunction(() => window.__ready === true, { timeout: 15000 }); await sleep(300);
  const rows = [];
  for (const n of STAGES) for (let k = 0; k < SEEDS; k++) {
    const r = await p.evaluate((n) => {
      const D = [...document.querySelectorAll('*')].find((x) => x.kmprDebug).kmprDebug;
      const T = 16, ROWS = 15, SOLID = new Set(['#', 'B', '?', 'M', 'P', '*', 'U', 'S', 's', 'W', 'w', '=', 'X']), WALLISH = new Set([...SOLID, 'F']);
      D.load(n);
      const s0 = D.s; s0.lives = 99;
      const tile = (s, x, y) => { const tx = Math.floor(x / T), ty = Math.floor(y / T); if (ty < 0) return '.'; if (ty >= ROWS || tx < 0 || tx >= s.lv.W) return tx < 0 ? '#' : '.'; return s.lv.map[ty][tx]; };
      const solid = (s, x, y) => SOLID.has(tile(s, x, y));
      let deaths = 0, wasDying = false, maxX = 0, bossFrames = 0, bossHits = 0, lastHp = null, stuck = 0, lastX = 0;
      const bot = (s, keys, press) => {
        const p = s.p, b = s.boss;
        p.inv = Math.max(p.inv, 0.3);
        if (s.lv.side === 'date') s.pat = 100;
        if (s.bombs) s.bombs.length = 0;
        if (s.task) s.task.t = 99;
        if (s.hk) s.hk.timer = 999;
        s.timeLeft = Math.max(s.timeLeft, 60);
        let dir = 1, want = false;
        const fighting = b && b.active && b.hp > 0;
        if (fighting) {
          bossFrames++;
          const bc = b.x + b.w / 2, pc = p.x + p.w / 2;
          dir = bc < pc ? -1 : 1;
          want = p.ground && Math.abs(bc - pc) < 60;
          if (lastHp !== null && b.hp < lastHp) bossHits++;
          lastHp = b.hp;
        }
        if (!fighting && b && b.hp <= 0 && s.goalOn && p.x > s.lv.goal.x + 20) dir = -1; // walk back to the button
        let go = true;
        const feet = p.y + p.h;
        const edge = dir > 0 ? p.x + p.w + 2 + Math.abs(p.vx) * 3 : p.x - 2 - Math.abs(p.vx) * 3;
        const isWall = (x) => WALLISH.has(tile(s, x, feet - 4)) || WALLISH.has(tile(s, x, feet - 20));
        const wall = isWall(edge) || isWall(edge + dir * 6);
        const floorAt = (x) => { for (let dy = 2; dy <= 5 * T; dy += 8) if (solid(s, x, feet + dy)) return true; return s.ents.some((e) => e.t === 'mover' && x > e.x && x < e.x + e.w && Math.abs(e.y - feet) < 40); };
        let gapW = 0; if (!floorAt(edge + dir * 4)) { for (let k2 = 1; k2 <= 10; k2++) { if (floorAt(edge + dir * k2 * T)) break; gapW++; } }
        if (!fighting) {
          if (gapW >= 5) {
            // wide pit: wait for a moving platform near our edge
            const m = s.ents.find((e) => e.t === 'mover' && Math.abs((dir > 0 ? e.x : e.x + e.w) - edge) < 28);
            let plat = false; for (let k2 = 1; k2 <= gapW && !plat; k2++) for (let up = 1; up <= 5; up++) if (['=', 'X'].includes(tile(s, edge + dir * k2 * T, feet - up * T))) plat = true;
            if (m || plat) want = p.ground; else { go = false; want = false; }
          } else want = p.ground && (wall || gapW > 0);
        }
        keys.right = go && dir > 0; keys.left = go && dir < 0; keys.run = true;
        if (want && !keys.jump) press('jump', true);
        else if (keys.jump && (p.vy > 0 || (p.ground && !want))) press('jump', false);
        if (s.dying && !wasDying) deaths++;
        wasDying = !!s.dying;
        maxX = Math.max(maxX, p.x);
      };
      let s = D.s, frames = 0;
      for (; frames < 60 * 240 && s && !s.won && s.mode === 'play' && !s.over; frames += 60) {
        s = D.sim(60, bot);
        if (Math.abs(s.p.x - lastX) < 2 && !(s.boss && s.boss.active && s.boss.hp > 0)) stuck++; else stuck = 0;
        lastX = s.p.x;
        if (stuck > 15) break;
      }
      const b = s.boss;
      return { n, side: s.lv.side || (s.lv.isBoss ? 'boss' : 'main'), won: !!s.won, sec: Math.round(frames / 60), deaths, W: s.lv.W, maxX: Math.round(s.p.x / T), goal: Math.round(s.lv.goal.x / T), boss: b ? `${b.type} hp ${b.hp}/${b.max} ${Math.round(bossFrames / 60)}s` : '', stuck: stuck > 15 };
    }, n);
    rows.push(r);
  }
  console.table(rows);
  console.log('ERR', errs.join(' | ') || 'none');
  await br.close();
})();
