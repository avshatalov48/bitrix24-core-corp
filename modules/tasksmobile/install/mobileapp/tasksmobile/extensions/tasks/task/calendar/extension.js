/**
 * @module tasks/task/calendar
 */
jn.define('tasks/task/calendar', (require, exports, module) => {
	const {Type} = require('type');

	class Calendar
	{
		constructor()
		{
			this.isSettingsLoading = false;
			this.isSettingsLoaded = false;

			this.workTime = [
				{
					start: {
						hours: 0,
						minutes: 0,
					},
					end: {
						hours: 24,
						minutes: 0,
					},
				},
			];
			this.weekends = {
				0: true,
				6: true,
			};
			this.holidays = {};

			void this.loadSettings();
		}

		loadSettings()
		{
			return new Promise((resolve) => {
				if (this.isSettingsLoading || this.isSettingsLoaded)
				{
					resolve();
					return;
				}
				this.isSettingsLoading = true;

				(new RequestExecutor('tasksmobile.Calendar.getSettings'))
					.call()
					.then((response) => {
						this.setSettings(response.result);
						resolve();
					})
				;
			});
		}

		setSettings(settings, adaptSettings = true)
		{
			if (adaptSettings)
			{
				settings = this.adaptSettings(settings);
			}

			this.setWorkTime(settings.workTime);
			this.setWeekends(settings.weekEnds);
			this.setHolidays(settings.holidays);

			this.isSettingsLoading = false;
			this.isSettingsLoaded = true;
		}

		adaptSettings(inputSettings)
		{
			if (!Type.isPlainObject(inputSettings))
			{
				return {};
			}

			return {
				workTime: this.adaptWorkTime(inputSettings),
				weekEnds: this.adaptWeekends(inputSettings),
				holidays: this.adaptHolidays(inputSettings),
			};
		}

		adaptWorkTime(inputSettings)
		{
			const pad = function(num) {
				num = num.toString();

				if (num.length === 0)
				{
					return '00';
				}
				if (num.length === 1)
				{
					return `0${num}`;
				}

				return num;
			};
			const workHours = inputSettings.HOURS;
			const workStart = workHours.START;
			const workEnd = workHours.END;

			return `${pad(workStart.H)}:${pad(workStart.M)}-${pad(workEnd.H)}:${pad(workEnd.M)}`;
		}

		adaptWeekends(inputSettings)
		{
			const weekends = inputSettings.WEEKEND;
			const dayMap = {
				MO: 1,
				TU: 2,
				WE: 3,
				TH: 4,
				FR: 5,
				SA: 6,
				SU: 0,
			};

			return weekends.reduce((result, day) => {
				result.push(dayMap[day]);
				return result;
			}, []);
		}

		adaptHolidays(inputSettings)
		{
			const holidays = [];

			for (const k in inputSettings.HOLIDAYS)
			{
				holidays.push({
					month: parseInt(inputSettings.HOLIDAYS[k].M) - 1,
					day: parseInt(inputSettings.HOLIDAYS[k].D),
				});
			}

			return holidays;
		}

		setWorkTime(workTime)
		{
			if (Type.isStringFilled(workTime))
			{
				workTime = [workTime];
			}

			if (!Type.isArray(workTime))
			{
				return;
			}

			const times = [];
			for (let i = 0; i < workTime.length; i++)
			{
				const time = workTime[i];
				const regex = /(\d\d):(\d\d)-(\d\d):(\d\d)/;
				const matches = regex.exec(time);
				if (!matches)
				{
					continue;
				}

				const startHours = parseInt(matches[1], 10);
				const startMinutes = parseInt(matches[2], 10);
				const endHours = parseInt(matches[3], 10);
				const endMinutes = parseInt(matches[4], 10);

				times.push({
					start: {
						hours: startHours,
						minutes: startMinutes,
						time: startHours * 60 + startMinutes,
					},
					end: {
						hours: endHours,
						minutes: endMinutes,
						time: endHours * 60 + endMinutes,
					},
				});
			}

			if (this.isWorkTimeCorrect(times))
			{
				this.workTime = times;
			}
		}

		setWeekends(weekends)
		{
			if (!Type.isArray(weekends))
			{
				return;
			}

			this.weekends = {};
			for (let i = 0; i < weekends.length; i++)
			{
				const day = weekends[i];
				if (day >= 0 && day <= 6)
				{
					this.weekends[day] = true;
				}
			}
		}

		setHolidays(holidays)
		{
			if (!Type.isArray(holidays))
			{
				return;
			}

			this.holidays = {};
			for (let i = 0; i < holidays.length; i++)
			{
				const holiday = holidays[i];
				const isValidMonth = (Type.isNumber(holiday.month) && holiday.month >= 0 && holiday.month <= 11);
				const isValidDay = (Type.isNumber(holiday.day) && holiday.day >= 0 && holiday.day <= 31);

				if (isValidMonth && isValidDay)
				{
					this.holidays[`${holiday.month}_${holiday.day}`] = true;
				}
			}
		}

		calculateStartDate(endDate, duration)
		{
			let newDate = null;

			this.processEachDay(null, endDate, false, (start, end) => {
				const interval = end - start;
				if (interval >= duration)
				{
					newDate = new Date(end.getTime() - duration);
					return false;
				}
				else
				{
					duration -= interval;
				}
			});

			return newDate;
		}

		calculateEndDate(startDate, duration)
		{
			let newDate = null;

			this.processEachDay(startDate, null, true, (start, end) => {
				const interval = end - start;
				if (interval >= duration)
				{
					newDate = new Date(start.getTime() + duration);
					return false;
				}
				else
				{
					duration -= interval;
				}
			});

			return newDate;
		}

		calculateDuration(startDate, endDate)
		{
			let duration = 0;

			if (startDate < endDate)
			{
				this.processEachDay(startDate, endDate, true, (start, end) => duration += end - start);
			}
			else
			{
				this.processEachDay(endDate, startDate, true, (start, end) => duration -= end - start);
			}

			return duration;
		}

		getClosestWorkTime(date, isForward)
		{
			const startDate = (isForward ? date : null);
			const endDate = (isForward ? null : date);

			this.processEachDay(startDate, endDate, isForward, (start, end) => {
				date = (isForward ? start : end);
				return false;
			});

			return new Date(date.getTime());
		}

		processEachDay(startDate, endDate, isForward, callback)
		{
			const currentDate = new Date((isForward ? startDate.getTime() : endDate.getTime()));
			const isEndless = (isForward ? !endDate : !startDate);

			while (isEndless || (isForward ? currentDate < endDate : currentDate > startDate))
			{
				const intervals = this.getWorkHours(currentDate);

				for (
					let i = (isForward ? 0 : intervals.length - 1);
					(isForward ? i < intervals.length : i >= 0);
					(isForward ? i++ : i--)
				)
				{
					const interval = intervals[i];
					const intervalStart = interval.startDate;
					const intervalEnd = interval.endDate;

					if (
						(endDate !== null && intervalStart > endDate)
						|| (startDate !== null && intervalEnd < startDate)
					)
					{
						continue;
					}

					const availableStart = (startDate !== null && intervalStart < startDate ? startDate : intervalStart);
					const availableEnd = (endDate !== null && intervalEnd > endDate ? endDate : intervalEnd);

					if (callback.call(this, availableStart, availableEnd) === false)
					{
						return false;
					}
				}

				currentDate.setUTCHours(0, 0, 0, 0);
				currentDate.setUTCDate(currentDate.getUTCDate() + (isForward ? 1 : -1));
			}
		}

		getWorkHours(date)
		{
			if (this.isWeekend(date) || this.isHoliday(date))
			{
				return [];
			}

			return this.getWorkIntervals(date);
		}

		getWorkIntervals(date)
		{
			const year = date.getFullYear();
			const month = date.getMonth();
			const day = date.getDate();
			const hours = [];

			for (let i = 0; i < this.workTime.length; i++)
			{
				const time = this.workTime[i];

				hours.push({
					startDate: new Date(year, month, day, time.start.hours, time.start.minutes),
					endDate: new Date(year, month, day, time.end.hours, time.end.minutes),
				});
			}

			return hours;
		}

		getWorkDayDuration()
		{
			const intervals = this.getWorkIntervals(new Date());
			let duration = 0;

			for (let i = 0; i < intervals.length; i++)
			{
				duration += intervals[i].endDate - intervals[i].startDate;
			}

			return duration;
		}

		isWorkTime(date)
		{
			if (this.isWeekend(date) || this.isHoliday(date))
			{
				return false;
			}

			let isWorkTime = null;

			this.processEachDay(date, null, true, (start, end) => {
				isWorkTime = (date >= start && date <= end);
				return false;
			});

			return isWorkTime;
		}

		isWeekend(date)
		{
			return !!this.weekends[date.getUTCDay()];
		}

		isHoliday(date)
		{
			return !!this.holidays[`${date.getUTCMonth()}_${date.getUTCDate()}`];
		}

		isWorkTimeCorrect(times)
		{
			if (!times.length)
			{
				return false;
			}

			times.sort((a, b) => (a.start.time - b.start.time));

			for (let i = 0; i < times.length; i++)
			{
				const time = times[i];

				if (
					time.start.hours < 0
					|| time.start.hours > 23
					|| time.end.hours < 0
					|| time.end.hours > 24
					|| time.start.minutes < 0
					|| time.start.minutes > 59
					|| time.end.minutes < 0
					|| time.end.minutes > 59
					|| time.start.time > time.end.time
				)
				{
					return false;
				}

				if (i > 0)
				{
					const prevTime = times[i - 1];

					if (prevTime.end.time > time.start.time)
					{
						return false;
					}
				}
			}

			return true;
		}
	}

	const CalendarSettings = new Calendar();

	module.exports = {Calendar, CalendarSettings};
});