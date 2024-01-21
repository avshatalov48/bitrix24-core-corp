/**
 * @module lists/process/simple-list/items
 */
jn.define('lists/process/simple-list/items', (require, exports, module) => {
	const { ListItemsFactory } = require('layout/ui/simple-list/items');
	const { PersonalProcess } = require('lists/process/simple-list/items/personal-process');

	const ListItemType = {
		PERSONAL_PROCESS: 'PersonalProcess',
	};

	class ProcessItemsFactory extends ListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.PERSONAL_PROCESS)
			{
				return new PersonalProcess(data);
			}

			return ListItemsFactory.create(type, data);
		}
	}

	module.exports = { ProcessItemsFactory, ListItemType };
});
