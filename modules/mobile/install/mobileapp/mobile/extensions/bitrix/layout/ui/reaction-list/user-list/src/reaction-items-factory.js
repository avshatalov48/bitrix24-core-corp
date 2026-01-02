/**
 * @module layout/ui/reaction-list/user-list/src/reaction-items-factory
 */
jn.define('layout/ui/reaction-list/user-list/src/reaction-items-factory', (require, exports, module) => {
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');
	const { ReduxReactionListItem } = require('layout/ui/reaction-list/user-list/src/redux-list-item');
	const { ReactionListItem } = require('layout/ui/reaction-list/user-list/src/list-item');

	const ListItemType = {
		REACTION: 'reaction',
		REDUX_REACTION: 'reduxReaction',
	};

	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.REDUX_REACTION)
			{
				return new ReduxReactionListItem(data);
			}

			if (type === ListItemType.REACTION)
			{
				return new ReactionListItem(data);
			}

			return super.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
