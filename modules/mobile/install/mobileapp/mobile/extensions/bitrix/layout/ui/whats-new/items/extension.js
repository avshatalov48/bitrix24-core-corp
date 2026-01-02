/**
 * @module layout/ui/whats-new/items
 */
jn.define('layout/ui/whats-new/items', (require, exports, module) => {
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');
	const {
		WhatsNewItem,
		FINISH_NOVELTY_TYPE,
		ERROR_TYPE,
		EMPTY_TYPE,
	} = require('layout/ui/whats-new/items/whats-new');

	const ListItemType = {
		WhatsNewItem: 'WhatsNewItem',
		FinishNoveltyItem: FINISH_NOVELTY_TYPE,
		ErrorItem: ERROR_TYPE,
		EmptyItem: EMPTY_TYPE,
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.WhatsNewItem)
			{
				return new WhatsNewItem(data);
			}

			return BaseListItemsFactory.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
