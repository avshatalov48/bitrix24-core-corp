/**
 * @module layout/ui/safe-image/src/shimmed-safe-image
 */
jn.define('layout/ui/safe-image/src/shimmed-safe-image', (require, exports, module) => {
	const { SafeImage } = require('layout/ui/safe-image/src/safe-image');

	/**
	 * @deprecated
	 * @see SafeImage param withShimmer
	 * @class ShimmedSafeImage
	 */
	class ShimmedSafeImage extends SafeImage
	{
		renderPlaceholder()
		{
			if (this.isSuccess())
			{
				return null;
			}

			return this.renderShimmer();
		}
	}

	module.exports = {
		ShimmedSafeImage,
	};
});
