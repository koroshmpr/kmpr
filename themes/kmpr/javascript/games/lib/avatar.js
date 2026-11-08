/**
 * Deterministic SVG avatars from a seed (public player hash / uuid).
 * Styles: blocks (symmetric identicon), orbit (rings + moons), bot (robot face), gem (faceted crystal).
 */
export const AVATAR_STYLES = [
	{ id: 'blocks', label: 'پیکسلی' },
	{ id: 'orbit', label: 'مداری' },
	{ id: 'bot', label: 'ربات' },
	{ id: 'gem', label: 'کریستال' },
];
export const AVATAR_HUES = [32, 200, 160, 265, 340, 12, 90, 220];

function hashBytes(seed) {
	let h = 2166136261 >>> 0;
	const out = [];
	const s = String(seed || 'kmpr');
	for (let i = 0; i < 32; i++) {
		h ^= s.charCodeAt(i % s.length) + i * 31;
		h = Math.imul(h, 16777619) >>> 0;
		out.push(h & 255);
	}
	return out;
}

export function avatarSvg(seed, opts = {}, size = 40) {
	const style = opts.style || 'blocks';
	const hue = Number.isFinite(+opts.hue) ? +opts.hue : 32;
	const b = hashBytes(seed);
	const c1 = `hsl(${hue} 85% 58%)`;
	const c2 = `hsl(${(hue + 40) % 360} 75% 45%)`;
	const bg = `hsl(${hue} 40% 12%)`;
	let body = '';
	if (style === 'blocks') {
		for (let y = 0; y < 5; y++) {
			for (let x = 0; x < 3; x++) {
				if (b[y * 3 + x] % 2) {
					const col = b[(y * 3 + x + 7) % 32] % 3 ? c1 : c2;
					body += `<rect x="${4 + x * 6.4}" y="${4 + y * 6.4}" width="6.4" height="6.4" fill="${col}"/>`;
					if (x < 2) body += `<rect x="${4 + (4 - x) * 6.4}" y="${4 + y * 6.4}" width="6.4" height="6.4" fill="${col}"/>`;
				}
			}
		}
	} else if (style === 'orbit') {
		body += `<circle cx="20" cy="20" r="${7 + (b[0] % 3)}" fill="${c1}"/>`;
		body += `<circle cx="20" cy="20" r="13" fill="none" stroke="${c2}" stroke-width="1.5" stroke-dasharray="${2 + (b[1] % 4)} 3"/>`;
		body += `<ellipse cx="20" cy="20" rx="17" ry="6" fill="none" stroke="${c1}" stroke-opacity=".6" stroke-width="1.5" transform="rotate(${b[2] % 180} 20 20)"/>`;
		for (let i = 0; i < 2 + (b[3] % 3); i++) {
			const a = ((b[4 + i] / 255) * Math.PI * 2).toFixed(2);
			body += `<circle cx="${(20 + Math.cos(a) * 13).toFixed(1)}" cy="${(20 + Math.sin(a) * 13).toFixed(1)}" r="2.2" fill="#fde68a"/>`;
		}
	} else if (style === 'bot') {
		const eye = b[0] % 3;
		body += `<line x1="20" y1="5" x2="20" y2="10" stroke="${c2}" stroke-width="2"/><circle cx="20" cy="5" r="2.2" fill="${c1}"/>`;
		body += `<rect x="8" y="10" width="24" height="${20 + (b[1] % 4)}" rx="${3 + (b[2] % 6)}" fill="${c1}"/>`;
		if (eye === 0) body += `<circle cx="15" cy="19" r="3" fill="${bg}"/><circle cx="25" cy="19" r="3" fill="${bg}"/>`;
		else if (eye === 1) body += `<rect x="12" y="17" width="16" height="4" rx="2" fill="${bg}"/>`;
		else body += `<rect x="12" y="16" width="5" height="5" fill="${bg}"/><rect x="23" y="16" width="5" height="5" fill="${bg}"/>`;
		body += `<rect x="14" y="${25 + (b[3] % 2)}" width="12" height="2.5" rx="1.2" fill="${c2}"/>`;
	} else {
		const pts = [];
		const n = 5 + (b[0] % 3);
		for (let i = 0; i < n; i++) {
			const a = (i / n) * Math.PI * 2 - Math.PI / 2;
			const r = 13 + (b[i + 1] % 4);
			pts.push([(20 + Math.cos(a) * r).toFixed(1), (20 + Math.sin(a) * r).toFixed(1)]);
		}
		body += `<polygon points="${pts.map((p) => p.join(',')).join(' ')}" fill="${c2}"/>`;
		pts.forEach((p, i) => {
			const q = pts[(i + 1) % n];
			body += `<polygon points="20,20 ${p.join(',')} ${q.join(',')}" fill="${i % 2 ? c1 : c2}" fill-opacity="${0.55 + (b[i + 8] % 4) * 0.12}"/>`;
		});
		body += `<circle cx="20" cy="20" r="3" fill="#fef3c7" fill-opacity=".8"/>`;
	}
	return `<svg width="${size}" height="${size}" class="shrink-0 rounded-xl" viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="${bg}"/>${body}</svg>`;
}
