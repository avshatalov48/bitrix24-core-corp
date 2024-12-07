/**
 * @module tasks/layout/fields/date-plan/dates-resolver
 */
jn.define('tasks/layout/fields/date-plan/dates-resolver', (require, exports, module) => {

	const { Loc } = require('loc');
	const { Type } = require('type');
	const { CalendarSettings } = require('tasks/task/calendar');
	const MAX_DURATION = 2_147_483_647;
	const ONE_DAY_IN_SECONDS = 86400;
	const ONE_HOUR_IN_SECONDS = 3600;
	const ONE_MINUTE_IN_SECONDS = 60;

	class DatesResolver
	{
		static get durationType()
		{
			return {
				days: 'days',
				hours: 'hours',
				minutes: 'minutes',
			};
		}

		static durationTypeNamePlural(count)
		{
			return {
				days: Loc.getMessagePlural('M_TASKS_DATE_PLAN_EDIT_FORM_DURATION_RULE_DAY', count),
				hours: Loc.getMessagePlural('M_TASKS_DATE_PLAN_EDIT_FORM_DURATION_RULE_HOUR', count),
				minutes: Loc.getMessagePlural('M_TASKS_DATE_PLAN_EDIT_FORM_DURATION_RULE_MINUTE', count),
			};
		}

		constructor(startDatePlan, endDatePlan, isMatchWorkTime = false, onFixDate = null)
		{
			this.calendar = CalendarSettings;

			this.isMatchWorkTime = isMatchWorkTime;

			this.startDatePlan = startDatePlan ?? null;
			this.endDatePlan = endDatePlan ?? null;
			this.duration = this.calculateDuration();
			this.onFixDate = onFixDate;
		}

		fixDate(date)
		{
			if (this.isMatchWorkTime && !this.calendar.isWorkTime(new Date(date * 1000)))
			{
				this.onFixDate?.();

				return Math.floor(this.calendar.getClosestWorkTime(new Date(date * 1000), true) / 1000);
			}

			return date;
		}

		calculateDuration()
		{
			if (this.startDatePlan === null || this.endDatePlan === null)
			{
				return 0;
			}

			if (this.isMatchWorkTime)
			{
				const duration = Math.floor(this.calendar.calculateDuration(
					new Date(this.startDatePlan * 1000),
					new Date(this.endDatePlan * 1000),
				) / 1000);
				if (duration > 0)
				{
					return duration;
				}
			}

			const duration = this.endDatePlan - this.startDatePlan;

			return duration >= 0 ? duration : null;
		}

		getStartDatePlan()
		{
			return this.startDatePlan;
		}

		getEndDatePlan()
		{
			return this.endDatePlan;
		}

		getDuration()
		{
			return this.duration;
		}

		get durationType()
		{
			return this.getDurationTypeByDuration(this.duration);
		}

		get durationValue()
		{
			return this.getDurationValueByType(this.durationType);
		}

		setIsMatchWorkTime(isMatchWorkTime)
		{
			this.isMatchWorkTime = isMatchWorkTime;
			if (this.isMatchWorkTime)
			{
				const oldDurationValue = this.durationValue;
				const oldDurationType = this.durationType;
				this.updateStartDatePlan(this.startDatePlan);
				if (this.endDatePlan)
				{
					this.updateDurationType(oldDurationType);
					this.updateDurationValue(oldDurationValue);
				}
			}
			else
			{
				this.endDatePlan = this.startDatePlan + this.durationValue * this.getMultiplier(this.durationType);
			}
		}

		updateStartDatePlan(newStartDatePlan)
		{
			if (newStartDatePlan && (!Type.isNumber(newStartDatePlan)))
			{
				return;
			}

			this.startDatePlan = newStartDatePlan;

			if (!Type.isNumber(newStartDatePlan))
			{
				this.duration = 0;

				return;
			}

			if (
				Type.isNumber(this.endDatePlan)
				&& (newStartDatePlan > this.endDatePlan || newStartDatePlan === this.endDatePlan)
			)
			{
				this.endDatePlan = newStartDatePlan + this.duration || ONE_DAY_IN_SECONDS;
			}

			if (this.isMatchWorkTime && newStartDatePlan)
			{
				this.startDatePlan = this.fixDate(newStartDatePlan);
				this.endDatePlan = this.endDatePlan ? this.fixDate(this.endDatePlan) : null;
				if (this.startDatePlan === this.endDatePlan)
				{
					this.endDatePlan = this.fixDate(this.endDatePlan + this.duration || ONE_DAY_IN_SECONDS);
				}
			}

			this.duration = this.calculateDuration();
		}

		updateEndDatePlan(newEndDatePlan)
		{
			if (newEndDatePlan && !Type.isNumber(newEndDatePlan))
			{
				return;
			}

			this.endDatePlan = newEndDatePlan;

			if (!Type.isNumber(newEndDatePlan))
			{
				this.duration = 0;

				return;
			}

			if (
				Type.isNumber(this.startDatePlan)
				&& (newEndDatePlan < this.startDatePlan || newEndDatePlan === this.startDatePlan))
			{
				this.startDatePlan = newEndDatePlan - ONE_DAY_IN_SECONDS;
			}

			if (this.isMatchWorkTime && newEndDatePlan)
			{
				this.endDatePlan = this.fixDate(newEndDatePlan);
				this.startDatePlan = this.startDatePlan ? this.fixDate(this.startDatePlan) : null;
				if (this.startDatePlan === this.endDatePlan)
				{
					this.startDatePlan = this.fixDate(this.startDatePlan - ONE_DAY_IN_SECONDS);
				}
			}

			this.duration = this.calculateDuration();
		}

		updateDuration(newDuration)
		{
			if (!Type.isNumber(newDuration))
			{
				return;
			}

			if (newDuration < MAX_DURATION && newDuration >= 0)
			{
				this.duration = newDuration;
				this.endDatePlan = this.startDatePlan === null ? null : this.startDatePlan + newDuration;
			}
		}

		updateDurationType(newDurationType)
		{
			const validDurationTypes = Object.values(this.constructor.durationType);
			if (!newDurationType || !validDurationTypes.includes(newDurationType))
			{
				return;
			}

			if (!this.endDatePlan || !this.startDatePlan)
			{
				return;
			}

			const newDuration = Number(this.durationValue) * this.getMultiplier(newDurationType);
			if (newDuration < MAX_DURATION)
			{
				this.duration = newDuration;

				this.endDatePlan = this.startDatePlan + this.duration;

				if (this.isMatchWorkTime && !this.calendar.isWorkTime(new Date(this.endDatePlan * 1000)))
				{
					this.endDatePlan = Math.floor(this.calendar.calculateEndDate(new Date(this.startDatePlan * 1000), this.duration * 1000) / 1000);
					this.onFixDate?.();
				}
			}
		}

		updateDurationValue(newDurationValue)
		{
			if (!newDurationValue)
			{
				this.duration = null;
				this.endDatePlan = null;

				return;
			}

			const newDuration = Number(newDurationValue) * this.getMultiplier(this.durationType);
			if (newDuration < MAX_DURATION)
			{
				this.duration = newDuration;

				if (this.startDatePlan === null)
				{
					const now = new Date();
					now.setSeconds(0);
					this.startDatePlan = Math.floor(now / 1000);
				}

				let newEndDatePlan = this.startDatePlan + this.duration;

				if (this.isMatchWorkTime)
				{
					newEndDatePlan = Math.floor(this.calendar.calculateEndDate(new Date(this.startDatePlan * 1000), this.duration * 1000) / 1000);
					this.onFixDate?.();
				}
				this.endDatePlan = newEndDatePlan;
			}
		}

		getDurationTypeByDuration(duration)
		{
			if (duration === null)
			{
				return DatesResolver.durationType.days;
			}

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

		getMultiplier(durationType)
		{
			switch (durationType)
			{
				case DatesResolver.durationType.days:
					return this.isMatchWorkTime ? Math.floor(this.calendar.getWorkDayDuration() / 1000) : ONE_DAY_IN_SECONDS;

				case DatesResolver.durationType.hours:
					return ONE_HOUR_IN_SECONDS;

				case DatesResolver.durationType.minutes:
					return ONE_MINUTE_IN_SECONDS;

				default:
					return 1;
			}
		}

		getDurationValueByType(durationType)
		{
			if (this.duration === null)
			{
				return null;
			}

			return Math.floor(this.duration / this.getMultiplier(durationType));
		}
	}

	module.exports = { DatesResolver };
});
