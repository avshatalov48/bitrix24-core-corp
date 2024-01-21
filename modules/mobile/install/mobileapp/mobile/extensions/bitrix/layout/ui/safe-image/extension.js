/**
 * @module layout/ui/safe-image
 */
jn.define('layout/ui/safe-image', (require, exports, module) => {
	const { SafeImage } = require('layout/ui/safe-image/src/safe-image');
	const { ShimmedSafeImage } = require('layout/ui/safe-image/src/shimmed-safe-image');

	module.exports = {
		SafeImage: (props) => new SafeImage(props),
		ShimmedSafeImage: (props) => new ShimmedSafeImage(props),
	};
});
