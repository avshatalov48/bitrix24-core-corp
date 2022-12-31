/**
 * @module layout/ui/fields/email
 */
jn.define('layout/ui/fields/email', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');
	const { StringFieldClass } = require('layout/ui/fields/string');
	const { domains } = require('layout/ui/fields/email/domains');
	const { isValidEmail } = require('utils/url');
	const { stringify } = require('utils/string');

	const DEFAULT = 'default';

	/**
	 * @class EmailField
	 */
	class EmailField extends StringFieldClass
	{
		constructor(props)
		{
			super(props);

			this.prevService = null;
			this.service = this.getEmailService(props.value);
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			this.prevService = this.service;
			this.service = this.getEmailService(newProps.value);
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			if (this.prevService !== this.service)
			{
				return true;
			}

			return super.shouldComponentUpdate(nextProps, nextState);
		}

		renderLeftIcons()
		{
			this.styles = this.getStyles();

			return Image({
				style: this.styles.leftIcon,
				uri: this.getImageUri(),
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
					error = BX.message('FIELD_ERROR_EMAIL');
				}
			}

			return error;
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

		getImageUri()
		{
			return `${BaseField.getExtensionPath()}/email/images/${this.service}.png`;
		}

		getEmailService(value)
		{
			const domain = this.getEmailDomain(value);
			const service = Object.keys(domains).find((name) => domains[name].includes(domain));

			return service || DEFAULT;
		}

		getEmailDomain(value)
		{
			const email = this.prepareValue(value);
			if (email.trim() === '')
			{
				return null;
			}

			const startIndex = email.lastIndexOf('@') + 1;

			return email.substring(startIndex).trim();
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

	}

	module.exports = {
		EmailType: 'email',
		EmailField: (props) => new EmailField(props),
	};

});
