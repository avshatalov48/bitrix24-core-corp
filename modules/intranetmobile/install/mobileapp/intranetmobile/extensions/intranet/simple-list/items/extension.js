/**
 * @module intranet/simple-list/items
 */
jn.define('intranet/simple-list/items', (require, exports, module) => {
	const { User } = require('intranet/simple-list/items/user-redux');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');

	const ListItemType = {
		USER: 'User',
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.USER)
			{
				return new User(data);
			}

			return BaseListItemsFactory.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
