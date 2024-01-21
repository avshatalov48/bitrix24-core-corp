/**
 * @module tasks/layout/simple-list/items
 */
jn.define('tasks/layout/simple-list/items', (require, exports, module) => {
	const { Task } = require('tasks/layout/simple-list/items/task-redux');
	const { TaskKanban } = require('tasks/layout/simple-list/items/task-kanban');
	const { ListItemsFactory: BaseListItemsFactory } = require('layout/ui/simple-list/items');

	const ListItemType = {
		TASK: 'Task',
		KANBAN: 'TaskKanban',
	};

	/**
	 * @class ListItemsFactory
	 */
	class ListItemsFactory extends BaseListItemsFactory
	{
		static create(type, data)
		{
			if (type === ListItemType.TASK)
			{
				return new Task(data);
			}

			if (type === ListItemType.KANBAN)
			{
				return new TaskKanban(data);
			}

			return BaseListItemsFactory.create(type, data);
		}
	}

	module.exports = { ListItemsFactory, ListItemType };
});
