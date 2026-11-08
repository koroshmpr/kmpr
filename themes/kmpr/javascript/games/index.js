/**
 * KMPR Arcade entry — tiny bootstrap. Everything else is dynamically imported:
 * the shell + one engine on a game page, the hub module on /games/.
 */
const boot = () => {
	// App layer (service worker, install prompt, app bar, prefetch) — tiny, every game-center page.
	import('./pwa.js').then(({ initPwa }) => initPwa());
	const shells = document.querySelectorAll('[data-game-shell]');
	if (shells.length) {
		import('./shell.js').then(({ createShell }) => shells.forEach((el) => createShell(el)));
	}
	if (document.querySelector('[data-arcade-hub], [data-game-best], [data-arcade-profile], [data-gh]')) {
		import('./hub.js').then(({ initHub }) => initHub());
	}
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();
