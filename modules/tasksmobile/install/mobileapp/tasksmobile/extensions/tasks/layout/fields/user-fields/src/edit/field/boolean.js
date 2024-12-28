/**
 * @module tasks/layout/fields/user-fields/edit/field/boolean
 */
jn.define('tasks/layout/fields/user-fields/edit/field/boolean', (require, exports, module) => {
	const { EditBaseField } = require('tasks/layout/fields/user-fields/edit/field/base');
	const { Area } = require('ui-system/layout/area');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Color, Indent } = require('tokens');
	const { Text2 } = require('ui-system/typography');
	const { SwitcherSize, Switcher } = require('ui-system/blocks/switcher');
	const { SelectField } = require('layout/ui/fields/select/theme/air');
	const { useCallback } = require('utils/function');

	const BooleanDisplayType = {
		CHECKBOX: 'CHECKBOX',
		DROPDOWN: 'DROPDOWN',
		RADIO: 'RADIO',
	};
	const IconSize = 24;

	class EditBooleanField extends EditBaseField
	{
		render()
		{
			if (this.settings.displayType === BooleanDisplayType.CHECKBOX)
			{
				return Area(
					{
						excludePaddingSide: {
							bottom: true,
						},
					},
					View(
						{
							style: {
								flexShrink: 1,
								flexDirection: 'row',
								alignItems: 'center',
								justifyContent: 'space-between',
							},
							onClick: () => this.updateValue(this.state.value === '1' ? '0' : '1'),
						},
						this.renderTitle(),
						this.renderSingleValue(this.state.value),
					),
				);
			}

			return super.render();
		}

		renderSingleValue(value, index = 0)
		{
			switch (this.settings.displayType)
			{
				case BooleanDisplayType.CHECKBOX:
					return this.renderSwitcher(value);

				case BooleanDisplayType.DROPDOWN:
					return this.renderDropDown(value);

				case BooleanDisplayType.RADIO:
					return this.renderRadio(value);

				default:
					return this.renderSwitcher(value);
			}
		}

		renderSwitcher(value)
		{
			return Switcher({
				style: {
					marginLeft: Indent.L.toNumber(),
				},
				checked: value === '1',
				size: SwitcherSize.L,
				disabled: this.isReadOnly,
				testId: `${this.testId}_SWITCHER`,
				onClick: (newValue) => this.updateValue(newValue ? '1' : '0'),
			});
		}

		renderDropDown(value)
		{
			return View(
				{
					style: {
						paddingTop: Indent.M.toNumber(),
						paddingBottom: Indent.S.toNumber(),
					},
				},
				SelectField({
					value,
					config: {
						mode: 'popupMenu',
						items: [
							{
								name: this.settings.noLabel,
								value: '0',
							},
							{
								name: this.settings.yesLabel,
								value: '1',
							},
						],
						parentWidget: this.parentWidget,
					},
					title: this.props.title,
					readOnly: this.isReadOnly,
					showTitle: false,
					required: true,
					showRequired: false,
					multiple: false,
					testId: `${this.testId}_DROPDOWN`,
					onChange: useCallback((newValue) => this.updateValue(newValue)),
				}),
			);
		}

		renderRadio(value)
		{
			const { yesLabel, noLabel } = this.settings;

			return View(
				{
					testId: `${this.testId}_RADIO`,
				},
				this.renderRadioItem(noLabel, value, '0'),
				View({ style: { height: Indent.XS.toNumber() } }),
				this.renderRadioItem(yesLabel, value, '1'),
			);
		}

		renderRadioItem(text, value, itemValue)
		{
			const isChecked = value === itemValue;
			const paddingTop = Indent.L.toNumber();
			const paddingBottom = Indent.XL2.toNumber();

			return View(
				{
					style: {
						paddingTop,
						paddingBottom,
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						height: IconSize + paddingTop + paddingBottom,
						borderBottomWidth: 1,
						borderBottomColor: Color.bgSeparatorPrimary.toHex(),
					},
					testId: `${this.testId}_RADIO_${itemValue}`,
					onClick: () => this.updateValue(itemValue),
				},
				Text2({
					text,
					testId: `${this.testId}_RADIO_${itemValue}_TEXT`,
				}),
				isChecked && IconView({
					style: {
						marginLeft: Indent.L.toNumber(),
					},
					size: IconSize,
					icon: Icon.SENDED,
					color: Color.accentMainPrimary,
					testId: `${this.testId}_RADIO_${itemValue}_ICON`,
				}),
			);
		}

		get icon()
		{
			return Icon.SWITCHER;
		}
	}

	module.exports = { EditBooleanField };
});
