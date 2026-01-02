/**
 * @module user-profile/common-tab/src/block/common-fields/src/field/email-field
 */
jn.define('user-profile/common-tab/src/block/common-fields/src/field/email-field', (require, exports, module) => {
	const { BaseField } = require('user-profile/common-tab/src/block/common-fields/src/field/base-field');
	const { isValidEmail } = require('utils/email');
	const { Link4 } = require('ui-system/blocks/link');
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const {
		InputSize,
		InputDesign,
		InputMode,
		EmailInput,
	} = require('ui-system/form/inputs/email');

	class EmailField extends BaseField
	{
		renderViewModeFieldValue(value, idx)
		{
			const { id } = this.props;

			if (isValidEmail(value))
			{
				return Link4({
					testId: this.getTestId(`email-${id.toLowerCase()}-${idx}`),
					text: value,
					onClick: () => {
						const mailto = `mailto:${value}`;
						Application.openUrl(mailto);
					},
				});
			}

			return Text4({
				text: value,
				color: Color.base1,
			});
		}

		renderEditModeFieldValue(value, idx)
		{
			const { id } = this.props;

			return EmailInput({
				forwardRef: (ref) => this.bindRef(ref, idx),
				testId: this.getTestId(`email-input-${id.toLowerCase()}-${idx}`),
				value,
				size: InputSize.M,
				design: InputDesign.GREY,
				mode: InputMode.NAKED,
				validation: true,
				onChange: this.#onEmailChange,
				onFocus: () => this.onFocus(idx),
			});
		}

		getFieldContainerStyle()
		{
			const { isEditMode, isFirst } = this.props;

			return {
				marginTop: isFirst ? 0 : Indent.XL2.toNumber(),
				alignItems: 'flex-start',
				borderBottomColor: Color.bgSeparatorSecondary.toHex(),
				borderBottomWidth: isEditMode ? 1 : 0,
			};
		}

		getFieldTitleStyle()
		{
			return {};
		}

		#onEmailChange = (newValue) => {
			const { onChange } = this.props;
			this.setState({ value: newValue }, () => {
				onChange?.(newValue);
			});
		};
	}

	module.exports = { EmailField };
});
