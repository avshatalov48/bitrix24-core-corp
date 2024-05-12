/**
 * @module calendar/model/sharing/rule
 */
jn.define('calendar/model/sharing/rule', (require, exports, module) => {
	const { Range } = require('calendar/model/sharing/range');
	const { Duration } = require('utils/date');

	/**
	 * @class Rule
	 */
	class Rule
	{
		constructor(rule, weekStart)
		{
			this.DEFAULT_SLOT_SIZE = 30;
			const slotSize = BX.prop.getNumber(rule, 'slotSize', this.DEFAULT_SLOT_SIZE);
			const ranges = BX.prop.getArray(rule, 'ranges', []);
			const availableSlots = BX.prop.getArray(rule, 'availableSlots', []);

			this.weekStart = weekStart;
			this.ranges = [];
			this.availableSlots = availableSlots.map((availableSlotSize) => parseInt(availableSlotSize, 10));
			this.setSlotSize(slotSize);
			this.hash = BX.prop.getString(rule, 'hash', '');
			this.maxRanges = BX.prop.getNumber(rule, 'maxRanges', 5);
			this.internalRangeId = 1;

			for (const range of ranges)
			{
				this.addRange(range, false);
			}
		}

		/**
		 * @returns {string}
		 */
		getHash()
		{
			return this.hash;
		}

		/**
		 * @returns {number}
		 */
		getSlotSize()
		{
			return this.slotSize;
		}

		/**
		 * @param value {number}
		 */
		setSlotSize(value)
		{
			const slotSize = parseInt(value, 10);
			this.slotSize = this.availableSlots.includes(slotSize) ? slotSize : this.DEFAULT_SLOT_SIZE;
			for (const range of this.getRanges())
			{
				range.setSlotSize(this.slotSize);
			}
		}

		getFormattedSlotSize()
		{
			return Duration.createFromMinutes(this.slotSize).format();
		}

		/**
		 * @returns {number[]}
		 */
		getAvailableSlotSizes()
		{
			return this.availableSlots;
		}

		/**
		 * @returns {Range[]}
		 */
		getRanges()
		{
			return this.ranges;
		}

		/**
		 * Sorts ranges identically to the web
		 */
		getSortedRanges()
		{
			return [...this.ranges].sort((a, b) => this.compareRanges(a, b));
		}

		compareRanges(range1, range2)
		{
			const weekdaysWeight1 = this.getWeekdaysWeight(range1.getWeekDays());
			const weekdaysWeight2 = this.getWeekdaysWeight(range2.getWeekDays());

			if (weekdaysWeight1 !== weekdaysWeight2)
			{
				return weekdaysWeight1 - weekdaysWeight2;
			}

			if (range1.getFrom() !== range2.getFrom())
			{
				return range1.getFrom() - range2.getFrom();
			}

			return range1.getTo() - range2.getTo();
		}

		getWeekdaysWeight(weekdays)
		{
			return weekdays
				.map((w) => (w < this.weekStart ? w + 10 : w))
				.sort((a, b) => a - b)
				.reduce((accumulator, w, index) => {
					return accumulator + w * 10 ** (10 - index);
				}, 0);
		}

		/**
		 * @param range {from, to, weekdays}
		 * @param {boolean} isNew
		 */
		addRange(range, isNew = true)
		{
			if (!this.canAddRange())
			{
				return;
			}

			this.ranges.push(
				new Range({
					id: this.internalRangeId,
					...range,
					weekStart: this.weekStart,
					slotSize: this.slotSize,
					isNew,
				}),
			);
			this.internalRangeId++;
		}

		/**
		 * @param rangeToRemove {Range}
		 * @returns {boolean}
		 */
		removeRange(rangeToRemove)
		{
			if (!this.canRemoveRange())
			{
				return false;
			}

			this.ranges = this.ranges.filter((range) => {
				return range.getId() !== rangeToRemove.getId();
			});

			return true;
		}

		/**
		 * @returns {boolean}
		 */
		canAddRange()
		{
			return this.ranges.length < this.maxRanges;
		}

		/**
		 * @returns {boolean}
		 */
		canRemoveRange()
		{
			return this.ranges.length > 1;
		}
	}

	module.exports = {
		Rule,
	};
});
