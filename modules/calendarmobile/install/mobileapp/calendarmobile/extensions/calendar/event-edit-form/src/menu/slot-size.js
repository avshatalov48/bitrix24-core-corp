/**
 * @module calendar/event-edit-form/menu/slot-size
 */
jn.define('calendar/event-edit-form/menu/slot-size', (require, exports, module) => {
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');
	const { Duration } = require('utils/date');

	class SlotSizeMenu extends BaseMenu
	{
		getItems()
		{
			const slotSizes = [15, 30, 45, 60, 90, 120, 180];

			return slotSizes.map((slotSize) => ({
				id: String(slotSize),
				testId: `calendar-event-edit-form-slot-size-menu-${slotSize}`,
				sectionCode: baseSectionType,
				title: this.formatSlotSize(slotSize),
				checked: slotSize === this.props.slotSize,
			}));
		}

		formatSlotSize(slotSize)
		{
			return Duration.createFromMinutes(slotSize).format();
		}
	}

	module.exports = { SlotSizeMenu };
});
