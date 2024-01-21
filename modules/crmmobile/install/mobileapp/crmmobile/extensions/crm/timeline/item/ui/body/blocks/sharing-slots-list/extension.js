/**
 * @module crm/timeline/item/ui/body/blocks/sharing-slots-list
 */
jn.define('crm/timeline/item/ui/body/blocks/sharing-slots-list', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const AppTheme = require('apptheme');
	const { shortTime } = require('utils/date/formats');

	/**
	 * @class TimelineItemBodySharingSlotsListBlock
	 */
	class TimelineItemBodySharingSlotsListBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{
					testId: 'TimelineItemBodySharingSlotsListBlockContainer',
					style: {
						alignItems: 'flex-start',
					},
				},
				...this.props.listItems.map((item) => this.renderItem(item.properties)),
			);
		}

		renderItem(item)
		{
			const itemText = Loc.getMessage('M_CRM_TIMELINE_ITEM_CALENDAR_SHARING_SLOTS_RANGE_V2', {
				'#WEEKDAYS#': item.weekdaysFormatted,
				'#FROM_TIME#': this.formatMinutes(item.rule.from),
				'#TO_TIME#': this.formatMinutes(item.rule.to),
				'#DURATION#': item.durationFormatted,
			});

			return View(
				{
					testId: 'TimelineItemBodySharingSlotsListItemBlockContainer',
					style: {
						marginBottom: 5,
						backgroundColor: AppTheme.colors.bgPrimary,
						borderRadius: 64,
						paddingHorizontal: 8,
						paddingVertical: 4,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text({
					testId: 'TimelineItemBodySharingSlotsListBlockText',
					text: itemText,
					style: {
						fontSize: 12,
						fontWeight: '600',
						color: AppTheme.colors.base3,
					},
				}),
			);
		}

		formatMinutes(minutes)
		{
			const date = new Date();
			date.setHours(0, 0, 0, 0);

			const moment = Moment.createFromTimestamp(date.getTime() / 1000 + minutes * 60);

			return moment.format(shortTime);
		}
	}

	module.exports = { TimelineItemBodySharingSlotsListBlock };
});
