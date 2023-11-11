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
		constructor(props, factory) {
			super(props, factory);
			this.fontSize = 13;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: this.props.inline ? 'row' : 'column',
						flexWrap: this.props.inline ? 'wrap' : 'no-wrap',
					},
				},
				this.renderTitle(),
				this.renderInnerContent(),
			);
		}

		renderTitle()
		{
			return View(
				{
					style: {
						maxWidth: this.props.inline ? '40%' : '100%',
					},
				},
				Text({
					text: this.props.title,
					ellipsize: 'end',
					numberOfLines: 1,
					style: {
						fontSize: this.fontSize,
						fontWeight: '400',
						color: '#828B95',
						marginRight: 4,
					},
				}),
			);
		}

		renderInnerContent()
		{
			const { rendererName, properties } = this.props.contentBlock;
			const { inline } = this.props;

			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.factory.make(rendererName, {
					...properties,
					inline,
				}),
			);
		}
	}

	module.exports = { TimelineItemBodyWithTitleBlock };
});
