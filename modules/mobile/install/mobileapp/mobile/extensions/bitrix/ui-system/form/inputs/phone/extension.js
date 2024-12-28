/**
 * @module ui-system/form/inputs/phone
 */
jn.define('ui-system/form/inputs/phone', (require, exports, module) => {
	const {
		getCountryCode,
		showCountryPicker,
		getGlobalCountryCode,
		getFlagImageByCountryCode,
	} = require('utils/phone');
	const { Corner } = require('tokens');
	const { phoneUtils } = require('native/phonenumber');
	const { refSubstitution } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');
	const { PropTypes } = require('utils/validation');
	const { PhoneNumberField } = require('ui-system/typography/phone-field');
	const { Input, InputClass, InputSize, InputMode, InputDesign, Icon } = require('ui-system/form/inputs/input');

	/**
	 * @typedef {InputProps} PhoneInputProps
	 * @property {boolean} [showDefaultCountryPhoneCode]
	 * @property {boolean} [showCountryFlag]
	 * @property {string} [countryCode]
	 *
	 * @class EmailInputTheme
	 */
	class PhoneInput extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.currentValue = null;
			this.globalCountryCode = getGlobalCountryCode();

			this.initState(props, true);
		}

		componentWillReceiveProps(nextProps)
		{
			this.initState(nextProps);
		}

		initState(props, initialState)
		{
			this.currentValue = props.value;

			if (initialState)
			{
				this.state = {
					countryCode: props.countryCode,
				};
			}
		}

		getFieldProps()
		{
			return {
				...this.props,
				value: this.getValuePhoneNumber(),
				leftContent: this.getLeftContent(),
				onChange: this.handleOnChangeNumber,
				onClickLeftContent: this.handleOnClickLeftContent,
				keyboardType: 'phone-pad',
				placeholder: null,
			};
		}

		getValuePhoneNumber()
		{
			const phoneNumber = this.getValue();
			const phoneCode = this.getPhoneCode();

			if (this.isShowDefaultCountryPhoneCode() && !this.currentValue && phoneCode)
			{
				return `+${phoneCode}`;
			}

			return phoneNumber;
		}

		handleOnClickLeftContent = async () => {
			const { phoneNumber } = await showCountryPicker({ phoneNumber: this.getValue() });

			this.handleOnChangeNumber(phoneNumber);
		};

		handleOnChangeNumber = (value) => {
			this.currentValue = value;

			if (!this.isShowCountryFlag())
			{
				this.handleOnChange(value);

				return;
			}

			const { countryCode: prevCountryCode } = this.state;
			const hasValue = value && value !== '+';
			const countryCode = hasValue ? this.getCountryCode() : this.globalCountryCode;

			if (countryCode === prevCountryCode)
			{
				this.handleOnChange(value);
			}
			else
			{
				this.setState(
					{ countryCode },
					() => {
						this.handleOnChange(value);
					},
				);
			}
		};

		getLeftContent()
		{
			if (!this.isShowCountryFlag())
			{
				return null;
			}

			const countryCode = this.getCountryCode();
			const uri = getFlagImageByCountryCode(countryCode);

			if (countryCode === this.globalCountryCode || !uri)
			{
				return Icon.EARTH;
			}

			return Image({
				uri,
				resizeMode: 'contain',
				named: Icon.EARTH.getIconName(),
				style: {
					width: 22,
					height: 18,
					borderRadius: Corner.XS.toNumber(),
				},
				onFailure: this.handleOnFailure,
			});
		}

		handleOnFailure = () => {
			this.setState({
				countryCode: this.globalCountryCode,
			});
		};

		getPhoneCode()
		{
			return phoneUtils.getPhoneCode(this.getCountryCode());
		}

		getCurrentCountryCode()
		{
			const { countryCode } = this.state;

			return countryCode;
		}

		getCountryCode()
		{
			return getCountryCode(this.getValue(), this.getCurrentCountryCode());
		}

		getValue()
		{
			if (this.currentValue !== null)
			{
				return this.currentValue;
			}

			const { value } = this.state;

			return value;
		}

		isShowDefaultCountryPhoneCode()
		{
			const { showDefaultCountryPhoneCode } = this.props;

			return Boolean(showDefaultCountryPhoneCode);
		}

		isShowCountryFlag()
		{
			const { showCountryFlag } = this.props;

			return Boolean(showCountryFlag);
		}

		render()
		{
			return Input({
				element: PhoneNumberField,
				...this.getFieldProps(),
			});
		}

		handleOnChange(value)
		{
			const { onChange } = this.props;

			onChange?.(value);
		}
	}

	PhoneInput.defaultProps = {
		showCountryFlag: true,
		showDefaultCountryPhoneCode: true,
		...InputClass.defaultProps,
	};

	PhoneInput.propTypes = {
		...InputClass.propTypes,
		showDefaultCountryPhoneCode: PropTypes.bool,
		showCountryFlag: PropTypes.bool,
		countryCode: PropTypes.string,
	};

	module.exports = {
		/**
		 * @param {PhoneInputProps} props
		 * @returns {PhoneInput}
		 */
		PhoneInput: (props) => refSubstitution(PhoneInput)(props),
		InputSize,
		InputMode,
		InputDesign,
		Icon,
	};
});
