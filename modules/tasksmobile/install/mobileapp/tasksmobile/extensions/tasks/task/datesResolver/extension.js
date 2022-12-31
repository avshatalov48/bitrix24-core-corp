/**
 * @module tasks/task/datesResolver
 */
jn.define('tasks/task/datesResolver', (require, exports, module) => {
	const {CalendarSettings} = require('tasks/task/calendar');
	const {EventEmitter} = require('event-emitter');
	const {Type} = require('type');

	class DatesResolver
	{
		static get durationType()
		{
			return {
				days: 'days',
				hours: 'hours',
				minutes: 'mins',
			};
		}

		constructor(data)
		{
			this.calendar = CalendarSettings;

			this.id = data.id;
			this.guid = data.guid;
			this.isMatchWorkTime = data.isMatchWorkTime;
			this.deadline = (data.deadline ? data.deadline / 1000 : 0);
			this.startDateStamp = (data.startDatePlan ? data.startDatePlan / 1000 : 0);
			this.endDateStamp = (data.endDatePlan ? data.endDatePlan / 1000 : 0);
			this.durationByType = 0;
			this.durationInSeconds = 0;
			this.durationType = DatesResolver.durationType.days;

			this.isSolving = false;
			this.maxDuration = 2147483647;

			this.eventEmitter = EventEmitter.createWithUid(this.guid);
			this.setInitialDuration();
		}

		setData(data)
		{
			this.isMatchWorkTime = data.isMatchWorkTime;
			this.deadline = (data.deadline ? data.deadline / 1000 : 0);
			this.startDateStamp = (data.startDatePlan ? data.startDatePlan / 1000 : 0);
			this.endDateStamp = (data.endDatePlan ? data.endDatePlan / 1000 : 0);

			this.setInitialDuration();
		}

		setInitialDuration()
		{
			let duration = 0;

			if (this.startDateStamp && this.endDateStamp)
			{
				const startDate = (this.startDateStamp ? new Date(this.startDateStamp * 1000) : null);
				const endDate = (this.endDateStamp ? new Date(this.endDateStamp * 1000) : null);

				if (startDate && endDate && startDate < endDate)
				{
					duration = this.calculateDuration(startDate, endDate) / 1000;
				}
			}

			this.setDurationFromSeconds(duration);
		}

		on(event, callback)
		{
			this.eventEmitter.on(event, callback);

			return this;
		}

		updateDeadline(deadlineStamp, shouldSave = false)
		{
			if (!this.checkNoWorkDays())
			{
				this.solveDeadline(deadlineStamp, shouldSave);
			}
		}

		updateStartDate(startDateStamp)
		{
			if (!this.checkNoWorkDays())
			{
				const startDate = new Date(startDateStamp * 1000);
				startDate.setSeconds(0);

				this.setStartDate(startDate);
				this.solveTriangle(true, false, false);
			}
		}

		updateEndDate(endDateStamp)
		{
			if (!this.checkNoWorkDays())
			{
				const endDate = new Date(endDateStamp * 1000);
				endDate.setSeconds(0);

				this.setEndDate(endDate);
				this.solveTriangle(false, true, false);
			}
		}

		updateDuration(duration)
		{
			if (!this.checkNoWorkDays())
			{
				this.durationByType = duration;
				this.durationInSeconds = this.getDurationInSeconds(this.durationType, this.durationByType);

				this.solveTriangle(false, false, true);
			}
		}

		updateDurationType(durationType)
		{
			if (!this.checkNoWorkDays())
			{
				this.durationType = durationType;
				this.durationInSeconds = this.getDurationInSeconds(this.durationType, this.durationByType);

				this.solveTriangle(false, false, true);
			}
		}

		checkNoWorkDays()
		{
			if (!this.isMatchWorkTime)
			{
				return false;
			}

			let result = true;
			const weekends = this.calendar.weekends;
			const dayNumbers = [0, 1, 2, 3, 4, 5, 6];

			dayNumbers.forEach((dayNumber) => {
				if (!(dayNumber in weekends))
				{
					result = false;
				}
			});

			return result;
		}

		setDeadline(date)
		{
			this.deadline = (date.getTime() / 1000);
		}

		setStartDate(date)
		{
			this.startDateStamp = (date.getTime() / 1000);
		}

		setEndDate(date)
		{
			this.endDateStamp = (date.getTime() / 1000);
		}

		setIsMatchWorkTime(isMatchWorkTime)
		{
			this.isMatchWorkTime = isMatchWorkTime;

			if (this.checkNoWorkDays())
			{
				return;
			}

			this.recalculateDuration();

			if (this.startDateStamp)
			{
				this.solveTriangle(true, false, false);
			}
			else if (this.endDateStamp)
			{
				this.solveTriangle(false, true, false);
			}

			if (this.deadline)
			{
				this.solveDeadline(this.deadline);
			}
		}

		fixDeadline(date)
		{
			if (this.isMatchWorkTime && !this.calendar.isWorkTime(date))
			{
				date = this.calendar.getClosestWorkTime(date, true);
			}

			return date;
		}

		fixStartDate(date)
		{
			if (this.isMatchWorkTime && !this.calendar.isWorkTime(date))
			{
				date = this.calendar.getClosestWorkTime(date, true);
			}

			return date;
		}

		fixEndDate(date)
		{
			if (this.isMatchWorkTime && !this.calendar.isWorkTime(date))
			{
				date = this.calendar.getClosestWorkTime(date, false);
			}

			return date;
		}

		calculateStartDate(endDate, duration)
		{
			if (this.isMatchWorkTime)
			{
				return this.calendar.calculateStartDate(endDate, duration);
			}
			else
			{
				return new Date(endDate.getTime() - duration);
			}
		}

		calculateEndDate(startDate, duration)
		{
			if (this.isMatchWorkTime)
			{
				return this.calendar.calculateEndDate(startDate, duration);
			}
			else
			{
				return new Date(startDate.getTime() + duration);
			}
		}

		calculateDuration(startDate, endDate)
		{
			if (this.isMatchWorkTime)
			{
				const duration = this.calendar.calculateDuration(startDate, endDate);
				if (duration > 0)
				{
					return duration;
				}
			}

			return (endDate - startDate);
		}

		solveDeadline(deadlineStamp, shouldSave = false)
		{
			const oldDeadline = this.deadline;
			const newDeadline = new Date(deadlineStamp * 1000);
			newDeadline.setSeconds(0);

			this.setDeadline(this.fixDeadline(newDeadline));

			if (this.deadline !== oldDeadline)
			{
				this.eventEmitter.emit('datesResolver:deadlineChanged', [this.deadline, shouldSave]);
			}
		}

		solveTriangle(startDateChanged, endDateChanged, durationChanged)
		{
			if (durationChanged)
			{
				this.solveOnDurationChange();
			}
			else if (startDateChanged)
			{
				this.solveOnStartDateChange();
			}
			else if (endDateChanged)
			{
				this.solveOnEndDateChange();
			}

			this.eventEmitter.emit('datesResolver:datesChanged', [this.startDateStamp, this.endDateStamp]);
		}

		solveOnDurationChange()
		{
			if (this.isSolving)
			{
				return;
			}
			this.isSolving = true;

			const durationMs = this.durationInSeconds * 1000;
			const startDate = (this.startDateStamp ? new Date(this.startDateStamp * 1000) : null);
			const endDate = (this.endDateStamp ? new Date(this.endDateStamp * 1000) : null);

			if (this.durationInSeconds && !this.isMaxDurationReached(durationMs))
			{
				if (startDate)
				{
					this.setEndDate(this.calculateEndDate(startDate, durationMs));
				}
				else if (endDate)
				{
					this.setStartDate(this.calculateStartDate(endDate, durationMs));
				}
			}
			this.isSolving = false;
		}

		solveOnStartDateChange()
		{
			if (this.isSolving)
			{
				return;
			}
			this.isSolving = true;

			const defaultDuration = this.getMultiplier(DatesResolver.durationType.days);
			const defaultDurationMs = defaultDuration * 1000;
			let duration = this.durationInSeconds;
			let durationMs = duration * 1000;
			let startDate = (this.startDateStamp ? new Date(this.startDateStamp * 1000) : null);
			let endDate = (this.endDateStamp ? new Date(this.endDateStamp * 1000) : null);

			if (startDate)
			{
				const fixedStartDate = this.fixStartDate(startDate);
				if (fixedStartDate.getTime() !== startDate.getTime())
				{
					startDate = fixedStartDate;
					this.setStartDate(fixedStartDate);
				}

				if (duration)
				{
					if (!this.isMaxDurationReached(durationMs))
					{
						this.setEndDate(this.calculateEndDate(startDate, durationMs));
					}
				}
				else if (endDate)
				{
					const fixedEndDate = this.fixEndDate(endDate);
					endDate = fixedEndDate;
					this.setEndDate(fixedEndDate);

					if (startDate < endDate)
					{
						durationMs = this.calculateDuration(startDate, endDate);
						duration = durationMs / 1000;

						this.setDurationFromSeconds(duration);
						this.isMaxDurationReached(durationMs);
					}
					else
					{
						this.setDurationFromSeconds(defaultDuration);
						this.setEndDate(this.calculateEndDate(startDate, defaultDurationMs));
					}
				}
			}
			this.isSolving = false;
		}

		solveOnEndDateChange()
		{
			if (this.isSolving)
			{
				return;
			}
			this.isSolving = true;

			const defaultDuration = this.getMultiplier(DatesResolver.durationType.days);
			const defaultDurationMs = defaultDuration * 1000;
			let duration = this.durationInSeconds;
			let durationMs = duration * 1000;
			const startDate = (this.startDateStamp ? new Date(this.startDateStamp * 1000) : null);
			let endDate = (this.endDateStamp ? new Date(this.endDateStamp * 1000) : null);

			if (endDate)
			{
				const fixedEndDate = this.fixEndDate(endDate);
				if (fixedEndDate.getTime() !== endDate.getTime())
				{
					endDate = fixedEndDate;
					this.setEndDate(fixedEndDate);
				}

				if (startDate)
				{
					if (startDate < endDate)
					{
						durationMs = this.calculateDuration(startDate, endDate);
						duration = durationMs / 1000;

						this.setDurationFromSeconds(duration);
						this.isMaxDurationReached(durationMs);
					}
					else
					{
						this.setDurationFromSeconds(defaultDuration);
						this.setStartDate(this.calculateStartDate(endDate, defaultDurationMs));
					}
				}
				else if (duration)
				{
					this.setDurationFromSeconds(duration);
					if (!this.isMaxDurationReached(durationMs))
					{
						this.setStartDate(this.calculateStartDate(endDate, durationMs));
					}
				}
			}
			this.isSolving = false;
		}

		isMaxDurationReached(duration)
		{
			return (!Type.isNumber(duration) || (duration / 1000 >= this.maxDuration));
		}

		getMultiplier(durationType)
		{
			switch (durationType)
			{
				case DatesResolver.durationType.days:
					return (this.isMatchWorkTime ? this.getWorkDayDuration() : 86400);

				case DatesResolver.durationType.hours:
					return 3600;

				case DatesResolver.durationType.minutes:
					return 60;

				default:
					return 1;
			}
		}

		getWorkDayDuration()
		{
			if (!this.workDayDuration)
			{
				const duration = this.calendar.getWorkDayDuration();
				this.workDayDuration = (duration > 0 ? duration / 1000 : 86400);
			}

			return this.workDayDuration;
		}

		setDurationFromSeconds(durationInSeconds)
		{
			if (!Type.isNumber(durationInSeconds))
			{
				return;
			}

			this.durationInSeconds = durationInSeconds;

			if (durationInSeconds)
			{
				const durationType = this.getDurationTypeByDuration(durationInSeconds);
				if (durationType !== this.durationType)
				{
					this.durationType = durationType;
				}
			}

			this.durationByType = this.getDurationByType();
		}

		getDurationTypeByDuration(duration)
		{
			for (let i = 0; i < Object.values(DatesResolver.durationType).length; i++)
			{
				const durationType = Object.values(DatesResolver.durationType)[i];
				const durationInDurationType = this.getMultiplier(durationType);

				if (duration % durationInDurationType === 0)
				{
					return durationType;
				}
			}

			return DatesResolver.durationType.minutes;
		}

		getDurationInSeconds(durationType, durationByType)
		{
			durationByType = parseInt(durationByType, 10);

			if (Type.isNumber(durationByType) && durationByType > 0)
			{
				return (this.getMultiplier(durationType) * durationByType);
			}

			return 0;
		}

		getDurationByType()
		{
			const value = Math.floor(this.durationInSeconds / this.getMultiplier(this.durationType));

			if (value > 0)
			{
				return value;
			}

			return 0;
		}

		recalculateDuration()
		{
			this.durationInSeconds = this.getDurationInSeconds(this.durationType, this.durationByType);
		}
	}

	module.exports = {DatesResolver};
});