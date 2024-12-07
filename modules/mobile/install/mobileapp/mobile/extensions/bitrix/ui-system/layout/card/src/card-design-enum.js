/**
 * @module ui-system/layout/card/src/card-design-enum
 */
jn.define('ui-system/layout/card/src/card-design-enum', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class BadgeCounterDesignType
	 * @template TBadgeModeType
	 * @extends {BaseEnum<BadgeCounterDesignType>}
	 */
	class CardDesign extends BaseEnum
	{
		static PRIMARY = new CardDesign('PRIMARY', {
			backgroundColor: Color.bgContentPrimary,
		});

		static SECONDARY = new CardDesign('SECONDARY', {
			backgroundColor: Color.bgContentSecondary,
		});

		static ACCENT = new CardDesign('ACCENT', {
			backgroundColor: Color.accentSoftBlue2,
		});

		static WARNING = new CardDesign('WARNING', {
			backgroundColor: Color.accentSoftOrange2,
		});

		static ALERT = new CardDesign('ALERT', {
			backgroundColor: Color.accentSoftRed2,
		});

		/**
		 * @return {{backgroundColor: Color}}
		 */
		getStyle()
		{
			const { backgroundColor } = this.getValue();

			return {
				backgroundColor: backgroundColor.toHex(),
			};
		}
	}

	module.exports = { CardDesign };
});
