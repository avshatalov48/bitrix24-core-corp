/**
 * @module layout/polyfill
 */
jn.define('layout/polyfill', (require, exports, module) => {

	const ShimmerViewPolyfill = Application.getApiVersion() >= 49 ? ShimmerView : View;

	module.exports = {
		ShimmerView: ShimmerViewPolyfill,
	};
});
