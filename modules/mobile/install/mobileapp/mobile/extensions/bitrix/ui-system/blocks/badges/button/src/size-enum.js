/**
 * @module ui-system/blocks/badges/button/src/size-enum
 */
jn.define('ui-system/blocks/badges/button/src/size-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class BadgeButtonSize
	 */
	class BadgeButtonSize extends BaseEnum
	{
		static S = new BadgeButtonSize('S', 18);
		static M = new BadgeButtonSize('M', 20);
		static L = new BadgeButtonSize('L', 22);

		/**
		 *
		 * @returns {number}
		 */
		getIconSize()
		{
			return this.toNumber() - 2;
		}

		/**
		 *
		 * @returns {number}
		 */
		getBackgroundSize()
		{
			return this.toNumber();
		}
	}

	module.exports = { BadgeButtonSize };
});
