/**
 * @module calendar/date-helper
 */
jn.define('calendar/date-helper', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');

	class DateHelper
	{
		static getDayCode(day)
		{
			return `${this.addZero(day.getDate())}.${this.addZero(day.getMonth() + 1)}.${day.getFullYear()}`;
		}

		static addZero(date)
		{
			return `0${date}`.slice(-2);
		}

		static getMonthName(day)
		{
			const moment = Moment.createFromTimestamp(day.getTime() / 1000);

			return moment.format('LLL');
		}
	}

	module.exports = { DateHelper };
});
