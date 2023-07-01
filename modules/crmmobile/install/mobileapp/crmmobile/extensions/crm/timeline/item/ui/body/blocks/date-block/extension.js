/**
 * @module crm/timeline/item/ui/body/blocks/date-block
 */
jn.define('crm/timeline/item/ui/body/blocks/date-block', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { Moment } = require('utils/date');
	const { dayShortMonth, shortTime, mediumDate } = require('utils/date/formats');

	/**
	 * @class TimelineItemBodyDateBlock
	 */
	class TimelineItemBodyDateBlock extends TimelineItemBodyBlock
	{
		render()
		{
			const moment = Moment.createFromTimestamp(this.props.value);
			const dateFormat = moment.inThisYear ? dayShortMonth() : mediumDate();

			return View(
				{},
				Text({
					text: this.props.withTime
						? moment.format(`${dateFormat}, ${shortTime()}`)
						: moment.format(dateFormat),
					style: {
						fontSize: 14,
						color: '#525C69',
						marginLeft: 5,
					},
				}),
			);
		}
	}

	module.exports = { TimelineItemBodyDateBlock };
});
