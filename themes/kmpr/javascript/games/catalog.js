/**
 * Arcade catalog — lightweight metadata shared by the hub, the shell and the engines.
 * Engines themselves are lazy-loaded (dynamic import) from ./index.js.
 *
 * kind: 'arcade' = single-player score chase, 'match' = board/duel game (AI / local 2P / online).
 * Achievement ids are the contract between the catalog and each engine's api.unlock(id).
 */
export const CATALOG = {
	jota: {
		title: 'سفر فضانورد',
		kind: 'arcade',
		achievements: [
			{ id: 'first_kill', title: 'اولین شلیک دقیق', desc: 'اولین سیارک را منفجر کن', icon: '🎯' },
			{ id: 'stage3', title: 'عبور از کمربند', desc: 'به مرحله ۳ برس', icon: '🪐' },
			{ id: 'boss', title: 'شکارچی بشقاب‌پرنده', desc: 'یک غول مرحله را شکست بده', icon: '🛸' },
			{ id: 'combo', title: 'ضریب پنج', desc: 'ضریب امتیاز را به ×۵ برسان', icon: '🔥' },
			{ id: 'flawless', title: 'بی‌خطا', desc: 'یک مرحله را بدون آسیب تمام کن', icon: '🛡️' },
			{ id: 's25k', title: 'ستاره‌دار', desc: '۲۵٬۰۰۰ امتیاز بگیر', icon: '⭐' },
		],
	},
	'bug-hunter': {
		title: 'شکارچی باگ',
		kind: 'arcade',
		achievements: [
			{ id: 'first_squash', title: 'اولین دیباگ', desc: 'اولین باگ را له کن', icon: '🐞' },
			{ id: 'combo10', title: 'دیباگر زنجیره‌ای', desc: '۱۰ باگ پشت‌سرهم بدون خطا', icon: '⛓️' },
			{ id: 'golden', title: 'باگ طلایی', desc: 'یک باگ طلایی شکار کن', icon: '🏅' },
			{ id: 'feature', title: 'باگ نیست، فیچره!', desc: '۵ فیچر را دست‌نخورده رها کن', icon: '✅' },
			{ id: 'level5', title: 'نسخه ۵', desc: 'به سطح ۵ برس', icon: '🚀' },
			{ id: 's5k', title: 'بیلد سبز', desc: '۵٬۰۰۰ امتیاز بگیر', icon: '🟢' },
		],
	},
	'type-code': {
		title: 'تایپ کد',
		kind: 'arcade',
		achievements: [
			{ id: 'wpm30', title: 'گرم کردن انگشت‌ها', desc: 'سرعت ۳۰ کلمه در دقیقه', icon: '⌨️' },
			{ id: 'wpm50', title: 'تایپیست حرفه‌ای', desc: 'سرعت ۵۰ کلمه در دقیقه', icon: '⚡' },
			{ id: 'wpm70', title: 'انگشتان نوری', desc: 'سرعت ۷۰ کلمه در دقیقه', icon: '🌠' },
			{ id: 'clean_line', title: 'خط تمیز', desc: 'یک قطعه کد را بدون غلط تایپ کن', icon: '✨' },
			{ id: 'acc98', title: 'دقت جراحی', desc: 'آزمون را با دقت ۹۸٪ تمام کن', icon: '🎯' },
			{ id: 'lines10', title: 'ده کامیت', desc: '۱۰ قطعه را در یک آزمون تمام کن', icon: '📦' },
		],
	},
	'stack-memory': {
		title: 'حافظه استک',
		kind: 'arcade',
		achievements: [
			{ id: 'first_pair', title: 'اولین جفت', desc: 'اولین جفت را پیدا کن', icon: '🧩' },
			{ id: 'perfect', title: 'حافظه کش', desc: 'یک مرحله را بدون اشتباه تمام کن', icon: '💾' },
			{ id: 'combo4', title: 'هیت پشت هیت', desc: '۴ جفت پشت‌سرهم', icon: '🔗' },
			{ id: 'speed', title: 'لود سریع', desc: 'مرحله را با بیش از نصف زمان باقی‌مانده تمام کن', icon: '⏱️' },
			{ id: 'level5', title: 'فول‌استک', desc: 'به مرحله ۵ برس', icon: '🏗️' },
			{ id: 's5k', title: 'رم اضافه', desc: '۵٬۰۰۰ امتیاز بگیر', icon: '🧠' },
		],
	},
	'pixel-runner': {
		title: 'رانر پیکسلی',
		kind: 'arcade',
		achievements: [
			{ id: 'm500', title: 'اسپرینت اول', desc: '۵۰۰ متر بدو', icon: '🏃' },
			{ id: 'm2000', title: 'ماراتن ددلاین', desc: '۲٬۰۰۰ متر بدو', icon: '🏁' },
			{ id: 'coffee10', title: 'کافئین', desc: '۱۰ قهوه در یک دست جمع کن', icon: '☕' },
			{ id: 'focus', title: 'حالت تمرکز', desc: 'حالت تمرکز را فعال کن', icon: '🧘' },
			{ id: 'double', title: 'پرش دوبل', desc: 'از روی یک دیوار مرج‌کانفلیکت بپر', icon: '🧱' },
			{ id: 's10k', title: 'شیپ شد', desc: '۱۰٬۰۰۰ امتیاز بگیر', icon: '📦' },
		],
	},
	'hex-hunter': {
		title: 'معمای رنگ',
		kind: 'arcade',
		achievements: [
			{ id: 'first', title: 'چشم رنگی', desc: 'اولین پاسخ درست', icon: '🎨' },
			{ id: 'streak10', title: 'پالت کامل', desc: '۱۰ پاسخ درست پشت‌سرهم', icon: '🌈' },
			{ id: 'fast', title: 'رنگ‌سنج سریع', desc: 'در کمتر از یک ثانیه درست جواب بده', icon: '⚡' },
			{ id: 'hsl', title: 'استاد HSL', desc: 'به مرحله فرمت hsl برس', icon: '🧪' },
			{ id: 'level10', title: 'دیزاینر', desc: 'به مرحله ۱۰ برس', icon: '🖌️' },
			{ id: 's5k', title: 'پیکسل‌پرفکت', desc: '۵٬۰۰۰ امتیاز بگیر', icon: '💎' },
		],
	},
	snake: {
		title: 'مار نئونی',
		kind: 'arcade',
		achievements: [
			{ id: 'len20', title: 'مار بلند', desc: 'طول مار را به ۲۰ برسان', icon: '🐍' },
			{ id: 'level5', title: 'مرحله ۵', desc: 'به مرحله ۵ برس', icon: '🧭' },
			{ id: 'power', title: 'پاورآپ', desc: 'سه پاورآپ را در یک دست بگیر', icon: '⚡' },
			{ id: 'walls', title: 'بدون دیوار', desc: 'در حالت دیواردار ۱۵ میوه بخور', icon: '🧱' },
			{ id: 'golden', title: 'سیب طلایی', desc: 'یک سیب طلایی بخور', icon: '🍏' },
			{ id: 's3k', title: 'ماری به طول اینترنت', desc: '۳٬۰۰۰ امتیاز بگیر', icon: '🌐' },
		],
	},
	'sky-shooter': {
		title: 'تیرانداز آسمان',
		kind: 'arcade',
		achievements: [
			{ id: 'first', title: 'اولین هدف', desc: 'اولین هدف را بزن', icon: '🎯' },
			{ id: 'double', title: 'دو با یک تیر', desc: 'دو هدف را با یک شلیک بزن', icon: '✌️' },
			{ id: 'perfect_round', title: 'دور بی‌نقص', desc: 'همه اهداف یک دور را بزن', icon: '💯' },
			{ id: 'round5', title: 'دور ۵', desc: 'به دور ۵ برس', icon: '🌅' },
			{ id: 'golden', title: 'بادبادک طلایی', desc: 'هدف طلایی را بزن', icon: '🪁' },
			{ id: 's10k', title: 'تک‌تیرانداز', desc: '۱۰٬۰۰۰ امتیاز بگیر', icon: '🏆' },
		],
	},
	xo: {
		title: 'ایکس‌او',
		kind: 'match',
		online: true,
		achievements: [
			{ id: 'win_easy', title: 'گرم کردن', desc: 'هوش مصنوعی آسان را ببر', icon: '❌' },
			{ id: 'win_medium', title: 'استراتژیست', desc: 'هوش مصنوعی متوسط را ببر', icon: '⭕' },
			{ id: 'draw_hard', title: 'هم‌تراز ماشین', desc: 'با هوش مصنوعی شکست‌ناپذیر مساوی کن', icon: '🤖' },
			{ id: 'online_win', title: 'اولین برد آنلاین', desc: 'یک بازی آنلاین را ببر', icon: '🌐' },
			{ id: 'online5', title: 'پنج برد آنلاین', desc: '۵ بار آنلاین ببر', icon: '🏆' },
			{ id: 'local', title: 'دو نفره', desc: 'یک بازی دو نفره روی یک دستگاه انجام بده', icon: '👥' },
		],
	},
	rps15: {
		title: 'سنگ کاغذ قیچی ۱۵ حالته',
		kind: 'match',
		online: true,
		achievements: [
			{ id: 'first_win', title: 'اولین پیروزی', desc: 'یک مسابقه را ببر', icon: '✊' },
			{ id: 'dragon', title: 'اژدهاسوار', desc: 'با اژدها یک دست را ببر', icon: '🐉' },
			{ id: 'sweep', title: 'جارو', desc: 'یک مسابقه را بدون باخت حتی یک دست ببر', icon: '🧹' },
			{ id: 'all15', title: 'کلکسیونر', desc: 'هر ۱۵ حرکت را حداقل یک بار بازی کن', icon: '🎴' },
			{ id: 'online_win', title: 'برد آنلاین', desc: 'یک مسابقه آنلاین را ببر', icon: '🌐' },
			{ id: 'beat_ai', title: 'ذهن‌خوان', desc: 'هوش یادگیرنده را در بهترین از ۷ ببر', icon: '🧠' },
		],
	},
	chess: {
		title: 'شطرنج',
		kind: 'match',
		online: true,
		achievements: [
			{ id: 'first_win', title: 'کیش و مات', desc: 'اولین برد را بگیر', icon: '♚' },
			{ id: 'win_medium', title: 'استاد محلی', desc: 'هوش مصنوعی متوسط را ببر', icon: '♞' },
			{ id: 'castle', title: 'قلعه‌رفتن', desc: 'یک بار قلعه برو', icon: '♜' },
			{ id: 'promote', title: 'ارتقا', desc: 'یک پیاده را ارتقا بده', icon: '♛' },
			{ id: 'en_passant', title: 'آنپاسان', desc: 'یک آنپاسان بزن', icon: '♟' },
			{ id: 'online_win', title: 'برد آنلاین', desc: 'یک بازی آنلاین را ببر', icon: '🌐' },
		],
	},
	'color-race': {
		title: 'رنگ‌بازی',
		kind: 'match',
		online: true,
		achievements: [
			{ id: 'first_win', title: 'رنگ برنده', desc: 'یک مسابقه را ببر', icon: '🟧' },
			{ id: 'domination', title: 'تسلط کامل', desc: 'بیش از ۷۵٪ خانه‌ها را رنگ کن', icon: '🟦' },
			{ id: 'comeback', title: 'بازگشت', desc: 'از عقب ببر', icon: '🔄' },
			{ id: 'online_win', title: 'برد آنلاین', desc: 'یک مسابقه آنلاین را ببر', icon: '🌐' },
			{ id: 'local', title: 'دو دست روی یک صفحه', desc: 'یک مسابقه دو نفره روی یک دستگاه', icon: '👥' },
			{ id: 'fast', title: 'انگشت‌های سریع', desc: 'در ۱۰ ثانیه ۴۰ خانه رنگ کن', icon: '⚡' },
		],
	},
};

export const ENGINE_LOADERS = {
	jota: () => import('./engines/jota.js'),
	'bug-hunter': () => import('./engines/bug-hunter.js'),
	'type-code': () => import('./engines/type-code.js'),
	'stack-memory': () => import('./engines/stack-memory.js'),
	'pixel-runner': () => import('./engines/pixel-runner.js'),
	'hex-hunter': () => import('./engines/hex-hunter.js'),
	snake: () => import('./engines/snake.js'),
	'sky-shooter': () => import('./engines/sky-shooter.js'),
	xo: () => import('./engines/xo.js'),
	rps15: () => import('./engines/rps15.js'),
	chess: () => import('./engines/chess.js'),
	'color-race': () => import('./engines/color-race.js'),
};
