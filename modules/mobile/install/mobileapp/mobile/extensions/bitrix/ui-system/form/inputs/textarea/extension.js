/**
 * @module ui-system/form/inputs/textarea
 */
jn.define('ui-system/form/inputs/textarea', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { CharacterCounter } = require('ui-system/form/inputs/textarea/src/character-counter');
	const { InputClass, InputSize, InputMode, InputDesign, InputVisualDecorator, Icon } = require(
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
			return {
				...super.getFieldStyle(),
				height: '100%',
			};
		}

		getWrapperContentStyle()
		{
			return {
				...super.getWrapperContentStyle(),
				alignItems: 'flex-start',
			};
		}

		getInputStyle()
		{
			const minPaddingBottom = Indent.XL.toNumber();
			const paddingBottom = this.isShowCharacterCount()
				? minPaddingBottom + Indent.XL2.toNumber()
				: minPaddingBottom;

			return {
				...super.getInputStyle(),
				paddingTop: Indent.L.toNumber(),
				alignItems: 'flex-start',
				paddingBottom,
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
		height: 96,
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
		Icon,
	};
});
