/**
 * @module im/messenger/lib/utils/date
 */
jn.define('im/messenger/lib/utils/date', (require, exports, module) => {
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');
	const { Moment } = require('utils/date');
	const { date, dayMonth } = require('utils/date/formats');

	class DateUtils extends TimeAgoFormat
	{
		/**
		 * @public
		 * @param {Moment} moment
		 */
		formatLastActivityDate(moment)
		{
			if (moment.isJustNow(this.justNowDelay))
			{
				return this.getPhrase({
					code: 'MOBILE_UI_TIME_AGO_JUST_NOW',
				});
			}

			// yesterday at #TIME#
			if (moment.isYesterday)
			{
				return this.formatYesterday(moment);
			}

			if (moment.withinMinute)
			{
				return this.formatSeconds(moment);
			}

			if (moment.withinHour)
			{
				return this.formatMinutes(moment);
			}

			if (moment.isToday)
			{
				// #NUM# hours ago
				if (moment.hoursFromNow <= 5)
				{
					return this.formatHours(moment);
				}

				// today at #TIME#
				return this.formatToday(moment);
			}

			if (moment.daysFromNow <= 31 && moment.monthsFromNow < 2)
			{
				return this.formatDays(moment);
			}

			if (moment.monthsFromNow < 12)
			{
				return this.formatMonths(moment);
			}

			return this.formatMoreYear(moment);
		}

		/**
		 *
		 * @param {Moment} moment
		 * @return {string}
		 */
		formatIdleDate(moment)
		{
			if (moment.withinMinute)
			{
				return this.formatSeconds(moment);
			}

			if (moment.withinHour)
			{
				return this.formatMinutes(moment);
			}

			if (moment.withinDay)
			{
				return this.formatDays(moment);
			}

			return this.formatDays(moment);
		}

		/**
		 * @param {Moment} moment
		 * @return {string}
		 */
		formatVacationDate(moment)
		{
			if (moment.inThisYear)
			{
				return moment.format(dayMonth, env.languageId);
			}

			return moment.format(date, env.languageId);
		}


	}

	module.exports = { DateUtils };
});
