/**
 * @module calendar/event-edit-form/pages/date-time-page
 */
jn.define('calendar/event-edit-form/pages/date-time-page', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');

	const { SlotCalendar } = require('calendar/event-edit-form/layout/slot-calendar');
	const { SlotSizeSelector } = require('calendar/event-edit-form/layout/slot-size-selector');
	const { SlotList } = require('calendar/event-edit-form/layout/slot-list');
	const { SaveEventContainer } = require('calendar/event-edit-form/layout/save-event-container');

	/**
	 * @class DateTimePage
	 */
	class DateTimePage extends LayoutComponent
	{
		render()
		{
			return Box(
				{
					style: {
						flex: 1,
					},
					safeArea: {
						bottom: true,
					},
				},
				View(
					{
						style: {
							flex: 1,
							backgroundColor: Color.bgContentSecondary.toHex(),
						},
					},
					this.renderCalendar(),
					this.renderSlots(),
					this.renderFooter(),
				),
			);
		}

		renderCalendar()
		{
			return Area(
				{
					isFirst: true,
					excludePaddingSide: {
						horizontal: true,
					},
					style: {
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						borderBottomWidth: 1,
						backgroundColor: Color.bgSecondary.toHex(),
					},
				},
				new SlotCalendar(),
			);
		}

		renderSlots()
		{
			return Area(
				{
					excludePaddingSide: {
						bottom: true,
					},
					style: {
						marginTop: Indent.M.toNumber(),
						flex: 1,
						backgroundColor: Color.bgSecondary.toHex(),
					},
				},
				new SlotSizeSelector(),
				new SlotList(),
			);
		}

		renderFooter()
		{
			return new SaveEventContainer({
				layout: this.props.parentLayout,
			});
		}
	}

	module.exports = { DateTimePage };
});
