# Visitor accounts + friends

Files: `inc/accounts.php` (accounts, sync, linking, REST, /account/ route, header button, assets),
`inc/accounts-friends.php` (friends), `template-parts/account/page.php`, `javascript/account/{index,sync,ui,friends}.js`
→ `public/js/account.js`, `tailwind/custom/account.css` → `public/css/account.css` (`npm run production:account`).
Hooks into shared code (small edits): `do_action('kmpr_account_button', 'site'|'games')` in the two headers,
`kmpr:profile-reload` listener in `games/lib/store.js`, «ورود / ثبت‌نام» line in the `pwa.js` nickname dialog.
QC: `tools/accounts-qc/test.cjs`.

## Switches (تنظیمات سایت › حساب کاربران)
- `acct_on` master (default off). Off → no button/script, `/account/` 404, `kmpr/v1/account/*` not registered (404), games stay anonymous.
- `acct_notify_signup` Telegram to the owner on sign-up · `acct_session_days` "remember me" length (60) · `friends_on`.

## Data model
- WP user, role `subscriber`, meta `kmpr_acct` (marks a visitor account; only these get the visitor policies).
  Login = mobile (09…) or username as `user_login`; e-mail sign-ups get a random `user_login` and log in by e-mail.
- `kmpr_pid` = the account's canonical arcade player uuid (the first device's uuid → its leaderboard rows stay).
- `kmpr_acct_data` = ONE JSON per user (≤ 64 KB): `{v, games:{engine:{best,plays,time,ach[],last,at}}, kv:{localStorage key: string}, avatar}`.
  User meta (not a table): read once per page for the logged-in user only, written at most every few seconds,
  no cross-user queries → a custom table buys nothing. Synced kv keys: `kmpr_lessons_done` + prefix `kmpr_kr_`
  (filter `kmpr_acct_sync_keys` to add more, e.g. a new game's progress key).
- Option `kmpr_acct_alias` {old device uuid → user id} (non-autoload).

## Merge rules (same in PHP `kmpr_acct_merge` and JS `sync.js`)
Numbers = max, lists = union, maps = per key, booleans = or, opaque strings = server wins. Best/plays/time = max,
achievements = union, story progress/stars = furthest, nickname = account display name. Commutative → any order of
syncs on any device converges; nothing is ever lowered.

## Linking a device (login / sign-up / changed device id)
`kmpr_acct_link()`: device uuid ≠ account uuid and not owned by another account → scores rows re-keyed
(best = GREATEST, plays summed), players row (XP/W/L/D summed), `_s_player` of subscription requests re-pointed,
alias recorded. Subscription: filter `pre_update_option_kmpr_sub_members` re-keys any aliased uuid on every write,
adding the remaining time of both (also catches a Zarinpal payment that completes after linking). A uuid owned by
another account is never moved.

## Sync flow (local first)
Logged-in page → inline `window.kmprAccount.data` (server copy) is merged into localStorage by account.js (in `<head>`,
defer → runs before the games module), profile id/nickname set to the account's. Changes: `Storage.setItem` hook on
synced keys → debounced POST `/account/sync` (4 s), `kmpr:game-over` (1.2 s), keepalive flush on hide/pagehide, pull on
return to tab. Server merges and returns the merged copy. Offline → nothing breaks, next sync catches up.
Logged-in game REST calls (`/games/*`, `/arcade/*`) get `player_id`/`nickname` forced to the account's → one account =
one leaderboard player. Log-out wipes the synced keys on the device. Expired session → device keeps its copy as a new
anonymous uuid.

## Security
- Passwords: WP core hashing (bcrypt in WP ≥ 6.8), policy 8–128 chars + weak list; rehash on login when needed.
- CSRF: every account call needs header `X-KMPR-Acct: 1` (forces CORS preflight) + same-host Origin/Referer;
  logged-in calls additionally need the `wp_rest` nonce (stale nonce → refreshed via core `admin-ajax.php?action=rest-nonce`).
- Brute force: login 6 fails / 15 min per account and 20 / 15 min per IP (checked BEFORE the password), register 5/h/IP,
  reset 5/h/IP + 3/h/account, reset-confirm 20/h/IP, password change 6/15 min. IP = REMOTE_ADDR only (behind a CDN set
  the real IP at the web-server level).
- No enumeration: same message + same hashing cost for unknown users; reset always answers the same. Registration with a
  taken identifier returns a neutral message (rate-limited) — full protection would need e-mail/SMS verification.
- Honeypot `website`; visitor accounts can't log in as/with privileged users (owner/editors get the generic error and
  keep wp-login); visitors are redirected out of wp-admin (admin-ajax allowed), admin bar hidden;
  `auth_cookie_expiration` changed only for visitor accounts (remember → N days, else 2 days). Cookies = WP core (HttpOnly, Secure on https).
- Password change/reset destroys all sessions. `/account/` is noindex + no-cache.

## REST (`kmpr/v1/account/…`)
`POST register {name, login, email?, password, pid, data}` · `POST login {login, password, remember, pid, data}` ·
`POST logout` · `GET me` · `POST sync {pid, data}` · `POST profile {name} | {email, password}` · `POST password {current, password}` ·
`POST reset/request {login}` (e-mail via `wp_mail`; action `kmpr_acct_reset_sent($uid, $url)` for a future SMS) · `POST reset/confirm {u, key, password}`.
Friends: `GET friends` · `POST friends/request {handle|code}` · `friends/respond {id, accept}` · `friends/remove {id}` ·
`friends/block {id, unblock?}` · `friends/settings {handle?, findable?, new_link?}` · `friends/invite {to[], game, code?}` · `friends/invite/dismiss {code}`.

## Friends
User meta: `kmpr_friends` [ids], `kmpr_freq_in`/`kmpr_freq_out` {id: time}, `kmpr_blocked` [ids] (caps: 200 friends,
50 pending), public `kmpr_handle` (auto `player123456`, changeable), private share code `kmpr_fcode`
(`/account/?add=CODE`, works when not findable; «لینک تازه» revokes). `kmpr_findable=0` hides from handle search.
Emails/phones/logins never leave the server. A block is never revealed (request looks "sent").
Online status = presence transient of `inc/games-lobby.php` (friend's account pid hash). Invites: transient
`kmpr_finv_{uid}` (15 min, ≤ 10) delivered on the presence heartbeat response (`friends`, `invites`, `friendReqs` added by
`rest_post_dispatch`) and polled every 30 s on /account/. «دعوت به بازی» without a room = intent transient
(10 min) fulfilled when the inviter's next `POST /games/rooms` for that game succeeds.

### JS API for games (e.g. the draw-and-guess game)
```js
window.kmprFriends.available            // logged-in visitor + friends on
await window.kmprFriends.invite(game, roomCode)   // game = post id or engine slug; opens a friend picker
window.dispatchEvent(new CustomEvent('kmpr:friends-invite', { detail: { game, code } })) // same, fire-and-forget
window.kmprFriends.open()               // friends page (or login sheet when anonymous)
window.kmprAccounts.open('login'|'register') // auth sheet; any element with [data-acct-open] does the same
```
Built-in rooms (6-char code in the rooms table) are validated (inviter must be a player of the room); other codes
(4–12 chars) are accepted from friends only, rate-limited. Invite URL = game permalink `?room=CODE`.
Online-capable games for the picker = engines in `kmpr_game_room_handlers()` (filter `kmpr_friends_invite_engines`).
