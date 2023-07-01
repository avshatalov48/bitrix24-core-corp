/**
 * @module crm/timeline/action/show-menu
 */
jn.define('crm/timeline/action/show-menu', (require, exports, module) => {
	const { BaseTimelineAction } = require('crm/timeline/action/base');
	const { TimelineItemContextMenu } = require('crm/timeline/item/ui/context-menu');

	class ShowMenuAction extends BaseTimelineAction
	{
		execute()
		{
			const items = [];

			Object.keys(this.value.items).forEach((key) => {
				items.push({ ...this.value.items[key], id: key });
			});

			const menu = new TimelineItemContextMenu({
				items,
				onAction: (action) => this.factory.execute({
					...action,
					source: this.source,
					entity: this.entity,
				}),
			});

			if (menu.hasItems())
			{
				menu.open();
			}
		}
	}

	module.exports = { ShowMenuAction };
});
