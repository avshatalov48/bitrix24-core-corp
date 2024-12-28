/**
 * @module calendar/event-edit-form/data-managers/slots-calculator
 */
jn.define('calendar/event-edit-form/data-managers/slots-calculator', (require, exports, module) => {
	const stepSize = 30;

	class SlotsCalculator
	{
		static calculate({ year, month, accessibility, slotSize, skipEventId })
		{
			const daysCount = new Date(year, month + 1, 0).getDate();

			const slotsMap = {};
			for (let day = 1; day <= daysCount; day++)
			{
				const date = new Date(year, month, day);

				slotsMap[day] = this.calculateDate({ date, accessibility, slotSize, skipEventId });
			}

			return slotsMap;
		}

		static calculateDate({ date, accessibility, slotSize, skipEventId })
		{
			const slots = [];

			const slotDuration = 1000 * 60 * slotSize;
			const step = 1000 * 60 * stepSize;
			const from = new Date(Math.ceil(Math.max(date.getTime(), Date.now()) / step) * step);
			const to = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 24);

			const dayAccessibility = accessibility.filter((event) => parseInt(event.id, 10) !== skipEventId
				&& parseInt(event.parentId, 10) !== skipEventId
				&& this.doIntervalsIntersect(
					event.from,
					event.to,
					from.getTime(),
					to.getTime(),
				));

			while (from.getTime() + slotDuration <= to.getTime())
			{
				const slotFrom = from.getTime();
				const slotTo = from.getTime() + slotDuration;

				const available = dayAccessibility.every((event) => !this.doIntervalsIntersect(
					event.from,
					event.to,
					slotFrom,
					slotTo,
				));

				if (available)
				{
					slots.push({
						id: `${slotFrom}-${slotTo}`,
						from: slotFrom,
						to: slotTo,
					});
				}

				from.setTime(from.getTime() + step);
			}

			return slots;
		}

		/**
		 * @private
		 */
		static doIntervalsIntersect(from1, to1, from2, to2)
		{
			const startsInside = from2 <= from1 && from1 < to2;
			const endsInside = from2 < to1 && to1 <= to2;
			const startsBeforeEndsAfter = from1 <= from2 && to1 >= to2;

			return startsInside || endsInside || startsBeforeEndsAfter;
		}
	}

	module.exports = { SlotsCalculator };
});
