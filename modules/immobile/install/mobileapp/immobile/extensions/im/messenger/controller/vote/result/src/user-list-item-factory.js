/**
 * @module im/messenger/controller/vote/result/user-list-item-factory
 */
jn.define('im/messenger/controller/vote/result/user-list-item-factory', (require, exports, module) => {
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');
	const { UserListItem, USER_LIST_ITEM_TYPE } = require('im/messenger/controller/vote/result/user-list-item');

	class UserListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === USER_LIST_ITEM_TYPE)
			{
				return new UserListItem(data);
			}

			return super.create(type, data);
		}
	}

	module.exports = { UserListItemsFactory, USER_LIST_ITEM_TYPE };
});
