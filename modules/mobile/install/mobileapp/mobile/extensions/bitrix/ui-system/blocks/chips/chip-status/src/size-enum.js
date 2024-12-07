/**
 * @module ui-system/blocks/chips/chip-status/src/size-enum
 */
jn.define('ui-system/blocks/chips/chip-status/src/size-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Text5, Text6 } = require('ui-system/typography/text');

	/**
	 * @class ChipStatusSize
	 * @template TChipStatusSize
	 * @extends {BaseEnum<ChipStatusSize>}
	 */
	class ChipStatusSize extends BaseEnum
	{
		static NORMAL = new ChipStatusSize('NORMAL', {
			typography: Text5,
			height: 24,
		});

		static SMALL = new ChipStatusSize('SMALL', {
			typography: Text6,
			height: 19,
		});

		getTypography()
		{
			return this.getValue().typography;
		}

		getHeight()
		{
			return this.getValue().height;
		}
	}

	module.exports = { ChipStatusSize };
});
