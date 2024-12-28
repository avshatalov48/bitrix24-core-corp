/**
 * @module utils/page-manager
 */
jn.define('utils/page-manager', (require, exports, module) => {

	const SAFE_AREA_DEFAULT_HEIGHT = 18;

	/**
	 * @param {number} height
	 * @returns {number}
	 */
	function getMediumHeight({ height = 0 })
	{
		if (Application.getPlatform() === 'ios')
		{
			return height;
		}

		let bottomPadding = SAFE_AREA_DEFAULT_HEIGHT;

		if (device.isGestureNavigation)
		{
			bottomPadding += device.screen.safeArea.bottom;
		}

		return height + bottomPadding;
	}

	module.exports = {
		getMediumHeight,
	};
});
