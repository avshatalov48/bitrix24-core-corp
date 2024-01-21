/**
 * @module layout/ui/safe-image/src/shimmed-safe-image
 */
jn.define('layout/ui/safe-image/src/shimmed-safe-image', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { SafeImage } = require('layout/ui/safe-image/src/safe-image');
	const { ShimmerView } = require('layout/polyfill');

	/**
	 * @class ShimmedSafeImage
	 */
	class ShimmedSafeImage extends SafeImage
	{
		renderPlaceholder()
		{
			if (this.state.success)
			{
				return null;
			}

			return ShimmerView(
				{ animating: true },
				View(
					{
						style: {
							...this.props.style,
							backgroundColor: AppTheme.colors.base6,
						},
					},
				),
			);
		}
	}

	module.exports = {
		ShimmedSafeImage,
	};
});
