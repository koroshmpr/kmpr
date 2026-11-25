/**
 * Visitor accounts — entry (IIFE bundle public/js/account.js, loaded in <head> with defer when the
 * dashboard switch is on). Sync runs first, before the games module reads the arcade profile.
 */
import { C, bootSync } from './sync.js';
import { openAuth, renderPage } from './ui.js';
import { initFriends } from './friends.js';

if (C.rest) {
	bootSync();
	document.addEventListener('click', (e) => {
		const t = e.target.closest('[data-acct-open]');
		if (!t || C.user) return;
		e.preventDefault();
		openAuth(t.dataset.acctOpen === 'register' ? 'register' : 'login');
	});
	const ready = () => {
		const page = document.querySelector('[data-acct-page]');
		if (page) renderPage(page);
		initFriends();
		window.kmprAccounts = { open: openAuth, get user() { return C.user; } };
	};
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
	else ready();
}
