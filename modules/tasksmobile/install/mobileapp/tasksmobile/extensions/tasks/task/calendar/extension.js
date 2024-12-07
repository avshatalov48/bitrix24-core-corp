/**
 * @module tasks/task/calendar
 */
jn.define('tasks/task/calendar', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Type } = require('type');
	const { MemoryStorage } = require('native/memorystore');
	const { SubstituteStorage } = require('tasks/task/calendar/src/substitute-storage');
	const { Feature } = require('feature');

	const STORAGE_NAME = 'calendarSettingsStore';
	const store = Feature.isMemoryStorageSupported()
		? new MemoryStorage(STORAGE_NAME)
		: new SubstituteStorage(STORAGE_NAME);

	class Calendar
	{
		constructor()
		{
			this.settings = {};

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
			this.serverOffset = -(new Date()).getTimezoneOffset() * 60;
			this.clientOffset = -(new Date()).getTimezoneOffset() * 60;

			void this.loadSettings();
		}

		async loadSettings()
		{
			const executor = new RunActionExecutor('tasksmobile.Calendar.getSettings');

			const cachedData = await this.#getCachedData(executor);
			if (cachedData)
			{
				this.setSettings(cachedData);

				return cachedData;
			}

			const isSettingsLoading = await store.get('isSettingsLoading');
			if (isSettingsLoading)
			{
				return this.#waitForDataFromCache(executor);
			}

			return this.#loadDataFromBackend(executor);
		}

		async #getCachedData(executor)
		{
			if (!executor.getCache().isExpired())
			{
				return store.get('data');
			}

			return null;
		}

		async #waitForDataFromCache(executor)
		{
			return new Promise((resolve) => {
				const interval = setInterval(async () => {
					const data = await store.get('data');

					if (data)
					{
						clearInterval(interval);
						resolve(data);
					}

					const isSettingsLoading = await store.get('isSettingsLoading');
					if (!isSettingsLoading)
					{
						clearInterval(interval);
						const repeatedRequest = await this.#loadDataFromBackend(executor);
						resolve(repeatedRequest);
					}
				}, 50);
			});
		}

		async #loadDataFromBackend(executor)
		{
			await store.set('isSettingsLoading', true);

			return new Promise((resolve, reject) => {
				executor
					.setCacheHandler(async (response) => {
						await this.handleResponse(response, resolve);
					})
					.setHandler(async (response) => {
						if (response.status !== 'success')
						{
							await store.set('isSettingsLoading', false);
							reject();

							return;
						}
						await this.handleResponse(response, resolve);
					})
					.setSkipRequestIfCacheExists()
					.setCacheTtl(3600)
					.call(true);
			});
		}

		async handleResponse(response, resolve)
		{
			await store.set('data', response.data);
			this.setSettings(response.data);
			await store.set('isSettingsLoading', false);
			resolve(response.data);
		}

		setSettings(settings, adaptSettings = true)
		{
			this.settings = settings;

			const adaptedSettings = (adaptSettings ? Calendar.adaptSettings(settings) : settings);

			this.setWorkTime(adaptedSettings.workTime);
			this.setWeekends(adaptedSettings.weekEnds);
			this.setHolidays(adaptedSettings.holidays);
			this.setServerOffset(adaptedSettings.serverOffset);
		}

		getSettings()
		{
			return this.settings;
		}

		static adaptSettings(inputSettings)
		{
			if (!Type.isPlainObject(inputSettings))
			{
				return {};
			}

			return {
				workTime: Calendar.adaptWorkTime(inputSettings),
				weekEnds: Calendar.adaptWeekends(inputSettings),
				holidays: Calendar.adaptHolidays(inputSettings),
				serverOffset: inputSettings.SERVER_OFFSET,
			};
		}

		static adaptWorkTime(inputSettings)
		{
			const pad = function(num) {
				const numLength = num.toString().length;

				if (numLength === 0)
				{
					return '00';
				}

				if (numLength === 1)
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

		static adaptWeekends(inputSettings)
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

		static adaptHolidays(inputSettings)
		{
			const holidays = [];

			inputSettings.HOLIDAYS.forEach(({ M, D }) => {
				holidays.push({
					month: parseInt(M, 10) - 1,
					day: parseInt(D, 10),
				});
			});

			return holidays;
		}

		setWorkTime(workTime)
		{
			let arrayedWorkTime = workTime;

			if (Type.isStringFilled(arrayedWorkTime))
			{
				arrayedWorkTime = [arrayedWorkTime];
			}

			if (!Type.isArray(arrayedWorkTime))
			{
				return;
			}

			const times = [];
			for (const time of arrayedWorkTime)
			{
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
			for (const day of weekends)
			{
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
			for (const holiday of holidays)
			{
				const isValidMonth = (Type.isNumber(holiday.month) && holiday.month >= 0 && holiday.month <= 11);
				const isValidDay = (Type.isNumber(holiday.day) && holiday.day >= 0 && holiday.day <= 31);

				if (isValidMonth && isValidDay)
				{
					this.holidays[`${holiday.month}_${holiday.day}`] = true;
				}
			}
		}

		setServerOffset(serverOffset)
		{
			this.serverOffset = serverOffset;
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

				// eslint-disable-next-line no-param-reassign
				duration -= interval;

				return true;
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

				// eslint-disable-next-line no-param-reassign
				duration -= interval;

				return true;
			});

			return newDate;
		}

		calculateDuration(startDate, endDate)
		{
			let duration = 0;

			if (startDate < endDate)
			{
				this.processEachDay(startDate, endDate, true, (start, end) => {
					duration += end - start;
				});
			}
			else
			{
				this.processEachDay(endDate, startDate, true, (start, end) => {
					duration -= end - start;
				});
			}

			return duration;
		}

		getClosestWorkTime(date, isForward)
		{
			const startDate = (isForward ? date : null);
			const endDate = (isForward ? null : date);

			this.processEachDay(startDate, endDate, isForward, (start, end) => {
				// eslint-disable-next-line no-param-reassign
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

			return true;
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

			for (const interval of intervals)
			{
				duration += interval.endDate - interval.startDate;
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
			return Boolean(this.weekends[date.getUTCDay()]);
		}

		isHoliday(date)
		{
			return Boolean(this.holidays[`${date.getUTCMonth()}_${date.getUTCDate()}`]);
		}

		isWeekendInLocal(date)
		{
			return Boolean(this.weekends[date.getDay()]);
		}

		isHolidayInLocal(date)
		{
			return Boolean(this.holidays[`${date.getMonth()}_${date.getDate()}`]);
		}

		isWorkTimeCorrect(times)
		{
			if (times.length === 0)
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

	module.exports = { Calendar, CalendarSettings };
});
