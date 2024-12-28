/**
 * @module calendar/date-helper
 */
jn.define('calendar/date-helper', (require, exports, module) => {
	const { Moment } = require('utils/date');
	const { dayOfWeekMonth, fullDate, date: shortDate, shortTime } = require('utils/date/formats');
	const { capitalize } = require('utils/string');

	class DateHelper
	{
		static get dayLength()
		{
			return 86_400_000;
		}

		static getDayCode(date)
		{
			return `${this.addZero(date.getDate())}.${this.addZero(date.getMonth() + 1)}.${date.getFullYear()}`;
		}

		static getMonthCode(date)
		{
			return `${this.addZero(date.getMonth() + 1)}.${date.getFullYear()}`;
		}

		static getDayMonthCode(date)
		{
			return `${date.getDate()}.${this.addZero(date.getMonth() + 1)}`;
		}

		static addZero(date)
		{
			return `0${date}`.slice(-2);
		}

		static get timezoneOffset()
		{
			return new Date().getTimezoneOffset() * 60000;
		}

		static getDateTimezoneOffset(date)
		{
			return date.getTimezoneOffset() * 60000;
		}

		static getDateFromDayCode(dayCode)
		{
			const parsed = DateHelper.parseDayCode(dayCode);

			return new Date(parsed.year, parsed.month - 1, parsed.date, 0, 0, 0, 0);
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

		static formatMonthCode(monthCode)
		{
			const date = DateHelper.getDateFromMonthCode(monthCode);

			return DateHelper.formatMonthYear(date);
		}

		static getDateFromMonthCode(monthCode)
		{
			const [month, year] = monthCode.split('.').map((code) => parseInt(code, 10));

			return new Date(year, month - 1, 1, 0, 0, 0, 0);
		}

		static formatMonthYear(date)
		{
			const month = DateHelper.getMonthName(date);
			const year = date.getFullYear().toString();

			return `${capitalize(month)} ${year}`;
		}

		static getMonthName(date)
		{
			const moment = Moment.createFromTimestamp(date.getTime() / 1000);

			return moment.format('LLLL');
		}

		static getShortMonthName(date)
		{
			const moment = Moment.createFromTimestamp(date.getTime() / 1000);

			return moment.format('LLL');
		}

		static formatDate(date)
		{
			const moment = Moment.createFromTimestamp(date.getTime() / 1000);

			return moment.format(shortDate());
		}

		static formatTime(date)
		{
			const moment = Moment.createFromTimestamp(date.getTime() / 1000);

			return moment.format(shortTime());
		}

		static getDateHeaderString(timestamp)
		{
			const moment = Moment.createFromTimestamp(timestamp / 1000);
			const format = moment.inThisYear ? dayOfWeekMonth() : fullDate();
			const dateString = moment.format(format);

			return dateString.at(0).toUpperCase() + dateString.slice(1);
		}
	}

	module.exports = { DateHelper, Moment };
});
