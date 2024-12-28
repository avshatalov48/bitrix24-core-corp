/**
 * @module ui-system/blocks/chips/chip-text-input/src/design-enum
 */
jn.define('ui-system/blocks/chips/chip-text-input/src/design-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Color } = require('tokens');

	/**
	 * @class ChipTextInputStatusDesign
	 * @template TChipTextInputStatusDesign
	 * @extends {BaseEnum<ChipTextInputStatusDesign>}
	 */
	class ChipTextInputStatusDesign extends BaseEnum
	{
		static DEFAULT = new ChipTextInputStatusDesign('DEFAULT', {
			backgroundColor: Color.accentSoftBlue3.toHex(),
			color: Color.accentMainPrimary,
		});

		static ERROR = new ChipTextInputStatusDesign('ERROR', {
			backgroundColor: Color.accentSoftRed3.toHex(),
			color: Color.accentMainAlert,
		});

		static COLLAB = new ChipTextInputStatusDesign('COLLAB', {
			backgroundColor: Color.collabBgContent1.toHex(),
			color: Color.collabAccentPrimaryAlt,
		});

		static EXTRANET = new ChipTextInputStatusDesign('EXTRANET', {
			backgroundColor: Color.accentSoftOrange3.toHex(),
			color: Color.accentExtraOrange,
		});

		getStyle()
		{
			return this.getValue();
		}
	}

	module.exports = { ChipTextInputStatusDesign };
});
