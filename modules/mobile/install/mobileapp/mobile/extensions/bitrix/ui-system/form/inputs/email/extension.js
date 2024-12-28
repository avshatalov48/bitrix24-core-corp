/**
 * @module ui-system/form/inputs/email
 */
jn.define('ui-system/form/inputs/email', (require, exports, module) => {
	const { refSubstitution } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');
	const { EmailFieldClass } = require('layout/ui/fields/email');
	const { PropTypes } = require('utils/validation');
	const { getDomainImageUri, getEmailServiceName, defaultImageName, isValidEmail } = require('utils/email');
	const { StringInput, StringInputClass, InputSize, InputMode, InputDesign, Icon } = require(
		'ui-system/form/inputs/string',
	);
	const { InputClass } = require('ui-system/form/inputs/input');
	const { InputDomainIconPlace } = require('ui-system/form/inputs/email/src/domain-icon-place-enum');

	/**
	 * @typedef {InputProps} EmailInputProps
	 * @property {boolean} [validation]
	 * @property {InputDomainIconPlace} [domainIconPlace]
	 *
	 * @class EmailInputClass
	 */
	class EmailInputClass extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = this.initState(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.state = this.initState(nextProps);
		}

		initState(props)
		{
			const state = {
				value: props.value,
			};

			const shouldRenderDomain = Boolean(this.getDomainIconPlace());
			if (shouldRenderDomain)
			{
				state.emailService = this.getEmailServiceName(props.value);
			}

			return state;
		}

		render()
		{
			return StringInput({
				...this.props,
				...this.getDomainIcon(),
				value: this.getValue(),
				autoCapitalize: 'none',
				keyboardType: 'email-address',
				onChange: this.handleOnChangeText,
				onValid: this.handleOnValidation,
				errorText: this.getValidationErrorMessage(),
			});
		}

		getValue()
		{
			const { value } = this.state;

			return value;
		}

		getValidationErrorMessage()
		{
			return EmailFieldClass.getValidationErrorMessage();
		}

		getDomainIcon()
		{
			const iconPlacement = this.getDomainIconPlace();
			const defaultContentIcon = this.props?.[iconPlacement] || Icon.MAIL;

			if (!iconPlacement)
			{
				return {};
			}

			const { emailService } = this.state;
			const isDefinedEmailService = emailService && emailService !== defaultImageName;

			return {
				[iconPlacement]: isDefinedEmailService
					? this.renderDomainIcon(emailService)
					: defaultContentIcon,
			};
		}

		renderDomainIcon(service)
		{
			const size = InputClass.getIconSize();

			return Image({
				style: {
					width: size,
					height: size,
				},
				uri: getDomainImageUri({ service }),
				resizeMode: 'contain',
			});
		}

		handleOnChangeText = (value) => {
			const emailService = this.getEmailServiceName(value);
			const { emailService: prevEmailService } = this.state;
			const { onChange } = this.props;

			if (emailService === prevEmailService)
			{
				onChange?.(value);
			}
			else
			{
				this.setState(
					{
						value,
						emailService,
					},
					() => {
						onChange?.(value);
					},
				);
			}
		};

		/**
		 * @returns {InputDomainIconPlace}
		 */
		getDomainIconPlace()
		{
			const { domainIconPlace } = this.props;

			if (!domainIconPlace)
			{
				return null;
			}

			return InputDomainIconPlace.resolve(domainIconPlace, InputDomainIconPlace.LEFT).getValue();
		}

		/**
		 * @returns {Boolean}
		 */
		handleOnValidation = (value) => {
			const { validation } = this.props;

			if (!validation)
			{
				return true;
			}

			const { onValid } = this.props;

			return onValid ? onValid?.(value) : isValidEmail(value);
		};

		getEmailServiceName(email)
		{
			return getEmailServiceName(email);
		}

		isError()
		{
			const { error: propsError } = this.props;
			const { error: stateError } = this.state;

			return Boolean(propsError || stateError);
		}
	}

	EmailInputClass.defaultProps = {
		...StringInputClass.defaultProps,
		validation: false,
	};

	EmailInputClass.propTypes = {
		...StringInputClass.propTypes,
		validation: PropTypes.bool,
		domainIconPlace: PropTypes.instanceOf(InputDomainIconPlace),
		onChange: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {EmailInputProps} props
		 * @returns {EmailInputClass}
		 */
		EmailInput: (props) => refSubstitution(EmailInputClass)(props),
		InputSize,
		InputMode,
		InputDesign,
		InputDomainIconPlace,
		Icon,
	};
});
