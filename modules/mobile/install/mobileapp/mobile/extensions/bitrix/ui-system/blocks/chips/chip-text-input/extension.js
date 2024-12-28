/**
 * @module ui-system/blocks/chips/chip-text-input
 */

jn.define('ui-system/blocks/chips/chip-text-input', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { ChipButtonClass: ChipButton, ChipButtonSize } = require('ui-system/blocks/chips/chip-button');
	const { ChipTextInputStatusDesign } = require('ui-system/blocks/chips/chip-text-input/src/design-enum');

	/**
	 * @typedef {Object} ChipTextInputProps
	 * @property {string} testId
	 * @property {string} [text]
	 * @property {boolean} [dropdown]
	 * @property {ChipTextInputStatusDesign} [design=ChipTextInputStatusDesign.DEFAULT]
	 * @property {Function} [forwardRef]
	 *
	 * @class ChipTextInput
	 */
	class ChipTextInput extends ChipButton
	{
		initStyle(props)
		{
			this.design = this.getDesign(props);
			this.size = ChipButtonSize.SMALL;
		}

		getTypography()
		{
			return Text4;
		}

		getDesign(props)
		{
			const { design } = props;

			return ChipTextInputStatusDesign.resolve(design, ChipTextInputStatusDesign.DEFAULT).getStyle();
		}

		getIconColor()
		{
			return Color.base4;
		}

		isRounded()
		{
			return false;
		}
	}

	ChipTextInput.defaultProps = {
		dropdown: true,
	};

	ChipTextInput.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string,
		dropdown: PropTypes.bool,
		forwardRef: PropTypes.func,
		design: PropTypes.instanceOf(ChipTextInputStatusDesign),
	};

	module.exports = {
		/**
		 * @param {ChipTextInputProps} props
		 * @returns {ChipTextInput}
		 */
		ChipTextInput: (props) => new ChipTextInput(props),
		ChipTextInputStatusDesign,
	};
});
