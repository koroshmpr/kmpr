/**
 * Arcade monetisation (demand test) — loaded only on Game Center pages.
 * No deps. Server markup lives in inc/arcade-monetize.php (cards + inert <template>s + <dialog>).
 *
 *  - price variant A/B per visitor (localStorage), revealed via html[data-am-v]
 *  - impressions (once per session per unit, ≥50% visible), clicks → batched POST /arcade/track
 *  - fills [data-kmpr-slot="game-intro"|"game-over"] from the templates
 *  - pre-signup dialog → POST /arcade/presignup (never a payment)
 */
(() => {
	const C = window.kmprAM;
	if (!C || !C.rest) return;
	const d = document;

	/* ---------- storage helpers (private mode safe) ---------- */
	const get = (s, k) => {
		try {
			return s.getItem(k);
		} catch (e) {
			return null;
		}
	};
	const set = (s, k, v) => {
		try {
			s.setItem(k, v);
		} catch (e) {
			/* ignore */
		}
	};
	const LS = window.localStorage;
	const SS = window.sessionStorage;

	let variant = get(LS, 'kmpr_am_v');
	if (variant !== 'A' && variant !== 'B') {
		variant = Math.random() < 0.5 ? 'A' : 'B';
		set(LS, 'kmpr_am_v', variant);
	}
	d.documentElement.dataset.amV = variant;

	/* ---------- tracking (batched) ---------- */
	const queue = [];
	let timer = 0;
	const flush = () => {
		clearTimeout(timer);
		if (!queue.length) return;
		const body = JSON.stringify({ ev: queue.splice(0, 15) });
		try {
			fetch(C.rest + '/track', { method: 'POST', keepalive: true, credentials: 'omit', headers: { 'Content-Type': 'application/json' }, body }).catch(() => {});
		} catch (e) {
			/* ignore */
		}
		if (queue.length) flush();
	};
	const track = (ev, now) => {
		if (!C.track) return;
		// Speculatively prerendered page (games-pwa.php): count only once the visitor actually lands on it.
		if (document.prerendering) {
			document.addEventListener('prerenderingchange', () => track(ev, now), { once: true });
			return;
		}
		queue.push(ev);
		clearTimeout(timer);
		if (now) flush();
		else timer = setTimeout(flush, 1500);
	};
	addEventListener('pagehide', flush);
	d.addEventListener('visibilitychange', () => d.visibilityState === 'hidden' && flush());

	/** Unit element → base event. */
	const evOf = (el) => {
		const t = el.dataset.am;
		const ev = { t };
		if (el.dataset.amS) ev.s = el.dataset.amS;
		if (t === 'pass') ev.v = variant;
		if (t === 'sp') ev.g = +el.dataset.amG || C.gid;
		return ev;
	};

	let seen = {};
	try {
		seen = JSON.parse(get(SS, 'kmpr_am_seen') || '{}') || {};
	} catch (e) {
		seen = {};
	}
	const io =
		'IntersectionObserver' in window
			? new IntersectionObserver(
					(entries) =>
						entries.forEach((en) => {
							if (!en.isIntersecting) return;
							io.unobserve(en.target);
							const ev = evOf(en.target);
							const key = [ev.t, ev.s, ev.v, ev.g].join('.');
							if (seen[key]) return;
							seen[key] = 1;
							set(SS, 'kmpr_am_seen', JSON.stringify(seen));
							track({ ...ev, a: 'i' });
						}),
					{ threshold: 0.5 }
				)
			: null;
	const watch = (root) => io && (root.matches && root.matches('[data-am]') ? [root] : []).concat([...root.querySelectorAll('[data-am]')]).forEach((el) => io.observe(el));

	/* ---------- dialog ---------- */
	const modal = d.querySelector('[data-am-modal]');
	let ctx = { s: 'hub' };
	let openedAt = 0;
	const profile = () => {
		try {
			return JSON.parse(get(LS, 'kmpr_arcade_v1') || 'null') || {};
		} catch (e) {
			return {};
		}
	};
	const openModal = (surface) => {
		if (!modal || typeof modal.showModal !== 'function') return false;
		ctx = { s: surface === 'over' ? 'over' : 'hub' };
		const form = modal.querySelector('[data-am-form]');
		const done = get(LS, 'kmpr_am_done') === '1';
		modal.querySelector('[data-am-step="form"]').hidden = done;
		modal.querySelector('[data-am-step="done"]').hidden = !done;
		const nick = form.elements.nickname;
		if (!nick.value) nick.value = profile().nickname || '';
		status('');
		openedAt = Date.now();
		modal.showModal();
		(done ? modal.querySelector('[data-am-step="done"] button') : nick.value ? form.elements.contact : nick).focus();
		return true;
	};
	const status = (msg, tone) => {
		const el = modal && modal.querySelector('[data-am-status]');
		if (!el) return;
		el.textContent = msg;
		el.dataset.tone = tone || '';
	};
	if (modal) {
		// Keep keys inside the dialog away from the game shell's key handlers.
		modal.addEventListener('keydown', (e) => e.stopPropagation());
		modal.addEventListener('click', (e) => {
			if (e.target === modal || e.target.closest('[data-am-close]')) modal.close();
		});
		const form = modal.querySelector('[data-am-form]');
		form.addEventListener('submit', (e) => {
			e.preventDefault();
			const btn = form.querySelector('[type="submit"]');
			if (btn.disabled) return;
			btn.disabled = true;
			status('در حال ثبت…');
			const p = profile();
			fetch(C.rest + '/presignup', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					nickname: form.elements.nickname.value.trim(),
					contact: form.elements.contact.value.trim(),
					website: form.elements.website.value,
					opened: openedAt,
					variant,
					surface: ctx.s,
					game: C.gid || 0,
					player: p.id || '',
				}),
			})
				.then((r) => r.json().catch(() => ({})))
				.then((r) => {
					if (r && r.ok) {
						set(LS, 'kmpr_am_done', '1');
						modal.querySelector('[data-am-step="form"]').hidden = true;
						const done = modal.querySelector('[data-am-step="done"]');
						done.hidden = false;
						done.querySelector('h2').focus();
					} else {
						status((r && r.message) || 'ثبت انجام نشد؛ دوباره امتحان کن.', 'warn');
						if (r && r.field) form.elements[r.field] && form.elements[r.field].focus();
					}
				})
				.catch(() => status('اتصال برقرار نشد؛ دوباره امتحان کن.', 'warn'))
				.finally(() => (btn.disabled = false));
		});
	}

	/* ---------- clicks ---------- */
	d.addEventListener('click', (e) => {
		const go = e.target.closest('[data-am-go]');
		if (!go) return;
		const unit = go.closest('[data-am]');
		if (!unit) return;
		const kind = go.dataset.amGo;
		track({ ...evOf(unit), a: 'c' }, true);
		if (kind === 'buy') {
			if (openModal(unit.dataset.amS)) e.preventDefault();
		} else if (kind === 'pay' && C.pay) {
			const price = (C.price && C.price[variant]) || '';
			go.href = C.pay.replace('{price}', price).replace('{variant}', variant);
		}
	});

	/* ---------- hub / sidebar cards already in the page ---------- */
	watch(d);

	/* ---------- game slots ---------- */
	const tpl = (name) => {
		const t = d.querySelector(`template[data-am-tpl="${name}"]`);
		return t ? t.content.cloneNode(true) : null;
	};
	const sponsorNode = () => tpl('sponsor') || tpl('sponsor-cta');

	let overs = +get(LS, 'kmpr_am_go') || 0;
	let plan = null; // content decided for the current game-over
	const decide = () => {
		const every = Math.max(2, +C.every || 3);
		const out = [];
		let main = null;
		if (C.pass && overs > 1 && overs % every === 0) main = 'pass';
		else if (C.adv && (overs % 2 === 0 || !C.sup)) main = 'adv';
		else if (C.sup) main = 'support';
		else if (C.adv) main = 'adv';
		if (d.querySelector('template[data-am-tpl="sponsor"]') || main !== 'adv') out.push('sponsor');
		if (main) out.push(main);
		return out;
	};
	const fill = (slot, parts) => {
		if (!slot || slot.dataset.amFilled) return;
		slot.dataset.amFilled = '1';
		const frag = d.createDocumentFragment();
		parts.forEach((p) => {
			const n = p === 'sponsor' ? sponsorNode() : tpl(p);
			if (n) frag.appendChild(n);
		});
		if (!frag.childNodes.length) return;
		slot.classList.add('am-slot');
		slot.appendChild(frag);
		watch(slot);
	};
	const fillAll = () => {
		d.querySelectorAll('[data-kmpr-slot="game-intro"]:not([data-am-filled])').forEach((s) => fill(s, ['sponsor']));
		if (plan) d.querySelectorAll('[data-kmpr-slot="game-over"]:not([data-am-filled])').forEach((s) => fill(s, plan));
	};

	if (C.gid) {
		addEventListener('kmpr:game-start', () => {
			track({ t: 'pl', a: 's', g: C.gid });
			plan = null;
		});
		addEventListener('kmpr:game-over', () => {
			overs += 1;
			set(LS, 'kmpr_am_go', String(overs));
			plan = decide();
			fillAll();
			requestAnimationFrame(fillAll);
		});
		// Screens are swapped via innerHTML: fill fresh slots in the observer microtask (before paint → no shift).
		new MutationObserver(fillAll).observe(d.querySelector('[data-overlay]') || d.body, { childList: true, subtree: true });
		addEventListener('kmpr:focus-change', (e) => {
			// Focus mode hides page chrome; a pre-signup dialog must never sit over an active run.
			if (e.detail && e.detail.active && modal && modal.open && !plan) modal.close();
		});
		fillAll();
	}
})();
