/**
 * @module stafftrack/date-helper
 */
jn.define('stafftrack/date-helper', (require, exports, module) => {
	const { Moment } = require('utils/date');
	const { dayMonth, shortTime, dayShortMonth } = require('utils/date/formats');
	const { capitalize } = require('utils/string');

	class DateHelper
	{
		static getCurrentDayCode()
		{
			return this.getDayCode(new Date());
		}

		static getDayCode(date)
		{
			return `${this.addZero(date.getDate())}.${this.getMonthCode(date)}`;
		}

		static getMonthCode(date)
		{
			return `${this.addZero(date.getMonth() + 1)}.${date.getFullYear()}`;
		}

		static getDateCode(date)
		{
			const d = this.addZero(date.getDate());
			const m = this.addZero(date.getMonth() + 1);
			const Y = date.getFullYear();
			const H = this.addZero(date.getHours());
			const i = this.addZero(date.getMinutes());
			const s = this.addZero(date.getSeconds());

			return `${d}.${m}.${Y} ${H}:${i}:${s}`;
		}

		static addZero(date)
		{
			return `0${date}`.slice(-2);
		}

		static getDateFromMonthCode(monthCode)
		{
			const [month, year] = monthCode.split('.').map((code) => parseInt(code, 10));

			return new Date(year, month - 1, 1, 0, 0, 0, 0);
		}

		static getTimestampFromDate(date)
		{
			return Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0);
		}

		static formatDayMonth(date)
		{
			const moment = Moment.createFromTimestamp(date.getTime() / 1000);

			return moment.format(dayMonth());
		}

		static formatMonthCode(monthCode)
		{
			const date = DateHelper.getDateFromMonthCode(monthCode);

			return DateHelper.formatMonthYear(date);
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

		static formatTime(date)
		{
			const moment = Moment.createFromTimestamp(date.getTime() / 1000);

			return moment.format(shortTime()).toLocaleUpperCase(env.languageId);
		}

		static formatDate(date)
		{
			const moment = Moment.createFromTimestamp(date.getTime() / 1000);

			return moment.format(dayShortMonth()).toLocaleLowerCase(env.languageId);
		}

		static getTimezoneOffset()
		{
			const date = new Date();

			return date.getTimezoneOffset() * (-60);
		}
	}

	module.exports = { DateHelper };
});
