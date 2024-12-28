/**
 * @module calendar/statemanager/redux/slices/events/recursion-parser
 */
jn.define('calendar/statemanager/redux/slices/events/recursion-parser', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('calendar/date-helper');

	class RecursionParser
	{
		/**
		 * @param {EventReduxModel} event
		 * @param {{fromLimit: number, toLimit: number}} limits
		 * @return {EventReduxModel[]}
		 */
		static parseRecursion(event, limits)
		{
			if (!event)
			{
				return [];
			}

			if (!event.recurrenceRule)
			{
				return [event];
			}

			const repetitions = this.parseTimestamps(event, limits);

			return repetitions.map((from) => ({
				...event,
				dateFromTs: from,
				dateToTs: from + event.eventLength * 1000,
			}));
		}

		/**
		 * @private
		 * @param {EventReduxModel} event
		 * @param {{fromLimit: Date, toLimit: Date}} limits
		 * @return {number[]}
		 */
		static parseTimestamps(event, { fromLimit, toLimit })
		{
			const timestamps = [];

			const rrule = event.recurrenceRule;
			const until = rrule.UNTIL_TS || toLimit / 1000;

			const fullDayOffset = event.isFullDay ? DateHelper.timezoneOffset : 0;
			let from = new Date(event.dateFromTs - fullDayOffset);
			const to = new Date(Math.min(toLimit, until * 1000));
			to.setHours(from.getHours(), from.getMinutes());

			const fromYear = from.getFullYear();
			const fromMonth = from.getMonth();
			const fromDate = from.getDate();
			const fromHour = from.getHours();
			const fromMinute = from.getMinutes();

			let count = 0;

			while (from <= to)
			{
				if (rrule.COUNT > 0 && count >= rrule.COUNT)
				{
					break;
				}

				const exclude = event.excludedDates.includes(DateHelper.formatDate(new Date(from)));
				const include = !exclude
					&& from.getTime() >= fromLimit
					&& from.getTime() + event.eventLength <= toLimit
				;

				if (rrule.FREQ === 'WEEKLY')
				{
					const weekDay = this.getWeekDayByInd(new Date(from).getDay());

					if (Type.isStringFilled(rrule.BYDAY[weekDay]))
					{
						if (include)
						{
							timestamps.push(from.getTime() + fullDayOffset);
						}
						count++;
					}

					const skipWeek = (rrule.INTERVAL - 1) * 7 + 1;
					const delta = weekDay === 'SU' ? skipWeek : 1;

					from = new Date(from.getFullYear(), from.getMonth(), from.getDate() + delta, fromHour, fromMinute);
				}

				if (['DAILY', 'MONTHLY', 'YEARLY'].includes(rrule.FREQ))
				{
					if (include)
					{
						timestamps.push(from.getTime() + fullDayOffset);
					}
					count++;

					// eslint-disable-next-line default-case
					switch (rrule.FREQ)
					{
						case 'DAILY':
							from = new Date(fromYear, fromMonth, fromDate + count * rrule.INTERVAL, fromHour, fromMinute, 0, 0);
							break;
						case 'MONTHLY':
							from = new Date(fromYear, fromMonth + count * rrule.INTERVAL, fromDate, fromHour, fromMinute, 0, 0);
							break;
						case 'YEARLY':
							from = new Date(fromYear + count * rrule.INTERVAL, fromMonth, fromDate, fromHour, fromMinute, 0, 0);
							break;
					}
				}
			}

			return timestamps;
		}

		/**
		 * @private
		 * @param {number} index
		 * @return {string}
		 */
		static getWeekDayByInd(index)
		{
			return ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'][index];
		}
	}

	module.exports = { RecursionParser };
});
