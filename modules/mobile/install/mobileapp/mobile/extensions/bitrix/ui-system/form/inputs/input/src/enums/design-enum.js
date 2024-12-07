/**
 * @module ui-system/form/inputs/input/src/enums/design-enum
 */
jn.define('ui-system/form/inputs/input/src/enums/design-enum', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class InputDesign
	 * @template TInputDesign
	 * @extends {BaseEnum<InputDesign>}
	 */
	class InputDesign extends BaseEnum
	{
		static PRIMARY = new InputDesign('PRIMARY', {
			borderColor: Color.accentMainPrimary,
			borderColorFocused: Color.accentMainPrimary,
		});

		static GREY = new InputDesign('GREY', {
			borderColor: Color.base5,
			borderColorFocused: Color.accentMainPrimary,
		});

		static LIGHT_GREY = new InputDesign('LIGHT_GREY', {
			borderColor: Color.bgSeparatorPrimary,
			borderColorFocused: Color.accentMainPrimary,
		});

		static #DISABLED = new InputDesign('DISABLED', {
			backgroundColor: Color.base7,
			borderColorFocused: Color.base7,
		});

		getDisabled()
		{
			return InputDesign.#DISABLED;
		}

		getStyle()
		{
			return this.getValue();
		}
	}

	module.exports = { InputDesign };
});
