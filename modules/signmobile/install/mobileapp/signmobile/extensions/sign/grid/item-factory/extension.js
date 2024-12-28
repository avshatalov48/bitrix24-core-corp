/**
 * @module sign/grid/item-factory
 */
jn.define('sign/grid/item-factory', (require, exports, module) => {
	const { Document } = require('sign/grid/item-factory/document');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');

	const ListItemType = {
		DOCUMENT: 'Document',
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.DOCUMENT)
			{
				return new Document(data);
			}

			return BaseListItemsFactory.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
