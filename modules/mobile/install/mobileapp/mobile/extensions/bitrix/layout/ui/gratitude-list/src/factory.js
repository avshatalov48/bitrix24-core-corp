/**
 * @module layout/ui/gratitude-list/src/factory
 */
jn.define('layout/ui/gratitude-list/src/factory', (require, exports, module) => {
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');
	const { GratitudeListItem } = require('layout/ui/gratitude-list/src/item');

	const ListItemType = {
		GRATITUDE: 'gratitude',
	};

	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.GRATITUDE)
			{
				return new GratitudeListItem(data);
			}

			return super.create(type, data);
		}
	}

	module.exports = {
		ListItemsFactory,
		ListItemType,
	};
});
