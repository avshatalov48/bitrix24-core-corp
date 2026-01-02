/**
 * @module calendar/statemanager/redux/slices/events/recursion-parser
 */
jn.define('calendar/statemanager/redux/slices/events/recursion-parser', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('calendar/date-helper');
	const { RecursionFrequency, BooleanParams } = require('calendar/enums');

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

			const fullDayOffset = event.fullDay ? DateHelper.timezoneOffset : 0;
			let from = new Date(event.dateFromTs - fullDayOffset);
			const to = new Date(Math.min(toLimit, until * 1000));

			const eventTimezone = event.timezone;
			const currentTzUseDST = this.doesTimezoneUseDST();
			const intlEnabled = typeof Intl !== 'undefined' && Intl.DateTimeFormat;
			const originalFrom = new Date(event.dateFromTs - fullDayOffset);
			const originalFromOffset = intlEnabled ? this.getTimezoneOffset(originalFrom, eventTimezone) : 0;
			const isDaylightSavingTimezone = event?.isDaylightSavingTimezone || '';

			to.setHours(from.getHours(), from.getMinutes());

			const fromYear = from.getFullYear();
			const fromMonth = from.getMonth();
			const fromDate = from.getDate();
			const fromHour = from.getHours();
			const fromMinute = from.getMinutes();

			const fixedFrequencyTypes = new Set([
				RecursionFrequency.DAILY,
				RecursionFrequency.MONTHLY,
				RecursionFrequency.YEARLY,
			]);

			let count = 0;

			while (from <= to)
			{
				if (rrule.COUNT > 0 && count >= rrule.COUNT)
				{
					break;
				}

				const exclude = event.excludedDates.includes(DateHelper.formatDate(from));
				const include = !exclude
					&& from.getTime() >= fromLimit
					&& from.getTime() + event.eventLength <= toLimit
				;

				if (rrule.FREQ === RecursionFrequency.WEEKLY)
				{
					const weekDay = this.getWeekDayByInd(from.getDay());

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

					this.handleDaylightSavingTime({
						from,
						originalFrom,
						originalFromOffset,
						isDaylightSavingTimezone,
						currentTzUseDST,
						eventTimezone,
						intlEnabled,
					});
				}

				if (fixedFrequencyTypes.has(rrule.FREQ))
				{
					if (include)
					{
						timestamps.push(from.getTime() + fullDayOffset);
					}
					count++;

					// eslint-disable-next-line default-case
					switch (rrule.FREQ)
					{
						case RecursionFrequency.DAILY:
							from = new Date(fromYear, fromMonth, fromDate + count * rrule.INTERVAL, fromHour, fromMinute, 0, 0);
							break;
						case RecursionFrequency.MONTHLY:
							from = new Date(fromYear, fromMonth + count * rrule.INTERVAL, fromDate, fromHour, fromMinute, 0, 0);
							break;
						case RecursionFrequency.YEARLY:
							from = new Date(fromYear + count * rrule.INTERVAL, fromMonth, fromDate, fromHour, fromMinute, 0, 0);
							break;
					}

					this.handleDaylightSavingTime({
						from,
						originalFrom,
						originalFromOffset,
						isDaylightSavingTimezone,
						currentTzUseDST,
						eventTimezone,
						intlEnabled,
					});
				}
			}

			return timestamps;
		}

		static handleDaylightSavingTime({
			from,
			originalFrom,
			originalFromOffset,
			isDaylightSavingTimezone,
			currentTzUseDST,
			eventTimezone,
			intlEnabled,
		})
		{
			if (
				currentTzUseDST
				&& isDaylightSavingTimezone === BooleanParams.NO
				&& originalFrom.getTimezoneOffset() !== from.getTimezoneOffset()
			)
			{
				const diff = (from.getTimezoneOffset() - originalFrom.getTimezoneOffset()) * 60000;
				from.setTime(from.getTime() - diff);
			}
			else if (
				intlEnabled
				&& !currentTzUseDST
				&& isDaylightSavingTimezone === BooleanParams.YES
			)
			{
				const diff = this.getDaylightSavingShift(eventTimezone, from, originalFromOffset);
				if (diff !== 0)
				{
					from.setTime(from.getTime() - diff);
				}
			}
		}

		static doesTimezoneUseDST()
		{
			const winterTime = new Date(2025, 0, 1);
			const summerTime = new Date(2025, 6, 1);

			return winterTime.getTimezoneOffset() !== summerTime.getTimezoneOffset();
		}

		static getDaylightSavingShift(timeZone, from, originalFromOffset)
		{
			const fromOffset = this.getTimezoneOffset(from, timeZone);

			return fromOffset - originalFromOffset;
		}

		static getTimezoneOffset(date, timeZone)
		{
			if (!timeZone)
			{
				return 0;
			}

			try
			{
				const utcDate = new Date(date.toLocaleString('en-US', { timeZone: 'UTC' }));
				const tzDate = new Date(date.toLocaleString('en-US', { timeZone }));

				return tzDate.getTime() - utcDate.getTime();
			}
			catch
			{
				return 0;
			}
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
