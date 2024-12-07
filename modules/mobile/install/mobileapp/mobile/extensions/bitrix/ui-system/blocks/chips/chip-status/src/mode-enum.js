/**
 * @module ui-system/blocks/chips/chip-status/src/mode-enum
 */
jn.define('ui-system/blocks/chips/chip-status/src/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class ChipStatusMode
	 * @template TChipStatusMode
	 * @extends {BaseEnum<ChipStatusMode>}
	 */
	class ChipStatusMode extends BaseEnum
	{
		static OUTLINE = new ChipStatusMode('OUTLINE', 'outline');
		static SOLID = new ChipStatusMode('SOLID', 'solid');
		static TINTED = new ChipStatusMode('TINTED', 'tinted');
		static WHITE_BG = new ChipStatusMode('WHITE_BG', 'whiteBg');
	}

	module.exports = { ChipStatusMode };
});
