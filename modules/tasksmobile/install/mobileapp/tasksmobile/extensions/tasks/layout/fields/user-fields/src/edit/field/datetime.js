/**
 * @module tasks/layout/fields/user-fields/edit/field/datetime
 */
jn.define('tasks/layout/fields/user-fields/edit/field/datetime', (require, exports, module) => {
	const { EditBaseField } = require('tasks/layout/fields/user-fields/edit/field/base');
	const { getBaseInputFieldProps } = require('tasks/layout/fields/user-fields/edit/field/input');
	const { Icon } = require('ui-system/blocks/icon');
	const { DateTimeInput, DatePickerType } = require('ui-system/form/inputs/datetime');
	const { useCallback } = require('utils/function');
	const { Moment } = require('utils/date');
	const { longDate, dayMonth, shortTime } = require('utils/date/formats');

	class EditDateTimeField extends EditBaseField
	{
		renderSingleValue(value, index = 0)
		{
			const moment = Moment.createFromTimestamp(Number(value));
			const fieldValue = (value === '' ? 0 : moment.timestamp);

			return DateTimeInput({
				...getBaseInputFieldProps(value, index, this),
				value: fieldValue,
				datePickerType: DatePickerType.DATETIME,
				enableTime: true,
				dateFormat: `${(moment.inThisYear ? dayMonth() : longDate())}, ${shortTime()}`,
				onChange: useCallback((newDate) => this.updateValue(String(newDate), index)),
			});
		}

		get icon()
		{
			return Icon.PLANNING;
		}
	}

	module.exports = { EditDateTimeField };
});
