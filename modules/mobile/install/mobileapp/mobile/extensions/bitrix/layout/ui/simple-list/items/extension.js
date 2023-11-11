/**
 * @module layout/ui/simple-list/items
 */
jn.define('layout/ui/simple-list/items', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { Extended } = require('layout/ui/simple-list/items/extended');
	const { EmptySpace } = require('layout/ui/simple-list/items/empty-space');

	const ListItemType = {
		BASE: 'Base',
		EXTENDED: 'Extended',
		EMPTY_SPACE: 'EmptySpace',
	};

	class ListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.BASE)
			{
				return new Base(data);
			}

			if (type === ListItemType.EXTENDED)
			{
				return new Extended(data);
			}

			if (type === ListItemType.EMPTY_SPACE)
			{
				return new EmptySpace(data);
			}

			return null;
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
