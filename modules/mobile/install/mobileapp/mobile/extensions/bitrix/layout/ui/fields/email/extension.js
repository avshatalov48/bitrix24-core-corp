/**
 * @module layout/ui/fields/email
 */
jn.define('layout/ui/fields/email', (require, exports, module) => {
	const { Loc } = require('loc');
	const { stringify } = require('utils/string');
	const { getDomainImageUri, getEmailServiceName, isValidEmail } = require('utils/email');
	const { StringFieldClass } = require('layout/ui/fields/string');

	/**
	 * @class EmailField
	 */
	class EmailField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);

			this.service = this.getEmailService(props.value);
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			const { value } = this.props;
			const service = this.getEmailService(nextProps.value);

			if (service !== this.getEmailService(value))
			{
				this.service = service;

				return true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		getEmailService(value)
		{
			return getEmailServiceName(this.prepareValue(value));
		}

		renderLeftIcons()
		{
			const style = this.getStyles();

			return Image({
				style: style.leftIcon,
				uri: getDomainImageUri({ service: this.service }),
				resizeMode: 'contain',
			});
		}

		getValidationErrorOnFocusOut()
		{
			let error = super.getValidationErrorOnFocusOut();
			if (!error)
			{
				const value = stringify(this.getValue());
				if (value !== '' && !isValidEmail(value))
				{
					error = EmailField.getValidationErrorMessage();
				}
			}

			return error;
		}

		static getValidationErrorMessage()
		{
			return Loc.getMessage('FIELD_ERROR_EMAIL');
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				keyboardType: 'email-address',
				autoCapitalize: 'none',
			};
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();

			return {
				...styles,
				leftIcon: {
					width: 22,
					height: 18,
					marginRight: 10,
					alignSelf: 'center',
					alignItems: 'center',
				},
			};
		}

		canCopyValue()
		{
			return this.isReadOnly();
		}

		copyMessage()
		{
			return Loc.getMessage('FIELD_EMAIL_VALUE_COPIED');
		}
	}

	module.exports = {
		EmailType: 'email',
		EmailFieldClass: EmailField,
		EmailField: (props) => new EmailField(props),
	};
});
