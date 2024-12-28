/**
 * @module tasks/layout/fields/user-fields/view/field/datetime
 */
jn.define('tasks/layout/fields/user-fields/view/field/datetime', (require, exports, module) => {
	const { ViewBaseField } = require('tasks/layout/fields/user-fields/view/field/base');
	const { Icon } = require('assets/icons');
	const { DateTimeField } = require('layout/ui/fields/datetime');
	const { Moment } = require('utils/date');
	const { longDate, dayMonth, shortTime } = require('utils/date/formats');

	class ViewDateTimeField extends ViewBaseField
	{
		prepareValue(value)
		{
			const moment = Moment.createFromTimestamp(Number(value));
			const dateTimeField = DateTimeField({
				value: moment.timestamp,
				config: {
					enableTime: true,
					dateFormat: `${(moment.inThisYear ? dayMonth() : longDate())}, ${shortTime()}`,
				},
			});

			return dateTimeField.getDisplayedValue();
		}

		get icon()
		{
			return Icon.PLANNING;
		}
	}

	module.exports = { ViewDateTimeField };
});
