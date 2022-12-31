/**
 * @module crm/work-time/work-time-moment
 */
jn.define('crm/work-time/work-time-moment', (require, exports, module) => {

	const { Moment } = require('utils/date');

	/**
	 * @class WorkTimeMoment
	 */
	class WorkTimeMoment
	{
		/**
		 * @param {Moment|null} moment
		 * @param {{
		 *   WEEK_START: string,
		 *   TIME_FROM: string,
		 *   TIME_TO: string,
		 *   HOLIDAYS: string[],
		 *   DAY_OFF: string[]
		 * }} calendar
		 */
		constructor(moment, calendar)
		{
			this.moment = moment || new Moment();
			this.calendar = calendar;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isHoliday()
		{
			const day = this.moment.format('d.MM');
			return this.calendar.HOLIDAYS.includes(day);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isDayOff()
		{
			const day = this.moment.format('E', 'en').toUpperCase().substring(0, 2);
			return this.calendar.DAY_OFF.includes(day);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isWorkingDay()
		{
			return !this.isHoliday() && !this.isDayOff();
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isWorkingTime()
		{
			const startOfWorkingShift = this.getStartOfWorkingShift().moment;
			const endOfWorkingShift = this.getEndOfWorkingShift().moment;

			return this.moment.isAfter(startOfWorkingShift) && this.moment.isBefore(endOfWorkingShift);
		}

		/**
		 * @public
		 * @return {WorkTimeMoment}
		 */
		getStartOfWorkingShift()
		{
			const moment = this.moment.clone();
			const startTime = this.calendar.TIME_FROM.split('.');
			const startTimeHours = Number(startTime[0]);
			const startTimeMinutes = startTime[1] ? Number(startTime[1]) : 0;

			moment.date.setHours(startTimeHours, startTimeMinutes, 0, 0);
			return new WorkTimeMoment(moment, this.calendar);
		}

		/**
		 * @public
		 * @return {WorkTimeMoment}
		 */
		getEndOfWorkingShift()
		{
			const moment = this.moment.clone();
			const endTime = this.calendar.TIME_TO.split('.');
			const endTimeHours = Number(endTime[0]);
			const endTimeMinutes = endTime[1] ? Number(endTime[1]) : 0;

			moment.date.setHours(endTimeHours, endTimeMinutes, 0, 0);
			return new WorkTimeMoment(moment, this.calendar);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isBeforeWorkingShift()
		{
			return this.moment.isBefore(this.getStartOfWorkingShift().moment);
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isAfterWorkingShift()
		{
			return this.moment.isAfter(this.getEndOfWorkingShift().moment);
		}

		/**
		 * @param {number} times
		 * @return {WorkTimeMoment}
		 */
		getNextWorkingDay(times = 1)
		{
			times = Math.round(times);
			if (times < 1) times = 1;

			let workTimeMoment;
			let nextDay = this.moment.clone();
			let maxAttempts = 365;

			while (times > 0 && maxAttempts > 0)
			{
				nextDay = nextDay.addDays(1);
				workTimeMoment = new WorkTimeMoment(nextDay, this.calendar);
				if (workTimeMoment.isWorkingDay())
				{
					times--;
				}
				else
				{
					maxAttempts--;
				}
			}

			const nextWorkDayFound = (times === 0);

			return nextWorkDayFound
				? workTimeMoment
				: (new WorkTimeMoment(this.moment.addDays(1), this.calendar)).getStartOfWorkingShift();
		}
	}

	module.exports = { WorkTimeMoment };

});