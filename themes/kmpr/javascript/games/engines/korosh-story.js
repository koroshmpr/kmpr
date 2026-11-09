/**
 * «کوروش دیباگر» — comic-book story: intro (the bug that broke deploy night), one panel before every stage, outro.
 * Panels are drawn procedurally on canvas (no images); text lives in DOM (caption box + speech bubble).
 */
import { drawHero } from '../lib/hero.js';
import { esc } from '../lib/util.js';

export const GAME_NAME = 'کوروش دیباگر';

// Inspired by the owner's real project nights (Kaleh «Mast Seven» campaign, Gilda store, Khanesarmaye).
export const CHAPTERS = ['شبی که سایت کاله خوابید', 'روز قرعه‌کشی ماست سون', 'فروشگاه گیلدا و درگاه قطع', 'خانه سرمایه، ساعت ۴ صبح'];

export const INTRO = [
	{ scene: 'desk', tired: 1, mood: 'tired', cap: 'هر برنامه‌نویس چند شب دارد که هیچ‌وقت یادش نمی‌رود. اولینِ کوروش: شب قبل از قرعه‌کشی بزرگ کمپین ماست سون کاله.', say: 'همه‌چی آماده‌ست. فردا روز بزرگیه ☕' },
	{ scene: 'alarm', mood: 'shock', cap: 'ساعت ۲۳:۵۸. صفحه قرمز می‌شود: سایت از دسترس خارج شده است!', say: 'سایت پایینه؟! فردا قرعه‌کشیه!' },
	{ scene: 'army', mood: 'shock', cap: 'باگ‌ها از همه طرف هجوم می‌آورند و دکمه DEPLOY قفل می‌شود. وقت زیادی نمانده.', say: 'نه نه نه… مردم منتظرن!' },
	{ scene: 'dive', mood: 'fire', cap: 'کوروش تصمیم می‌گیرد سایت را از نو بسازد؛ همین امشب. عینک، قهوه، شیرجه توی کد.', say: 'تا صبح یه سایت جدید بالا میارم.' },
];

// one panel per stage (4 worlds × 3 stages; every 3rd is a boss)
export const STAGES = [
	{ cap: 'نیمه‌شب. کوروش سایت جدید کاله را از صفر می‌سازد؛ اول صفحه مسابقه ماست سون.', say: 'اول فرم ثبت‌نام مسابقه.' },
	{ cap: 'سایت جدید تقریباً آماده است… که ناگهان سایت قبلی خودش برمی‌گردد! حالا دو سایت داریم.', say: 'جدی؟! الان برگشتی؟ 😅', mood: 'shock' },
	{ cap: 'صبح فردا: دامنه کاله تمام شده؛ درست روز قرعه‌کشی! باگ غول‌پیکر راه سایت را بسته.', say: 'دامنه رو تمدید کنید، این یکی با من!', mood: 'shock' },
	{ cap: 'روز قرعه‌کشی ماست سون. هجوم مردم شروع شده و سرورها نفس‌نفس می‌زنند.', say: 'چقدر آدم! کش رو روشن کن!', mood: 'shock' },
	{ cap: 'درخواست‌ها پشت سر هم می‌رسند و تایم‌اوت‌ها همه‌جا می‌دوند.', say: 'فقط باید تا قرعه‌کشی دووم بیاره.' },
	{ cap: 'سرور اصلی زیر بار هجوم مردم هنگ کرده و همه درخواست‌ها را قورت می‌دهد.', say: 'ری‌استارتت می‌کنم؛ قرعه‌کشی باید برگزار شه!' },
	{ cap: 'پرونده بعدی: فروشگاه کیف گیلدا. سایت ساخته شد، کلی کار سئو انجام شد و درگاه پرداخت هم گرفتیم.', say: 'همه‌چی آماده‌ست. بیاید خرید کنید!', mood: 'happy' },
	{ cap: 'چند وقت بعد: مشتری‌ها آمدند… سبد خرید پر شد… و دکمه پرداخت؟ درگاه قطع است!', say: 'درگاه قطعه؟! الان؟!', mood: 'shock' },
	{ cap: 'هر مشکل درگاه که حل می‌شد، مشکل بعدی سبز می‌شد. حالا وقت نبرد آخر با خود درگاه است.', say: 'امروز این درگاه وصل می‌شه!', mood: 'fire' },
	{ cap: 'پرونده خانه سرمایه. قیمت دلار و طلا هر ثانیه عوض می‌شود و افزونه قیمت روز نباید یک لحظه قطع شود.', say: 'قیمت‌ها باید زنده بمونن.' },
	{ cap: 'وبینار تا چند ساعت دیگر شروع می‌شود و لندینگ ثبت‌نامش هنوز بالا نیامده.', say: 'آخرین لندینگ. تمرکز!' },
	{ cap: 'ارباب ددلاین: پشت همه این شب‌های بی‌خوابی، همیشه یک نفر بود.', say: 'ددلاین یا نه، امشب دیپلوی می‌کنیم!' },
];

export const OUTRO = [
	{ scene: 'win', mood: 'happy', cap: 'آخرین باگ رفع شد. همه سایت‌ها بالا هستند و دکمه DEPLOY سبز شد…', say: 'دیپلوی! 🚀' },
	{ scene: 'desk', tired: 0.7, mood: 'happy', cap: 'این‌ها فقط چند شب از کار کوروش بودند. هر پروژه چالش خودش را دارد، و هر چالش یک راه‌حل.', say: 'تا پروژه بعدی… و باگ بعدی 😎' },
];

// side story: Maedeh is upset about the overtime → drive the L90 through Tehran traffic → reach her on foot
export const SIDE_NAME = 'مائده';
export const SIDE_STORY = [
	[
		{ scene: 'phone', who: 'maedeh', mood: 'angry', cap: 'ساعت ۹ شب. کوروش هنوز سر کد است که گوشی می‌لرزد: «مائده»، حسابی شاکی.', say: 'باز تا الان سر کاری؟! قرار بود ساعت ۷ بیای!' },
		{ scene: 'drive', mood: 'fire', cap: 'لپ‌تاپ بسته شد، سوییچ ال ۹۰ برداشته شد. حالا فقط ترافیک تهران مانده… و چاله‌ها.', say: 'دارم میام! فقط این ترافیک…' },
	],
	[
		{ mood: 'fire', cap: 'ال ۹۰ پارک شد، ولی تا خانه مائده هنوز راه است و تماس‌ها و ایمیل‌های کاری دنبالش افتاده‌اند. گل جمع کن تا حوصله‌اش تمام نشود!', say: 'امشب هیچ تماس کاری‌ای جواب نمی‌دم!' },
	],
];
// second side mission (index 2 → stage 102): war days, fetch the Sheba site files from the office in Pasdaran
SIDE_STORY.push([
	{ scene: 'phone', phone: 'تسک فوری 🚨', mood: 'shock', cap: 'روزهای جنگ. کوروش برای امنیت به بومهن رفته… که یک پیام کاری می‌رسد.', say: 'فایل‌های سایت شبا روی لپ‌تاپ شرکته؟! باید برم پاسداران…' },
	{ scene: 'drive', car: '207', mood: 'fire', cap: 'با پسرخاله و ۲۰۷ او راه می‌افتند؛ از بومهن تا شرکت در پاسداران. آسمان آرام نیست.', say: 'پسرخاله: کمربندت رو ببند، گاز می‌دم!' },
]);
export const WAR_NAME = 'ماموریت پاسداران';
export const WAR_OUTRO = [
	{ scene: 'desk', tired: 0.6, mood: 'happy', cap: 'لپ‌تاپ برداشته شد و هر دو سالم برگشتند. همان شب، از خانه در بومهن، سایت شبا دوباره بالا آمد.', say: 'تسک: انجام شد ✓ … ولی دیگه نمی‌رم! 😮‍💨' },
];
export const SIDE_OUTRO = [
	{ scene: 'meet', who: 'maedeh', mood: 'happy', cap: 'بالاخره رسید؛ با یک دسته گل و یک قول: امشب لپ‌تاپ تعطیل است.', say: 'این دفعه رو می‌بخشمت… 😊' },
];

// end-of-chapter cutscene after the Kaleh lottery boss: the Makan year-end party
export const AWARD = [
	{ scene: 'party', mood: 'shock', cap: 'جشن پایان سال آژانس ماکان، در یک تالار شلوغ. بعد از کمپین ماست سون کاله، وقت اعلام بهترین کارمند سال است…', say: 'نگار: و بهترین کارمند امسال… کوروش! 👏' },
	{ scene: 'award', mood: 'happy', cap: 'کوروش روی سن می‌رود و تندیس را می‌گیرد. شایان، رفیقی که همیشه کمکش کرده، ردیف اول نشسته؛ مائده هم کنارش دست می‌زند.', say: 'می‌خوام از کسی تشکر کنم که همیشه پشتم بوده…' },
	{ scene: 'gaffe', mood: 'happy', cap: 'همه منتظر اسم شایان‌اند. کوروش (که از هیجان مهمانی حسابی گیج است) میکروفون را محکم می‌گیرد…', say: '…آقا محمدحسین! واقعاً ممنونم! 🙏' },
	{ scene: 'laugh', mood: 'shock', cap: 'سالن از خنده منفجر شد. محمدحسین هنوز نفهمیده برای چه تشکر شنیده، و شایان هنوز هم این را یادش می‌آورد.', say: 'شایان: من بودم که همه شب‌ها کمکت کردم! 😑' },
];

const OL = '#16120f';
const ease = (x) => { x = Math.min(1, Math.max(0, x)); const c = 1.7; return 1 + (c + 1) * Math.pow(x - 1, 3) + c * Math.pow(x - 1, 2); }; // easeOutBack
let FF = 'system-ui, sans-serif';
/** Maedeh: side profile, long wavy hair under a loose pink shawl, lilac coat. mood: angry | happy. (x, by) bottom-centre. */
export function drawMaedeh(c, x, by, k, face, mood, t) {
	const angry = mood === 'angry', SK = '#eab48f', HR = '#3b2216';
	c.save(); c.translate(x, by); c.scale(face * k, k); c.lineJoin = 'round'; c.lineCap = 'round';
	const tap = angry ? Math.max(0, Math.sin(t * 9)) * 1.6 : 0, sway = angry ? 0 : Math.sin(t * 2.4) * 0.6;
	c.rotate(sway * 0.03);
	// hair falling behind the back
	c.fillStyle = HR; c.strokeStyle = OL; c.lineWidth = 1;
	c.beginPath(); c.moveTo(-1.5, -33); c.bezierCurveTo(-8, -32, -8, -24, -6.5, -17); c.quadraticCurveTo(-5 + Math.sin(t * 3) * 0.6, -15.5, -3, -17.5); c.lineTo(0, -26); c.closePath(); c.fill(); c.stroke();
	// legs + sneakers
	c.fillStyle = '#1e3a8a'; c.fillRect(-2.6, -10, 2.4, 9); c.fillRect(0.4, -10 - tap * 0.3, 2.4, 9);
	c.fillStyle = '#f8fafc'; c.strokeStyle = OL; c.lineWidth = 0.8;
	c.beginPath(); c.roundRect(-3, -1.6, 4.4, 1.8, 0.8); c.fill(); c.stroke();
	c.beginPath(); c.roundRect(0.2, -1.6 - tap, 4.6, 1.8, 0.8); c.fill(); c.stroke();
	// long coat
	c.fillStyle = '#a78bfa'; c.lineWidth = 1;
	c.beginPath(); c.moveTo(-3.6, -24); c.lineTo(3.4, -24); c.quadraticCurveTo(4.6, -16, 5, -9); c.lineTo(-4.6, -9); c.quadraticCurveTo(-4.4, -16, -3.6, -24); c.closePath(); c.fill(); c.stroke();
	c.strokeStyle = '#7c3aed'; c.lineWidth = 0.6; c.beginPath(); c.moveTo(-4.2, -15); c.lineTo(4.6, -15); c.stroke(); // belt
	// arms
	c.strokeStyle = OL; c.lineWidth = 1;
	if (angry) { c.fillStyle = '#8b5cf6'; c.beginPath(); c.roundRect(-3, -20.5, 8, 3, 1.4); c.fill(); c.stroke(); c.fillStyle = SK; c.beginPath(); c.arc(4.6, -19, 1.1, 0, 7); c.fill(); c.stroke(); } // crossed arms
	else { c.fillStyle = '#8b5cf6'; c.beginPath(); c.roundRect(0, -22.5, 2.6, 6.5, 1.3); c.fill(); c.stroke(); c.fillStyle = SK; c.beginPath(); c.arc(2.6, -16.2, 1.2, 0, 7); c.fill(); c.stroke(); }
	// neck + head
	c.fillStyle = SK; c.fillRect(-0.6, -26.5, 2.2, 3);
	c.beginPath(); c.moveTo(-3, -31); c.quadraticCurveTo(1, -36.5, 4.2, -32); c.lineTo(4.4, -30.6); c.quadraticCurveTo(5.6, -29.6, 4.6, -29); c.lineTo(4.6, -28.2); c.quadraticCurveTo(4.8, -26.4, 2.6, -26); c.quadraticCurveTo(-1, -25.6, -2.4, -27.6); c.closePath();
	c.fill(); c.strokeStyle = OL; c.lineWidth = 0.9; c.stroke();
	// bangs + top hair
	c.fillStyle = HR; c.beginPath(); c.moveTo(-3.4, -29); c.quadraticCurveTo(-3.6, -36, 2, -35.4); c.quadraticCurveTo(5, -34.6, 4.6, -32); c.quadraticCurveTo(2.4, -33.4, 0.6, -31.6); c.quadraticCurveTo(-1, -30, -3.4, -29); c.fill(); c.stroke();
	// loose pink shawl over the back of the head and the shoulders
	c.fillStyle = '#f9a8d4'; c.beginPath(); c.moveTo(-4.2, -24); c.quadraticCurveTo(-5.4, -33, -0.6, -35.6); c.quadraticCurveTo(-3.2, -32, -2.6, -27.4); c.quadraticCurveTo(-1, -24.2, 3.2, -24.4); c.quadraticCurveTo(0, -22.6, -4.2, -24); c.fill(); c.lineWidth = 0.8; c.stroke();
	c.beginPath(); c.moveTo(-4, -24); c.quadraticCurveTo(-6 + Math.sin(t * 3), -20, -5.4, -16.5); c.lineTo(-3.8, -17); c.quadraticCurveTo(-4, -20.5, -2.6, -23.6); c.closePath(); c.fill(); c.stroke();
	// face: eye with lashes, brow, mouth
	c.fillStyle = OL; c.beginPath(); c.ellipse(2.6, -30.6, 0.55, 0.75, 0, 0, 7); c.fill();
	c.strokeStyle = OL; c.lineWidth = 0.5; c.beginPath(); c.moveTo(2.9, -31.3); c.lineTo(3.7, -31.8); c.moveTo(2.5, -31.4); c.lineTo(3, -32.1); c.stroke();
	c.lineWidth = 0.6; c.beginPath();
	if (angry) { c.moveTo(1.6, -32.8); c.lineTo(3.8, -32); } else { c.moveTo(1.6, -32.4); c.quadraticCurveTo(2.7, -33.2, 3.8, -32.6); }
	c.stroke();
	c.strokeStyle = '#be123c'; c.lineWidth = 0.7; c.beginPath();
	if (angry) { c.moveTo(3.4, -27.4); c.quadraticCurveTo(4, -27.8, 4.5, -27.3); } else { c.moveTo(3.3, -27.8); c.quadraticCurveTo(4, -27, 4.6, -27.8); }
	c.stroke();
	if (!angry) { c.fillStyle = 'rgba(244,114,182,.45)'; c.beginPath(); c.ellipse(1.6, -28.6, 1.2, 0.7, 0, 0, 7); c.fill(); }
	c.restore();
	// emotion FX (screen-aligned)
	if (angry) {
		c.save(); c.translate(x - face * 3 * k, by - 36 * k); c.strokeStyle = '#dc2626'; c.lineWidth = 1.1 * k;
		[[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([a, b]) => { c.beginPath(); c.moveTo(a * 0.6 * k, b * 0.6 * k); c.quadraticCurveTo(a * 2.2 * k, b * 0.4 * k, a * 2.4 * k, b * 2.4 * k); c.stroke(); });
		c.restore();
	} else {
		for (let i = 0; i < 3; i++) { const u = (t * 0.5 + i / 3) % 1; heart(c, x + (i - 1) * 6 * k + Math.sin(t * 3 + i) * 2 * k, by - (36 + u * 14) * k, 1.4 * k, 1 - u); }
	}
}
/** front-facing chibi for the party crowd. o: { skin, hair, style: short|curly|shawl, top, pants, glasses, beard, mood: smile|laugh|shock|meh, clap, name } */
function person(c, x, by, k, o, t) {
	c.save(); c.translate(x, by); c.scale(k, k); c.lineJoin = 'round'; c.lineCap = 'round'; c.strokeStyle = OL;
	const jig = o.mood === 'laugh' ? Math.abs(Math.sin(t * 12)) * 1.2 : 0;
	c.translate(0, -jig);
	c.fillStyle = o.pants || '#1f2937'; c.fillRect(-3.4, -10, 3, 10); c.fillRect(0.4, -10, 3, 10);
	c.fillStyle = '#111'; c.fillRect(-3.8, -1.4, 3.6, 1.6); c.fillRect(0.2, -1.4, 3.6, 1.6);
	c.fillStyle = o.top || '#475569'; c.lineWidth = 1; c.beginPath(); c.roundRect(-5, -21, 10, 12, 3); c.fill(); c.stroke();
	// arms
	const clap = o.clap ? Math.abs(Math.sin(t * 10)) : 0;
	c.lineWidth = 2.6; c.strokeStyle = o.top || '#475569';
	if (o.clap) { [-1, 1].forEach((d) => { c.beginPath(); c.moveTo(d * 4.4, -19); c.lineTo(d * (1 + clap * 2.4), -15); c.stroke(); }); c.fillStyle = o.skin; [-1, 1].forEach((d) => { c.beginPath(); c.arc(d * (0.9 + clap * 2.4), -15, 1.2, 0, 7); c.fill(); }); }
	else if (o.palm) { c.beginPath(); c.moveTo(4.4, -19); c.lineTo(2, -25); c.stroke(); c.fillStyle = o.skin; c.beginPath(); c.ellipse(1, -26.5, 2.2, 1.5, 0, 0, 7); c.fill(); }
	else if (o.point) { c.beginPath(); c.moveTo(-4.4, -19); c.lineTo(-2, -16); c.stroke(); c.fillStyle = o.skin; c.beginPath(); c.arc(-1.4, -15.6, 1.2, 0, 7); c.fill(); }
	else { [-1, 1].forEach((d) => { c.beginPath(); c.moveTo(d * 4.4, -19); c.lineTo(d * 5.2, -12); c.stroke(); }); }
	c.strokeStyle = OL;
	// head
	c.fillStyle = o.skin; c.lineWidth = 1; c.beginPath(); c.arc(0, -26.5, 5.4, 0, 7); c.fill(); c.stroke();
	c.fillStyle = o.hair;
	if (o.style === 'shawl') { c.beginPath(); c.arc(0, -27.5, 6.6, Math.PI * 0.95, Math.PI * 2.05); c.lineTo(6, -21); c.lineTo(4.4, -22); c.quadraticCurveTo(4.6, -30, 0, -30.4); c.quadraticCurveTo(-4.6, -30, -4.4, -22); c.lineTo(-6, -21); c.closePath(); c.fill(); c.stroke(); }
	else if (o.style === 'curly') { for (let i = 0; i < 9; i++) { const a = Math.PI + (i / 8) * Math.PI; c.beginPath(); c.arc(Math.cos(a) * 5, -27.5 + Math.sin(a) * 5, 2.1, 0, 7); c.fill(); } }
	else { c.beginPath(); c.arc(0, -27.6, 5.6, Math.PI * 1.02, Math.PI * 1.98); c.quadraticCurveTo(3, -29.4, -5.4, -27.4); c.fill(); }
	if (o.beard) { c.fillStyle = 'rgba(40,30,25,.55)'; c.beginPath(); c.arc(0, -25.4, 4.8, 0.15, Math.PI - 0.15); c.fill(); }
	// eyes + mouth
	c.fillStyle = OL;
	if (o.mood === 'laugh') { c.lineWidth = 0.8; [-2, 2].forEach((ex) => { c.beginPath(); c.arc(ex, -27, 0.9, Math.PI, 0); c.stroke(); }); c.fillStyle = '#7f1d1d'; c.beginPath(); c.arc(0, -24.6, 2, 0, Math.PI); c.fill(); }
	else { [-2, 2].forEach((ex) => { c.beginPath(); c.arc(ex, -27, o.mood === 'shock' ? 0.9 : 0.65, 0, 7); c.fill(); }); c.lineWidth = 0.8; c.beginPath();
		if (o.mood === 'shock') c.arc(0, -24.2, 1.1, 0, 7); else if (o.mood === 'meh') { c.moveTo(-1.6, -24.2); c.lineTo(1.6, -24.2); } else c.arc(0, -25.2, 1.8, 0.2, Math.PI - 0.2);
		c.stroke(); }
	if (o.glasses) { c.lineWidth = 0.6; c.strokeRect(-3.4, -28, 2.6, 1.9); c.strokeRect(0.8, -28, 2.6, 1.9); }
	if (o.q) { c.font = `900 7px ${FF}`; c.textAlign = 'center'; c.lineWidth = 2; c.strokeText('؟', 6, -33); c.fillStyle = '#fde047'; c.fillText('؟', 6, -33); }
	c.restore();
	if (o.name) { c.font = `800 ${Math.round(4.2 * k)}px ${FF}`; c.textAlign = 'center'; c.textBaseline = 'middle'; c.fillStyle = 'rgba(0,0,0,.6)'; const tw = c.measureText(o.name).width + 6; c.fillRect(x - tw / 2, by + 2, tw, 5.4 * k); c.fillStyle = '#fff'; c.fillText(o.name, x, by + 2 + 2.7 * k); }
}
const CAST = {
	negar: { skin: '#e8b48f', hair: '#1e293b', style: 'shawl', top: '#0f172a', pants: '#1f2937', glasses: true, name: 'نگار' },
	shayan: { skin: '#d9a07a', hair: '#1c1917', style: 'short', top: '#065f46', beard: true, name: 'شایان' },
	mh: { skin: '#e0ac85', hair: '#3f2a1d', style: 'curly', top: '#9a3412', name: 'محمدحسین' },
};
const hall = (c, w, h, t) => {
	const g = c.createLinearGradient(0, 0, 0, h); g.addColorStop(0, '#1e1b4b'); g.addColorStop(1, '#4c1d95');
	c.fillStyle = g; c.fillRect(0, 0, w, h);
	for (let i = 0; i < 3; i++) { const x = w * (0.2 + i * 0.3); c.strokeStyle = '#fde68a'; c.lineWidth = 1; c.beginPath(); c.moveTo(x, 0); c.lineTo(x, 12); c.stroke(); for (let j = 0; j < 7; j++) { c.fillStyle = `rgba(253,230,138,${0.5 + Math.sin(t * 4 + j + i) * 0.4})`; c.beginPath(); c.arc(x + (j - 3) * 4, 15 + Math.abs(j - 3), 1.6, 0, 7); c.fill(); } }
	c.fillStyle = '#7c2d12'; c.fillRect(0, h * 0.62, w, h * 0.06); c.fillStyle = '#9a3412'; c.fillRect(0, h * 0.68, w, h); // stage
	c.fillStyle = '#b91c1c'; c.fillRect(0, 0, w * 0.06, h * 0.62); c.fillRect(w * 0.94, 0, w * 0.06, h * 0.62); // curtains
	c.save(); c.translate(w / 2, h * 0.14); c.fillStyle = '#facc15'; c.strokeStyle = OL; c.lineWidth = 2; c.beginPath(); c.roundRect(-70, -9, 140, 18, 4); c.fill(); c.stroke();
	c.fillStyle = OL; c.font = `900 10px ${FF}`; c.textAlign = 'center'; c.textBaseline = 'middle'; c.fillText('جشن سالانه آژانس ماکان 🎉', 0, 1); c.restore();
};
const crowd = (c, w, h, t, laugh) => { for (let i = 0; i < 18; i++) { const x = (i / 17) * w, j = laugh ? Math.abs(Math.sin(t * 10 + i)) * 3 : 0; c.fillStyle = i % 2 ? '#1e1b4b' : '#312e81'; c.beginPath(); c.arc(x, h - 6 - j, 11, 0, 7); c.fill(); } };
const trophy = (c, x, y, k) => { c.save(); c.translate(x, y); c.scale(k, k); c.fillStyle = '#facc15'; c.strokeStyle = OL; c.lineWidth = 1; c.beginPath(); c.moveTo(-4, -10); c.lineTo(4, -10); c.quadraticCurveTo(4, -3, 0, -2); c.quadraticCurveTo(-4, -3, -4, -10); c.fill(); c.stroke(); c.fillRect(-0.8, -2, 1.6, 3); c.fillRect(-3, 1, 6, 1.8); c.strokeRect(-3, 1, 6, 1.8); c.restore(); };
function heart(c, x, y, k, a = 1) {
	c.save(); c.globalAlpha = a; c.translate(x, y); c.scale(k, k);
	c.beginPath(); c.moveTo(0, 2); c.bezierCurveTo(-3, 0, -2.6, -2.6, 0, -1.2); c.bezierCurveTo(2.6, -2.6, 3, 0, 0, 2); c.fillStyle = '#f43f5e'; c.fill(); c.lineWidth = 0.5; c.strokeStyle = OL; c.stroke();
	c.restore();
}
/** Korosh's white L90 sedan (side view), Korosh at the wheel. (x, by) bottom-centre, k scale. */
export function drawL90(c, x, by, k, t, moving = true, model = 'l90') {
	if (model === '207') return draw207(c, x, by, k, t, moving);
	c.save(); c.translate(x, by); c.scale(k, k); c.lineJoin = 'round';
	const bob = moving ? Math.abs(Math.sin(t * 12)) * 0.5 : 0;
	c.translate(0, -bob);
	// cabin + glass
	c.fillStyle = '#e2e8f0'; c.strokeStyle = OL; c.lineWidth = 1.2;
	c.beginPath(); c.moveTo(-12, -13); c.lineTo(-8, -21); c.lineTo(6, -21); c.lineTo(11, -13); c.closePath(); c.fill(); c.stroke();
	c.fillStyle = '#1e293b'; c.beginPath(); c.moveTo(-10, -13.6); c.lineTo(-7.3, -19.6); c.lineTo(-1, -19.6); c.lineTo(-1, -13.6); c.closePath(); c.fill();
	c.beginPath(); c.moveTo(0.4, -13.6); c.lineTo(0.4, -19.6); c.lineTo(5.6, -19.6); c.lineTo(9.4, -13.6); c.closePath(); c.fill();
	// Korosh at the wheel: afro + shades
	c.save(); c.beginPath(); c.moveTo(0.4, -13.6); c.lineTo(0.4, -19.6); c.lineTo(5.6, -19.6); c.lineTo(9.4, -13.6); c.closePath(); c.clip();
	c.fillStyle = '#121016'; c.beginPath(); c.arc(3.4, -18.4, 3.6, 0, 7); c.fill();
	c.fillStyle = '#d9a07a'; c.beginPath(); c.ellipse(5, -15.6, 1.8, 2.2, 0, 0, 7); c.fill();
	c.fillStyle = '#0b0b0e'; c.fillRect(4.6, -16.8, 2.6, 1);
	c.restore();
	c.fillStyle = 'rgba(255,255,255,.35)'; c.beginPath(); c.moveTo(-8, -15); c.lineTo(-6.4, -18.6); c.lineTo(-5.4, -18.6); c.lineTo(-7, -15); c.fill();
	// body
	c.fillStyle = '#f8fafc'; c.beginPath(); c.moveTo(-19, -12.5); c.quadraticCurveTo(-19.5, -5, -17, -4); c.lineTo(17.5, -4); c.quadraticCurveTo(19.5, -6, 19, -11); c.lineTo(11, -13.5); c.lineTo(-13, -13.5); c.closePath(); c.fill(); c.lineWidth = 1.3; c.stroke();
	c.strokeStyle = '#94a3b8'; c.lineWidth = 0.7; c.beginPath(); c.moveTo(-17, -9); c.lineTo(17.5, -9); c.moveTo(-0.4, -13.4); c.lineTo(-0.4, -5); c.stroke();
	c.fillStyle = '#fde047'; c.beginPath(); c.ellipse(18, -10.5, 1.2, 1.4, 0, 0, 7); c.fill(); c.strokeStyle = OL; c.lineWidth = 0.6; c.stroke(); // headlight
	c.fillStyle = '#ef4444'; c.fillRect(-19.2, -12, 1.6, 2.6); // tail light
	c.fillStyle = '#fff'; c.fillRect(-18.6, -8, 5, 2.4); c.fillStyle = '#1d4ed8'; c.fillRect(-18.6, -8, 1, 2.4); c.strokeStyle = OL; c.lineWidth = 0.4; c.strokeRect(-18.6, -8, 5, 2.4); // plate
	c.fillStyle = '#334155'; c.fillRect(1.6, -11, 2.2, 0.8); c.fillRect(-9, -11, 2.2, 0.8); // handles
	// wheels
	[-11, 11].forEach((wx) => {
		c.save(); c.translate(wx, -4.2 + bob); c.rotate(moving ? t * 18 : 0);
		c.fillStyle = '#111827'; c.beginPath(); c.arc(0, 0, 4.2, 0, 7); c.fill(); c.strokeStyle = OL; c.lineWidth = 1; c.stroke();
		c.fillStyle = '#cbd5e1'; c.beginPath(); c.arc(0, 0, 2, 0, 7); c.fill();
		c.strokeStyle = '#64748b'; c.lineWidth = 0.6; c.beginPath(); c.moveTo(-2, 0); c.lineTo(2, 0); c.moveTo(0, -2); c.lineTo(0, 2); c.stroke();
		c.restore();
	});
	if (moving) { c.fillStyle = 'rgba(203,213,225,.7)'; const u = (t * 3) % 1; c.beginPath(); c.arc(-21 - u * 6, -6 - u * 2, 1.4 + u * 1.6, 0, 7); c.fill(); }
	c.restore();
}

/** the cousin's silver Peugeot 207 hatchback: cousin driving, Korosh in the passenger seat with the laptop bag. */
function draw207(c, x, by, k, t, moving) {
	c.save(); c.translate(x, by); c.scale(k, k); c.lineJoin = 'round';
	const bob = moving ? Math.abs(Math.sin(t * 13)) * 0.5 : 0;
	c.translate(0, -bob);
	c.fillStyle = '#cbd5e1'; c.strokeStyle = OL; c.lineWidth = 1.2;
	c.beginPath(); c.moveTo(-17, -12); c.quadraticCurveTo(-16, -21, -8, -21.5); c.lineTo(3, -21.5); c.quadraticCurveTo(9, -20, 13, -13); c.closePath(); c.fill(); c.stroke();
	c.fillStyle = '#1e293b'; c.beginPath(); c.moveTo(-14.6, -13.4); c.quadraticCurveTo(-14, -19.6, -8, -20); c.lineTo(-2, -20); c.lineTo(-2, -13.4); c.closePath(); c.fill();
	c.beginPath(); c.moveTo(-0.6, -13.4); c.lineTo(-0.6, -20); c.lineTo(2.8, -20); c.quadraticCurveTo(7.6, -18.6, 10.8, -13.4); c.closePath(); c.fill();
	c.save(); c.beginPath(); c.rect(-15, -20.4, 26, 7.2); c.clip();
	c.fillStyle = '#121016'; c.beginPath(); c.arc(-6.6, -18.2, 3.4, 0, 7); c.fill(); c.fillStyle = '#d9a07a'; c.beginPath(); c.ellipse(-5, -15.8, 1.6, 2, 0, 0, 7); c.fill(); c.fillStyle = '#0b0b0e'; c.fillRect(-5.4, -16.9, 2.4, 0.9); // Korosh
	c.fillStyle = '#1c1917'; c.beginPath(); c.arc(3.6, -18.6, 2.4, Math.PI, 0); c.fill(); c.fillStyle = '#e0ac85'; c.beginPath(); c.ellipse(4.4, -16, 1.8, 2.2, 0, 0, 7); c.fill(); // cousin
	c.restore();
	c.fillStyle = '#e2e8f0'; c.beginPath(); c.moveTo(-18, -12.5); c.quadraticCurveTo(-18.6, -5, -16.4, -4); c.lineTo(17, -4); c.quadraticCurveTo(19, -6.4, 18.4, -10); c.quadraticCurveTo(16, -13, 12, -13.4); c.lineTo(-16, -13.4); c.closePath(); c.fill(); c.lineWidth = 1.3; c.stroke();
	c.strokeStyle = '#94a3b8'; c.lineWidth = 0.7; c.beginPath(); c.moveTo(-1.3, -13.2); c.lineTo(-1.3, -5); c.moveTo(-16, -8.6); c.lineTo(17.6, -8.6); c.stroke();
	c.fillStyle = '#fde047'; c.beginPath(); c.ellipse(17.2, -10.6, 1.4, 1, -0.3, 0, 7); c.fill(); c.fillStyle = '#ef4444'; c.fillRect(-18.4, -12, 1.4, 2.4);
	c.fillStyle = '#0f172a'; c.fillRect(-12, -16, 2.4, 3); // laptop bag strap in the window
	[-10.5, 10.5].forEach((wx) => { c.save(); c.translate(wx, -4.2 + bob); c.rotate(moving ? t * 18 : 0); c.fillStyle = '#111827'; c.beginPath(); c.arc(0, 0, 4, 0, 7); c.fill(); c.strokeStyle = OL; c.lineWidth = 1; c.stroke(); c.fillStyle = '#9ca3af'; c.beginPath(); c.arc(0, 0, 1.9, 0, 7); c.fill(); c.strokeStyle = '#4b5563'; c.lineWidth = 0.6; c.beginPath(); c.moveTo(-1.9, 0); c.lineTo(1.9, 0); c.moveTo(0, -1.9); c.lineTo(0, 1.9); c.stroke(); c.restore(); });
	c.restore();
}

/**
 * Shayan — caricature bust (big head): swept-up dark hair with a receding hairline, thick brows,
 * dark rectangular glasses, short boxed beard + moustache, navy hoodie with drawstrings.
 * (x, by) = bottom-centre of the bust, talk 0..1 opens the mouth, wink for a cheeky moment.
 */
export function drawShayan(c, x, by, k, t, talk = 0, wink = false) {
	const SK = '#e3b38d', SKD = '#c99572', HR = '#2a1d17';
	c.save(); c.translate(x, by); c.scale(k, k); c.lineJoin = 'round'; c.lineCap = 'round'; c.strokeStyle = OL;
	c.rotate(Math.sin(t * 3) * 0.03);
	// hoodie shoulders + hood
	c.fillStyle = '#1f2a44'; c.lineWidth = 1.4;
	c.beginPath(); c.moveTo(-24, 0); c.quadraticCurveTo(-23, -15, -10, -17); c.lineTo(10, -17); c.quadraticCurveTo(23, -15, 24, 0); c.closePath(); c.fill(); c.stroke();
	c.fillStyle = '#18213a'; c.beginPath(); c.moveTo(-11, -17); c.quadraticCurveTo(0, -9, 11, -17); c.quadraticCurveTo(0, -13, -11, -17); c.fill(); c.stroke();
	c.strokeStyle = '#6b7280'; c.lineWidth = 1.1; c.beginPath(); c.moveTo(-4, -13); c.quadraticCurveTo(-5, -7, -4.4, -2); c.moveTo(4, -13); c.quadraticCurveTo(5, -7, 4.6, -2); c.stroke();
	c.fillStyle = '#dc2626'; c.fillRect(-5.2, -3, 1.6, 2.4); c.fillRect(3.8, -3, 1.6, 2.4); // red aglets
	// neck
	c.fillStyle = SKD; c.strokeStyle = OL; c.lineWidth = 1; c.fillRect(-4, -20, 8, 5);
	// ears
	c.fillStyle = SK; [-1, 1].forEach((d) => { c.beginPath(); c.ellipse(d * 12.2, -31, 2.2, 3.2, 0, 0, 7); c.fill(); c.stroke(); });
	// big caricature head
	c.beginPath(); c.moveTo(-11.5, -38); c.quadraticCurveTo(-12.5, -26, -9.5, -21); c.quadraticCurveTo(-5, -15.5, 0, -15.5); c.quadraticCurveTo(5, -15.5, 9.5, -21); c.quadraticCurveTo(12.5, -26, 11.5, -38); c.quadraticCurveTo(11, -48, 0, -48.5); c.quadraticCurveTo(-11, -48, -11.5, -38); c.closePath();
	c.fillStyle = SK; c.fill(); c.lineWidth = 1.3; c.stroke();
	// short boxed beard + moustache (stubble texture)
	c.fillStyle = 'rgba(42,29,23,.62)';
	c.beginPath(); c.moveTo(-11.3, -30); c.quadraticCurveTo(-11, -22, -8, -19.5); c.quadraticCurveTo(-4, -15.8, 0, -15.8); c.quadraticCurveTo(4, -15.8, 8, -19.5); c.quadraticCurveTo(11, -22, 11.3, -30); c.quadraticCurveTo(9, -24, 5, -24.4); c.quadraticCurveTo(0, -25.6, -5, -24.4); c.quadraticCurveTo(-9, -24, -11.3, -30); c.fill();
	c.fillStyle = 'rgba(42,29,23,.8)'; c.beginPath(); c.moveTo(-5.6, -25.6); c.quadraticCurveTo(0, -28.2, 5.6, -25.6); c.quadraticCurveTo(0, -26.6, -5.6, -25.6); c.fill();
	c.fillStyle = 'rgba(30,20,15,.35)'; for (let i = 0; i < 26; i++) { const a = Math.PI * (0.1 + (i / 25) * 0.8), r = 9 + (i % 3); c.fillRect(Math.cos(a) * r - 0.3, -27 + Math.sin(a) * r * 0.9, 0.6, 0.6); }
	// mouth (smirk / talking)
	c.fillStyle = '#5b1f1a';
	if (talk > 0.05) { c.beginPath(); c.ellipse(0.6, -22.6, 2.6, 0.6 + talk * 1.6, 0, 0, 7); c.fill(); c.fillStyle = '#fff'; c.fillRect(-1.4, -23.4, 4, 0.7); }
	else { c.strokeStyle = '#5b1f1a'; c.lineWidth = 1; c.beginPath(); c.moveTo(-2.4, -22.8); c.quadraticCurveTo(1, -21.6, 3.4, -23.6); c.stroke(); }
	// nose
	c.strokeStyle = SKD; c.lineWidth = 1; c.beginPath(); c.moveTo(-0.6, -33); c.quadraticCurveTo(-1.4, -28.4, -2.4, -27.6); c.quadraticCurveTo(0, -26.6, 2.4, -27.6); c.stroke();
	c.fillStyle = 'rgba(150,95,70,.5)'; c.beginPath(); c.ellipse(-1.2, -27.4, 0.8, 0.4, 0, 0, 7); c.ellipse(1.4, -27.4, 0.8, 0.4, 0, 0, 7); c.fill();
	// eyes behind dark rectangular glasses
	c.fillStyle = '#fff'; [-5, 5].forEach((ex) => { c.beginPath(); c.ellipse(ex, -33, 2.1, 1.3, 0, 0, 7); c.fill(); });
	c.fillStyle = '#2b1a12'; c.beginPath(); c.arc(-4.7, -32.9, 0.95, 0, 7); c.fill();
	if (wink) { c.fillStyle = SK; c.fillRect(2.6, -34.6, 5, 2.4); c.strokeStyle = OL; c.lineWidth = 0.9; c.beginPath(); c.moveTo(3, -32.8); c.quadraticCurveTo(5, -31.6, 7, -32.8); c.stroke(); }
	else { c.beginPath(); c.arc(5.3, -32.9, 0.95, 0, 7); c.fill(); }
	c.strokeStyle = '#3b1f24'; c.lineWidth = 1.2;
	[-5.2, 5.2].forEach((gx) => { c.beginPath(); c.roundRect(gx - 4.2, -35.6, 8.4, 5.4, 1.6); c.stroke(); });
	c.beginPath(); c.moveTo(-1, -34.2); c.quadraticCurveTo(0, -35, 1, -34.2); c.moveTo(-9.4, -34.4); c.lineTo(-11.6, -34); c.moveTo(9.4, -34.4); c.lineTo(11.6, -34); c.stroke();
	c.fillStyle = 'rgba(255,255,255,.35)'; c.fillRect(-8, -35, 2, 1); c.fillRect(2.4, -35, 2, 1);
	// thick straight brows
	c.fillStyle = HR; c.beginPath(); c.roundRect(-9, -38.4, 7, 1.6, 0.8); c.roundRect(2, -38.4, 7, 1.6, 0.8); c.fill();
	// swept-up quiff with a receding hairline (M shape)
	c.beginPath(); c.moveTo(-11.8, -36); c.quadraticCurveTo(-13, -46, -6, -49.5); c.quadraticCurveTo(-2, -53.5, 4, -52); c.quadraticCurveTo(9.5, -51.5, 11, -47); c.quadraticCurveTo(12.8, -43, 11.8, -36);
	c.quadraticCurveTo(10.4, -41, 7.6, -42.4); c.quadraticCurveTo(4, -40.6, 1.6, -44.2); c.quadraticCurveTo(-2, -41.6, -6.6, -42.2); c.quadraticCurveTo(-10, -41, -11.8, -36); c.closePath();
	c.fillStyle = HR; c.fill(); c.lineWidth = 1.2; c.stroke();
	c.strokeStyle = '#4a3428'; c.lineWidth = 0.7; c.beginPath(); c.moveTo(-6, -47); c.quadraticCurveTo(-1, -51, 5, -49.6); c.moveTo(-3, -45); c.quadraticCurveTo(1, -48, 6, -47); c.stroke();
	c.restore();
}

/**
 * The «bug» enemy: a side-view glitch beetle — dark carapace with glowing circuit seams, red LED eye,
 * antenna and mandibles, six scuttling legs. (x, by) = bottom-centre, dir = facing, k = scale.
 */
export function drawBeetle(c, x, by, k, dir, t, alt = false) {
	const shell = alt ? ['#5b21b6', '#2e1065'] : ['#1e3a8a', '#0b1437'], glow = alt ? '#e879f9' : '#22d3ee';
	c.save(); c.translate(x, by); c.scale(dir * k, k); c.lineJoin = 'round'; c.lineCap = 'round';
	const step = Math.sin(t * 14);
	c.strokeStyle = OL; c.lineWidth = 1.2;
	for (let i = 0; i < 3; i++) { const lx = -4 + i * 4, sw = (i % 2 ? step : -step) * 1.6; c.beginPath(); c.moveTo(lx, -3.5); c.lineTo(lx + sw - 1.2, -1.8); c.lineTo(lx + sw * 1.4 - 0.4, 0); c.stroke(); }
	// carapace
	const g = c.createLinearGradient(0, -13, 0, -2); g.addColorStop(0, shell[0]); g.addColorStop(1, shell[1]);
	c.beginPath(); c.moveTo(-8, -3.2); c.bezierCurveTo(-8.5, -11, -1, -14, 3, -11.6); c.quadraticCurveTo(5.6, -10, 5.6, -4); c.quadraticCurveTo(-1, -1.6, -8, -3.2); c.closePath();
	c.fillStyle = g; c.fill(); c.lineWidth = 1.4; c.strokeStyle = OL; c.stroke();
	// glowing circuit seams
	c.strokeStyle = glow; c.lineWidth = 0.75;
	c.beginPath(); c.moveTo(-6.4, -5); c.lineTo(-4, -8); c.lineTo(-1.2, -8); c.lineTo(0.6, -10.6); c.moveTo(-3, -4.4); c.lineTo(-1, -6.2); c.lineTo(2.4, -6.2); c.stroke();
	c.fillStyle = glow; [[-4, -8], [0.6, -10.6], [2.4, -6.2]].forEach(([px, py]) => { c.beginPath(); c.arc(px, py, 0.7, 0, 7); c.fill(); });
	c.fillStyle = 'rgba(255,255,255,.28)'; c.beginPath(); c.ellipse(-2.6, -10.4, 3, 1, -0.25, 0, 7); c.fill();
	// head, mandibles, antenna, LED eye
	c.beginPath(); c.arc(6.6, -5, 3, 0, 7); c.fillStyle = '#0f172a'; c.fill(); c.lineWidth = 1.3; c.strokeStyle = OL; c.stroke();
	c.lineWidth = 1.1; c.beginPath(); c.moveTo(8.8, -3.4); c.quadraticCurveTo(10.8, -3.2, 10.6, -1.4); c.moveTo(8.6, -2.6); c.quadraticCurveTo(9.8, -1.2, 9.2, -0.4); c.stroke();
	const wig = Math.sin(t * 9) * 0.8;
	c.beginPath(); c.moveTo(6.4, -7.6); c.quadraticCurveTo(7.6, -12.4, 11 + wig, -12.4); c.stroke();
	c.fillStyle = '#f43f5e'; c.beginPath(); c.arc(11 + wig, -12.4, 1.1, 0, 7); c.fill();
	c.beginPath(); c.ellipse(7.5, -5.6, 1.5, 1, -0.35, 0, 7); c.fill();
	c.fillStyle = '#fff'; c.beginPath(); c.arc(7.9, -5.9, 0.42, 0, 7); c.fill();
	c.strokeStyle = OL; c.lineWidth = 0.9; c.beginPath(); c.moveTo(5.6, -7.4); c.lineTo(8.8, -6.5); c.stroke(); // angry brow
	c.restore();
}
const bug = (c, x, y, r, col = '#ef4444', t = 0) => drawBeetle(c, x, y + r * 0.9, r / 7.5, -1, t, col !== '#ef4444');
// comic sound-effect lettering with a pop-in
const sfx = (c, text, x, y, size, rot, fill, t) => {
	if (t <= 0) return;
	const k = ease(t * 4);
	c.save(); c.translate(x, y); c.rotate(rot); c.scale(k, k);
	c.font = `900 ${size}px ${FF}`; c.textAlign = 'center'; c.textBaseline = 'middle'; c.lineJoin = 'round';
	c.lineWidth = size * 0.32; c.strokeStyle = OL; c.strokeText(text, 0, 0);
	c.fillStyle = fill; c.fillText(text, 0, 0);
	c.restore();
};
const burst = (c, x, y, r, n, fill, t = 1, spin = 0) => {
	const k = ease(t * 4); if (k <= 0) return;
	c.save(); c.translate(x, y); c.rotate(spin); c.scale(k, k); c.beginPath();
	for (let i = 0; i < n * 2; i++) { const a = (i / (n * 2)) * Math.PI * 2, rr = i % 2 ? r * 0.62 : r * (0.92 + ((i * 37) % 10) / 60); c.lineTo(Math.cos(a) * rr, Math.sin(a) * rr); }
	c.closePath(); c.fillStyle = fill; c.fill(); c.lineWidth = 2.5; c.strokeStyle = OL; c.stroke(); c.restore();
};
const speed = (c, w, h, cx, cy, col, t, radial) => {
	c.save(); c.strokeStyle = col; c.lineCap = 'round';
	for (let i = 0; i < 26; i++) {
		const seed = (i * 7919) % 97 / 97;
		if (radial) { const a = i / 26 * Math.PI * 2 + seed * 0.2, r0 = 40 + ((t * 300 + seed * 200) % 160); c.lineWidth = 1 + seed * 2; c.beginPath(); c.moveTo(cx + Math.cos(a) * r0, cy + Math.sin(a) * r0 * 0.6); c.lineTo(cx + Math.cos(a) * (r0 + 30), cy + Math.sin(a) * (r0 + 30) * 0.6); c.stroke(); }
		else { const y = 10 + seed * (h - 40), x = w - ((t * 520 + i * 61) % (w + 120)); c.lineWidth = 1 + seed * 2; c.beginPath(); c.moveTo(x, y); c.lineTo(x + 40 + seed * 50, y); c.stroke(); }
	}
	c.restore();
};
let dots = null;
const halftone = (c, w, h) => {
	if (!dots) { const d = document.createElement('canvas'); d.width = d.height = 6; const x = d.getContext('2d'); x.fillStyle = 'rgba(0,0,0,.16)'; x.beginPath(); x.arc(3, 3, 1.1, 0, 7); x.fill(); dots = c.createPattern(d, 'repeat'); }
	const g = c.createLinearGradient(0, 0, 0, h); g.addColorStop(0, '#000'); g.addColorStop(0.45, 'rgba(0,0,0,0)');
	c.save(); c.fillStyle = dots; c.globalAlpha = 0.9; c.fillRect(0, 0, w, h * 0.5); c.restore();
	c.save(); c.globalAlpha = 0.18; c.fillStyle = g; c.fillRect(0, 0, w, h); c.restore();
};
const laptop = (c, x, y, w, screen, t) => {
	const h = w * 0.62;
	c.lineWidth = 2; c.strokeStyle = OL;
	c.fillStyle = '#334155'; c.beginPath(); c.roundRect(x, y - h, w, h, 4); c.fill(); c.stroke();
	c.fillStyle = screen; c.fillRect(x + 4, y - h + 4, w - 8, h - 8);
	c.fillStyle = '#94a3b8'; c.beginPath(); c.roundRect(x - 6, y, w + 12, 5, 2); c.fill(); c.stroke();
	if (screen !== '#7f1d1d') { for (let i = 0; i < 5; i++) { c.fillStyle = ['#4ade80', '#fbbf24', '#93c5fd'][i % 3]; c.fillRect(x + 8 + (i % 2) * 6, y - h + 9 + i * 6, Math.min(w - 20, (w - 30) * (0.4 + ((i * 37) % 50) / 100) * Math.min(1, t * 2 - i * 0.2)), 2.5); } }
	else { c.fillStyle = '#fecaca'; c.font = `900 12px ${FF}`; c.textAlign = 'center'; c.textBaseline = 'middle'; c.fillText('ERROR 500', x + w / 2, y - h / 2 + (Math.sin(t * 30) > 0 ? 1 : -1)); }
};
const room = (c, w, h, red, t) => {
	const flash = red ? (Math.sin(t * 14) > 0 ? 1 : 0.7) : 1;
	const g = c.createLinearGradient(0, 0, 0, h); g.addColorStop(0, red ? `rgb(${110 * flash} 20 30)` : '#111a3a'); g.addColorStop(1, red ? '#1c0509' : '#0a1024');
	c.fillStyle = g; c.fillRect(0, 0, w, h);
	c.fillStyle = red ? '#5b1820' : '#1e2a5a'; c.fillRect(w * 0.06, h * 0.12, w * 0.24, h * 0.36);
	c.fillStyle = '#fef3c7'; c.beginPath(); c.arc(w * 0.12, h * 0.22, 7, 0, 7); c.fill();
	c.fillStyle = red ? '#2a0a0f' : '#0f172a'; c.fillRect(w * 0.175, h * 0.12, 3, h * 0.36);
	c.fillStyle = '#7c4a24'; c.fillRect(0, h * 0.8, w, 6); c.fillStyle = '#4a2a12'; c.fillRect(0, h * 0.8 + 6, w, h);
};
const desk = (c, w, h, red, t) => {
	c.fillStyle = '#a16207'; c.fillRect(w * 0.46, h * 0.6, w * 0.5, 8); c.fillStyle = OL; c.fillRect(w * 0.48, h * 0.6 + 8, 5, h * 0.2); c.fillRect(w * 0.92, h * 0.6 + 8, 5, h * 0.2);
	laptop(c, w * 0.62, h * 0.6, 74, red ? '#7f1d1d' : '#0b1220', t);
};
const scroller = (c, w, h, wd, t, sp = 1) => {
	const g = c.createLinearGradient(0, 0, 0, h); g.addColorStop(0, wd.sky[0]); g.addColorStop(1, wd.sky[1]);
	c.fillStyle = g; c.fillRect(0, 0, w, h);
	if (wd.theme === 'night' || wd.theme === 'under') for (let i = 0; i < 22; i++) { c.fillStyle = `rgb(255 255 255 / ${0.3 + ((i * 7) % 5) / 10})`; c.fillRect(((i * 71) - t * 20 * sp) % w + (((i * 71) - t * 20 * sp) % w < 0 ? w : 0), (i * 29) % (h * 0.6), 2, 2); }
	// far layer: the world's landmark, scrolling slowly
	const off = (t * 30 * sp) % 160;
	for (let k = -1; k < 3; k++) {
		const x = k * 160 - off + 60;
		c.fillStyle = 'rgba(0,0,0,.14)';
		if (wd.theme === 'plains') { c.beginPath(); c.roundRect(x, h * 0.3, 70, 70, 4); c.fill(); c.fillStyle = 'rgba(255,255,255,.35)'; c.fillRect(x + 6, h * 0.3 + 10, 40, 5); c.fillRect(x + 6, h * 0.3 + 20, 55, 4); }
		else if (wd.theme === 'under') { c.fillRect(x, h * 0.25, 40, h * 0.55); for (let j = 0; j < 5; j++) { c.fillStyle = j % 2 ? '#22c55e' : '#38bdf8'; c.fillRect(x + 6, h * 0.29 + j * 12, 4, 3); } }
		else if (wd.theme === 'desert') { c.beginPath(); c.moveTo(x, h * 0.8); c.quadraticCurveTo(x + 60, h * 0.45, x + 130, h * 0.8); c.fill(); c.fillStyle = '#b45309'; c.fillText && c.fillRect(x + 50, h * 0.62, 3, 12); }
		else if (wd.theme === 'date') { c.fillRect(x, h * 0.42, 50, h * 0.38); c.beginPath(); c.moveTo(x - 4, h * 0.42); c.lineTo(x + 25, h * 0.28); c.lineTo(x + 54, h * 0.42); c.fill(); c.fillStyle = '#fde68a'; c.fillRect(x + 10, h * 0.5, 7, 8); c.fillRect(x + 32, h * 0.5, 7, 8); }
		else { c.fillRect(x + 10, h * 0.3, 26, h * 0.5); c.fillStyle = '#fbbf24'; c.fillRect(x + 16, h * 0.36, 5, 5); c.fillRect(x + 25, h * 0.48, 5, 5); }
	}
	c.fillStyle = wd.dirt; c.fillRect(0, h * 0.8, w, h); c.fillStyle = wd.top; c.fillRect(0, h * 0.8, w, 6); c.fillStyle = wd.topD; c.fillRect(0, h * 0.8 + 6, w, 2);
	c.fillStyle = wd.dirtD; for (let x = -((t * 140 * sp) % 32); x < w; x += 32) c.fillRect(x, h * 0.8 + 14, 10, 4);
};

/** manga close-up of Korosh's face with an emotion: tired | shock | fire | happy */
export function drawClose(c, w, h, mood, t, who) {
	c.save();
	c.fillStyle = '#fff'; c.fillRect(0, 0, w, h);
	const hx = 78, hy = 112; // head centre on this canvas
	if (mood === 'fire') speed(c, w, h, hx, hy, 'rgba(0,0,0,.85)', t, true);
	else if (mood === 'shock') { for (let i = 0; i < 26; i++) { const x = (i / 26) * w; c.fillStyle = '#111'; c.fillRect(x, 0, 2 + (i % 3), h * (0.35 + ((i * 37) % 10) / 40)); } }
	else if (mood === 'happy') { for (let i = 0; i < 9; i++) { const a = i * 0.7 + t, r = 50 + (i % 3) * 14; const x = hx + Math.cos(a) * r, y = hy + Math.sin(a) * r * 0.8; c.fillStyle = '#111'; c.save(); c.translate(x, y); c.rotate(t); c.beginPath(); for (let k = 0; k < 8; k++) { const rr = k % 2 ? 2 : 6; c.lineTo(Math.cos(k * Math.PI / 4) * rr, Math.sin(k * Math.PI / 4) * rr); } c.fill(); c.restore(); } }
	else halftone(c, w, h);
	if (who === 'maedeh') {
		if (mood === 'angry') { for (let i = 0; i < 26; i++) { c.fillStyle = '#111'; c.fillRect((i / 26) * w, 0, 2 + (i % 3), h * (0.3 + ((i * 37) % 10) / 40)); } }
		else for (let i = 0; i < 7; i++) { const u = (t * 0.3 + i / 7) % 1; heart(c, 20 + i * 20, h - u * h, 2.4, 1 - u); }
		drawMaedeh(c, 74, h + 92, 6, -1, mood === 'angry' ? 'angry' : 'happy', t);
		c.strokeStyle = '#111'; c.lineWidth = 3; c.strokeRect(1.5, 1.5, w - 3, h - 3);
		c.restore();
		return;
	}
	const shake = mood === 'shock' ? Math.sin(t * 40) * 1.2 : 0;
	c.save(); c.translate(hx - 1.4 * 5 + shake, hy + 29.6 * 5); c.scale(5, 5);
	drawHero(c, 0, -32, 'big', 'none', 'stand', 1, 1, 1, null, 0, mood === 'tired' ? 1 : 0);
	c.restore();
	const ex = hx + 22, ey = hy - 8;
	if (mood === 'shock') {
		c.fillStyle = '#fff'; c.strokeStyle = '#111'; c.lineWidth = 2;
		const d = (t * 30) % 20;
		c.beginPath(); c.moveTo(ex - 40, ey - 30 + d); c.quadraticCurveTo(ex - 47, ey - 18 + d, ex - 40, ey - 14 + d); c.quadraticCurveTo(ex - 33, ey - 18 + d, ex - 40, ey - 30 + d); c.fill(); c.stroke(); // sweat drop
		c.lineWidth = 2.5; [0, 1, 2].forEach((i) => { c.beginPath(); c.moveTo(ex + 18 + i * 8, ey - 22 - i * 3); c.lineTo(ex + 28 + i * 10, ey - 34 - i * 5); c.stroke(); }); // shock marks
	} else if (mood === 'fire') {
		c.strokeStyle = '#111'; c.lineWidth = 3;
		c.save(); c.translate(ex + 10, ey - 46); c.rotate(0.3); [[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([a, b]) => { c.beginPath(); c.moveTo(a * 2, b * 2); c.quadraticCurveTo(a * 7, b * 1, a * 8, b * 8); c.stroke(); }); c.restore(); // anger vein
		c.fillStyle = '#fff'; c.beginPath(); c.moveTo(ex + 2, ey - 4); c.lineTo(ex + 14, ey - 9); c.lineTo(ex + 6, ey - 1); c.fill(); // glint on the lens
	} else if (mood === 'tired') {
		c.globalAlpha = 0.9; [0, 1, 2].forEach((i) => { const u = (t * 0.5 + i / 3) % 1; c.globalAlpha = 1 - u; c.font = `900 ${14 + i * 4}px ${FF}`; c.fillStyle = '#111'; c.fillText('z', 20 + u * 20, 60 - u * 40 + i * 10); }); c.globalAlpha = 1;
	}
	c.restore();
	// panel ink border inside the canvas edge
	c.strokeStyle = '#111'; c.lineWidth = 3; c.strokeRect(1.5, 1.5, w - 3, h - 3);
}

/** draw one panel; t = seconds since the panel appeared (drives the action) */
export function drawPanel(c, w, h, panel, t, worlds) {
	if (typeof document !== 'undefined' && FF === 'system-ui, sans-serif') FF = getComputedStyle(document.body).fontFamily || FF;
	c.save();
	c.clearRect(0, 0, w, h);
	const shake = (amp, until) => { const a = Math.max(0, amp * (1 - t / until)); if (a) c.translate((Math.random() - 0.5) * a, (Math.random() - 0.5) * a); };
	const heroAt = (x, y = h * 0.8, frame = 'stand', face = 1, k = 2.3, shoot = 0, tired = panel.tired ?? 0, rot = 0) => {
		c.save(); c.translate(x, y); c.rotate(rot); c.scale(k, k);
		drawHero(c, 0, -32, 'big', 'none', frame, face, 1, 1, frame === 'stand' ? null : (t * 1.8) % 1, shoot, tired);
		c.restore();
	};
	const sc = panel.scene;
	if (sc === 'desk') {
		const z = 1 + Math.min(0.08, t * 0.02); c.translate(w / 2, h / 2); c.scale(z, z); c.translate(-w / 2, -h / 2); // slow push-in
		room(c, w, h, false, t); desk(c, w, h, false, t);
		heroAt(w * 0.3);
		sfx(c, 'تیک… تاک…', w * 0.86, h * 0.18, 13, 0.08, '#93c5fd', t - 0.3);
		if (panel.tired) [0, 1, 2].forEach((i) => { const u = (t * 0.6 + i / 3) % 1; c.globalAlpha = 1 - u; sfx(c, 'z', w * 0.36 + u * 20, h * 0.2 - u * 26, 10 + i * 3, 0, '#e2e8f0', 1); c.globalAlpha = 1; });
	} else if (sc === 'alarm') {
		shake(10, 0.9);
		room(c, w, h, true, t); desk(c, w, h, true, t);
		const pop = ease((t - 0.15) * 2.2);
		if (pop > 0) { c.save(); c.translate(w * 0.62 + 37, h * 0.42); c.scale(pop * 1.6, pop * 1.6); bug(c, 0, 0, 12, '#ef4444', t); c.restore(); }
		const jump = t < 0.6 ? Math.sin((t / 0.6) * Math.PI) * 22 : 0;
		heroAt(w * 0.26, h * 0.8 - jump, t < 0.6 ? 'jump' : 'stand', 1, 2.3, 0, 0, t < 0.6 ? -0.12 : 0);
		burst(c, w * 0.52, h * 0.22, 34, 11, '#fde047', t - 0.15, t * 0.5);
		sfx(c, 'کرش!', w * 0.52, h * 0.22, 26, -0.15, '#ef4444', t - 0.15);
	} else if (sc === 'army') {
		shake(5, 3);
		const g = c.createRadialGradient(w / 2, h / 2, 10, w / 2, h / 2, w * 0.7); g.addColorStop(0, '#991b1b'); g.addColorStop(1, '#1c0509');
		c.fillStyle = g; c.fillRect(0, 0, w, h);
		c.save(); c.translate(w / 2, 0); c.rotate(t * 3); c.fillStyle = 'rgba(248,113,113,.18)'; c.beginPath(); c.moveTo(0, 0); c.arc(0, 0, w, -0.3, 0.3); c.fill(); c.restore(); // alarm beam
		c.save(); c.translate(w * 0.5, h * 0.52);
		c.fillStyle = '#475569'; c.strokeStyle = OL; c.lineWidth = 3; c.beginPath(); c.roundRect(-50, -17, 100, 34, 17); c.fill(); c.stroke();
		c.fillStyle = '#cbd5e1'; c.font = `900 15px ${FF}`; c.textAlign = 'center'; c.textBaseline = 'middle'; c.fillText('DEPLOY', 0, 1);
		const drop = Math.min(1, Math.max(0, (t - 0.9) * 5));
		c.translate(0, -60 * (1 - drop));
		c.fillStyle = '#fbbf24'; c.beginPath(); c.roundRect(-14, -12, 28, 22, 4); c.fill(); c.stroke(); c.beginPath(); c.arc(0, -12, 8, Math.PI, 0); c.lineWidth = 4; c.stroke();
		c.restore();
		const n = Math.min(22, Math.floor(2 + t * 12));
		for (let i = 0; i < n; i++) { const a = i * 2.39 + t * (i % 2 ? 0.7 : -0.5), rr = 46 + (i % 5) * 14 + Math.sin(t * 5 + i) * 4; bug(c, w / 2 + Math.cos(a) * rr * 1.7, h * 0.52 + Math.sin(a) * rr * 0.75, 6 + (i % 3) * 2.5, i % 4 ? '#ef4444' : '#a855f7', t + i); }
		if (drop >= 1) { burst(c, w * 0.5, h * 0.5 - 12, 26, 9, '#fef08a', t - 1.1); sfx(c, 'قفل شد!', w * 0.5, h * 0.18, 22, 0.08, '#fbbf24', t - 1.1); }
	} else if (sc === 'dive') {
		c.fillStyle = '#020617'; c.fillRect(0, 0, w, h);
		for (let i = 9; i > 0; i--) { c.strokeStyle = `hsl(${140 + i * 8 + t * 60} 80% ${30 + i * 4}%)`; c.lineWidth = 4; c.beginPath(); c.ellipse(w * 0.68, h * 0.5, i * 13 + Math.sin(t * 6 + i) * 3, i * 9, 0, 0, 7); c.stroke(); }
		speed(c, w, h, w * 0.68, h * 0.5, 'rgba(134,239,172,.55)', t, true);
		c.fillStyle = '#4ade80'; c.font = `700 10px ui-monospace,monospace`;
		for (let i = 0; i < 22; i++) { const x = (i * 53) % w, y = ((i * 37 + t * 90) % (h + 20)) - 10; c.fillText(i % 3 ? '1' : '{ }', x, y); }
		const u = (t % 2.6) / 2.6, k = 2.4 * (1 - u * 0.75);
		heroAt(w * (0.12 + u * 0.56), h * (0.75 - Math.sin(u * Math.PI) * 0.35 + u * 0.05), 'jump', 1, k, 0, 0, -0.4 - u * 2.5);
		sfx(c, 'شیرجه!', w * 0.3, h * 0.2, 24, -0.12, '#4ade80', t - 0.3);
	} else if (sc === 'phone') {
		room(c, w, h, false, t); desk(c, w, h, false, t);
		heroAt(w * 0.3, h * 0.8, 'stand', 1, 2.3, 0, 0.8);
		const buzz = Math.sin(t * 50) * (Math.sin(t * 3) > 0 ? 1.5 : 0);
		c.save(); c.translate(w * 0.52 + buzz, h * 0.6 - 7); c.fillStyle = '#0f172a'; c.strokeStyle = OL; c.lineWidth = 1.5; c.beginPath(); c.roundRect(-9, -15, 18, 15, 3); c.fill(); c.stroke();
		c.fillStyle = '#f472b6'; c.fillRect(-7, -13, 14, 11); c.fillStyle = '#fff'; c.font = `900 6px ${FF}`; c.textAlign = 'center'; c.textBaseline = 'middle'; c.fillText(panel.phone || 'مائده 😠', 0, -7.5); c.restore();
		if (Math.sin(t * 3) > 0) { c.strokeStyle = '#f8fafc'; c.lineWidth = 1.5; [-1, 1].forEach((d) => { c.beginPath(); c.arc(w * 0.52, h * 0.6 - 14, 14 + (t * 20) % 6, d > 0 ? -0.6 : Math.PI - 0.6, d > 0 ? 0.6 : Math.PI + 0.6); c.stroke(); }); }
		sfx(c, 'ززز… ززز…', w * 0.6, h * 0.24, 18, -0.1, '#f472b6', t - 0.2);
	} else if (sc === 'drive') {
		const wd = worlds[panel.car === '207' ? 6 : 4] || worlds[0];
		scroller(c, w, h, wd, t, 2.5);
		speed(c, w, h, 0, 0, 'rgba(255,255,255,.6)', t, false);
		drawL90(c, w * 0.36, h * 0.8 + 2, 2.2, t, true, panel.car);
		if (panel.car === '207') { const u = t % 1.6; if (u < 0.5) { burst(c, w * 0.78, h * 0.5, 30 + u * 40, 10, '#fb923c', u, u * 2); burst(c, w * 0.78, h * 0.5, 16 + u * 20, 8, '#fde047', u, -u * 2); } sfx(c, 'بـــوم!', w * 0.78, h * 0.3, 22, 0.15, '#f97316', u < 0.5 ? u : 0); }
		const cx2 = w * 0.92 - ((t * 60) % (w * 0.5));
		c.save(); c.translate(cx2, h * 0.8); c.fillStyle = '#facc15'; c.strokeStyle = OL; c.lineWidth = 2; c.beginPath(); c.roundRect(-22, -22, 44, 16, 4); c.fill(); c.stroke(); c.fillStyle = '#111'; c.beginPath(); c.arc(-12, -5, 5, 0, 7); c.arc(12, -5, 5, 0, 7); c.fill(); c.restore(); // taxi ahead
		sfx(c, 'ویــــژ!', w * 0.62, h * 0.22, 24, -0.12, '#f8fafc', t - 0.2);
		if (panel.car !== '207') sfx(c, 'بوق! بوق!', w * 0.86, h * 0.4, 13, 0.15, '#facc15', t - 0.9);
	} else if (sc === 'meet') {
		const wd = worlds[5] || worlds[0];
		scroller(c, w, h, wd, 0, 0);
		c.fillStyle = '#fef9c3'; c.beginPath(); c.arc(w * 0.85, h * 0.18, 12, 0, 7); c.fill();
		for (let i = 0; i < 16; i++) { const x = i * (w / 15), y = h * 0.12 + Math.sin(i * 0.8) * 4 + 6; c.fillStyle = ['#f472b6', '#fde047', '#60a5fa', '#4ade80'][i % 4]; c.globalAlpha = 0.6 + Math.sin(t * 4 + i) * 0.4; c.beginPath(); c.arc(x, y, 2.4, 0, 7); c.fill(); c.globalAlpha = 1; }
		heroAt(w * 0.36, h * 0.8, 'stand', 1, 2.3, 0, 0);
		drawMaedeh(c, w * 0.66, h * 0.8, 2.3, -1, t < 0.8 ? 'angry' : 'happy', t);
		// bouquet
		c.save(); c.translate(w * 0.45, h * 0.5); ['#f43f5e', '#fb7185', '#e11d48'].forEach((col, i) => { c.fillStyle = col; c.strokeStyle = OL; c.lineWidth = 1; c.beginPath(); c.arc(i * 4 - 4, -4 - (i % 2) * 3, 3, 0, 7); c.fill(); c.stroke(); }); c.strokeStyle = '#15803d'; c.lineWidth = 2; c.beginPath(); c.moveTo(0, 0); c.lineTo(-2, 10); c.stroke(); c.restore();
		burst(c, w * 0.55, h * 0.24, 22, 9, '#fbcfe8', t - 0.8, t);
		sfx(c, '💗', w * 0.55, h * 0.24, 20, 0, '#f43f5e', t - 0.8);
	} else if (sc === 'party' || sc === 'award' || sc === 'gaffe' || sc === 'laugh') {
		if (sc !== 'party') shake(sc === 'laugh' ? 5 : 0, 3);
		hall(c, w, h, t);
		const stageY = h * 0.66;
		if (sc === 'party') {
			c.fillStyle = 'rgba(254,249,195,.18)'; c.beginPath(); c.moveTo(w * 0.5, 0); c.lineTo(w * 0.36 + Math.sin(t * 2) * 30, stageY); c.lineTo(w * 0.64 + Math.sin(t * 2) * 30, stageY); c.fill(); // roaming spotlight
			person(c, w * 0.5, stageY, 1.5, { ...CAST.negar, mood: 'smile' }, t);
			c.fillStyle = '#334155'; c.fillRect(w * 0.5 + 9, stageY - 26, 2, 26); c.beginPath(); c.arc(w * 0.5 + 10, stageY - 28, 2.6, 0, 7); c.fill(); // mic stand
			crowd(c, w, h, t, false);
			sfx(c, 'دررررر…', w * 0.2, h * 0.32, 18, -0.15, '#fde047', t - 0.2);
			sfx(c, 'کوروش!', w * 0.8, h * 0.32, 20, 0.12, '#f472b6', t - 1);
		} else {
			c.fillStyle = 'rgba(254,249,195,.22)'; c.beginPath(); c.moveTo(w * 0.5, 0); c.lineTo(w * 0.34, stageY); c.lineTo(w * 0.62, stageY); c.fill();
			const c0 = c; c0.save(); c0.translate(w * 0.47, stageY); c0.scale(1.45, 1.45); drawHero(c0, 0, -32, 'big', 'none', 'stand', 1, 1, 1, null, sc === 'award' ? 0.6 : 0, 0); c0.restore();
			if (sc === 'award') trophy(c, w * 0.47 + 18, stageY - 32 + Math.sin(t * 3), 1.6);
			c.fillStyle = '#334155'; c.fillRect(w * 0.47 + 22, stageY - 24, 2, 24); c.beginPath(); c.arc(w * 0.47 + 23, stageY - 26, 2.6, 0, 7); c.fill();
			person(c, w * 0.72, stageY, 1.35, { ...CAST.negar, clap: sc === 'award', mood: sc === 'laugh' ? 'laugh' : sc === 'gaffe' ? 'shock' : 'smile' }, t);
			// front row
			const fy = h * 0.98;
			person(c, w * 0.2, fy, 1.5, { ...CAST.shayan, clap: sc === 'award', point: sc === 'gaffe', palm: sc === 'laugh', mood: sc === 'gaffe' ? 'shock' : sc === 'laugh' ? 'meh' : 'smile' }, t);
			drawMaedeh(c, w * 0.36, fy + 2, 1.1, 1, 'happy', t);
			person(c, w * 0.84, fy, 1.5, { ...CAST.mh, clap: sc === 'award', q: sc !== 'award', mood: sc === 'laugh' ? 'smile' : sc === 'gaffe' ? 'shock' : 'smile' }, t);
			if (sc === 'award') { for (let i = 0; i < 30; i++) { const sd = (i * 7919) % 101 / 101, y = ((t * (50 + sd * 50) + sd * h) % (h + 20)) - 10; c.fillStyle = `hsl(${i * 37} 90% 60%)`; c.fillRect((i * 47) % w, y, 3, 5); } sfx(c, '👏 تشویق!', w * 0.22, h * 0.3, 16, -0.12, '#fde047', t - 0.3); }
			if (sc === 'gaffe') { c.strokeStyle = 'rgba(254,249,195,.5)'; c.lineWidth = 3; c.beginPath(); c.moveTo(w * 0.5, h * 0.05); c.lineTo(w * 0.84, h * 0.62); c.stroke(); burst(c, w * 0.84, h * 0.42, 20, 9, '#fde047', t - 0.6, t); sfx(c, '؟!', w * 0.84, h * 0.42, 18, 0.1, '#ef4444', t - 0.6); }
			if (sc === 'laugh') { crowd(c, w, h + 30, t, true); sfx(c, 'هاهاهاها 😂', w * 0.5, h * 0.3, 24, -0.08, '#fde047', t - 0.1); sfx(c, 'قهقهه!', w * 0.16, h * 0.18, 13, -0.2, '#f472b6', t - 0.5); }
		}
	} else if (sc === 'win') {
		const g = c.createLinearGradient(0, 0, 0, h); g.addColorStop(0, '#fde68a'); g.addColorStop(1, '#fb923c');
		c.fillStyle = g; c.fillRect(0, 0, w, h);
		for (let i = 0; i < 16; i++) { const a = (i / 16) * Math.PI * 2 + t * 0.4; c.strokeStyle = 'rgb(255 255 255 / 0.35)'; c.lineWidth = 7; c.beginPath(); c.moveTo(w * 0.66, h * 0.42); c.lineTo(w * 0.66 + Math.cos(a) * w, h * 0.42 + Math.sin(a) * w); c.stroke(); }
		burst(c, w * 0.66, h * 0.42, 62, 14, '#fef08a', t, t * 0.3);
		c.save(); c.translate(w * 0.66, h * 0.42); const pr = t > 0.5 && t < 0.7 ? 0.9 : 1; c.scale(pr, pr);
		c.fillStyle = '#22c55e'; c.strokeStyle = OL; c.lineWidth = 3; c.beginPath(); c.roundRect(-52, -18, 104, 36, 18); c.fill(); c.stroke();
		c.fillStyle = '#fff'; c.font = `900 15px ${FF}`; c.textAlign = 'center'; c.textBaseline = 'middle'; c.fillText('DEPLOY ✓', 0, 1); c.restore();
		for (let i = 0; i < 40; i++) { const sd = (i * 7919) % 101 / 101, y = ((t * (60 + sd * 60) + sd * h) % (h + 20)) - 10; c.fillStyle = `hsl(${i * 37} 90% 55%)`; c.save(); c.translate((i * 47) % w, y); c.rotate(t * 4 + i); c.fillRect(-3, -1.5, 6, 3); c.restore(); }
		c.fillStyle = '#16a34a'; c.fillRect(0, h * 0.8, w, h);
		const hop = Math.abs(Math.sin(t * 5)) * 14;
		heroAt(w * 0.24, h * 0.8 - hop, hop > 3 ? 'jump' : 'stand', 1, 2.3, 0.8, 0);
		sfx(c, 'دیپلوی شد!', w * 0.66, h * 0.15, 22, -0.06, '#22c55e', t - 0.4);
	} else if (panel.boss) {
		// VS face-off: diagonal split, hero vs boss
		shake(8, 1.2);
		const wd = worlds[panel.world];
		c.fillStyle = wd.sky[0]; c.fillRect(0, 0, w, h);
		c.save(); c.beginPath(); c.moveTo(w * 0.55, 0); c.lineTo(w, 0); c.lineTo(w, h); c.lineTo(w * 0.42, h); c.closePath(); c.clip();
		c.fillStyle = '#1c0509'; c.fillRect(0, 0, w, h); speed(c, w, h, w * 0.78, h * 0.5, 'rgba(244,63,94,.4)', t, true);
		const grow = 0.7 + ease(t * 1.5) * 0.3, r = 46 * grow;
		c.fillStyle = '#0b0b0e'; c.beginPath(); c.ellipse(w * 0.8, h * 0.66, r * 1.25, r, 0, 0, 7); c.fill();
		for (let i = -2; i <= 2; i++) { c.strokeStyle = '#0b0b0e'; c.lineWidth = 4; c.beginPath(); c.moveTo(w * 0.8 + i * 14, h * 0.66 + r * 0.6); c.lineTo(w * 0.8 + i * 20, h * 0.66 + r + 10); c.stroke(); }
		c.fillStyle = '#f43f5e'; [-1, 1].forEach((k) => { c.beginPath(); c.ellipse(w * 0.8 + k * 16, h * 0.6, 7, 3 + Math.abs(Math.sin(t * 3)) * 3, k * 0.3, 0, 7); c.fill(); });
		c.restore();
		c.save(); c.beginPath(); c.moveTo(0, 0); c.lineTo(w * 0.55, 0); c.lineTo(w * 0.42, h); c.lineTo(0, h); c.closePath(); c.clip();
		speed(c, w, h, w * 0.2, h * 0.5, 'rgba(255,255,255,.5)', t, true);
		heroAt(w * 0.2 + (1 - ease(t * 2)) * -60, h * 0.95, 'stand', 1, 2.7, 0.7, 0);
		c.restore();
		c.strokeStyle = OL; c.lineWidth = 5; c.beginPath(); c.moveTo(w * 0.55, 0); c.lineTo(w * 0.42, h); c.stroke();
		burst(c, w * 0.49, h * 0.42, 28, 10, '#fde047', t - 0.2, t);
		sfx(c, 'VS', w * 0.49, h * 0.43, 24, -0.1, '#ef4444', t - 0.2);
		sfx(c, worlds[panel.world].bossName, w * 0.76, h * 0.88, 13, -0.04, '#fca5a5', t - 0.5);
	} else {
		// run-and-gun: world scrolls, Korosh runs and blasts a bug with code
		const wd = worlds[panel.world];
		scroller(c, w, h, wd, t, 1);
		speed(c, w, h, 0, 0, 'rgba(255,255,255,.55)', t, false);
		const cyc = t % 2.2, hit = cyc > 0.75;
		const bx = w * 0.78 - cyc * 20, by = h * 0.8 - 10 - Math.abs(Math.sin(t * 6)) * 6;
		if (!hit) bug(c, bx, by, 10, '#ef4444', t);
		if (cyc > 0.35 && !hit) { const u = (cyc - 0.35) / 0.4, x = w * 0.33 + (bx - w * 0.33) * u; c.font = `900 14px ui-monospace,monospace`; c.textAlign = 'center'; c.lineWidth = 4; c.strokeStyle = OL; c.strokeText('</>', x, h * 0.62); c.fillStyle = '#22d3ee'; c.fillText('</>', x, h * 0.62); }
		if (hit && cyc < 1.6) { burst(c, bx, by - 6, 24, 9, '#fde047', (cyc - 0.75), cyc * 3); sfx(c, 'پاق!', bx, by - 8, 18, 0.2, '#f97316', cyc - 0.75); }
		bug(c, w * 0.95 - (t * 30) % 60, h * 0.8 - 8, 7, '#a855f7', t + 2);
		heroAt(w * 0.22, h * 0.8 - Math.abs(Math.sin(t * 9)) * 3, 'run1', 1, 2.2, cyc > 0.3 && cyc < 0.6 ? 1 : 0, 0);
	}
	c.restore();
	halftone(c, w, h);
}

/**
 * Show a comic sequence over `host`; calls done() when finished or skipped. Returns a close() function.
 * worlds = WORLDS (for stage panels), label = small title over the panel, sound(name) = optional sfx.
 */
export function showComic(host, panels, { worlds, label = GAME_NAME, done, skip = true, sound }) {
	const el = document.createElement('div');
	el.className = 'kr-comic';
	el.setAttribute('role', 'dialog');
	el.setAttribute('aria-label', label);
	el.innerHTML = `<div class="kr-comic__box">
		<p class="kr-comic__k">${esc(label)}</p>
		<div class="kr-comic__page" data-c-page>
			<div class="kr-comic__panel kr-comic__main" data-c-panel><div class="kr-comic__in"><canvas width="640" height="360" aria-hidden="true"></canvas></div>
				<p class="kr-comic__cap" data-c-cap aria-live="polite"></p></div>
			<div class="kr-comic__panel kr-comic__close" data-c-panel><div class="kr-comic__in"><canvas width="320" height="360" aria-hidden="true" data-c-close></canvas></div>
				<p class="kr-comic__say" data-c-say></p></div>
		</div>
		<div class="kr-comic__bar"><span class="kr-comic__dots" data-c-dots></span>
			${skip && panels.length > 1 ? '<button type="button" class="kr-comic__skip" data-c-skip>رد کردن</button>' : ''}
			<button type="button" class="kr-comic__go" data-c-go></button></div>
	</div>`;
	host.appendChild(el);
	const cv = el.querySelector('canvas'), cx = cv.getContext('2d'), cx2 = el.querySelector('[data-c-close]').getContext('2d');
	cx.scale(2, 2); cx2.scale(2, 2);
	const capEl = el.querySelector('[data-c-cap]'), sayEl = el.querySelector('[data-c-say]'), pn = el.querySelector('[data-c-page]');
	let i = 0, raf = 0, closed = false, t0 = performance.now(), typing = 0, full = '';
	const SOUND = { desk: 'click', alarm: 'boom', army: 'wrong', dive: 'jump', win: 'level' };
	const frame = () => { const tt = (performance.now() - t0) / 1000, p = panels[i]; drawPanel(cx, 320, 180, p, tt, worlds); drawClose(cx2, 160, 180, p.mood || 'fire', tt, p.who); raf = requestAnimationFrame(frame); };
	const finishType = () => { clearInterval(typing); typing = 0; capEl.textContent = full; sayEl.classList.add('is-on'); };
	const show = () => {
		const p = panels[i];
		t0 = performance.now();
		full = p.cap;
		capEl.textContent = '';
		sayEl.textContent = p.say || '';
		sayEl.hidden = !p.say;
		sayEl.classList.remove('is-on');
		sayEl.classList.toggle('is-shout', ['shock', 'fire', 'angry'].includes(p.mood) || !!p.boss);
		let n = 0;
		clearInterval(typing);
		typing = setInterval(() => { n += 2; capEl.textContent = full.slice(0, n); if (n >= full.length) finishType(); }, 28);
		el.querySelector('[data-c-dots]').innerHTML = panels.length > 1 ? panels.map((_, k) => `<i class="${k === i ? 'is-on' : ''}"></i>`).join('') : '';
		el.querySelector('[data-c-go]').textContent = i < panels.length - 1 ? 'ادامه ◂' : 'بزن بریم! ◂';
		pn.classList.remove('is-in', 'is-alt'); void pn.offsetWidth; pn.classList.add('is-in'); if (i % 2) pn.classList.add('is-alt');
		if (sound) sound(SOUND[p.scene] || (p.boss ? 'boom' : 'shoot'));
	};
	const close = () => {
		if (closed) return;
		closed = true;
		cancelAnimationFrame(raf);
		clearInterval(typing);
		window.removeEventListener('keydown', onKey, true);
		el.remove();
	};
	const next = () => { if (closed) return; if (typing) return finishType(); if (i < panels.length - 1) { i++; show(); } else { close(); done && done(); } };
	const onKey = (e) => {
		if (['Enter', 'Space', 'ArrowLeft', 'KeyX'].includes(e.code)) { e.preventDefault(); e.stopPropagation(); next(); }
		else if (e.code === 'Escape' && skip) { e.preventDefault(); e.stopPropagation(); close(); done && done(); }
	};
	el.addEventListener('click', (e) => {
		if (e.target.closest('[data-c-skip]')) { close(); done && done(); return; }
		if (e.target.closest('[data-c-go], [data-c-panel]')) next();
	});
	window.addEventListener('keydown', onKey, true);
	show();
	frame();
	setTimeout(() => !closed && el.querySelector('[data-c-go]').focus({ preventScroll: true }), 30);
	return close;
}
