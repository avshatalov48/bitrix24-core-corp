/**
 * @module crm/timeline/item/ui/body/blocks/with-title
 */
jn.define('crm/timeline/item/ui/body/blocks/with-title', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');

	/**
	 * @class TimelineItemBodyWithTitleBlock
	 */
	class TimelineItemBodyWithTitleBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: this.props.inline ? 'row' : 'column',
						flexWrap: this.props.inline ? 'wrap' : 'no-wrap',
					},
				},
				Text({
					text: this.props.title,
					style: {
						fontSize: 13,
						fontWeight: '400',
						color: '#828B95',
						marginRight: 4,
					},
				}),
				this.renderInnerContent(),
			);
		}

		renderInnerContent()
		{
			const { rendererName, properties } = this.props.contentBlock;
			return this.factory.make(rendererName, properties);
		}
	}

	module.exports = { TimelineItemBodyWithTitleBlock };
});
