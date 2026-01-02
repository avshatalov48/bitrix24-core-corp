/**
 * @module layout/ui/viewer-list/src/list-items-factory
 */
jn.define('layout/ui/viewer-list/src/list-items-factory', (require, exports, module) => {
	const { ViewerListItem } = require('layout/ui/viewer-list/src/list-item');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');

	const ListItemType = {
		VIEWERS: 'viewers',
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.VIEWERS)
			{
				return new ViewerListItem(data);
			}

			return BaseListItemsFactory.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
