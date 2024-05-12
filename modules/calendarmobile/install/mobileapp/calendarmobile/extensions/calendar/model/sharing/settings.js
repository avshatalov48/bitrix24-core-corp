/**
 * @module calendar/model/sharing/settings
 */
jn.define('calendar/model/sharing/settings', (require, exports, module) => {
	const { Rule } = require('calendar/model/sharing/rule');
	const { Analytics } = require('calendar/sharing/analytics');

	/**
	 * @class Settings
	 */
	class Settings
	{
		constructor(settings)
		{
			const weekStart = BX.prop.getString(settings, 'weekStart', 'MO');
			const workTimeStart = BX.prop.getNumber(settings, 'workTimeStart', 9);
			const workTimeEnd = BX.prop.getNumber(settings, 'workTimeEnd', 19);
			const weekHolidays = BX.prop.getArray(settings, 'weekHolidays', ['SA', 'SU']);
			const rule = BX.prop.getObject(settings, 'rule', {});

			this.weekStart = this.getIndByWeekDay(weekStart);
			this.workTimeStart = Math.floor(workTimeStart) + 5 * (workTimeStart - Math.floor(workTimeStart)) / 3;
			this.workTimeEnd = Math.floor(workTimeEnd) + 5 * (workTimeEnd - Math.floor(workTimeEnd)) / 3;
			this.workDays = this.getWorkingDays(weekHolidays);
			this.rule = new Rule(rule, this.weekStart);
		}

		isDefaultRule()
		{
			return !this.isDifferentFrom(this.getDefaultRule());
		}

		isDifferentFrom(anotherRule)
		{
			return !this.objectsEqual(anotherRule, this.getRuleArray());
		}

		getChanges()
		{
			const defaultRule = this.getDefaultRule();

			const sizeChanged = this.rule.slotSize !== defaultRule.slotSize;
			const daysChanged = JSON.stringify(this.rule.ranges) !== JSON.stringify(defaultRule.ranges);

			const changes = [];

			if (daysChanged)
			{
				changes.push(Analytics.ruleChanges.custom_days);
			}

			if (sizeChanged)
			{
				changes.push(Analytics.ruleChanges.custom_length);
			}

			return changes;
		}

		getDefaultRule()
		{
			const workTimeStart = this.getWorkTimeStart();
			const workTimeEnd = this.getWorkTimeEnd();
			const workDays = this.getWorkDays();

			return {
				slotSize: 60,
				ranges: [{
					from: parseInt(workTimeStart * 60, 10),
					to: parseInt(workTimeEnd * 60, 10),
					weekdays: workDays,
				}],
			};
		}

		objectsEqual(obj1, obj2)
		{
			return JSON.stringify(this.sortKeys(obj1)) === JSON.stringify(this.sortKeys(obj2));
		}

		sortKeys(object)
		{
			return Object.keys(object).sort().reduce(
				(obj, key) => {
					obj[key] = object[key];

					return obj;
				},
				{},
			);
		}

		getRuleArray()
		{
			return {
				ranges: this.getRule().getRanges().map((range) => {
					return {
						from: range.getFrom(),
						to: range.getTo(),
						weekdays: range.getWeekDays(),
					};
				}),
				slotSize: this.getRule().getSlotSize(),
			};
		}

		/**
		 * @returns {Rule}
		 */
		getRule()
		{
			return this.rule;
		}

		setRule(rule)
		{
			this.rule = rule;
		}

		/**
		 * @returns {number}
		 */
		getWeekStart()
		{
			return this.weekStart;
		}

		/**
		 * @returns {number[]}
		 */
		getWorkDays()
		{
			return this.workDays;
		}

		/**
		 * @returns {number}
		 */
		getWorkTimeStart()
		{
			return this.workTimeStart;
		}

		/**
		 * @returns {number}
		 */
		getWorkTimeEnd()
		{
			return this.workTimeEnd;
		}

		/**
		 * @param weekHolidays {string[]}
		 * @returns {number[]}
		 */
		getWorkingDays(weekHolidays)
		{
			const weekHolidaysInt = new Set(weekHolidays.map((day) => this.getIndByWeekDay(day)));

			return [0, 1, 2, 3, 4, 5, 6].filter((day) => !weekHolidaysInt.has(day));
		}

		/**
		 * @param index {number}
		 * @returns {string}
		 */
		getWeekDayByInd(index)
		{
			return ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'][index];
		}

		/**
		 * @param weekDay {string}
		 * @returns {*}
		 */
		getIndByWeekDay(weekDay)
		{
			return { SU: 0, MO: 1, TU: 2, WE: 3, TH: 4, FR: 5, SA: 6 }[weekDay];
		}
	}

	module.exports = {
		Settings,
	};
});
