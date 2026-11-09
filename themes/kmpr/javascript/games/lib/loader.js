/** 2D loaders (5 styles) used for engine loading and page-to-page navigation. Pure CSS animation, no assets. */
const STYLES = {
	pixel: '<div class="kl-pixel"><i></i></div>',
	bounce: '<div class="kl-bounce"><i></i><i></i><i></i></div>',
	blocks: '<div class="kl-blocks"><i></i><i></i><i></i><i></i></div>',
	orbit: '<div class="kl-orbit"><i></i><b></b></div>',
	pad: '<div class="kl-pad"><span></span><i></i><i></i></div>',
};
const TIPS = ['در حال آماده کردن زمین بازی…', 'دسته‌ها را شارژ می‌کنیم…', 'یک لحظه، بازی دارد گرم می‌شود…', 'نکته: Esc بازی را متوقف می‌کند', 'رکوردت را یادت هست؟'];
export function loaderHTML(style) {
	const keys = Object.keys(STYLES);
	const k = STYLES[style] ? style : keys[Math.floor(Math.random() * keys.length)];
	return `<div class="kl" role="status" aria-label="در حال بارگذاری">${STYLES[k]}<p class="kl__tip">${TIPS[Math.floor(Math.random() * TIPS.length)]}</p></div>`;
}
