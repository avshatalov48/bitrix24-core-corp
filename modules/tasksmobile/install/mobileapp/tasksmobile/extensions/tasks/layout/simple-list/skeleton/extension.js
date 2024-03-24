/**
 * @module tasks/layout/simple-list/skeleton
 */
jn.define('tasks/layout/simple-list/skeleton', (require, exports, module) => {
	const { TaskKanbanItemSkeleton } = require('tasks/layout/simple-list/skeleton/src/kanban-skeleton');
	const { TaskListItemSkeleton } = require('tasks/layout/simple-list/skeleton/src/list-skeleton');

	module.exports = {
		TaskListItemSkeleton,
		TaskKanbanItemSkeleton,
	};
});
