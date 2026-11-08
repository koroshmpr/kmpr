/**
 * "Need a break?" game nudge — see inc/play-nudge.php for the rules.
 * Shows at most once per session; snoozes 3 days after "later", 7 days after a click-through.
 */
const KEY_SESSION = 'kmpr_nudge_seen';
const KEY_SNOOZE = 'kmpr_nudge_snooze';
const DAY = 864e5;

const store = (fn) => { try { return fn(); } catch (e) { return null; } };

export default function initPlayNudge() {
	const el = document.getElementById('kmpr-nudge');
	if (!el) return;
	const cfg = store(() => JSON.parse(el.dataset.config));
	if (!cfg || !cfg.games?.length) return;
	if (store(() => sessionStorage.getItem(KEY_SESSION))) return;
	if (Number(store(() => localStorage.getItem(KEY_SNOOZE)) || 0) > Date.now()) return;

	const start = Date.now();
	let lastActive = Date.now();
	let maxDepth = 0;
	let shown = false;
	let idx = Math.floor(Math.random() * cfg.games.length);

	const depth = () => {
		const max = document.documentElement.scrollHeight - innerHeight;
		return max > 0 ? scrollY / max : 1;
	};
	const busy = () => {
		const a = document.activeElement;
		return (a && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName)) || document.body.classList.contains('overflow-hidden') || document.hidden;
	};

	const fill = () => {
		const g = cfg.games[idx % cfg.games.length];
		el.querySelector('[data-nudge-title]').textContent = cfg.title;
		el.querySelector('[data-nudge-text]').textContent = (cfg.text || '%s').replace('%s', g.title);
		const img = el.querySelector('[data-nudge-img]');
		if (g.img) img.src = g.img;
		img.alt = g.title;
		el.querySelectorAll('[data-nudge-link]').forEach((a) => (a.href = g.url));
	};

	const hide = (snoozeDays) => {
		el.classList.remove('is-open');
		setTimeout(() => (el.hidden = true), 350);
		if (snoozeDays) store(() => localStorage.setItem(KEY_SNOOZE, String(Date.now() + snoozeDays * DAY)));
		cleanup();
	};

	const show = () => {
		if (shown || busy()) return;
		shown = true;
		store(() => sessionStorage.setItem(KEY_SESSION, '1'));
		fill();
		el.hidden = false;
		requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-open')));
		cleanup();
	};

	el.querySelectorAll('[data-nudge-close]').forEach((b) => b.addEventListener('click', () => hide(3)));
	el.querySelector('[data-nudge-go]')?.addEventListener('click', () => store(() => localStorage.setItem(KEY_SNOOZE, String(Date.now() + 7 * DAY))));
	el.querySelector('[data-nudge-shuffle]')?.addEventListener('click', () => {
		idx += 1;
		fill();
	});
	el.addEventListener('keydown', (e) => e.key === 'Escape' && hide(3));

	// --- Triggers ---
	const elapsed = () => (Date.now() - start) / 1000;
	const check = () => {
		if (shown) return;
		const t = elapsed();
		const idle = (Date.now() - lastActive) / 1000;
		if (cfg.ctx === '404' && t >= 2.5) return show();
		if (cfg.ctx === 'search' && t >= 20) return show();
		if (cfg.ctx === 'article' && ((t >= 75 && maxDepth >= 0.55) || (t >= 60 && idle >= 40))) return show();
		if (cfg.ctx === 'browse' && t >= 90 && maxDepth >= 0.4) return show();
	};
	const onScroll = () => {
		maxDepth = Math.max(maxDepth, depth());
		lastActive = Date.now();
	};
	const onActive = () => (lastActive = Date.now());
	const onExit = (e) => {
		if (cfg.ctx === 'article' && !e.relatedTarget && e.clientY <= 0 && elapsed() >= 30) show();
	};
	const timer = setInterval(check, 1000);
	addEventListener('scroll', onScroll, { passive: true });
	addEventListener('pointerdown', onActive, { passive: true });
	addEventListener('keydown', onActive, { passive: true });
	document.addEventListener('mouseout', onExit);

	function cleanup() {
		clearInterval(timer);
		removeEventListener('scroll', onScroll);
		removeEventListener('pointerdown', onActive);
		removeEventListener('keydown', onActive);
		document.removeEventListener('mouseout', onExit);
	}
}
