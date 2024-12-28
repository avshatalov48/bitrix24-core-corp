/**
 * @module disk/simple-list/items
 */
jn.define('disk/simple-list/items', (require, exports, module) => {
	const { File } = require('disk/simple-list/items/file-redux');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');

	const ListItemType = {
		FILE: 'File',
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.FILE)
			{
				return new File(data);
			}

			return BaseListItemsFactory.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
