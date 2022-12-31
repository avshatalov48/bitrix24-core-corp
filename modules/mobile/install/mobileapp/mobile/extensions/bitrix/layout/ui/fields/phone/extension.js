/**
 * @module layout/ui/fields/phone
 */
jn.define('layout/ui/fields/phone', (require, exports, module) => {

	const { StringFieldClass } = require('layout/ui/fields/string');
	const { phoneUtils } = require('native/phonenumber');
	const { getCountryCode } = require('utils/phone');
	const { stringify } = require('utils/string');

	const DEFAULT = 'default';

	/**
	 * @class PhoneField
	 */
	class PhoneField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);

			this.prevCountryCode = null;
			this.countryCode = getCountryCode(props.value);
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			this.prevCountryCode = this.countryCode;
			this.countryCode = getCountryCode(newProps.value);
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			if (this.prevCountryCode !== this.countryCode)
			{
				return true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		handleOnImageClick()
		{
			if (this.isReadOnly())
			{
				return null;
			}

			dialogs.showCountryPicker()
				.then(({ phoneCode }) => {
					const newPhoneNumber = this.changePhoneNumberCode(phoneCode);
					this.handleChange(newPhoneNumber);
				})
				.catch(console.error);
		}

		changePhoneNumberCode(phoneCode)
		{
			const phoneNumber = stringify(this.getValue());
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
			return PhoneNumberField(this.getFieldInputProps());
		}

		renderLeftIcons()
		{
			const { image } = this.state;
			this.styles = this.getStyles();
			const imageUri = image || this.getImage() || this.getDefaultImage();

			return Image({
				style: this.styles.leftIcon,
				uri: imageUri,
				onClick: this.handleOnImageClick.bind(this),
				resizeMode: 'contain',
				onFailure: () => {
					this.setState({
						image: this.getDefaultImage(),
					});
				},
			});
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			const phoneNumber = phoneUtils.getFormattedNumber(this.getValue(), this.countryCode) || this.getValue();

			return Text({
				...this.getReadOnlyRenderParams(),
				text: phoneNumber,
			});
		}

		getFieldInputProps()
		{
			const fieldProps = super.getFieldInputProps();

			return {
				...fieldProps,
				countryCode: this.countryCode,
				enable: !this.isReadOnly(),
				style: this.styles.phoneField,
				keyboardType: 'phone-pad',
			};
		}

		getImage()
		{
			const flagImage = this.countryCode && sharedBundle.getImage(`flags/${this.countryCode}.png`);

			return flagImage || this.getDefaultImage();
		}

		getDefaultImage()
		{
			return `${Fields.Base.getExtensionPath()}/phone/images/${DEFAULT}.png`;
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
				leftIcon: {
					width: 22,
					height: 18,
					marginRight: 10,
					alignSelf: 'center',
					alignItems: 'center',
				},
			};
		}
	}

	module.exports = {
		PhoneType: 'phone',
		PhoneField: (props) => new PhoneField(props),
	};

});
