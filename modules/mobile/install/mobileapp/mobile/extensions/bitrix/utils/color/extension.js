/**
 * @module utils/color
 */
jn.define('utils/color', (require, exports, module) => {
	const DEFAULT_OPACITY = 0.12;

	/**
	 * Converts hex color to argb
	 *
	 * @param {string} hexColor with leading #-sign
	 * @param {number} [opacity] from 0 to 1
	 * @returns {string} argb color with leading #-sign
	 */
	function transparent(hexColor, opacity = 0)
	{
		hexColor = prepareHexColor(hexColor);

		if (opacity < 0 || opacity > 1)
		{
			throw new TypeError('Parameter "opacity" must be in range 0..1');
		}

		hexColor = hexColor.slice(1);
		opacity = Math.round(255 * opacity).toString(16);

		opacity = opacity.length < 2 ? `0${opacity}` : opacity;

		return `#${opacity}${hexColor}`;
	}

	/**
	 * @param {string} hexColor
	 * @return {*}
	 */
	function prepareHexColor(hexColor)
	{
		if (hexColor.length === 4)
		{
			hexColor = hexColor.replaceAll(/#([\dA-Fa-f])([\dA-Fa-f])([\dA-Fa-f])/g, '#$1$1$2$2$3$3');
		}

		if (!hexColor.startsWith('#') || hexColor.length < 7)
		{
			throw new TypeError(
				'Parameter "hexColor" must be fully qualified HEX-representation of color with leading #-sign',
			);
		}

		return hexColor;
	}

	/**
	 * Calculates rgb components from hex color
	 *
	 * @param {string} hexColor with leading #-sign
	 */
	function hexToRgb(hexColor)
	{
		hexColor = prepareHexColor(hexColor);
		hexColor = hexColor.slice(1);

		const r = parseInt(hexColor.slice(0, 2), 16);
		const g = parseInt(hexColor.slice(2, 4), 16);
		const b = parseInt(hexColor.slice(4, 6), 16);

		return [r, g, b];
	}

	/**
	 * Checks if color is light
	 *
	 * @param {string} hexColor with leading #-sign
	 * @returns {boolean}
	 */
	function isLightColor(hexColor)
	{
		const rgb = hexToRgb(hexColor);
		const brightness = Math.round(
			((parseInt(rgb[0]) * 299) + (parseInt(rgb[1]) * 587) + (parseInt(rgb[2]) * 114)) / 1000,
		);

		return brightness >= 145;
	}

	/**
	 * Checks if color is dark
	 *
	 * @param {string} hexColor with leading #-sign
	 * @returns {boolean}
	 */
	function isDarkColor(hexColor)
	{
		return !isLightColor(hexColor);
	}

	/**
	 * Converts to lightened color with provided opacity
	 *
	 * @param {string} hexColor with leading #-sign
	 * @param {number} [opacity] from 0 to 1
	 * @returns {string} hex color with leading #-sign
	 */
	function lighten(hexColor, opacity = DEFAULT_OPACITY)
	{
		const rgb = hexToRgb(hexColor);
		const r = Math.round((255 - rgb[0]) * opacity + rgb[0]).toString(16).padStart(2, '0');
		const g = Math.round((255 - rgb[1]) * opacity + rgb[1]).toString(16).padStart(2, '0');
		const b = Math.round((255 - rgb[2]) * opacity + rgb[2]).toString(16).padStart(2, '0');

		return `#${r}${g}${b}`;
	}

	/**
	 * Converts to darkened color with provided opacity
	 *
	 * @param {string} hexColor with leading #-sign
	 * @param {number} [opacity] from 0 to 1
	 * @returns {string} hex color with leading #-sign
	 */
	function darken(hexColor, opacity = DEFAULT_OPACITY)
	{
		const rgb = hexToRgb(hexColor);
		const r = Math.round(rgb[0] * (1 - opacity)).toString(16).padStart(2, '0');
		const g = Math.round(rgb[1] * (1 - opacity)).toString(16).padStart(2, '0');
		const b = Math.round(rgb[2] * (1 - opacity)).toString(16).padStart(2, '0');

		return `#${r}${g}${b}`;
	}

	/**
	 * Converts color to pressed color
	 *
	 * @param {string} hexColor with leading #-sign
	 * @param {number} [opacity] from 0 to 1
	 * @returns {string} hex color with leading #-sign
	 */
	function getPressedColor(hexColor, opacity = DEFAULT_OPACITY)
	{
		if (isLightColor(hexColor))
		{
			return darken(hexColor, opacity / 2);
		}

		return lighten(hexColor, opacity);
	}

	/**
	 * Returns object with default and pressed colors
	 *
	 * @param {string} hexColor with leading #-sign
	 * @param {number} [opacity] from 0 to 1
	 * @returns {object}
	 */
	function withPressed(hexColor, opacity = DEFAULT_OPACITY)
	{
		return {
			default: hexColor,
			pressed: getPressedColor(hexColor, opacity),
		};
	}

	module.exports = {
		transparent,
		hexToRgb,
		isLightColor,
		isDarkColor,
		lighten,
		darken,
		getPressedColor,
		withPressed,
		prepareHexColor,
	};
});
