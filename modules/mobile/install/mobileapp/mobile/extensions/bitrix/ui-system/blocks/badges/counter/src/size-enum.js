/**
 * @module ui-system/blocks/badges/counter/src/size-enum
 */
jn.define('ui-system/blocks/badges/counter/src/size-enum', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');
	const { Text5, Text6, Text7 } = require('ui-system/typography/text');

	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class BadgeCounterSize
	 * @template TBadgeCounterSize
	 * @extends {BaseEnum<BadgeCounterSize>}
	 */

	class BadgeCounterSize extends BaseEnum
	{
		static DOT = new BadgeCounterSize('DOT', {
			height: 5,
		});

		static XS = new BadgeCounterSize('XS', {
			height: isAndroid ? 15 : 14,
			typography: Text7,
			paddingHorizontal: Indent.XS,
		});

		static S = new BadgeCounterSize('S', {
			height: isAndroid ? 17 : 16,
			typography: Text6,
			paddingHorizontal: Indent.XS,
		});

		static M = new BadgeCounterSize('M', {
			height: 18,
			typography: Text5,
			paddingHorizontal: Indent.S,
		});

		/**
		 * @returns {number}
		 */
		getHeight()
		{
			return this.getValue().height;
		}

		/**
		 * @returns {Indent}
		 */
		getPaddingHorizontal()
		{
			return this.getValue().paddingHorizontal;
		}

		/**
		 * @returns {TextBase}
		 */
		getTypography()
		{
			return this.getValue().typography;
		}

		isDot()
		{
			return this.equal(BadgeCounterSize.DOT);
		}
	}

	module.exports = {
		BadgeCounterSize,
	};
});
