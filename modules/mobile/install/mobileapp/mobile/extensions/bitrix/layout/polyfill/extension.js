/**
 * @module layout/polyfill
 */
jn.define('layout/polyfill', (require, exports, module) => {

	const ShimmerViewPolyfill = ShimmerView;

	module.exports = {
		ShimmerView: ShimmerViewPolyfill,
	};
});
