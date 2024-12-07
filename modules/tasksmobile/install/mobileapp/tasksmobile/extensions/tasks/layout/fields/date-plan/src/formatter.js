/**
 * @module tasks/layout/fields/date-plan/formatter
 */
jn.define('tasks/layout/fields/date-plan/formatter', (require, exports, module) => {
	const { Moment } = require('utils/date');
	const { dayShortMonth, shortTime, longDate, date } = require('utils/date/formats');
	const { Loc } = require('loc');

	function getFormattedDateTime(sourceDate)
	{
		if (!sourceDate)
		{
			return '';
		}

		const moment = new Moment(sourceDate * 1000);
		const dateFormat = (moment.inThisYear ? dayShortMonth : longDate);

		let formattedDateTime = moment.format(dateFormat());

		if (moment.inThisYear)
		{
			formattedDateTime += Loc.getMessage('M_TASKS_DATE_PLAN_EDIT_FORM_DATE_TIME', {
				'#TIME#': moment.format(shortTime()),
			});
		}

		return formattedDateTime;
	}

	function getFormattedDate(sourceDate)
	{
		if (!sourceDate)
		{
			return '';
		}

		const moment = new Moment(sourceDate * 1000);

		return moment.format(date());
	}

	module.exports = { getFormattedDateTime, getFormattedDate };
});
