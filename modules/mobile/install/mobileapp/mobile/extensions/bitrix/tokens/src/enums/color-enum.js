/**
 * @module tokens/src/enums/color-enum
 */
jn.define('tokens/src/enums/color-enum', (require, exports, module) => {
	const { Type } = require('type');
	const { AppTheme } = require('apptheme/extended');
	const { withPressed, transparent } = require('utils/color');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class ColorEnum
	 * @extends {BaseEnum<ColorEnum>}
	 */
	class ColorEnum extends BaseEnum
	{
		withPressed()
		{
			return withPressed(this.toHex());
		}

		/**
		 * @param {number=} opacity
		 * @return {string}
		 */
		toHex(opacity)
		{
			const opacityValue = !Type.isNil(opacity) && Number(opacity);
			const hexColor = this.toString();

			if (opacityValue && Type.isNumber(opacityValue) && opacityValue < 1)
			{
				return transparent(hexColor, opacityValue);
			}

			return hexColor;
		}

		/**
		 * @param {'light' | 'dark'} themeId
		 * @return {string}
		 */
		toHexByThemeId = (themeId = 'light') => AppTheme.getColorByThemeId(themeId)[this.getName()];
	}

	module.exports = { ColorEnum };
});
