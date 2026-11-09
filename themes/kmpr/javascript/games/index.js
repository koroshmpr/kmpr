/**
 * KMPR Arcade entry — tiny bootstrap. Everything else is dynamically imported:
 * the shell + one engine on a game page, the hub module on /games/.
 * The engine chunk is requested in parallel with the shell (no import waterfall).
 */
import { loaderHTML } from './lib/loader.js';

const boot = () => {
	// App layer (service worker, install prompt, app bar, prefetch, page loader) — tiny, every game-center page.
	import('./pwa.js').then(({ initPwa }) => initPwa());
	const shells = document.querySelectorAll('[data-game-shell]');
	if (shells.length) {
		shells.forEach((el) => {
			const f = el.querySelector('[data-frame]');
			if (f && !f.querySelector('.kl')) f.insertAdjacentHTML('beforeend', loaderHTML());
		});
		const catalog = import('./catalog.js');
		shells.forEach((el) => catalog.then(({ ENGINE_LOADERS }) => ENGINE_LOADERS[el.dataset.engine] && ENGINE_LOADERS[el.dataset.engine]().catch(() => {})));
		import('./shell.js').then(({ createShell }) => shells.forEach((el) => createShell(el)));
	}
	if (document.querySelector('[data-arcade-hub], [data-game-best], [data-arcade-profile], [data-gh]')) {
		import('./hub.js').then(({ initHub }) => initHub());
	}
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();
