/**
 * @module tasks/layout/fields/user-fields/edit/field/base
 */
jn.define('tasks/layout/fields/user-fields/edit/field/base', (require, exports, module) => {
	const { BaseField } = require('tasks/layout/fields/user-fields/field/base');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { Area } = require('ui-system/layout/area');
	const { Text3 } = require('ui-system/typography');
	const { Indent, Color, Typography } = require('tokens');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');
	const { AhaMoment } = require('ui-system/popups/aha-moment');
	const { Loc } = require('tasks/loc');

	const IconSize = 24;

	class EditBaseField extends BaseField
	{
		constructor(props)
		{
			super(props);

			this.refsMap = new Map();
		}

		render()
		{
			return Area(
				{
					excludePaddingSide: {
						bottom: true,
					},
				},
				this.renderTitle(),
				this.renderValues(),
				this.isMultiple && !this.isReadOnly && this.renderAddValueButton(),
			);
		}

		renderTitle()
		{
			return View(
				{
					style: {
						flexShrink: 1,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				IconView({
					size: IconSize,
					icon: this.icon,
					color: Color.accentMainPrimary,
				}),
				Text3({
					style: {
						flexShrink: 1,
						marginLeft: Indent.XS.toNumber(),
					},
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.props.title,
					testId: `${this.testId}_TITLE`,
				}),
				this.isMandatory && Text3({
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
					text: '*',
					color: Color.accentMainAlert,
					testId: `${this.testId}_MANDATORY`,
				}),
				this.shouldShowSettingsInfoHint && IconView({
					style: {
						marginLeft: this.isMandatory ? 0 : Indent.XS.toNumber(),
					},
					size: IconSize,
					icon: Icon.QUESTION,
					color: Color.base5,
					testId: `${this.testId}_SETTINGS_INFO`,
					forwardRef: (ref) => {
						this.settingsInfoButtonRef = ref;
					},
					onClick: () => AhaMoment.show({
						targetRef: this.settingsInfoButtonRef,
						description: this.trimNewlines(this.getSettingsInfoDescription()),
						closeButton: false,
						testId: `${this.testId}_SETTINGS_INFO_POPUP`,
					}),
				}),
			);
		}

		renderValues()
		{
			return View(
				{
					style: {
						paddingLeft: IconSize + Indent.XS.toNumber(),
						paddingVertical: Indent.XS.toNumber(),
					},
					testId: `${this.testId}_VALUES`,
				},
				...(this.isMultiple ? this.renderMultipleValues() : [this.renderSingleValue(this.state.value)]),
			);
		}

		renderMultipleValues()
		{
			return this.state.value.map((value, index) => this.renderSingleValue(value, index));
		}

		renderSingleValue(value, index)
		{
			return Text({
				text: value,
				style: {
					paddingTop: Indent.L.toNumber(),
					paddingBottom: Indent.XL2.toNumber(),
					paddingRight: Indent.XL3.toNumber(),
					...Typography.text2.getStyle(),
					color: Color.base2.toHex(),
					testId: `${this.testId}_VALUE_${index}`,
				},
			});
		}

		renderAddValueButton()
		{
			return AddButton({
				style: {
					paddingLeft: IconSize + Indent.XS.toNumber(),
					paddingTop: Indent.L.toNumber(),
					paddingBottom: Indent.XL2.toNumber(),

				},
				text: Loc.getMessage('TASKS_FIELDS_USER_FIELDS_EDIT_ADD_VALUE'),
				testId: this.testId,
				onClick: () => this.addValue(),
			});
		}

		addValue()
		{
			this.setState(
				{
					value: [...this.state.value, ''],
					shouldShowErrors: true,
				},
				() => {
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

		updateValue(
			value,
			index,
			shouldFocusAfter = false,
			shouldShowValueValidationError = true,
			shouldCheckIfValueChanged = true,
		)
		{
			const preparedValue = this.prepareValue(value);

			if (this.isReadOnly || (shouldCheckIfValueChanged && !this.isValueChanged(preparedValue, index)))
			{
				return;
			}

			let newValue = preparedValue;

			if (this.isMultiple)
			{
				newValue = [...this.state.value];
				newValue[index] = preparedValue;
			}

			this.setState(
				{
					value: newValue,
					shouldShowErrors: true,
				},
				() => {
					if (shouldShowValueValidationError && !this.isValueValid(preparedValue))
					{
						this.showValueValidationError(preparedValue);
					}

					if (shouldFocusAfter)
					{
						this.focus(this.refsMap.get(index));
					}

					this.props.onChange(this.props.fieldName, newValue);
				},
			);
		}

		prepareValue(value)
		{
			return value;
		}

		isValueChanged(value, index)
		{
			if (this.isMultiple)
			{
				return this.state.value[index] !== value;
			}

			return this.state.value !== value;
		}

		removeValue(index)
		{
			if (this.isMultiple)
			{
				const newValue = this.state.value.filter((_, i) => i !== index);

				if (newValue.length === 0)
				{
					this.updateValue('', index, true);
				}
				else
				{
					const wasValidByRequired = this.isValidByRequired();

					this.setState(
						{
							value: newValue,
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
		}

		focus(ref)
		{
			ref?.focus?.();
		}

		blur(ref)
		{
			return ref?.handleOnBlur?.();
		}

		get shouldShowSettingsInfoHint()
		{
			return false;
		}

		getSettingsInfoDescription()
		{
			return '';
		}

		trimNewlines(str)
		{
			return str.replaceAll(/^\n+|\n+$/g, '');
		}
	}

	module.exports = { EditBaseField };
});
