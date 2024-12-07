/**
 * @module ui-system/blocks/chips/chip-button/src/design-enum
 */
jn.define('ui-system/blocks/chips/chip-button/src/design-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Color } = require('tokens');
	const { ChipButtonMode } = require('ui-system/blocks/chips/chip-button/src/mode-enum');

	/**
	 * @class ChipButtonDesign
	 * @template TChipButtonDesign
	 * @extends {BaseEnum<ChipButtonDesign>}
	 */
	class ChipButtonDesign extends BaseEnum
	{
		static PRIMARY = new ChipButtonDesign('PRIMARY', {
			[ChipButtonMode.SOLID]: {
				backgroundColor: Color.accentMainPrimary.withPressed(),
				color: Color.baseWhiteFixed,
			},
			[ChipButtonMode.OUTLINE]: {
				borderWidth: 1,
				borderColor: Color.accentSoftBorderBlue.toHex(),
				color: Color.accentMainPrimary,
			},
		});

		static SUCCESS = new ChipButtonDesign('SUCCESS', {
			[ChipButtonMode.SOLID]: {
				backgroundColor: Color.accentMainSuccess.withPressed(),
				color: Color.baseWhiteFixed,
			},
			[ChipButtonMode.OUTLINE]: {
				borderWidth: 1,
				borderColor: Color.accentSoftBorderGreen.toHex(),
				color: Color.accentMainSuccess,
			},
		});

		static ALERT = new ChipButtonDesign('ALERT', {
			[ChipButtonMode.SOLID]: {
				backgroundColor: Color.accentMainAlert.withPressed(),
				color: Color.baseWhiteFixed,
			},
			[ChipButtonMode.OUTLINE]: {
				borderWidth: 1,
				borderColor: Color.accentSoftBorderRed.toHex(),
				color: Color.accentMainAlert,
			},
		});

		static BLACK = new ChipButtonDesign('BLACK', {
			[ChipButtonMode.SOLID]: {
				backgroundColor: Color.base2.withPressed(),
				color: Color.baseWhiteFixed,
			},
			[ChipButtonMode.OUTLINE]: {
				borderWidth: 1,
				borderColor: Color.base5.toHex(),
				color: Color.base1,
			},
		});

		static GREY = new ChipButtonDesign('GREY', {
			[ChipButtonMode.SOLID]: {
				backgroundColor: Color.base4.withPressed(),
				color: Color.baseWhiteFixed,
			},
			[ChipButtonMode.OUTLINE]: {
				borderWidth: 1,
				borderColor: Color.base5.toHex(),
				color: Color.base3,
			},
		});

		static #DISABLED = new ChipButtonDesign('GREY', {
			[ChipButtonMode.SOLID]: {
				backgroundColor: Color.base7.withPressed(),
				color: Color.baseWhiteFixed,
			},
			[ChipButtonMode.OUTLINE]: {
				borderWidth: 1,
				borderColor: Color.base6.toHex(),
				color: Color.base6,
			},
		});

		getDisabled()
		{
			return ChipButtonDesign.#DISABLED;
		}

		getStyle(mode)
		{
			const chipMode = ChipButtonMode.resolve(mode, ChipButtonMode.SOLID);

			return this.getValue()[chipMode.getValue()];
		}
	}

	module.exports = { ChipButtonDesign };
});
