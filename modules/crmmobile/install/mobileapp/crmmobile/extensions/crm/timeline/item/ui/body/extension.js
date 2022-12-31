/**
 * @module crm/timeline/item/ui/body
 */
jn.define('crm/timeline/item/ui/body', (require, exports, module) => {

	const { TimelineItemBodyBlockFactory } = require('crm/timeline/item/ui/body/blocks');
	const { TimelineButtonSorter } = require('crm/timeline/item/ui/styles');

	const nothing = () => {};

    /**
     * @class TimelineItemBody
     */
    class TimelineItemBody extends LayoutComponent
    {
        constructor(props)
        {
            super(props);
        }

        render()
        {
			return View(
				{
					style: {
						paddingHorizontal: 16,
						paddingTop: 0,
						paddingBottom: 16,
					}
				},
				this.renderBodyLogo(),
				this.renderBodyBlocks(),
			);
        }

		renderBodyLogo()
		{
			return null;
		}

		renderBodyBlocks()
		{
			const factory = new TimelineItemBodyBlockFactory({
				model: this.props.model,
				itemScopeEventBus: this.props.itemScopeEventBus,
				timelineScopeEventBus: this.props.timelineScopeEventBus,
				onAction: this.props.onAction || nothing,
			});

			const blocks = Object.values(this.props.blocks || {})
				.sort(TimelineButtonSorter)
				.map(block => factory.make(block.rendererName, block.properties))
				.filter(block => block !== null);

			const isLast = (index, collection) => index === (collection.length - 1);

			return View(
				{},
				...blocks.map((block, index) => View(
					{
						style: {
							marginBottom: isLast(index, blocks) ? 0 : 10,
						}
					},
					block,
				))
			);
		}
    }

    module.exports = { TimelineItemBody };

});