/**
 * @module ui-system/blocks/badges/button/src/design-enum
 */
jn.define('ui-system/blocks/badges/button/src/design-enum', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class BadgeButtonDesign
	 */
	class BadgeButtonDesign extends BaseEnum
	{
		static GREY = new BadgeButtonDesign('GREY', {
			color: Color.baseWhiteFixed,
			backgroundColor: Color.base5,
			borderColor: Color.bgContentPrimary,
		});

		static WHITE = new BadgeButtonDesign('WHITE', {
			color: Color.base4,
			backgroundColor: Color.baseWhiteFixed,
			borderColor: Color.bgContentPrimary,
		});

		/**
		 *
		 * @returns {Color}
		 */
		getColor()
		{
			return this.getValue().color;
		}

		/**
		 *
		 * @returns {Color}
		 */
		getBackgroundColor()
		{
			return this.getValue().backgroundColor;
		}

		/**
		 *
		 * @returns {Color}
		 */
		getBorderColor()
		{
			return this.getValue().borderColor;
		}
	}

	module.exports = { BadgeButtonDesign };
});
