/**
 * @module tasks/layout/deadline-friendly-date
 */
jn.define('tasks/layout/deadline-friendly-date', (require, exports, module) => {
	const { AutoupdatingDatetime } = require('layout/ui/friendly-date/autoupdating-datetime');
	const { TimeAgoFormat } = require('layout/ui/friendly-date/time-ago-format');
	const { Loc } = require('loc');
	const { CalendarSettings } = require('tasks/task/calendar');
	const { Moment } = require('utils/date');
	const { dayMonth, longDate, shortTime } = require('utils/date/formats');

	/**
	 * @class DeadlineFriendlyDate
	 */
	class DeadlineFriendlyDate extends AutoupdatingDatetime
	{
		constructor(props)
		{
			super(props);
		}

		get timeAgoTextBuilder()
		{
			return new TimeAgoFormat({
				futureAllowed: true,
			});
		}

		get timeFormat()
		{
			const { workTime, serverOffset, clientOffset } = CalendarSettings;
			const { hours, minutes } = workTime[0].end;

			const newDate = new Date(this.moment.date);
			newDate.setSeconds(serverOffset - clientOffset, 0);
			newDate.setHours(hours, minutes, clientOffset - serverOffset);

			if (newDate.getTime() !== this.moment.date.getTime())
			{
				return this.moment.format(shortTime);
			}

			return '';
		}

		/**
		 * @private
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		makeText(moment)
		{
			if (moment.hasPassed)
			{
				return this.makePastText(moment);
			}

			return this.makeFutureText(moment);
		}

		/**
		 * @private
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		makePastText(moment)
		{
			if (moment.isToday)
			{
				return this.timeAgoTextBuilder.format(moment);
			}

			if (moment.isYesterday)
			{
				return this.timeFormat
					? Loc.getMessage('TASKSMOBILE_DEADLINE_FRIENDLY_DATE_YESTERDAY', { '#TIME#': this.timeFormat })
					: Loc.getMessage('MOBILE_UI_FRIENDLY_DATE_YESTERDAY_MSGVER_1');
			}

			const startOfDay = new Date(moment.date);
			startOfDay.setHours(0, 0, 0, 0);

			const startOfDayMoment = new Moment(startOfDay);

			if (startOfDayMoment.withinWeek)
			{
				return this.timeAgoTextBuilder.formatDays(startOfDayMoment);
			}

			if (startOfDayMoment.withinMonth)
			{
				return this.timeAgoTextBuilder.formatWeeks(startOfDayMoment);
			}

			if (startOfDayMoment.withinYear)
			{
				return this.timeAgoTextBuilder.formatMonths(startOfDayMoment);
			}

			return this.timeAgoTextBuilder.formatYears(startOfDayMoment);
		}

		/**
		 * @private
		 * @param {Moment} moment
		 * @return {string|null}
		 */
		makeFutureText(moment)
		{
			if (moment.isWithinSeconds(1800))
			{
				const nextMinuteMoment = moment.addMinutes(1);

				return this.timeAgoTextBuilder.formatMinutes(nextMinuteMoment);
			}

			if (moment.isToday)
			{
				return this.timeFormat
					? Loc.getMessage('TASKSMOBILE_DEADLINE_FRIENDLY_DATE_TODAY', { '#TIME#': this.timeFormat })
					: Loc.getMessage('MOBILE_UI_FRIENDLY_DATE_TODAY_MSGVER_1');
			}

			if (moment.isTomorrow)
			{
				return this.timeFormat
					? Loc.getMessage('TASKSMOBILE_DEADLINE_FRIENDLY_DATE_TOMORROW', { '#TIME#': this.timeFormat })
					: Loc.getMessage('MOBILE_UI_FRIENDLY_DATE_TOMORROW_MSGVER_1');
			}

			let date = null;

			if (moment.inThisWeek)
			{
				date = moment.format('EEEE');
			}
			else if (moment.inThisYear)
			{
				date = moment.format(dayMonth);
			}
			else
			{
				date = moment.format(longDate);
			}

			if (this.timeFormat)
			{
				return Loc.getMessage('TASKSMOBILE_DEADLINE_FRIENDLY_DATE_AT_TIME', {
					'#DATE#': date,
					'#TIME#': this.timeFormat,
				});
			}

			return date;
		}
	}

	module.exports = { DeadlineFriendlyDate };
});
