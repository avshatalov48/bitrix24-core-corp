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
		static create(type, props)
		{
			if (type === ListItemType.BASE)
			{
				return new Base(props);
			}

			if (type === ListItemType.EXTENDED)
			{
				return new Extended(props);
			}

			if (type === ListItemType.EMPTY_SPACE)
			{
				return new EmptySpace(props);
			}

			return null;
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
