/**
 * @module ui-system/blocks/chips/chip-status/src/design-enum
 */
jn.define('ui-system/blocks/chips/chip-status/src/design-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Color } = require('tokens');
	const { ChipStatusMode } = require('ui-system/blocks/chips/chip-status/src/mode-enum');

	/**
	 * @class ChipStatusDesign
	 * @template TChipStatusDesign
	 * @extends {BaseEnum<ChipStatusDesign>}
	 */
	class ChipStatusDesign extends BaseEnum
	{
		static PRIMARY = new ChipStatusDesign('PRIMARY', {
			[ChipStatusMode.SOLID]: {
				backgroundColor: Color.accentMainPrimary.toHex(),
				color: Color.baseWhiteFixed,
			},
			[ChipStatusMode.TINTED]: {
				color: Color.accentMainPrimary,
				backgroundColor: Color.accentSoftBlue2.toHex(),
			},
			[ChipStatusMode.OUTLINE]: {
				borderWidth: 1,
				color: Color.accentMainPrimary,
				borderColor: Color.accentSoftBlue1.toHex(),
			},
			[ChipStatusMode.WHITE_BG]: {
				color: Color.accentMainPrimary,
			},
		});

		static SUCCESS = new ChipStatusDesign('SUCCESS', {
			[ChipStatusMode.SOLID]: {
				backgroundColor: Color.accentMainSuccess.toHex(),
				color: Color.baseWhiteFixed,
			},
			[ChipStatusMode.TINTED]: {
				color: Color.accentExtraGrass,
				backgroundColor: Color.accentSoftGreen2.toHex(),
			},
			[ChipStatusMode.OUTLINE]: {
				borderWidth: 1,
				color: Color.accentMainSuccess,
				borderColor: Color.accentSoftGreen1.toHex(),
			},
			[ChipStatusMode.WHITE_BG]: {
				color: Color.accentMainSuccess,
			},
		});

		static WARNING = new ChipStatusDesign('WARNING', {
			[ChipStatusMode.SOLID]: {
				backgroundColor: Color.accentMainWarning.toHex(),
				color: Color.baseWhiteFixed,
			},
			[ChipStatusMode.TINTED]: {
				color: Color.accentSoftElementOrange,
				backgroundColor: Color.accentSoftOrange2.toHex(),
			},
			[ChipStatusMode.OUTLINE]: {
				borderWidth: 1,
				color: Color.accentMainWarning,
				borderColor: Color.accentSoftOrange1.toHex(),
			},
			[ChipStatusMode.WHITE_BG]: {
				color: Color.accentMainWarning,
			},
		});

		static ALERT = new ChipStatusDesign('ALERT', {
			[ChipStatusMode.SOLID]: {
				backgroundColor: Color.accentMainAlert.toHex(),
				color: Color.baseWhiteFixed,
			},
			[ChipStatusMode.TINTED]: {
				color: Color.accentMainAlert,
				backgroundColor: Color.accentSoftRed2.toHex(),
			},
			[ChipStatusMode.OUTLINE]: {
				borderWidth: 1,
				color: Color.accentMainAlert,
				borderColor: Color.accentSoftRed1.toHex(),
			},
			[ChipStatusMode.WHITE_BG]: {
				color: Color.accentMainAlert,
			},
		});

		static NEUTRAL = new ChipStatusDesign('NEUTRAL', {
			[ChipStatusMode.SOLID]: {
				backgroundColor: Color.base4.toHex(),
				color: Color.baseWhiteFixed,
			},
			[ChipStatusMode.TINTED]: {
				backgroundColor: Color.base7.toHex(),
				color: Color.base3,
			},
			[ChipStatusMode.OUTLINE]: {
				borderWidth: 1,
				color: Color.base3,
				borderColor: Color.bgSeparatorPrimary.toHex(),
			},
			[ChipStatusMode.WHITE_BG]: {
				color: Color.base3,
			},
		});

		getStyle(mode)
		{
			return this.getValue()[mode];
		}
	}

	module.exports = { ChipStatusDesign };
});
