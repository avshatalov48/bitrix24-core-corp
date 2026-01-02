/**
 * @module crm/timeline/item/ui/body/blocks/item-selector
 */
jn.define('crm/timeline/item/ui/body/blocks/item-selector', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { ItemSelector } = require('layout/ui/item-selector');

	/**
	 * @class TimelineItemBodyItemSelector
	 */
	class TimelineItemBodyItemSelector extends TimelineItemBodyBlock
	{
		constructor(props, factory)
		{
			super(props, factory);

			this.sendChanges = this.sendChanges.bind(this);
		}

		render()
		{
			return new ItemSelector({
				...this.props,
				onChange: this.sendChanges,
			});
		}

		sendChanges(selected)
		{
			if (!this.props.saveAction)
			{
				return;
			}

			const {
				value: updateAction,
				actionParams: updateParams,
				analytics,
			} = this.props.saveAction;

			if (!updateAction || !updateParams)
			{
				return;
			}
			const data = {
				...updateParams,
				value: selected,
			};

			BX.ajax.runAction(updateAction, { data, analyticsLabel: analytics })
				.catch((reject) => console.error(reject));
		}
	}

	module.exports = { TimelineItemBodyItemSelector };
});
