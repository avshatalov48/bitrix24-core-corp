/**
 * @module calendar/date-helper
 */
jn.define('calendar/date-helper', (require, exports, module) => {
	const { Moment } = require('utils/date');

	class DateHelper
	{
		static getDayCode(day)
		{
			return `${this.addZero(day.getDate())}.${this.addZero(day.getMonth() + 1)}.${day.getFullYear()}`;
		}

		static getTimestampFromDayCode(dayCode)
		{
			const parsed = DateHelper.parseDayCode(dayCode);

			return Date.UTC(parsed.year, parsed.month - 1, parsed.date, 0, 0, 0, 0);
		}

		static compareDayCodes(dayCode1, dayCode2)
		{
			const parsed1 = DateHelper.parseDayCode(dayCode1);
			const parsed2 = DateHelper.parseDayCode(dayCode2);

			if (parsed1.year !== parsed2.year)
			{
				return parsed1.year - parsed2.year;
			}

			if (parsed1.month !== parsed2.month)
			{
				return parsed1.month - parsed2.month;
			}

			return parsed1.date - parsed2.date;
		}

		static parseDayCode(dayCode)
		{
			const splitDate = dayCode.split('.').map((value) => parseInt(value, 10));

			return {
				date: splitDate[0],
				month: splitDate[1],
				year: splitDate[2],
			};
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

	module.exports = { DateHelper, Moment };
});
