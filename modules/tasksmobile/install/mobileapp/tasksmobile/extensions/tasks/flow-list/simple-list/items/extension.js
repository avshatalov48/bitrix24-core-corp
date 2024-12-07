/**
 * @module tasks/flow-list/simple-list/items
 */
jn.define('tasks/flow-list/simple-list/items', (require, exports, module) => {
	const { Flow } = require('tasks/flow-list/simple-list/items/flow-redux');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');
	const { ListItemType } = require('tasks/flow-list/simple-list/items/type');

	/**
	 * @class FlowListItemsFactory
	 */
	class FlowListItemsFactory extends BaseListItemsFactory
	{
		static create(getProps, data)
		{
			const props = getProps(data.item);

			return new Flow({
				...data,
				...props,
			});
		}
	}

	module.exports = { FlowListItemsFactory, ListItemType };
});
