/**
 * @module layout/ui/fields/phone
 */
jn.define('layout/ui/fields/phone', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { StringFieldClass } = require('layout/ui/fields/string');
	const { phoneUtils } = require('native/phonenumber');
	const {
		getCountryCode,
		showCountryPicker,
		getFlagImageByCountryCode,
		getGlobalCountryCode,
	} = require('utils/phone');
	const { stringify } = require('utils/string');
	const { BaseField } = require('layout/ui/fields/base');
	const { chevronDown } = require('assets/common');
	const { PropTypes } = require('utils/validation');

	const DEFAULT = 'default';

	/**
	 * @class PhoneField
	 */
	class PhoneField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);

			this.globalCountryCode = getGlobalCountryCode();
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
				countryCode = this.globalCountryCode;
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
			const { phoneNumber = '', countryCode = this.globalCountryCode } = value;

			if (countryCode !== this.globalCountryCode)
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

		handleOnImageClick = async () => {
			if (this.isReadOnly())
			{
				return;
			}

			const { phoneNumber } = await showCountryPicker({ phoneNumber: this.getValuePhoneNumber() });

			this.handleOnChangePhone(phoneNumber);
		};

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
			this.styles = this.getStyles();

			const imageUri = this.getFlagImageUri();

			if (!imageUri)
			{
				return null;
			}

			return View(
				{
					style: this.styles.leftIconWrapper,
					onClick: this.handleOnImageClick,
				},
				this.renderFlagImage({
					style: this.styles.flagIcon,
					imageUri,
				}),
				this.renderChevronDown(),
			);
		}

		renderFlagImage({ style = {}, imageUri })
		{
			if (!imageUri)
			{
				return null;
			}

			return Image({
				style,
				uri: imageUri,
				resizeMode: 'contain',
				onFailure: () => {
					this.setState({
						image: this.getDefaultImage(),
					});
				},
			});
		}

		renderChevronDown()
		{
			return !this.isReadOnly() && Image(
				{
					style: {
						height: 5,
						width: 7,
					},
					svg: {
						content: chevronDown(),
					},
				},
			);
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}
			const formattedNumber = phoneUtils.getFormattedNumber(
				this.getValuePhoneNumber(),
				this.getFieldCountryCode(),
			);
			const phoneNumber = formattedNumber || this.getValuePhoneNumber();

			return View(
				{
					style: this.styles.phoneFieldContainer,
					onLongClick: this.getContentLongClickHandler(),
					onClick: this.getContentClickHandler(),
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
				onChangeText: this.handleOnChangePhone,
				enable: !this.isReadOnly(),
				style: this.styles.phoneField,
				keyboardType: 'phone-pad',
			};
		}

		handleOnChangePhone = (phoneNumber) => {
			const { countryCode } = this.getValue();
			this.fieldValue = { phoneNumber, countryCode };

			this.handleChange({
				VALUE: phoneNumber,
				phoneNumber,
				countryCode: phoneNumber ? this.getCountryCodeByPhoneNumber(phoneNumber) : this.globalCountryCode,
			});
		};

		getFlagImageUri()
		{
			const { image } = this.state;
			const countryCode = this.getFieldCountryCode();

			return image || getFlagImageByCountryCode(countryCode) || this.getDefaultImage();
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
					color: this.isReadOnly() ? AppTheme.colors.accentMainLinks : AppTheme.colors.base1,
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

		canCopyValue()
		{
			return this.isReadOnly();
		}

		prepareValueToCopy()
		{
			return this.getValuePhoneNumber();
		}

		copyMessage()
		{
			return Loc.getMessage('FIELDS_PHONE_VALUE_COPIED');
		}
	}

	PhoneField.propTypes = {
		...StringFieldClass.propTypes,
		value: PropTypes.oneOfType([PropTypes.string, PropTypes.shape({
			countryCode: PropTypes.string,
			phoneNumber: PropTypes.string,
		})]),
	};

	module.exports = {
		PhoneType: 'phone',
		PhoneFieldClass: PhoneField,
		PhoneField: (props) => new PhoneField(props),
	};
});
