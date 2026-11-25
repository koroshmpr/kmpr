/**
 * Accounts + friends QC on the local site (http://localhost:10039).
 *
 *   cd tools/accounts-qc && NODE_PATH=../korosh-qc/node_modules node test.cjs
 *
 * Covers: sign-up (modal), log-out wipe, wrong password (generic), login rate limit, second browser context
 * sees the same scores/progress, anonymous data merge (max/union), subscription preserved (remaining time
 * added), leaderboard rows re-keyed to the account, friends (request → accept → online → invite → join room),
 * master switch off → 404 and no UI, mobile 390×844 screenshots (out/).
 * Test users are created with logins starting "qcacct" and removed at the end (also on failure).
 */
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer-core');

const BASE = 'http://localhost:10039';
const CHROME = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT = path.join(__dirname, 'out');
fs.mkdirSync(OUT, { recursive: true });
const php = (code) => execFileSync(path.join(__dirname, 'wp.sh'), [code], { encoding: 'utf8' }).trim();
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const results = [];
const check = (name, cond, extra = '') => {
	results.push({ name, ok: !!cond, extra });
	console.log(`${cond ? 'PASS' : 'FAIL'}  ${name}${extra ? ' — ' + extra : ''}`);
};

const SNAKE = Number(php('echo get_posts(array("post_type"=>"game","numberposts"=>1,"fields"=>"ids","meta_key"=>"game_engine","meta_value"=>"snake"))[0];'));
const XO_URL = php('echo get_permalink(get_posts(array("post_type"=>"game","numberposts"=>1,"fields"=>"ids","meta_key"=>"game_engine","meta_value"=>"xo"))[0]);');
const PID_A = 'aaaaaaaa-1111-4111-8111-' + Date.now().toString(16).padStart(12, '0').slice(-12);
const PID_B = 'bbbbbbbb-2222-4222-8222-' + Date.now().toString(16).padStart(12, '0').slice(-12);
const RUN = Date.now().toString(36).slice(-5);
const U1 = 'qcacct1' + RUN;
const U2 = 'qcacct2' + RUN;
const PW = 'Qc-strong-pass-42';

const CLEANUP = `
global $wpdb;
foreach (get_users(array("search"=>"qcacct*","search_columns"=>array("user_login"))) as $u) {
  $pid = get_user_meta($u->ID, "kmpr_pid", true);
  if ($pid) { $wpdb->delete(kmpr_games_table(), array("player_id"=>$pid)); $wpdb->delete(kmpr_games_players_table(), array("player_id"=>$pid)); $m=get_option("kmpr_sub_members",array()); unset($m[$pid]); update_option("kmpr_sub_members",$m,false); }
  require_once ABSPATH."wp-admin/includes/user.php"; wp_delete_user($u->ID);
}
foreach (array("${PID_A}","${PID_B}") as $p) { $wpdb->delete(kmpr_games_table(), array("player_id"=>$p)); $wpdb->delete(kmpr_games_players_table(), array("player_id"=>$p)); }
$m=get_option("kmpr_sub_members",array()); foreach (array_keys($m) as $k) if (strpos($k,"aaaaaaaa-1111")===0||strpos($k,"bbbbbbbb-2222")===0) unset($m[$k]); update_option("kmpr_sub_members",$m,false);
$al=get_option("kmpr_acct_alias",array()); foreach (array_keys($al) as $k) if (strpos($k,"aaaaaaaa-1111")===0||strpos($k,"bbbbbbbb-2222")===0) unset($al[$k]); update_option("kmpr_acct_alias",$al,false);
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\\\_transient%kmpr\\\\_ar\\\\_%'");
echo "clean";`;

function seedAnon(pid, best, kr, stars, lessons, extraGames = {}) {
	return `localStorage.setItem('kmpr_arcade_v1', JSON.stringify({ id: '${pid}', nickname: 'anon-${pid.slice(0, 4)}', hash: '', sound: false, avatar: { style: 'blocks', hue: 120 },
		games: { snake: { best: ${best}, plays: 3, time: 40, ach: ['snake-first'], last: ${best}, at: Date.now() }, ...${JSON.stringify(extraGames)} } }));
		localStorage.setItem('kmpr_kr_progress', '${kr}'); localStorage.setItem('kmpr_kr_stars', '${JSON.stringify(stars)}'); localStorage.setItem('kmpr_lessons_done', '${JSON.stringify(lessons)}');`;
}

(async () => {
	const prevOn = php('echo (int) get_option("options_acct_on", 0);');
	php(CLEANUP);
	php('update_option("options_acct_on",1); update_option("_options_acct_on","field_kmpr_acct_on"); update_option("options_friends_on",1); update_option("_options_friends_on","field_kmpr_friends_on"); update_option("options_acct_notify_signup",0); echo 1;');
	// Server-side anonymous history for both devices: a leaderboard row each + a paid subscription each.
	const now = Math.floor(Date.now() / 1000);
	php(`global $wpdb; $t=kmpr_games_table(); $d=current_time("mysql",true);
		$wpdb->insert($t,array("game_id"=>${SNAKE},"player_id"=>"${PID_A}","nickname"=>"anonA","score"=>120,"duration_ms"=>60000,"plays"=>3,"created_at"=>$d,"last_played_at"=>$d));
		$wpdb->insert($t,array("game_id"=>${SNAKE},"player_id"=>"${PID_B}","nickname"=>"anonB","score"=>50,"duration_ms"=>60000,"plays"=>2,"created_at"=>$d,"last_played_at"=>$d));
		$m=get_option("kmpr_sub_members",array()); $m["${PID_A}"]=array("since"=>${now},"until"=>${now}+10*86400,"nick"=>"anonA"); $m["${PID_B}"]=array("since"=>${now},"until"=>${now}+5*86400,"nick"=>"anonB"); update_option("kmpr_sub_members",$m,false); echo 1;`);

	const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--window-size=390,844'] });
	const mobile = { width: 390, height: 844, deviceScaleFactor: 2, isMobile: true, hasTouch: true };
	try {
		/* ---------- device A: anonymous data → sign up from the header modal ---------- */
		const ctxA = await browser.createBrowserContext();
		const a = await ctxA.newPage(); a.setDefaultNavigationTimeout(90000); a.setDefaultTimeout(60000);
		await a.setViewport(mobile);
		const errs = [];
		a.on('pageerror', (e) => errs.push(e.message));
		await a.goto(BASE + '/account/', { waitUntil: 'domcontentloaded' });
		await a.evaluate(seedAnon(PID_A, 120, 3, { 0: 2, 1: 3 }, [11, 12]));
		await a.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
		await a.click('[data-acct-btn][data-acct-open]');
		await a.waitForSelector('.ka-dlg[open] [data-form="login"]');
		await a.click('.ka-dlg [data-tab="register"]');
		await sleep(200);
		await a.screenshot({ path: path.join(OUT, 'm-01-register-sheet.png') });
		await a.type('.ka-dlg [data-form="register"] [name="name"]', 'کیان تست');
		await a.type('.ka-dlg [data-form="register"] [name="username"]', U1);
		await a.type('.ka-dlg [data-form="register"] [name="password"]', PW);
		await Promise.all([a.waitForNavigation({ waitUntil: 'domcontentloaded' }), a.click('.ka-dlg [data-form="register"] [type="submit"]')]);
		const uid1 = Number(php(`echo get_user_by("login","${U1}")->ID;`));
		check('sign-up creates a subscriber visitor account', uid1 > 0 && php(`$u=get_userdata(${uid1}); echo implode(",",$u->roles), get_user_meta(${uid1},"kmpr_acct",true)?"+acct":"";`) === 'subscriber+acct');
		check('header shows initials after sign-up', await a.$('.ka-hbtn.is-in'));
		const accPid = php(`echo get_user_meta(${uid1},"kmpr_pid",true);`);
		check('first device keeps its player id as the account id', accPid === PID_A, accPid);
		const profA = await a.evaluate(() => JSON.parse(localStorage.getItem('kmpr_arcade_v1')));
		check('local profile adopts account (id, nickname, acct)', profA.id === PID_A && profA.nickname === 'کیان تست' && profA.acct === uid1);
		const srv1 = JSON.parse(php(`echo get_user_meta(${uid1},"kmpr_acct_data",true);`));
		check('device data uploaded on sign-up', srv1.games.snake.best === 120 && srv1.kv.kmpr_kr_progress === '3' && srv1.kv.kmpr_lessons_done === '[11,12]');
		check('leaderboard row renamed to the account name', php(`global $wpdb; echo $wpdb->get_var("SELECT nickname FROM ".kmpr_games_table()." WHERE player_id='${PID_A}'");`) === 'کیان تست');
		await a.goto(BASE + '/account/', { waitUntil: 'domcontentloaded' });
		await a.screenshot({ path: path.join(OUT, 'm-02-account-page.png'), fullPage: true });

		/* ---------- device B: its own anonymous data, wrong password, then log in ---------- */
		const ctxB = await browser.createBrowserContext();
		const b = await ctxB.newPage(); b.setDefaultNavigationTimeout(90000); b.setDefaultTimeout(60000);
		await b.setViewport(mobile);
		await b.goto(BASE + '/account/', { waitUntil: 'domcontentloaded' });
		await b.evaluate(seedAnon(PID_B, 50, 5, { 1: 1, 2: 2 }, [12, 13], { xo: { best: 7, plays: 2, time: 20, ach: ['xo-win'], last: 7, at: Date.now() } }));
		await b.reload({ waitUntil: 'domcontentloaded' });
		await b.type('[data-form="login"] [name="username"]', U1);
		await b.type('[data-form="login"] [name="password"]', 'wrong-password-1');
		await b.click('[data-form="login"] [type="submit"]');
		await b.waitForFunction(() => document.querySelector('[data-form="login"] [data-err]').textContent.length > 3);
		const wrongMsg = await b.$eval('[data-form="login"] [data-err]', (e) => e.textContent);
		check('wrong password → generic message', /نام کاربری یا رمز عبور درست نیست/.test(wrongMsg), wrongMsg);
		const unknown = await b.evaluate(async () => (await fetch('/wp-json/kmpr/v1/account/login', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-KMPR-Acct': '1' }, body: JSON.stringify({ login: 'no_such_user_zz', password: 'x' }) })).json());
		check('unknown user → same generic message (no enumeration)', unknown.message === wrongMsg);
		await b.screenshot({ path: path.join(OUT, 'm-03-wrong-password.png') });
		await b.$eval('[data-form="login"] [name="password"]', (e) => (e.value = ''));
		await b.type('[data-form="login"] [name="password"]', PW);
		await Promise.all([b.waitForNavigation({ waitUntil: 'domcontentloaded' }), b.click('[data-form="login"] [type="submit"]')]);
		const profB = await b.evaluate(() => ({ p: JSON.parse(localStorage.getItem('kmpr_arcade_v1')), kr: localStorage.getItem('kmpr_kr_progress'), st: JSON.parse(localStorage.getItem('kmpr_kr_stars')), ls: JSON.parse(localStorage.getItem('kmpr_lessons_done')) }));
		check('second device switched to the account player id', profB.p.id === PID_A);
		check('merge: best score = max, plays = max, achievements = union', profB.p.games.snake.best === 120 && profB.p.games.snake.plays === 3 && profB.p.games.xo.best === 7, JSON.stringify(profB.p.games.snake));
		check('merge: progress = furthest, stars per stage max, lessons union', profB.kr === '5' && profB.st[0] === 2 && profB.st[1] === 3 && profB.st[2] === 2 && profB.ls.length === 3, JSON.stringify(profB));
		const rowsB = php(`global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM ".kmpr_games_table()." WHERE player_id='${PID_B}'"), "|", $wpdb->get_var("SELECT CONCAT(score,'/',plays) FROM ".kmpr_games_table()." WHERE player_id='${PID_A}' AND game_id=${SNAKE}");`);
		check('device B leaderboard rows folded into the account (best kept, plays summed)', rowsB === '0|120/5', rowsB);
		const sub = JSON.parse(php(`$m=get_option("kmpr_sub_members"); echo wp_json_encode(array("a"=>$m["${PID_A}"]["until"]??0,"b"=>isset($m["${PID_B}"])));`));
		const days = (sub.a - now) / 86400;
		check('subscription preserved: remaining time of both devices added', !sub.b && days > 14.9 && days < 15.1, days.toFixed(2) + ' days');

		/* device A pulls B's data on its next page load */
		await a.goto(BASE + '/account/', { waitUntil: 'domcontentloaded' });
		const pullA = await a.evaluate(() => ({ p: JSON.parse(localStorage.getItem('kmpr_arcade_v1')), kr: localStorage.getItem('kmpr_kr_progress') }));
		check('first device sees data from the second device', pullA.p.games.xo && pullA.p.games.xo.best === 7 && pullA.kr === '5');

		/* a score made while logged in on B syncs to A (local-first, setItem hook) */
		await b.evaluate(() => {
			const p = JSON.parse(localStorage.getItem('kmpr_arcade_v1'));
			p.games.snake.best = 333;
			localStorage.setItem('kmpr_arcade_v1', JSON.stringify(p));
		});
		await sleep(5500);
		await a.reload({ waitUntil: 'domcontentloaded' });
		check('new record on device B appears on device A', (await a.evaluate(() => JSON.parse(localStorage.getItem('kmpr_arcade_v1')).games.snake.best)) === 333);

		/* game REST requests of a logged-in visitor always act as the account player */
		const forced = await b.evaluate(async () => {
			const r = await fetch('/wp-json/kmpr/v1/games/presence', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.kmprAccount.nonce }, body: JSON.stringify({ player_id: 'cccccccc-3333-4333-8333-000000000000', nickname: 'fake', where: 'hub' }) });
			return (await r.json()).online.find((x) => x.me);
		});
		check('logged-in game calls are forced to the account id + name', forced && forced.nick === 'کیان تست');

		/* ---------- rate limit ---------- */
		const codes = await b.evaluate(async () => {
			const out = [];
			for (let i = 0; i < 7; i++) {
				const r = await fetch('/wp-json/kmpr/v1/account/login', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-KMPR-Acct': '1' }, body: JSON.stringify({ login: 'qc_rate_target', password: 'nope' + i }) });
				out.push(r.status);
			}
			return out;
		});
		check('login rate limit per account (6 fails → 429)', codes.slice(0, 6).every((c) => c === 401) && codes[6] === 429, codes.join(','));
		const csrf = await b.evaluate(async () => (await fetch('/wp-json/kmpr/v1/account/login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })).status);
		check('requests without the account header are refused (CSRF guard)', csrf === 403);

		/* ---------- friends: second account on context C ---------- */
		const ctxC = await browser.createBrowserContext();
		const c = await ctxC.newPage(); c.setDefaultNavigationTimeout(90000); c.setDefaultTimeout(60000);
		await c.setViewport(mobile);
		await c.goto(BASE + '/account/?tab=register', { waitUntil: 'domcontentloaded' });
		await c.type('[data-form="register"] [name="name"]', 'دوست تست');
		await c.type('[data-form="register"] [name="username"]', U2);
		await c.type('[data-form="register"] [name="password"]', PW);
		await Promise.all([c.waitForNavigation({ waitUntil: 'domcontentloaded' }), c.click('[data-form="register"] [type="submit"]')]);
		await c.waitForSelector('[data-friends-root] [data-settings]');
		const handle2 = await c.$eval('[data-settings] [name="handle"]', (e) => e.value);
		await b.goto(BASE + '/account/?f=1#friends', { waitUntil: 'domcontentloaded' });
		await b.waitForSelector('[data-add] [name="handle"]');
		await b.type('[data-add] [name="handle"]', '@' + handle2);
		await b.click('[data-add] [type="submit"]');
		await b.waitForFunction(() => /فرستاده شد/.test(document.querySelector('[data-add] [data-err]')?.textContent || ''));
		check('friend request by handle sent', true, '@' + handle2);
		await c.reload({ waitUntil: 'domcontentloaded' });
		await c.waitForSelector('[data-acc]');
		await c.screenshot({ path: path.join(OUT, 'm-04-friend-request.png'), fullPage: true });
		await c.click('[data-acc]');
		await c.waitForFunction(() => document.querySelectorAll('[data-inv]').length === 1);
		check('friend request accepted', true);

		// C goes to the game center → presence heartbeat → B sees C online.
		await c.goto(BASE + '/games/', { waitUntil: 'domcontentloaded' });
		await sleep(1500);
		const list = await b.evaluate(() => window.kmprFriends.list());
		const fr = (list.friends || [])[0];
		check('friend shows as online with status', fr && fr.online && /گیم‌سنتر|بازی/.test(fr.status), fr && fr.status);
		check('friend list never exposes email/login', !JSON.stringify(list).includes(U2) && !JSON.stringify(list).includes('@example'));
		const anonCtx = await browser.createBrowserContext();
		const anon = await anonCtx.newPage(); anon.setDefaultNavigationTimeout(90000); anon.setDefaultTimeout(60000);
		await anon.goto(BASE + '/games/', { waitUntil: 'domcontentloaded' });
		const anonRes = await anon.evaluate(async () => (await fetch('/wp-json/kmpr/v1/account/friends', { headers: { 'X-KMPR-Acct': '1' } })).status);
		check('anonymous visitor cannot read friends (401)', anonRes === 401);
		await anonCtx.close();

		// B invites C to xo → room is created on the xo page → C gets the invite on its heartbeat → joins.
		await b.reload({ waitUntil: 'domcontentloaded' });
		await b.waitForSelector('[data-inv]');
		await b.screenshot({ path: path.join(OUT, 'm-05-friends-list.png'), fullPage: true });
		await b.click('[data-inv]');
		await b.waitForSelector('.ka-dlg[open] select');
		await b.select('.ka-dlg[open] select', String(php('echo get_posts(array("post_type"=>"game","numberposts"=>1,"fields"=>"ids","meta_key"=>"game_engine","meta_value"=>"xo"))[0];')));
		await Promise.all([b.waitForNavigation({ waitUntil: 'domcontentloaded' }), b.click('.ka-dlg[open] button[value="go"]')]);
		check('invite → inviter lands on the game with create=1', b.url().startsWith(XO_URL) && b.url().includes('create=1'), b.url());
		await b.waitForFunction(() => /[?&]room=|data-room|کد اتاق/.test(location.href + document.body.innerText), { timeout: 20000 }).catch(() => {});
		await sleep(3000);
		await b.screenshot({ path: path.join(OUT, 'm-06-inviter-room.png') });
		const inv = JSON.parse(php(`echo wp_json_encode(get_transient("kmpr_finv_" . get_user_by("login","${U2}")->ID));`) || 'null');
		check('invite stored for the friend with the room code', Array.isArray(inv) && inv.length === 1 && /^[A-Z0-9]{6}$/.test(inv[0].code), inv && inv[0] && inv[0].code);
		const toast = await c.waitForSelector('.ka-toast', { timeout: 40000 }).catch(() => null);
		check('friend sees the invite toast in the game center (presence piggyback)', !!toast);
		if (toast) {
			await c.screenshot({ path: path.join(OUT, 'm-07-invite-toast.png') });
			await Promise.all([c.waitForNavigation({ waitUntil: 'domcontentloaded' }), c.click('.ka-toast .ka-btn:not(.ka-btn--ghost)')]);
			check('accepting the invite opens the room', c.url().includes('room=' + inv[0].code), c.url());
			await sleep(4000);
			const players = php(`echo count(kmpr_room_get("${inv[0].code}")["players"]);`);
			check('friend joined the room (2 players)', players === '2', players);
			await c.screenshot({ path: path.join(OUT, 'm-08-joined-room.png') });
		}
		// JS API for other games (picker).
		check('window.kmprFriends API available', await b.evaluate(() => typeof window.kmprFriends.invite === 'function' && window.kmprFriends.available));

		/* ---------- log-out wipes the device ---------- */
		await a.goto(BASE + '/account/', { waitUntil: 'domcontentloaded' });
		await Promise.all([a.waitForNavigation({ waitUntil: 'domcontentloaded' }), a.click('[data-logout]')]);
		const afterOut = await a.evaluate(() => ({ p: JSON.parse(localStorage.getItem('kmpr_arcade_v1') || 'null'), kr: localStorage.getItem('kmpr_kr_progress') }));
		check('log-out removes the account data from the device', (!afterOut.p || (afterOut.p.id !== PID_A && !afterOut.p.acct)) && afterOut.kr === null, JSON.stringify(afterOut).slice(0, 120));
		check('log-out shows the login form again', !!(await a.$('[data-form="login"]')));
		check('no JS errors on device A', errs.length === 0, errs.join(' | '));

		/* ---------- switch off ---------- */
		php('update_option("options_acct_on",0); echo 1;');
		const offPage = await fetch(BASE + '/account/');
		const offApi = await fetch(BASE + '/wp-json/kmpr/v1/account/login', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-KMPR-Acct': '1' }, body: '{}' });
		const home = await (await fetch(BASE + '/')).text();
		check('switch off → /account/ 404', offPage.status === 404, String(offPage.status));
		check('switch off → account API 404', offApi.status === 404, String(offApi.status));
		check('switch off → no header button / script', !home.includes('data-acct-btn') && !home.includes('account.js'));
	} catch (e) {
		console.error(e);
		check('test run without exceptions', false, e.message);
	} finally {
		await browser.close();
		php(`update_option("options_acct_on", ${Number(prevOn)}); echo 1;`);
		console.log(php(CLEANUP));
		const failed = results.filter((r) => !r.ok);
		fs.writeFileSync(path.join(OUT, 'results.json'), JSON.stringify(results, null, 2));
		console.log(`\n${results.length - failed.length}/${results.length} passed`);
		process.exit(failed.length ? 1 : 0);
	}
})();
