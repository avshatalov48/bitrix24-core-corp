/**
 * @module catalog/simple-list/items
 */
jn.define('catalog/simple-list/items', (require, exports, module) => {
	const { StoreDocument } = require('catalog/simple-list/items/store-document');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');

	const ListItemType = {
		STORE_DOCUMENT: 'StoreDocument',
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.STORE_DOCUMENT)
			{
				return new StoreDocument(data);
			}

			return BaseListItemsFactory.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
