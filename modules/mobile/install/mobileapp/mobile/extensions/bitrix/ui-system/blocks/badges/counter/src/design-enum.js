/**
 * @module ui-system/blocks/badges/counter/src/design-enum
 */
jn.define('ui-system/blocks/badges/counter/src/design-enum', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class BadgeCounterDesignType
	 * @template TBadgeModeType
	 * @extends {BaseEnum<BadgeCounterDesignType>}
	 */
	class BadgeCounterDesign extends BaseEnum
	{
		static PRIMARY = new BadgeCounterDesign('PRIMARY', {
			color: Color.baseWhiteFixed,
			backgroundColor: Color.accentMainPrimary,
		});

		static ALERT = new BadgeCounterDesign('ALERT', {
			color: Color.baseWhiteFixed,
			backgroundColor: Color.accentMainAlert,
		});

		static WHITE_ALERT = new BadgeCounterDesign('WHITE_ALERT', {
			color: Color.accentMainAlert,
			backgroundColor: Color.baseWhiteFixed,
		});

		static SUCCESS = new BadgeCounterDesign('SUCCESS', {
			color: Color.baseWhiteFixed,
			backgroundColor: Color.accentMainSuccess,
		});

		// todo use proper tokens
		static COLLAB_SUCCESS = new BadgeCounterDesign('COLLAB_SUCCESS', {
			color: Color.baseWhiteFixed,
			backgroundColor: new Color('collabSuccess', '#19CC45'),
		});

		static GREY = new BadgeCounterDesign('GREY', {
			color: Color.baseWhiteFixed,
			backgroundColor: Color.base5,
		});

		static LIGHT_GREY = new BadgeCounterDesign('LIGHT_GREY', {
			color: Color.base3,
			backgroundColor: Color.base7,
		});

		static LIGHT_GREY_ACCENT = new BadgeCounterDesign('LIGHT_GREY_ACCENT', {
			color: Color.base4,
			backgroundColor: Color.base7,
		});

		static LIGHT_GREY_NAVIGATION = new BadgeCounterDesign('LIGHT_GREY_NAVIGATION', {
			color: Color.base5,
			backgroundColor: Color.base7,
		});

		static WHITE = new BadgeCounterDesign('WHITE', {
			color: Color.base3,
			backgroundColor: Color.baseWhiteFixed,
		});

		getColor()
		{
			return this.getValue().color;
		}

		getBackgroundColor()
		{
			return this.getValue().backgroundColor;
		}
	}

	module.exports = { BadgeCounterDesign };
});
