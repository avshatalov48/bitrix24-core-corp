/**
 * @module tasks/layout/fields/result/date
 */
jn.define('tasks/layout/fields/result/date', (require, exports, module) => {
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { Loc } = require('loc');
	const { FormatterFactory } = require('layout/ui/friendly-date/formatter-factory');

	class Date extends FriendlyDate
	{
		// eslint-disable-next-line no-useless-constructor
		constructor(props)
		{
			super(props);
		}

		makeText(moment)
		{
			if (moment.isYesterday)
			{
				return Loc.getMessage(
					'TASKS_FIELDS_RESULT_DATE_FORMAT_YESTERDAY',
					{
						'#TIME#': this.formatTime(moment),
					},
				);
			}

			if (moment.isToday)
			{
				if (this.useTimeAgo && !moment.isOverSeconds(this.skipTimeAgoAfterSeconds))
				{
					return FormatterFactory.createDefault({
						defaultFormat: this.defaultTimeFormat,
						futureAllowed: this.futureAllowed,
						context: this.props.context,
					}).format(moment);
				}

				return Loc.getMessage(
					'TASKS_FIELDS_RESULT_DATE_FORMAT_TODAY',
					{
						'#TIME#': this.formatTime(moment),
					},
				);
			}

			return this.formatDefault(moment);
		}
	}

	module.exports = { Date };
});
