/**
 * @module ui-system/form/buttons/button/src/design-enum
 */
jn.define('ui-system/form/buttons/button/src/design-enum', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class ButtonDesign
	 * @template TButtonDesign
	 * @extends {BaseEnum<ButtonDesign>}
	 */
	class ButtonDesign extends BaseEnum
	{
		static FILLED = new ButtonDesign('FILLED', {
			color: Color.baseWhiteFixed,
			backgroundColor: Color.accentMainPrimary,
		});

		static TINTED = new ButtonDesign('TINTED', {
			color: Color.accentMainLink,
			backgroundColor: Color.accentSoftBlue2,
		});

		static OUTLINE = new ButtonDesign('OUTLINE', {
			color: Color.base2,
			borderColor: Color.base5,
			borderColorOpacity: 0.5,
		});

		static OUTLINE_ACCENT_1 = new ButtonDesign('OUTLINE_ACCENT_1', {
			color: Color.base1,
			borderColor: Color.accentMainPrimary,
		});

		static OUTLINE_ACCENT_2 = new ButtonDesign('OUTLINE_ACCENT_2', {
			color: Color.accentMainPrimary,
			borderColor: Color.accentMainPrimary,
			borderColorOpacity: 0.5,
		});

		static OUTLINE_NO_ACCENT = new ButtonDesign('OUTLINE_NO_ACCENT', {
			color: Color.base4,
			borderColor: Color.base5,
			borderColorOpacity: 0.5,
		});

		static PLAIN = new ButtonDesign('PLAIN', {
			color: Color.base2,
		});

		static PLAN_ACCENT = new ButtonDesign('PLAN_ACCENT', {
			color: Color.accentMainPrimary,
		});

		static PLAIN_NO_ACCENT = new ButtonDesign('PLAIN_NO_ACCENT', {
			color: Color.base4,
		});

		static #DISABLED = new ButtonDesign('DISABLED', {
			color: Color.base5,
			borderColor: Color.base6,
			backgroundColor: Color.base7,
		});

		getStyle()
		{
			return this.getValue();
		}

		getDisabled()
		{
			return ButtonDesign.#DISABLED;
		}

		/**
		 * @return {number}
		 */
		getOpacity(style)
		{
			return this.getStyle()?.[`${style}Opacity`];
		}
	}

	module.exports = { ButtonDesign };
});
