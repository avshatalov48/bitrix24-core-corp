/**
 * @module user-profile/common-tab/src/block/common-fields/src/field/phone-field
 */
jn.define('user-profile/common-tab/src/block/common-fields/src/field/phone-field', (require, exports, module) => {
	const { BaseField } = require('user-profile/common-tab/src/block/common-fields/src/field/base-field');
	const { getFormattedNumber } = require('utils/phone');
	const { Text4 } = require('ui-system/typography/text');
	const { Link4 } = require('ui-system/blocks/link');
	const { Color, Indent } = require('tokens');
	const { debounce } = require('utils/function');
	const { Loc } = require('loc');
	const {
		InputSize,
		InputDesign,
		InputMode,
		PhoneInput,
	} = require('ui-system/form/inputs/phone');

	class PhoneField extends BaseField
	{
		constructor(props)
		{
			super(props);
			this.debouncedUpdateIsValidState = debounce(this.#updateIsValidState, 300);
		}

		renderViewModeFieldValue(value, idx)
		{
			const { id } = this.props;
			const { isValid } = this.state;

			if (isValid)
			{
				return Link4({
					testId: this.getTestId(`phone-${id.toLowerCase()}-${idx}`),
					text: getFormattedNumber(value),
					numberOfLines: 1,
					onClick: () => {
						const callTo = `tel:${value}`;
						Application.openUrl(callTo);
					},
				});
			}

			return Text4({
				text: value,
				color: Color.base1,
			});
		}

		initState(props)
		{
			const { value, isValid } = props;

			this.state = {
				value: value ?? null,
				isValid: isValid ?? true,
			};
		}

		renderEditModeFieldValue(value, idx)
		{
			const { id } = this.props;

			return PhoneInput({
				forwardRef: (ref) => this.bindRef(ref, idx),
				testId: this.getTestId(`phone-input-${id.toLowerCase()}-${idx}`),
				value,
				size: InputSize.M,
				design: InputDesign.GREY,
				mode: InputMode.NAKED,
				onChange: this.#onPhoneChange,
				onFocus: () => this.onFocus(idx),
			});
		}

		#onPhoneChange = (newValue) => {
			this.setState({
				value: newValue,
			}, () => this.debouncedUpdateIsValidState());
		};

		#updateIsValidState = async () => {
			const { onChange } = this.props;
			const { value } = this.state;
			const isValid = await this.#isPhoneNumberValid(value);
			this.setState({ isValid }, () => onChange?.(value, isValid));
		};

		#isPhoneNumberValid = async (phoneNumber) => {
			if (phoneNumber === '')
			{
				return true;
			}

			const response = await BX.ajax.runAction('mobile.Profile.isPhoneNumberValid', {
				json: {
					phoneNumber,
				},
			})
				.catch((result) => {
					console.error(result);
				});
			if (response?.status === 'success')
			{
				return response.data;
			}

			return false;
		};

		getFieldTitleStyle()
		{
			return {};
		}

		getErrorText()
		{
			return Loc.getMessage('M_PROFILE_COMMON_FIELDS_PHONE_ERROR_TEXT');
		}

		getFieldContainerCustomStyle()
		{
			const { isEditMode } = this.props;

			return {
				paddingBottom: isEditMode ? Indent.XS.toNumber() : 0,
			};
		}
	}

	module.exports = { PhoneField };
});
