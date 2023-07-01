/**
 * @module layout/ui/fields/phone
 */
jn.define('layout/ui/fields/phone', (require, exports, module) => {
	const { StringFieldClass } = require('layout/ui/fields/string');
	const { phoneUtils } = require('native/phonenumber');
	const { getCountryCode } = require('utils/phone');
	const { stringify } = require('utils/string');
	const { BaseField } = require('layout/ui/fields/base');
	const { chevronDown } = require('assets/common');

	const DEFAULT = 'default';
	const GLOBAL_COUNTRY_CODE = 'XX';

	/**
	 * @class PhoneField
	 */
	class PhoneField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);

			this.onChangePhone = this.onChangePhone.bind(this);
		}

		shouldShowDefaultIcon()
		{
			return BX.prop.getBoolean(this.props, 'showDefaultIcon', true);
		}

		getFieldCountryCode()
		{
			const { countryCode, phoneNumber } = this.getValue();

			return countryCode || this.getCountryCodeByPhoneNumber(phoneNumber);
		}

		getCountryCodeByPhoneNumber(phoneNumber)
		{
			const { countryCode: userCountryCode } = this.getConfig();
			let countryCode = userCountryCode;

			if (phoneNumber && !phoneUtils.getCountryCode(phoneNumber))
			{
				countryCode = GLOBAL_COUNTRY_CODE;
			}

			return getCountryCode(phoneNumber, countryCode);
		}

		getValuePhoneNumber(value)
		{
			const { phoneNumber } = value || this.getValue();
			const phoneCode = phoneUtils.getPhoneCode(this.getFieldCountryCode());

			return !phoneNumber && phoneCode ? `+${phoneCode}` : stringify(phoneNumber || '');
		}

		prepareSingleValue(value)
		{
			return value;
		}

		isEmptyValue(value)
		{
			const { phoneNumber = '', countryCode = GLOBAL_COUNTRY_CODE } = value;

			if (countryCode !== GLOBAL_COUNTRY_CODE)
			{
				const phoneCode = phoneUtils.getPhoneCode(this.getFieldCountryCode());
				if (phoneNumber === `+${phoneCode}`)
				{
					return true;
				}
			}

			if (phoneNumber === '+')
			{
				return true;
			}

			return super.isEmptyValue(phoneNumber || '');
		}

		handleOnImageClick()
		{
			if (this.isReadOnly())
			{
				return null;
			}

			dialogs.showCountryPicker({ useRecent: true })
				.then(({ phoneCode }) => {
					const newPhoneNumber = this.changePhoneNumberCode(phoneCode);
					this.onChangePhone(newPhoneNumber);
				})
				.catch(console.error);
		}

		changePhoneNumberCode(phoneCode)
		{
			const phoneNumber = this.getValuePhoneNumber();
			if (phoneNumber === '')
			{
				return phoneCode;
			}

			const currentCountryPhoneCode = phoneUtils.getPhoneCode(phoneNumber);

			if (Boolean(currentCountryPhoneCode))
			{
				const countryPhoneCode = `+${currentCountryPhoneCode}`;

				return phoneNumber.startsWith(countryPhoneCode)
					? phoneNumber.replace(countryPhoneCode, phoneCode)
					: `${phoneCode}${phoneNumber}`;
			}

			return `${phoneCode}${phoneNumber}`;
		}

		renderEditableContent()
		{
			return View(
				{
					style: this.styles.phoneFieldContainer,
				},
				this.renderFlag(),
				PhoneNumberField(this.getFieldInputProps()),
			);
		}

		renderFlag()
		{
			const { image } = this.state;
			this.styles = this.getStyles();
			const imageUri = image || this.getImage() || this.getDefaultImage();

			if (!imageUri)
			{
				return null;
			}

			return View(
				{
					style: this.styles.leftIconWrapper,
					onClick: this.handleOnImageClick.bind(this),
				},
				Image({
					style: this.styles.flagIcon,
					uri: imageUri,
					resizeMode: 'contain',
					onFailure: () => {
						this.setState({
							image: this.getDefaultImage(),
						});
					},
				}),
				!this.isReadOnly() && Image(
					{
						style: {
							height: 5,
							width: 7,
						},
						svg: {
							content: chevronDown(),
						},
					},
				),
			);
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}
			const formattedNumber = phoneUtils.getFormattedNumber(this.getValuePhoneNumber(), this.getFieldCountryCode());
			const phoneNumber = formattedNumber || this.getValuePhoneNumber();

			return View(
				{
					style: this.styles.phoneFieldContainer,
				},
				this.renderFlag(),
				Text({
					...this.getReadOnlyRenderParams(),
					text: phoneNumber,
				}),
			);
		}

		getFieldInputProps()
		{
			return {
				...super.getFieldInputProps(),
				value: this.getValuePhoneNumber(),
				onChangeText: this.onChangePhone,
				enable: !this.isReadOnly(),
				style: this.styles.phoneField,
				keyboardType: 'phone-pad',
			};
		}

		onChangePhone(phoneNumber)
		{
			const { countryCode } = this.getValue();
			this.fieldValue = { phoneNumber, countryCode };

			this.handleChange({
				VALUE: phoneNumber,
				phoneNumber,
				countryCode: phoneNumber ? this.getCountryCodeByPhoneNumber(phoneNumber) : GLOBAL_COUNTRY_CODE,
			});
		}

		getImage()
		{
			const countryCode = this.getFieldCountryCode();
			const flagImage = countryCode && sharedBundle.getImage(`flags/${countryCode}.png`);

			return flagImage || this.getDefaultImage();
		}

		getDefaultImage()
		{
			if (!this.shouldShowDefaultIcon())
			{
				return null;
			}

			return `${BaseField.getExtensionPath()}/phone/images/${DEFAULT}.png`;
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				phoneField: {
					flex: 1,
					marginRight: 10,
					color: !this.isReadOnly() ? '#333333' : '#0b66c3',
					fontSize: 16,
					alignSelf: 'center',
				},
				leftIconWrapper: {
					marginRight: 5,
					flexDirection: 'row',
					alignItems: 'center',
				},
				flagIcon: {
					width: 22,
					height: 18,
					marginRight: 4,
				},
				phoneFieldContainer: {
					flexDirection: 'row',
					flex: 1,
				},
			};
		}
	}

	module.exports = {
		PhoneType: 'phone',
		PhoneField: (props) => new PhoneField(props),
	};

});
