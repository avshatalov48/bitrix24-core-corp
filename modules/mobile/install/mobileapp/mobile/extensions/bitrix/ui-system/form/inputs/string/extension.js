/**
 * @module ui-system/form/inputs/string
 */

jn.define('ui-system/form/inputs/string', (require, exports, module) => {
	const { refSubstitution } = require('utils/function');
	const { TextField } = require('ui-system/typography/text-field');
	const { Input, InputClass, InputSize, InputMode, InputDesign, Icon } = require('ui-system/form/inputs/input');

	/**
	 * @typedef {InputProps} StringInputProps
	 * @property {boolean} [isPassword]
	 *
	 * @function StringInput
	 * @param {StringInputProps} props
	 */
	class StringInput extends LayoutComponent
	{
		render()
		{
			const { isPassword, ...restProps } = this.props;

			return Input({
				...restProps,
				element: this.renderElement,
			});
		}

		renderElement = (fieldProps) => {
			const { isPassword } = this.props;

			return TextField({
				...fieldProps,
				isPassword,
				forcedValue: fieldProps.value,
			});
		};
	}

	StringInput.defaultProps = {
		...InputClass.defaultProps,
		isPassword: false,
	};

	StringInput.propTypes = {
		...InputClass.propTypes,
		isPassword: PropTypes.bool,
	};

	module.exports = {
		/**
		 * @param {StringInputProps} props
		 */
		StringInput: (props) => refSubstitution(StringInput)(props),
		StringInputClass: StringInput,
		InputSize,
		InputMode,
		InputDesign,
		Icon,
	};
});
