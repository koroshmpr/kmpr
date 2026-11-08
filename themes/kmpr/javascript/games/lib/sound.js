/** Tiny WebAudio synth — no audio files. Off by default. */
const PRESETS = {
	click: [['square', 660, 880, 0.05, 0.05]],
	shoot: [['square', 880, 440, 0.06, 0.03]],
	hit: [['sawtooth', 220, 60, 0.15, 0.08]],
	boom: [['noise', 0, 0, 0.35, 0.12], ['sine', 120, 40, 0.3, 0.12]],
	coin: [['square', 988, 988, 0.06, 0.05], ['square', 1319, 1319, 0.12, 0.05, 0.06]],
	jump: [['square', 330, 660, 0.12, 0.05]],
	hurt: [['sawtooth', 300, 80, 0.3, 0.1]],
	level: [['triangle', 523, 523, 0.1, 0.08], ['triangle', 659, 659, 0.1, 0.08, 0.1], ['triangle', 784, 784, 0.2, 0.08, 0.2]],
	over: [['triangle', 392, 196, 0.6, 0.1]],
	type: [['square', 1200, 1100, 0.02, 0.02]],
	wrong: [['square', 160, 120, 0.12, 0.06]],
	flip: [['triangle', 500, 700, 0.06, 0.05]],
	ach: [['triangle', 784, 784, 0.08, 0.07], ['triangle', 1047, 1047, 0.18, 0.07, 0.08]],
};

export function createSound(initial = false) {
	let ctx = null;
	let on = initial;
	let noiseBuf = null;

	const ensure = () => {
		if (!ctx) {
			const AC = window.AudioContext || window.webkitAudioContext;
			if (!AC) return null;
			ctx = new AC();
			noiseBuf = ctx.createBuffer(1, ctx.sampleRate * 0.4, ctx.sampleRate);
			const d = noiseBuf.getChannelData(0);
			for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
		}
		if (ctx.state === 'suspended') ctx.resume();
		return ctx;
	};

	const voice = ([type, f1, f2, dur, vol, delay = 0]) => {
		const t0 = ctx.currentTime + delay;
		const g = ctx.createGain();
		g.gain.setValueAtTime(vol, t0);
		g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
		g.connect(ctx.destination);
		if (type === 'noise') {
			const src = ctx.createBufferSource();
			src.buffer = noiseBuf;
			src.connect(g);
			src.start(t0);
			src.stop(t0 + dur);
			return;
		}
		const o = ctx.createOscillator();
		o.type = type;
		o.frequency.setValueAtTime(f1, t0);
		o.frequency.exponentialRampToValueAtTime(Math.max(20, f2), t0 + dur);
		o.connect(g);
		o.start(t0);
		o.stop(t0 + dur + 0.02);
	};

	return {
		get on() {
			return on;
		},
		set(v) {
			on = !!v;
			if (on) ensure();
		},
		/** play('coin') or play([[type, f1, f2, dur, vol, delay], ...]) — type: sine|square|triangle|sawtooth|noise */
		play(name) {
			const voices = Array.isArray(name) ? name : PRESETS[name];
			if (!on || !voices) return;
			if (!ensure()) return;
			try {
				voices.forEach(voice);
			} catch (e) {
				/* audio is best-effort */
			}
		},
	};
}
