/**
 * @module utils/color
 */
jn.define('utils/color', (require, exports, module) => {

	/**
	 * Converts hex color to argb
	 * @param {string} hexColor with leading #-sign
	 * @param {number} opacity from 0 to 1
	 */
	function transparent(hexColor, opacity = 0)
	{
		if (!hexColor.startsWith('#') || hexColor.length < 7)
		{
			throw new Error('Parameter "hexColor" must be fully qualified HEX-representation of color with leading #-sign');
		}
		if (opacity < 0 || opacity > 1)
		{
			throw new Error('Parameter "opacity" must be in range 0..1');
		}

		hexColor = hexColor.slice(1);
		opacity = Math.round(255 * opacity).toString(16);

		opacity = opacity.length < 2 ? `0${opacity}` : opacity;

		return `#${opacity}${hexColor}`;
	}

	module.exports = {
		transparent,
	};

});