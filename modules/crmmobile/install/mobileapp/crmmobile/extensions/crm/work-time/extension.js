/**
 * @module crm/work-time
 */
jn.define('crm/work-time', (require, exports, module) => {
	const { Moment } = require('utils/date');

	const TIME_SEPARATOR = ':';

	const defaultCalendar = {
		WEEK_START: 'MO',
		TIME_FROM: '9:0',
		TIME_TO: '18:0',
		HOLIDAYS: [],
		DAY_OFF: [],
	};

	const currentCalendar = jnExtensionData
		? (jnExtensionData.get('crm:work-time') || defaultCalendar)
		: defaultCalendar;

	/**
	 * @class WorkTimeMoment
	 */
	class WorkTimeMoment
	{
		/**
		 * @param {Moment|null} moment
		 * @param {object|null} calendar
		 */
		constructor(moment, calendar = null)
		{
			this.moment = moment || new Moment();
			this.calendar = calendar || currentCalendar;
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
			const day = this.moment.format('E', 'en').toUpperCase().slice(0, 2);
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
			const startTime = this.calendar.TIME_FROM.split(TIME_SEPARATOR);
			const startTimeHours = Number(startTime[0]);
			const startTimeMinutes = startTime[1] ? Number(startTime[1]) : 0;

			moment.date.setHours(startTimeHours, startTimeMinutes, 0, 0);
			return new WorkTimeMoment(moment);
		}

		/**
		 * @public
		 * @return {WorkTimeMoment}
		 */
		getEndOfWorkingShift()
		{
			const moment = this.moment.clone();
			const endTime = this.calendar.TIME_TO.split(TIME_SEPARATOR);
			const endTimeHours = Number(endTime[0]);
			const endTimeMinutes = endTime[1] ? Number(endTime[1]) : 0;

			moment.date.setHours(endTimeHours, endTimeMinutes, 0, 0);
			return new WorkTimeMoment(moment);
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
			if (times < 1)
			{
				times = 1;
			}

			let workTimeMoment;
			let nextDay = this.moment.clone();
			let maxAttempts = 365;

			while (times > 0 && maxAttempts > 0)
			{
				nextDay = nextDay.addDays(1);
				workTimeMoment = new WorkTimeMoment(nextDay);
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
				: (new WorkTimeMoment(this.moment.addDays(1))).getStartOfWorkingShift();
		}
	}

	module.exports = { WorkTimeMoment };
});
