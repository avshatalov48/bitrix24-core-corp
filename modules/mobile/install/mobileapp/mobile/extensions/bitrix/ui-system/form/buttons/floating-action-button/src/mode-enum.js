/**
 * @module ui-system/form/buttons/floating-action-button/src/mode-enum
 */
jn.define('ui-system/form/buttons/floating-action-button/src/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Color } = require('tokens');

	/**
	 * @class FloatingActionButtonMode
	 * @template TFloatingActionButtonMode
	 * @extends {BaseEnum<FloatingActionButtonMode>}
	 */
	class FloatingActionButtonMode extends BaseEnum
	{
		static BASE = new FloatingActionButtonMode('BASE', {
			color: {
				background: Color.base1,
				opacity: 0.32,
			},
			iconColor: {
				basic: Color.baseWhiteFixed,
				pressed: Color.base3,
			},
		});

		static ACCENT = new FloatingActionButtonMode('EMPTY', {
			color: {
				background: Color.accentMainPrimary,
			},
			iconColor: {
				basic: Color.baseWhiteFixed,
				pressed: Color.base3,
			},
		});

		getColor()
		{
			return this.getValue().color;
		}

		/**
		 * @returns {string}
		 */
		getButtonColor()
		{
			const color = this.getColor();

			return color.background.toHex(color.opacity);
		}

		/**
		 * @param {boolean} pressed
		 * @returns {string}
		 */
		getIconColor(pressed = false)
		{
			const colors = this.getValue()?.iconColor;

			return pressed ? colors.pressed : colors.basic;
		}
	}

	module.exports = { FloatingActionButtonMode };
});
