/**
 * Korosh — cute 2D cartoon hero in SIDE PROFILE (classic platformer view: he looks where he runs).
 * Shared by every game that stars the site owner (see GAME-RULES.md).
 * Look (from his references): round men's afro (more volume behind the head), wayfarer sunglasses,
 * moustache (no beard), lips, royal-blue short-sleeve shirt worn OPEN over a white tee (visible at the
 * chest), black trousers, white sneakers, watch, coffee cup in the front hand.
 * Animated procedurally: legs/arms swing opposite, shirt flap sways, hair bounces, attack swing.
 * Forms recolour the shirt: none/coffee = royal blue · dev = cyan · jet = indigo.
 *
 * drawHero(ctx, cx, top, size, form, frame, face, sx, sy, phase, shoot)
 *   (cx, top) = horizontal centre / top of the hitbox in world px (feet at top + HERO_H[size]); face 1 = right.
 *   frame: 'stand' | 'stand2' | 'run' | 'run1' | 'run2' | 'jump'; phase = run cycle (0..1); shoot = 1→0 swing.
 */
export const OL = '#16120f';
export const HERO_H = { big: 32, small: 22 };
const SHIRT = { none: ['#1d4ed8', '#3b6ef0'], coffee: ['#1d4ed8', '#3b6ef0'], dev: ['#0891b2', '#22b8d6'], jet: ['#4338ca', '#6366f1'], army: ['#4d5d3a', '#6b7c4f'] };
export const FORMS = { coffee: SHIRT.coffee[0], dev: SHIRT.dev[0], jet: SHIRT.jet[0] };
const SKIN = '#d9a07a', SKIN_D = '#b57d5b', HAIR = '#121016', BEARD = '#231a16', PANTS = '#17171c';

// Round afro seen from the side: centre behind the head, smooth outline with tiny curly bumps,
// irregular curl texture and a soft light on the top-front.
const AFRO = (() => {
	let seed = 31;
	const r = () => ((seed = (seed * 9301 + 49297) % 233280) / 233280);
	const cx = -1.4, cy = -5.6, R = 8.2;
	const edge = [];
	// round curl lobes all around the edge (scalloped silhouette)
	for (let i = 0; i < 22; i++) { const a = (i / 22) * Math.PI * 2 + r() * 0.12; const k = 0.97 + r() * 0.05; edge.push({ x: cx + Math.cos(a) * R * k, y: cy + Math.sin(a) * R * 0.95 * k, s: 2 + r() * 0.6 }); }
	// a few springy strands escaping the volume (upper / back side)
	const strands = [];
	[-2.6, -2.1, -1.55, -1.05, -0.5, 2.7, 3.05].forEach((a) => { strands.push({ a, len: 2.4 + r() * 1.6, turns: 2 + Math.floor(r() * 2), ph: r() * 6 }); });
	const tex = [];
	for (let i = 0; i < 120; i++) { const a = r() * Math.PI * 2, d = Math.sqrt(r()) * 1.02; tex.push({ x: cx + Math.cos(a) * R * d, y: cy + Math.sin(a) * R * 0.95 * d, s: 0.55 + r() * 0.5, st: r() * 6.28, hi: Math.sin(a) < 0.1 && Math.cos(a) > -0.35 && d > 0.35 }); }
	return { cx, cy, R, edge, tex, strands };
})();

/** Kept for older callers (nothing to load). */
export const sprites = {};
export function buildSprites() {}
export function loadSprites() { return Promise.resolve(); }

const sleeve = (ctx, fill, hem) => {
	ctx.beginPath(); ctx.moveTo(-2.2, -0.4); ctx.quadraticCurveTo(0, -1.9, 2.2, -0.4); ctx.lineTo(2.7, 3.7); ctx.quadraticCurveTo(0, 4.4, -2.7, 3.7); ctx.closePath();
	ctx.fillStyle = fill; ctx.fill(); ctx.lineWidth = 0.9; ctx.strokeStyle = OL; ctx.stroke();
	ctx.beginPath(); ctx.moveTo(-2.5, 2.8); ctx.quadraticCurveTo(0, 3.4, 2.5, 2.8); ctx.lineWidth = 0.4; ctx.strokeStyle = hem; ctx.stroke();
};
let umbrellaT = null;
function drawCanopy(ctx, now) {
	ctx.save(); ctx.setTransform(umbrellaT); ctx.rotate(Math.sin(now * 2) * 0.05);
	ctx.beginPath(); ctx.moveTo(-13, -32); ctx.quadraticCurveTo(0.8, -46, 14.6, -32);
	for (let i = 0; i < 4; i++) ctx.quadraticCurveTo(11.15 - i * 6.9, -34, 7.7 - i * 6.9, -32);
	ctx.closePath(); ctx.fillStyle = '#2563eb'; ctx.fill(); ctx.lineWidth = 0.9; ctx.strokeStyle = OL; ctx.stroke();
	ctx.strokeStyle = '#93c5fd'; ctx.lineWidth = 0.5; ctx.beginPath(); ctx.moveTo(0.8, -39.4); ctx.lineTo(-5.2, -32.6); ctx.moveTo(0.8, -39.4); ctx.lineTo(7, -32.6); ctx.stroke();
	ctx.fillStyle = OL; ctx.beginPath(); ctx.arc(0.8, -39.6, 0.6, 0, 7); ctx.fill();
	ctx.restore();
}
/** stage-themed item in the front hand (frame: fist at 0,0, always upright). */
function drawHeld(ctx, hold, now, run, shoot) {
	const thumb = () => rr(ctx, -0.2, -0.9, 1.6, 1.8, 0.8, SKIN, 0.6);
	switch (hold) {
		case 'none': return;
		case 'yogurt': // the «ماست سون» cup
			ctx.beginPath(); ctx.moveTo(0.2, -3.6); ctx.lineTo(4.4, -3.6); ctx.lineTo(3.9, 1.4); ctx.lineTo(0.7, 1.4); ctx.closePath(); ctx.fillStyle = '#f8fafc'; ctx.fill(); ctx.lineWidth = 0.8; ctx.strokeStyle = OL; ctx.stroke();
			ctx.fillStyle = '#1d4ed8'; ctx.fillRect(0.6, -2.2, 3.4, 1.6); ctx.fillStyle = '#fff'; ctx.font = '900 1.3px sans-serif'; ctx.textAlign = 'center'; ctx.fillText('7', 2.3, -0.9);
			rr(ctx, 0, -4.3, 4.6, 0.9, 0.4, '#e5e7eb', 0.6); thumb(); return;
		case 'bag': // Gilda handbag hanging from the fist
			ctx.strokeStyle = OL; ctx.lineWidth = 0.7; ctx.beginPath(); ctx.arc(1.2, 0.6, 1.6, Math.PI, 0); ctx.stroke();
			rr(ctx, -1.2, 0.8, 5, 4, 1.2, '#be185d', 0.8); ctx.fillStyle = '#fbcfe8'; ctx.fillRect(-0.6, 2, 3.8, 0.6); thumb(); return;
		case 'phone': // phone with a live price chart
			rr(ctx, 0.2, -4.6, 3, 5.4, 0.6, '#0f172a', 0.7); ctx.fillStyle = '#022c22'; ctx.fillRect(0.6, -4.1, 2.2, 4.2);
			ctx.strokeStyle = '#4ade80'; ctx.lineWidth = 0.35; ctx.beginPath(); ctx.moveTo(0.7, -0.6); ctx.lineTo(1.3, -1.6); ctx.lineTo(1.8, -1.1); ctx.lineTo(2.7, -3.4); ctx.stroke(); thumb(); return;
		case 'flowers': // bouquet for Maedeh
			ctx.strokeStyle = '#15803d'; ctx.lineWidth = 0.6; ctx.beginPath(); ctx.moveTo(1.4, 0.4); ctx.lineTo(0.8, -3.4); ctx.moveTo(1.4, 0.4); ctx.lineTo(2.2, -3.6); ctx.moveTo(1.4, 0.4); ctx.lineTo(3.2, -3); ctx.stroke();
			[[0.8, -3.8, '#e11d48'], [2.2, -4.2, '#f43f5e'], [3.3, -3.3, '#be123c']].forEach(([x, y, c]) => { ctx.beginPath(); ctx.arc(x, y, 0.95, 0, 7); ctx.fillStyle = c; ctx.fill(); ctx.lineWidth = 0.4; ctx.strokeStyle = OL; ctx.stroke(); });
			ctx.fillStyle = '#fde68a'; ctx.beginPath(); ctx.moveTo(0.2, -1.4); ctx.lineTo(3.2, -1.4); ctx.lineTo(1.9, 0.9); ctx.closePath(); ctx.fill(); thumb(); return;
		case 'umbrella': // shaft in the hand; the canopy is drawn after the head (see drawCanopy) so the afro never hides it
			ctx.strokeStyle = OL; ctx.lineWidth = 0.6; ctx.beginPath(); ctx.moveTo(0.8, 1.2); ctx.lineTo(0.8, -35); ctx.stroke();
			ctx.beginPath(); ctx.arc(0.2, 1.2, 0.7, 0, Math.PI); ctx.stroke();
			umbrellaT = ctx.getTransform();
			thumb(); return;
		case 'letter': // sealed envelope
			rr(ctx, -0.2, -3.2, 5, 3.4, 0.4, '#fef3c7', 0.7); ctx.strokeStyle = OL; ctx.lineWidth = 0.5; ctx.beginPath(); ctx.moveTo(-0.2, -3.2); ctx.lineTo(2.3, -1.4); ctx.lineTo(4.8, -3.2); ctx.stroke();
			ctx.fillStyle = '#b91c1c'; ctx.beginPath(); ctx.arc(2.3, -1.2, 0.6, 0, 7); ctx.fill(); thumb(); return;
		case 'papers': // stack of articles
			for (let i = 2; i >= 0; i--) rr(ctx, 0 + i * 0.4, -3.4 - i * 0.5, 4.2, 4.6, 0.3, i ? '#e2e8f0' : '#fff', 0.5);
			ctx.fillStyle = '#94a3b8'; for (let i = 0; i < 3; i++) ctx.fillRect(0.6, -2.6 + i * 1.1, 3 - (i % 2), 0.4); thumb(); return;
		case 'book': // WordPress book
			rr(ctx, -0.2, -4.2, 4.6, 5.6, 0.5, '#2563eb', 0.7); ctx.fillStyle = '#fff'; ctx.font = '900 1.6px sans-serif'; ctx.textAlign = 'center'; ctx.fillText('WP', 2.1, -1.3); ctx.fillStyle = '#1e3a8a'; ctx.fillRect(-0.2, -4.2, 0.8, 5.6); thumb(); return;
		case 'laptop': // closed laptop under the hand
			rr(ctx, -1, -1.2, 6.4, 3.6, 0.6, '#94a3b8', 0.7); ctx.fillStyle = '#e2e8f0'; ctx.beginPath(); ctx.arc(2.2, 0.6, 0.5, 0, 7); ctx.fill(); thumb(); return;
		default: // coffee to go (steaming when standing)
			rr(ctx, 0.4, -3.4, 3.6, 4.8, 0.8, '#fafaf5', 0.8);
			ctx.fillStyle = '#b45309'; ctx.fillRect(0.6, -1.6, 3.2, 1.3);
			rr(ctx, 0.1, -4.2, 4.2, 1.1, 0.5, '#e7e5e4', 0.7);
			thumb();
			if (!run && shoot <= 0) {
				ctx.strokeStyle = 'rgba(255,255,255,.55)'; ctx.lineWidth = 0.45;
				const st = now * 2;
				ctx.beginPath(); ctx.moveTo(2.2, -4.6); ctx.bezierCurveTo(1.4 + Math.sin(st) * 0.5, -5.6, 3 + Math.sin(st) * 0.5, -6.4, 2.2, -7.4); ctx.stroke();
			}
	}
}
const rr = (ctx, x, y, w, h, r, fill, lw = 1) => {
	ctx.beginPath();
	ctx.roundRect(x, y, w, h, r);
	ctx.fillStyle = fill;
	ctx.fill();
	ctx.lineWidth = lw;
	ctx.strokeStyle = OL;
	ctx.stroke();
};

/** tired 0..1: glasses slide down, heavy eyelid, slumped head, yawns (late-night programmer). */
/** hold: what is in his hand (stage themed) — coffee | yogurt | bag | phone | flowers | umbrella | letter | papers | book | laptop | none */
export function drawHero(ctx, cx, top, size, form, frame, face = 1, sx = 1, sy = 1, phase = null, shoot = 0, tired = 0, hold = 'coffee') {
	const [shirt, shirtL] = SHIRT[form] || SHIRT.none;
	const k = size === 'small' ? HERO_H.small / HERO_H.big : 1;
	const now = performance.now() / 1000;
	const run = frame === 'run' || frame === 'run1' || frame === 'run2';
	const air = frame === 'jump';
	const ph = phase !== null ? phase : frame === 'run1' ? 0.25 : frame === 'run2' ? 0.75 : 0;
	const sw = run ? Math.sin(ph * Math.PI * 2) : 0;
	const breeze = frame === 'stand2' ? 1 : 0;
	const bob = run ? Math.abs(Math.cos(ph * Math.PI * 2)) * 0.8 : 0;

	ctx.save();
	ctx.translate(Math.round(cx), Math.round(top + HERO_H[size]));
	ctx.scale(face * sx * k, sy * k);
	ctx.lineJoin = 'round';
	ctx.lineCap = 'round';
	ctx.translate(0, -bob);
	if (shoot > 0) ctx.rotate(0.1 * shoot);
	if (run) ctx.rotate(0.06); // slight forward lean while running

	// --- legs (side view: toes point forward)
	const leg = (ang, back) => {
		ctx.save();
		ctx.translate(0, -11);
		ctx.rotate(ang);
		rr(ctx, -2.2, 0, 4.4, 9, 2, back ? '#0c0c10' : PANTS, 0.9);
		rr(ctx, -2.4, 8.2, 6.6, 2.8, 1.4, back ? '#e2e8f0' : '#f8fafc', 0.9);
		ctx.restore();
	};
	leg(air ? 0.5 : -sw * 0.6, true);

	// --- back arm (behind the body)
	const armBack = air ? -1.9 : run ? -sw * 0.8 : 0.05;
	ctx.save();
	ctx.translate(-0.4, -20);
	ctx.rotate(armBack);
	rr(ctx, -1.5, 2, 3, 7, 1.5, SKIN_D, 0.9);
	sleeve(ctx, shirt, 'rgba(0,0,0,.3)');
	ctx.restore();

	leg(air ? -0.7 : sw * 0.6, false);

	// --- torso in profile: shirt body, white tee showing at the open front, front flap with buttons
	// long, loose shirt: the hem flares out and sways (trails back while running)
	const sway = Math.sin(now * 2.2) * 0.35 + (run ? -0.9 - Math.abs(sw) * 0.6 : 0) + (air ? -0.8 : 0);
	const body = () => {
		ctx.beginPath(); ctx.moveTo(-3, -21.6); ctx.lineTo(2.8, -21.6); ctx.quadraticCurveTo(4.3, -21.4, 4.2, -18.6);
		ctx.quadraticCurveTo(4.3, -12, 4.8 + sway * 0.6, -9); ctx.lineTo(5.1 + sway, -7.2);
		ctx.quadraticCurveTo(2.4 + sway, -6.9, 0.4 + sway, -8.6); // shirt-tail curves up at the side seam
		ctx.quadraticCurveTo(-2.2 + sway, -6.8, -4.7 + sway, -7.3);
		ctx.quadraticCurveTo(-4.3, -12, -3.9, -18.6); ctx.quadraticCurveTo(-3.9, -21.6, -3, -21.6); ctx.closePath();
	};
	body(); ctx.fillStyle = shirt; ctx.fill();
	ctx.fillStyle = '#f8fafc';
	ctx.beginPath(); ctx.moveTo(1.6, -21.4); ctx.lineTo(3.9, -21.2); ctx.quadraticCurveTo(4.3, -15, 4.1, -9.4); ctx.lineTo(1.9, -9.4); ctx.closePath(); ctx.fill();
	const flap = Math.sin(now * 3) * 0.35 * breeze + (run ? Math.abs(sw) * 0.9 : 0) - sway * 0.4;
	ctx.fillStyle = shirt; ctx.strokeStyle = OL; ctx.lineWidth = 1;
	ctx.beginPath(); ctx.moveTo(1.6, -21.4); ctx.lineTo(2.3, -21.4); ctx.quadraticCurveTo(3.2 + flap * 0.3, -13, 3.7 + flap * 0.3 + sway, -7); ctx.lineTo(1.6 - flap + sway, -6.9); ctx.closePath(); ctx.fill(); ctx.stroke();
	ctx.fillStyle = '#f1f5f9';
	for (let i = 0; i < 4; i++) { ctx.beginPath(); ctx.arc(2 - flap * (i / 5) + sway * i * 0.08, -18.6 + i * 3.1, 0.42, 0, 7); ctx.fill(); }
	ctx.strokeStyle = 'rgba(0,0,0,.22)'; ctx.lineWidth = 0.4; ctx.beginPath(); ctx.moveTo(2.7, -20.8); ctx.quadraticCurveTo(3.3 + flap * 0.3, -13, 3.4 + flap * 0.3 + sway, -7.4); ctx.stroke(); // placket edge
	// fabric folds
	ctx.strokeStyle = shirtL; ctx.lineWidth = 0.5;
	ctx.beginPath(); ctx.moveTo(-2.4, -12.5); ctx.quadraticCurveTo(-2.6 + sway * 0.5, -9.6, -2.3 + sway, -7.2); ctx.moveTo(-0.6, -16); ctx.quadraticCurveTo(-0.9, -14, -0.6, -12.6); ctx.stroke();
	ctx.strokeStyle = 'rgba(0,0,0,.25)'; ctx.lineWidth = 0.45; ctx.beginPath(); ctx.moveTo(0.4 + sway, -8.6); ctx.lineTo(0.2, -11.6); ctx.stroke(); // side seam
	body(); ctx.lineWidth = 1; ctx.strokeStyle = OL; ctx.stroke();
	// neck first; the collar wraps around it
	rr(ctx, -0.4, -24.6, 3, 3.6, 1, SKIN, 0.8);
	// tee neckline (white crew neck visible in the open front)
	ctx.fillStyle = '#f8fafc'; ctx.strokeStyle = OL; ctx.lineWidth = 0.6;
	ctx.beginPath(); ctx.moveTo(0.2, -22); ctx.quadraticCurveTo(1.6, -20.9, 3.2, -21.9); ctx.lineTo(3.4, -21); ctx.quadraticCurveTo(1.6, -19.8, 0.2, -21.2); ctx.closePath(); ctx.fill(); ctx.stroke();
	// collar band behind the neck (stands up a little)
	ctx.fillStyle = shirtL; ctx.lineWidth = 0.8;
	ctx.beginPath(); ctx.moveTo(-2.8, -21); ctx.lineTo(-2.3, -24); ctx.quadraticCurveTo(-1, -24.8, 0, -24.2); ctx.lineTo(0.1, -21.6); ctx.closePath(); ctx.fill(); ctx.stroke();
	// front collar: rolls over the shoulder and its point lies flat on the chest
	const collar = () => { ctx.beginPath(); ctx.moveTo(2.3, -23.7); ctx.quadraticCurveTo(3.5, -22.8, 3.7, -21.5); ctx.lineTo(5.1, -18.7); ctx.quadraticCurveTo(4, -19, 3.1, -19.5); ctx.quadraticCurveTo(2, -20.9, 1.5, -22.7); ctx.closePath(); };
	ctx.save(); ctx.translate(0.35, 0.45); collar(); ctx.fillStyle = 'rgba(0,0,0,.3)'; ctx.fill(); ctx.restore(); // shadow on the chest
	collar(); ctx.fillStyle = shirtL; ctx.fill(); ctx.lineWidth = 0.65; ctx.strokeStyle = OL; ctx.stroke();
	ctx.setLineDash([0.45, 0.45]); ctx.strokeStyle = 'rgba(255,255,255,.55)'; ctx.lineWidth = 0.25;
	ctx.beginPath(); ctx.moveTo(3.2, -21.6); ctx.lineTo(4.4, -19.2); ctx.stroke(); ctx.setLineDash([]); // top-stitch
	ctx.strokeStyle = 'rgba(0,0,0,.25)'; ctx.lineWidth = 0.4; ctx.beginPath(); ctx.moveTo(2.1, -22.9); ctx.quadraticCurveTo(2.6, -21, 3.3, -19.8); ctx.stroke(); // roll

	// --- front arm: elbow bent, coffee cup held level so it doesn't spill
	const armFront = shoot > 0 ? -1.45 - shoot * 1.1 : air ? -0.7 : run ? sw * 0.3 + 0.15 : 0.12;
	const fore = shoot > 0 ? 0 : -1.42 - armFront + (run ? Math.sin(ph * Math.PI * 4) * 0.04 : 0);
	const lean = (shoot > 0 ? 0.1 * shoot : 0) + (run ? 0.06 : 0);
	ctx.save();
	ctx.translate(0.6, -20);
	ctx.rotate(armFront);
	rr(ctx, -1.5, 2, 3, 4.6, 1.5, SKIN, 0.9); // upper arm
	ctx.save();
	ctx.translate(0, 5.6);
	ctx.rotate(fore);
	rr(ctx, -1.4, -0.8, 2.8, 5.8, 1.4, SKIN, 0.9); // forearm
	rr(ctx, -1.6, 3, 3.2, 1.3, 0.6, '#1f2937', 0.6); // watch
	ctx.translate(0, 5.4);
	ctx.rotate(-(armFront + fore) - lean); // cup stays upright whatever the arm does
	ctx.beginPath(); ctx.arc(0.2, 0, 1.4, 0, 7); ctx.fillStyle = SKIN; ctx.fill(); ctx.lineWidth = 0.8; ctx.strokeStyle = OL; ctx.stroke(); // fist
	drawHeld(ctx, hold, now, run, shoot);
	ctx.restore();
	sleeve(ctx, shirt, shirtL); // sleeve over the shoulder
	ctx.restore();

	// --- head in profile
	const hb = run ? Math.sin(ph * Math.PI * 4) * 0.5 : Math.sin(now * 2) * 0.25;
	ctx.save();
	ctx.translate(1.4 + tired * 0.5, -29.6 + tired * 0.7);
	if (tired) ctx.rotate(tired * 0.1); // head droops forward
	// afro (behind the face)
	const vol = (grow, fill) => {
		ctx.fillStyle = fill;
		ctx.beginPath(); ctx.ellipse(AFRO.cx, AFRO.cy + hb, AFRO.R - 1.6 + grow, AFRO.R * 0.95 - 1.6 + grow, 0, 0, 7); ctx.fill();
		AFRO.edge.forEach((e) => { ctx.beginPath(); ctx.arc(e.x, e.y + hb, e.s + grow, 0, 7); ctx.fill(); });
	};
	// stray strands (drawn first so their roots tuck under the volume)
	AFRO.strands.forEach((st) => {
		const ox = AFRO.cx + Math.cos(st.a) * (AFRO.R + 1.2), oy = AFRO.cy + hb * 1.3 + Math.sin(st.a) * (AFRO.R * 0.95 + 1.2);
		const dx = Math.cos(st.a), dy = Math.sin(st.a), nx = -dy, ny = dx;
		ctx.beginPath();
		for (let t = 0; t <= 24; t++) {
			const u = t / 24, w = Math.sin(u * Math.PI * 2 * st.turns + st.ph + now * 2) * 0.7 * (1 - u * 0.3);
			const px = ox - dx * 1.6 + dx * u * st.len + nx * w, py = oy - dy * 1.6 + dy * u * st.len + ny * w;
			t ? ctx.lineTo(px, py) : ctx.moveTo(px, py);
		}
		ctx.lineWidth = 1.3; ctx.strokeStyle = OL; ctx.stroke();
		ctx.lineWidth = 0.55; ctx.strokeStyle = '#2a2430'; ctx.stroke();
	});
	vol(0.9, OL);
	vol(0, HAIR);
	const lg = ctx.createRadialGradient(AFRO.cx + 3, AFRO.cy - 4 + hb, 0.5, AFRO.cx + 3, AFRO.cy - 4 + hb, 8);
	lg.addColorStop(0, 'rgba(110,100,125,.45)'); lg.addColorStop(1, 'rgba(110,100,125,0)');
	ctx.fillStyle = lg; ctx.beginPath(); ctx.ellipse(AFRO.cx, AFRO.cy + hb, AFRO.R, AFRO.R * 0.95, 0, 0, 7); ctx.fill();
	// texture = small round coils: soft body, dark crescent below, tiny sheen on the lit side
	const coils = AFRO.tex.concat(AFRO.edge.map((e, i) => ({ x: AFRO.cx + (e.x - AFRO.cx) * 1.04, y: AFRO.cy + (e.y - AFRO.cy) * 1.04, s: e.s * 0.62, st: i, hi: e.y < AFRO.cy && e.x > AFRO.cx - 3 })));
	const texture = () => { coils.forEach((c) => {
		const y = c.y + hb;
		ctx.fillStyle = c.hi ? '#2f2936' : '#211c27';
		ctx.beginPath(); ctx.arc(c.x, y, c.s, 0, 7); ctx.fill();
		ctx.strokeStyle = '#060508'; ctx.lineWidth = 0.28;
		ctx.beginPath(); ctx.arc(c.x, y, c.s, 0.2, 2.6); ctx.stroke();
		if (c.hi) { ctx.strokeStyle = 'rgba(150,140,165,.55)'; ctx.lineWidth = 0.22; ctx.beginPath(); ctx.arc(c.x, y, c.s * 0.62, 3.5 + c.st * 0.1, 4.9 + c.st * 0.1); ctx.stroke(); }
	}); };
	texture();
	// face (profile): skull + jaw, nose pointing forward
	ctx.fillStyle = SKIN; ctx.strokeStyle = OL; ctx.lineWidth = 1;
	ctx.beginPath();
	ctx.moveTo(-2.8, -3.8);
	ctx.quadraticCurveTo(2, -6.4, 4.6, -3.6); // forehead
	ctx.quadraticCurveTo(5.3, -2.6, 5, -1.7); // brow ridge → soft dip at the bridge
	ctx.bezierCurveTo(5.6, -0.8, 6.6, 0.3, 6.95, 1); // straight nasal bridge
	ctx.bezierCurveTo(7.25, 1.6, 7, 2.2, 6.3, 2.25); // rounded tip
	ctx.quadraticCurveTo(5.85, 2.3, 5.6, 2.55); // columella → under the nose
	ctx.quadraticCurveTo(5.75, 2.9, 5.65, 3.25); // philtrum
	ctx.quadraticCurveTo(5.85, 3.5, 5.45, 3.75); // upper lip → mouth corner
	ctx.quadraticCurveTo(5.75, 4.15, 5.3, 4.7); // lower lip
	ctx.quadraticCurveTo(4.95, 5, 5.05, 5.35); // dip under the lip
	ctx.bezierCurveTo(5.5, 5.8, 5.5, 6.7, 4.7, 7); // chin forward + round
	ctx.quadraticCurveTo(3.4, 7.3, 1.6, 6.9); // under the chin
	ctx.quadraticCurveTo(-0.4, 6.4, -1.4, 5.5); // jawline up to the angle
	ctx.bezierCurveTo(-2.6, 4.6, -3.3, 3.4, -3.3, 2.2); // up to the ear
	ctx.closePath(); ctx.fill(); ctx.stroke();
	// hair covers the top of the skull and meets the face at a curly hairline (forehead → temple → behind the ear)
	const HL = [[-4.6, 1.2], [-3.4, -1.4], [-1.6, -2.6], [0.6, -3.2], [2.6, -3.6], [4.2, -3.9], [5.4, -4.6]];
	ctx.save();
	ctx.beginPath(); ctx.moveTo(-14, -24); ctx.lineTo(14, -24); ctx.lineTo(14, HL[HL.length - 1][1]);
	for (let i = HL.length - 1; i >= 0; i--) ctx.lineTo(HL[i][0], HL[i][1] + hb * 0.4);
	ctx.lineTo(-14, 4); ctx.closePath(); ctx.clip();
	vol(0, HAIR);
	ctx.fillStyle = lg; ctx.beginPath(); ctx.ellipse(AFRO.cx, AFRO.cy + hb, AFRO.R, AFRO.R * 0.95, 0, 0, 7); ctx.fill();
	texture();
	ctx.restore();
	// curly edge along the hairline
	HL.forEach(([x, y], i) => { if (i === 0) return; ctx.beginPath(); ctx.arc(x, y + hb * 0.4, 1.05, 0, 7); ctx.fillStyle = HAIR; ctx.fill(); });
	ctx.strokeStyle = OL; ctx.lineWidth = 0.7; ctx.beginPath();
	HL.forEach(([x, y], i) => { const yy = y + hb * 0.4 + 0.9; i ? ctx.lineTo(x, yy) : ctx.moveTo(x, yy); }); ctx.stroke();
	// clean-shaven jaw; moustache only
	ctx.fillStyle = BEARD;
	ctx.beginPath(); ctx.moveTo(3.6, 3); ctx.quadraticCurveTo(4.9, 2.6, 5.7, 3.1); ctx.quadraticCurveTo(4.7, 3.5, 3.6, 3); ctx.fill();
	// soft shadow under the lower lip (chin)
	ctx.strokeStyle = SKIN_D; ctx.lineWidth = 0.55; ctx.beginPath(); ctx.moveTo(4.2, 5.25); ctx.quadraticCurveTo(4.7, 5.4, 5, 5.3); ctx.stroke();
	ctx.strokeStyle = 'rgba(150,95,70,.5)'; ctx.lineWidth = 0.45; ctx.beginPath(); ctx.moveTo(2.4, 6.6); ctx.quadraticCurveTo(3.8, 6.9, 4.8, 6.5); ctx.stroke(); // under-chin shade
	ctx.fillStyle = HAIR; ctx.fillRect(-3.2, -1.6, 1.2, 1.8); // short sideburn
	// nostril wing
	ctx.strokeStyle = SKIN_D; ctx.lineWidth = 0.55; ctx.beginPath(); ctx.moveTo(6.3, 1.25); ctx.bezierCurveTo(5.5, 0.9, 5.2, 2, 5.9, 2.1); ctx.stroke();
	ctx.fillStyle = 'rgba(80,40,30,.45)'; ctx.beginPath(); ctx.ellipse(6.2, 2.05, 0.45, 0.17, 0, 0, 7); ctx.fill();
	// lips: natural tone, small
	const yawn = tired > 0.5 ? Math.max(0, Math.sin(now * 1.3) * 1.6 - 0.6) : 0;
	if (yawn > 0) {
		ctx.fillStyle = '#3b1a14'; ctx.beginPath(); ctx.ellipse(5.05, 4.1, 0.55, 0.35 + yawn * 0.5, 0.15, 0, 7); ctx.fill();
		ctx.lineWidth = 0.4; ctx.strokeStyle = OL; ctx.stroke();
	} else {
		ctx.fillStyle = '#a8705f';
		ctx.beginPath(); ctx.moveTo(4.6, 3.6); ctx.quadraticCurveTo(5.5, 3.4, 5.5, 3.8); ctx.quadraticCurveTo(5.5, 4.5, 4.7, 4.3); ctx.closePath(); ctx.fill();
	}
	// wayfarer in profile: one lens + temple to the ear
	const gy = tired * 1.9;
	if (tired > 0.05) {
		const blink = Math.sin(now * 0.9) > 0.97 ? 0.45 : 0, lid = -2.25 + tired * 0.5 + blink; // half-closed
		ctx.fillStyle = 'rgba(110,70,90,.4)'; ctx.beginPath(); ctx.ellipse(4.3, -1.05, 1, 0.38, 0, 0, 7); ctx.fill(); // dark circles
		ctx.fillStyle = '#f8fafc'; ctx.strokeStyle = OL; ctx.lineWidth = 0.35;
		ctx.beginPath(); ctx.ellipse(4.45, -1.55, 1.05, 0.72, 0, 0, 7); ctx.fill(); ctx.stroke();
		ctx.fillStyle = '#3b2416'; ctx.beginPath(); ctx.arc(4.95, -1.3, 0.42, 0, 7); ctx.fill();
		ctx.fillStyle = OL; ctx.beginPath(); ctx.arc(5.05, -1.28, 0.2, 0, 7); ctx.fill();
		ctx.fillStyle = SKIN; ctx.beginPath(); ctx.rect(3.4, -2.4, 2.2, lid + 2.4); ctx.fill(); // heavy eyelid
		ctx.strokeStyle = OL; ctx.lineWidth = 0.45; ctx.beginPath(); ctx.moveTo(3.55, lid + 0.05); ctx.quadraticCurveTo(4.5, lid - 0.12, 5.35, lid + 0.12); ctx.stroke();
		ctx.strokeStyle = HAIR; ctx.lineWidth = 0.5; ctx.beginPath(); ctx.moveTo(3.3, -2.85); ctx.quadraticCurveTo(4.4, -3.1, 5.15, -2.6); ctx.stroke(); // tired brow
		ctx.translate(0, gy);
	}
	ctx.fillStyle = '#0b0b0e';
	ctx.beginPath(); ctx.moveTo(1.6, -2.4); ctx.lineTo(5.6, -2.3); ctx.lineTo(5.3, 0.2); ctx.quadraticCurveTo(3.4, 1.1, 1.8, 0.2); ctx.closePath(); ctx.fill();
	ctx.strokeStyle = '#0b0b0e'; ctx.lineWidth = 0.9; ctx.beginPath(); ctx.moveTo(1.8, -2); ctx.lineTo(-0.8, -1.2); ctx.stroke();
	ctx.fillStyle = 'rgba(160,190,230,.6)'; ctx.fillRect(3.4, -1.9, 1.5, 0.5);
	// a few curls falling over the forehead
	ctx.restore();
	if (hold === 'umbrella' && umbrellaT) { drawCanopy(ctx, now); umbrellaT = null; }
	ctx.restore();
}
