/**
 * Motion layer entry — printed inline at the end of <body> (see inc/assets.php) so reveal targets are
 * prepared before first paint instead of waiting for the deferred app bundle (no flash-then-animate).
 */
import initMotion, { scan } from './motion';

window.kmprMotion = { scan };
initMotion();
window.__kmprMotion = true;
