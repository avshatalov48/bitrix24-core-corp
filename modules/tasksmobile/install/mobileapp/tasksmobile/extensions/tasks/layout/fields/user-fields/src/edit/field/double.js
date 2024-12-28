/**
 * @module tasks/layout/fields/user-fields/edit/field/double
 */
jn.define('tasks/layout/fields/user-fields/edit/field/double', (require, exports, module) => {
	const { EditBaseField } = require('tasks/layout/fields/user-fields/edit/field/base');
	const { getBaseInputFieldProps } = require('tasks/layout/fields/user-fields/edit/field/input');
	const { Icon } = require('ui-system/blocks/icon');
	const { NumberInput } = require('ui-system/form/inputs/number');
	const { useCallback } = require('utils/function');
	const { Loc } = require('tasks/loc');

	class EditDoubleField extends EditBaseField
	{
		constructor(props)
		{
			super(props);

			this.focusedInputIndex = null;
		}

		renderSingleValue(value, index = 0)
		{
			return NumberInput({
				...getBaseInputFieldProps(value, index, this),
				placeholder: (
					this.isReadOnly ? '' : Loc.getMessage('TASKS_FIELDS_USER_FIELDS_EDIT_DOUBLE_PLACEHOLDER')
				),
				decimalDigits: this.settings.precision || 0,
				onFocus: useCallback(() => {
					this.focusedInputIndex = index;
					this.props.onFocus(this.refsMap.get(index));
				}),
				onBlur: useCallback(() => {
					const shouldFocusAfter = false;
					const shouldShowValueValidationError = this.indexToDelete === null || index !== this.indexToDelete;
					const shouldCheckIfValueChanged = false;

					this.focusedInputIndex = null;
					this.updateValue(
						this.refsMap.get(index).getValue(),
						index,
						shouldFocusAfter,
						shouldShowValueValidationError,
						shouldCheckIfValueChanged,
					);
				}),
				onChange: useCallback((newValue) => {
					let preparedValue = this.prepareValue(newValue);

					if (this.isMultiple)
					{
						preparedValue = [...this.state.value];
						preparedValue[index] = this.prepareValue(newValue);
					}

					this.setState({ value: preparedValue });
				}),

			});
		}

		get icon()
		{
			return Icon.QUANTITY;
		}

		get shouldShowSettingsInfoHint()
		{
			const { minValue = 0, maxValue = 0 } = this.settings;

			return minValue !== 0 || maxValue !== 0;
		}

		getSettingsInfoDescription()
		{
			const { minValue = 0, maxValue = 0 } = this.settings;
			const minValueDescription = Loc.getMessage(
				'TASKS_FIELDS_USER_FIELDS_EDIT_SETTINGS_INFO_MIN_VALUE',
				{
					'#VALUE#': minValue,
				},
			);
			const maxValueDescription = Loc.getMessage(
				'TASKS_FIELDS_USER_FIELDS_EDIT_SETTINGS_INFO_MAX_VALUE',
				{
					'#VALUE#': maxValue,
				},
			);

			return (
				(minValue ? `\n${minValueDescription}` : '')
				+ (maxValue ? `\n${maxValueDescription}` : '')
			);
		}

		prepareValue(value)
		{
			if (value === '')
			{
				return '';
			}

			const { precision = 0 } = this.settings;
			const factor = 10 ** precision;

			return (Math.round(parseFloat(value.replace(',', '.')) * factor) / factor).toString();
		}

		addValue()
		{
			if (this.focusedInputIndex === null)
			{
				super.addValue();
			}
			else
			{
				const focusedInputValue = this.prepareValue(this.refsMap.get(this.focusedInputIndex).getValue());
				const changedStateValue = this.state.value.map((value, index) => {
					return (index === this.focusedInputIndex ? focusedInputValue : value);
				});

				this.setState(
					{
						value: [...changedStateValue, ''],
						shouldShowErrors: true,
					},
					() => {
						if (!this.isValueValid(focusedInputValue))
						{
							this.showValueValidationError(focusedInputValue);
						}

						if (Application.getPlatform() === 'android')
						{
							setTimeout(() => this.focus(this.refsMap.get(this.refsMap.size - 1)), 250);
						}
						else
						{
							this.focus(this.refsMap.get(this.refsMap.size - 1));
						}
						this.props.onChange(this.props.fieldName, this.state.value);
					},
				);
			}
		}

		async removeValue(index)
		{
			this.indexToDelete = index;

			if (this.focusedInputIndex === null)
			{
				super.removeValue(index);
			}
			else if (this.isMultiple)
			{
				if (this.state.value.filter((_, i) => i !== index).length === 0)
				{
					this.updateValue('', index, true);
				}
				else
				{
					const wasValidByRequired = this.isValidByRequired();

					await this.blur(this.refsMap.get(this.focusedInputIndex));
					this.setState(
						{
							value: this.state.value.filter((_, i) => i !== index),
							shouldShowErrors: true,
						},
						() => {
							if (wasValidByRequired && !this.isValidByRequired())
							{
								this.showValueValidationError();
							}
							this.refsMap = new Map([...this.refsMap].filter(([_, value]) => Boolean(value)));
							this.props.onChange(this.props.fieldName, this.state.value);
						},
					);
				}
			}
			else
			{
				this.updateValue('', index, true);
			}

			this.indexToDelete = null;
		}
	}

	module.exports = { EditDoubleField };
});
