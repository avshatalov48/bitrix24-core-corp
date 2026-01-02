/**
 * @module app-rating-manager/manual-testing-tool/src/field
 */
jn.define('app-rating-manager/manual-testing-tool/src/field', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { NumberInput } = require('ui-system/form/inputs/number');
	const { DateTimeInput, DatePickerType } = require('ui-system/form/inputs/datetime');
	const { Switcher, SwitcherSize } = require('ui-system/blocks/switcher');
	const { Text4 } = require('ui-system/typography/text');
	const { debounce } = require('utils/function');

	const FieldType = {
		NUMBER: 'number',
		DATETIME: 'datetime',
		BOOLEAN: 'boolean',
	};

	class AppRatingTestingToolField extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.debounceNumberOnChange = debounce(this.#numberOnChange, 1000);
		}

		render()
		{
			const { type = FieldType.NUMBER } = this.props;

			return View(
				{},
				type === FieldType.NUMBER && this.#renderNumberInput(),
				type === FieldType.DATETIME && this.#renderDateTimeInput(),
				type === FieldType.BOOLEAN && this.#renderBooleanSwitcher(),
			);
		}

		#renderNumberInput()
		{
			const { testId, value, label } = this.props;

			return NumberInput({
				testId,
				value,
				label,
				onChange: this.debounceNumberOnChange,
			});
		}

		#numberOnChange = (newValue) => {
			const { label, onChange } = this.props;
			onChange?.({
				value: newValue,
				label,
			});
		};

		#renderDateTimeInput()
		{
			const { testId, value, label } = this.props;

			return DateTimeInput({
				testId,
				value,
				label,
				datePickerType: DatePickerType.DATETIME,
				onChange: this.#dateTimeOnChange,
			});
		}

		#dateTimeOnChange = (newValue) => {
			const { label, onChange } = this.props;
			onChange?.({
				value: newValue,
				label,
			});
		};

		#renderBooleanSwitcher()
		{
			const { testId, value, label } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingVertical: 6,
					},
				},
				Switcher({
					testId,
					useState: true,
					size: SwitcherSize.L,
					checked: value,
					onClick: this.#booleanOnClick,
				}),
				Text4({
					text: label,
					style: {
						flexGrow: 1,
						marginLeft: 10,
					},
				}),
			);
		}

		#booleanOnClick = (newValue) => {
			const { label, onChange } = this.props;
			onChange?.({
				value: newValue,
				label,
			});
		};
	}

	module.exports = {
		AppRatingTestingToolField: (props) => new AppRatingTestingToolField(props),
		FieldType,
	};
});
