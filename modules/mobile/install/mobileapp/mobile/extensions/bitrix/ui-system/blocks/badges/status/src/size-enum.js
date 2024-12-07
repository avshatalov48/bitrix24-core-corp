/**
 * @module ui-system/blocks/badges/status/src/size-enum
 */
jn.define('ui-system/blocks/badges/status/src/size-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class BadgeStatusSize
	 * @template TBadgeStatusSize
	 * @extends {BaseEnum<BadgeStatusSize>}
	 */
	class BadgeStatusSize extends BaseEnum
	{
		static NORMAL = new BadgeStatusSize('NORMAL', 16);

		static SMALL = new BadgeStatusSize('SMALL', 13);

		/**
		 * @param {boolean} outline
		 * @return {number}
		 */
		getIconSize(outline)
		{
			return outline ? this.toNumber() - 2 : this.toNumber();
		}

		/**
		 * @return {number}
		 */
		getBackgroundSize()
		{
			return this.toNumber();
		}
	}

	module.exports = { BadgeStatusSize };
});
