/**
 * @module crm/timeline/item/ui/body/blocks/line-of-text-blocks
 */
jn.define('crm/timeline/item/ui/body/blocks/line-of-text-blocks', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineButtonSorter } = require('crm/timeline/item/ui/styles');

	/**
	 * @class TimelineItemBodyLineOfTextBlocks
	 */
	class TimelineItemBodyLineOfTextBlocks extends TimelineItemBodyBlock
	{
		render()
		{
			if (!this.props.blocks)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
						alignItems: 'center', // todo Probably we'd better set position from props?
					},
				},
				...Object.values(this.props.blocks)
					.sort(TimelineButtonSorter)
					.map(({ rendererName, properties }) => View(
						{
							style: {
								marginRight: 4,
							},
						},
						this.factory.make(rendererName, properties),
					)),
			);
		}
	}

	module.exports = { TimelineItemBodyLineOfTextBlocks };
});
