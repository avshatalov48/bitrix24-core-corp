/**
 * @module ui-system/form/inputs/textarea
 */
jn.define('ui-system/form/inputs/textarea', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { CharacterCounter } = require('ui-system/form/inputs/textarea/src/character-counter');
	const { InputClass, InputSize, InputMode, InputDesign, InputVisualDecorator } = require(
		'ui-system/form/inputs/input',
	);

	/**
	 * @typedef {InputProps} TextAreaInputProps
	 * @property {boolean} [showCharacterCount=true]
	 * @property {number} [height=60]
	 *
	 * @class TextAreaInput
	 */
	class TextAreaInput extends InputClass
	{
		getContainerHeight()
		{
			const { height } = this.props;

			return Number(height);
		}

		getFieldStyle()
		{
			const { paddingVertical } = this.getSize().getInput();

			return {
				height: '100%',
				textAlign: this.getAlign(true),
				paddingTop: 12,
				paddingBottom: paddingVertical.toNumber(),
			};
		}

		getInputStyle()
		{
			const paddingBottom = Indent.XS.toNumber() + Indent.XL2.toNumber();
			const characterCountHeight = this.isShowCharacterCount() ? paddingBottom : 0;

			return {
				...super.getInputStyle(),
				paddingBottom: characterCountHeight,
			};
		}

		getSize()
		{
			return InputSize.M;
		}

		isMultiline()
		{
			return true;
		}

		isScrollEnabled()
		{
			return true;
		}

		isShowCharacterCount()
		{
			const { showCharacterCount } = this.props;

			return showCharacterCount;
		}
	}

	TextAreaInput.defaultProps = {
		...InputClass.defaultProps,
		height: 94,
		showCharacterCount: true,
		enableLineBreak: true,

	};

	TextAreaInput.propTypes = {
		...InputClass.propTypes,
		showCharacterCount: PropTypes.bool,
		height: PropTypes.number,
	};

	module.exports = {
		/**
		 * @param {TextAreaInputProps} props
		 * @returns {CharacterCounter}
		 */
		TextAreaInput: (props) => {
			const { showCharacterCount = true } = props;
			const decorator = showCharacterCount ? CharacterCounter : InputVisualDecorator;

			return decorator({ component: TextAreaInput, ...props });
		},
		InputSize,
		InputMode,
		InputDesign,
	};
});
