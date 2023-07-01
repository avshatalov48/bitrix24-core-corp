/**
 * @module crm/timeline/item/ui/body
 */
jn.define('crm/timeline/item/ui/body', (require, exports, module) => {
	const { TimelineItemBodyBlockFactory } = require('crm/timeline/item/ui/body/blocks');
	const { TimelineButtonSorter, isScopeMobile } = require('crm/timeline/item/ui/styles');
	const { get } = require('utils/object');

	const nothing = () => {};

	/**
	 * @class TimelineItemBody
	 */
	class TimelineItemBody extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						paddingHorizontal: 16,
						paddingTop: 0,
						paddingBottom: get(this, 'props.style.paddingBottom', 16),
					},
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

			/** @type {TimelineItemBodyBlock[]} */
			const blocks = Object.values(this.props.blocks || {})
				.filter((block) => isScopeMobile(block.scope))
				.sort(TimelineButtonSorter)
				.map((block) => factory.make(block.rendererName, block.properties))
				.filter((block) => block !== null);

			const isLast = (index, collection) => index === (collection.length - 1);

			return View(
				{
					testId: 'TimelineItemBody',
				},
				...blocks.map((block, index) => View(
					{
						style: {
							marginBottom: isLast(index, blocks) ? 0 : block.getBottomGap(),
						},
					},
					block,
				)),
			);
		}
	}

	module.exports = { TimelineItemBody };
});
