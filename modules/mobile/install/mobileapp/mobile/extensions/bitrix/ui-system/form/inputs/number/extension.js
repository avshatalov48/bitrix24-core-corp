/**
 * @module ui-system/form/inputs/number
 */
jn.define('ui-system/form/inputs/number', (require, exports, module) => {
	const { isNil } = require('utils/type');
	const { refSubstitution } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');
	const { MoneyField } = require('ui-system/typography/money-field');
	const { Input, InputSize, InputMode, InputDesign, Icon } = require('ui-system/form/inputs/input');

	/**
	 * @typedef {Object} NumberInputProps
	 * @property {boolean} [shouldShowToolbar=true]
	 * @property {boolean} [hideZero=true]
	 * @property {'.' | ','} [decimalSeparator='.']
	 * @property {number} [decimalDigits=0]
	 * @property {boolean} [useGroupSeparator=false]
	 * @property {number} [groupSize=0]
	 * @property {string} [groupSeparator=' ']
	 *
	 * @class NumberInput
	 */
	class NumberInput extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.config = null;
		}

		render()
		{
			const { numberConfig, props } = this.getProps(this.props);
			this.config = numberConfig;

			return Input({
				...props,
				element: this.renderElement,
			});
		}

		renderElement = (fieldProps) => {
			return MoneyField({
				...fieldProps,
				...this.config,
				forcedValue: fieldProps.value,
			});
		};

		getProps(initProps)
		{
			const {
				shouldShowToolbar,
				hideZero,
				decimalSeparator,
				decimalDigits,
				useGroupSeparator,
				groupSize,
				groupSeparator,
				...restProps
			} = initProps;

			const cleanNil = (values) => Object.fromEntries(
				Object.entries(values).filter(([_, value]) => !isNil(value)),
			);

			const numberConfig = cleanNil({
				shouldShowToolbar,
				hideZero,
				decimalSeparator,
				decimalDigits,
				useGroupSeparator,
				groupSize,
				groupSeparator,
			});

			return { props: restProps, numberConfig };
		}
	}

	NumberInput.defaultProps = {
		shouldShowToolbar: true,
		hideZero: true,
		useGroupSeparator: false,
		decimalDigits: 0,
	};

	NumberInput.propTypes = {
		value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		shouldShowToolbar: PropTypes.bool,
		hideZero: PropTypes.bool,
		decimalDigits: PropTypes.number,
		decimalSeparator: PropTypes.oneOf(['.', ',']),
		useGroupSeparator: PropTypes.bool,
		groupSize: PropTypes.number,
		groupSeparator: PropTypes.string,
	};

	module.exports = {
		/**
		 * @param {NumberInputProps} props
		 */
		NumberInput: (props) => refSubstitution(NumberInput)(props),
		InputSize,
		InputMode,
		InputDesign,
		Icon,
	};
});
