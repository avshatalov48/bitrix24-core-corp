/**
 * @module intranet/qualification/fields/phone
 */
jn.define('intranet/qualification/fields/phone', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { BBCodeText } = require('ui-system/typography');
	const { PhoneInput, InputSize } = require('ui-system/form/inputs/phone');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { PropTypes } = require('utils/validation');
	const { createTestIdGenerator } = require('utils/test');
	const { isPhoneNumber, getCountryCode } = require('utils/phone');

	class Phone extends PureComponent
	{
		static isValidPhone = (value) => isPhoneNumber(value);

		/**
		 * @param {Object} props
		 * @param {string} props.testId
		 * @param {string} [props.value='']
		 * @param {string} [props.agreementUrl='']
		 * @param {string} [props.buttonText=Loc.getMessage('QUALIFICATION_FIELDS_PHONE_BUTTON_DEFAULT_TEXT')]
		 * @param {Function} [props.onChange=null]
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({ context: this });

			this.#updateState(this.props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.#updateState(nextProps);
		}

		#updateState(newState)
		{
			this.state = {
				value: newState.value,
			};
		}

		render()
		{
			return View(
				{
					testId: this.getTestId('container'),
				},
				PhoneInput({
					value: this.state.value,
					size: InputSize.L,
					showCountryFlag: false,
					testId: this.getTestId('phone-field'),
					onValid: Phone.isValidPhone,
					onChange: this.#handleOnChange,
					errorText: Loc.getMessage('QUALIFICATION_FIELDS_PHONE_ERROR'),
				}),
				BBCodeText({
					style: {
						marginTop: Indent.L.toNumber(),
					},
					size: 6,
					color: Color.base4,
					value: Loc.getMessage(
						'QUALIFICATION_FIELDS_PHONE_FOOTER',
						{
							...this.#getAgreementUrlAnchors(),
							'#BUTTON_TEXT#': this.props.buttonText,
						},
					),
					testId: this.getTestId('footer-field'),
				}),
			);
		}

		#getAgreementUrlAnchors()
		{
			const { agreementUrl } = this.props;

			if (!agreementUrl)
			{
				return {
					'#URL_START#': '',
					'#URL_END#': '',
				};
			}

			return {
				'#URL_START#': `[URL=${agreementUrl}]`,
				'#URL_END#': '[/URL]',
			};
		}

		#handleOnChange = (value) => {
			this.setState({ value }, () => this.props.onChange?.({ value, countryCode: getCountryCode(value) }));
		};
	}

	Phone.defaultProps = {
		value: '',
		agreementUrl: '',
		buttonText: Loc.getMessage('QUALIFICATION_FIELDS_PHONE_BUTTON_DEFAULT_TEXT'),
		onChange: null,
	};

	Phone.propTypes = {
		testId: PropTypes.string.isRequired,
		value: PropTypes.string,
		agreementUrl: PropTypes.string,
		buttonText: PropTypes.string,
		onChange: PropTypes.func,
	};

	module.exports = { Phone };
});
