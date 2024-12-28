/**
 * @module ui-system/blocks/chips/chip-button/src/mode-enum
 */
jn.define('ui-system/blocks/chips/chip-button/src/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class ChipButtonMode
	 * @template TChipButtonMode
	 * @extends {BaseEnum<ChipButtonMode>}
	 */
	class ChipButtonMode extends BaseEnum
	{
		static SOLID = new ChipButtonMode('SOLID', 'solid');

		static OUTLINE = new ChipButtonMode('OUTLINE', 'outline');
	}

	module.exports = { ChipButtonMode };
});
