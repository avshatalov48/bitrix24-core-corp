/**
 * @module ui-system/blocks/switcher/src/mode-enum
 */
jn.define('ui-system/blocks/switcher/src/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Color } = require('tokens');

	/**
	 * @class SwitcherMode
	 * @template TSwitcherMode
	 * @extends {BaseEnum<SwitcherMode>}
	 */
	class SwitcherMode extends BaseEnum
	{
		static SOLID = new SwitcherMode('SOLID', {
			thumb: {
				true: {
					backgroundColor: Color.baseWhiteFixed.toHex(),
				},
				false: {
					backgroundColor: Color.baseWhiteFixed.toHex(),
				},
			},
			track: {
				true: {
					backgroundColor: Color.accentMainPrimaryalt.toHex(),
				},
				false: {
					backgroundColor: Color.base6.toHex(),
				},
			},
		});

		static TINTED = new SwitcherMode('TINTED', {
			thumb: {
				true: {
					backgroundColor: Color.baseWhiteFixed.toHex(),
				},
				false: {
					backgroundColor: Color.baseWhiteFixed.toHex(),
				},
			},
			track: {
				true: {
					backgroundColor: Color.accentSoftBlue1.toHex(),
				},
				false: {
					backgroundColor: Color.base7.toHex(),
				},
			},
		});

		static #DISABLED = new SwitcherMode('DISABLED', {
			thumb: {
				true: {
					backgroundColor: Color.base7.toHex(),
				},
				false: {
					backgroundColor: Color.base7.toHex(),
				},
			},
			track: {
				true: {
					borderWidth: 1,
					borderColor: Color.base7.toHex(),
					backgroundColor: Color.base8.toHex(),
				},
				false: {
					borderWidth: 1,
					borderColor: Color.base7.toHex(),
					backgroundColor: Color.base8.toHex(),
				},
			},
		});

		getDisabled()
		{
			return SwitcherMode.#DISABLED;
		}

		getThumbColor()
		{
			return this.getValue().thumb;
		}

		getTrackStyle()
		{
			return this.getValue().track;
		}
	}

	module.exports = { SwitcherMode };
});
