/**
 * @module crm/timeline/item/ui/body/blocks/text-block
 */
jn.define('crm/timeline/item/ui/body/blocks/text-block', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineFontSize, TimelineFontColor, TimelineFontWeight } = require('crm/timeline/item/ui/styles');

	/**
	 * @class TimelineItemBodyTextBlock
	 */
	class TimelineItemBodyTextBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{},
				Text({
					text: this.props.value,
					style: {
						fontSize: TimelineFontSize.get(this.props.size),
						color: TimelineFontColor.get(this.props.color),
						fontWeight: TimelineFontWeight.get(this.props.weight),
					},
				}),
			);
		}
	}

	module.exports = { TimelineItemBodyTextBlock };
});
