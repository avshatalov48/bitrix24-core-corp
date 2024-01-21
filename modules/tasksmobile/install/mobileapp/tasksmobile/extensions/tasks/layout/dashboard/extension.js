/**
 * @module tasks/layout/dashboard
 */
jn.define('tasks/layout/dashboard', (require, exports, module) => {
	const { KanbanAdapter } = require('tasks/layout/dashboard/kanban-adapter');
	const { ListAdapter } = require('tasks/layout/dashboard/list-adapter');

	module.exports = {
		KanbanAdapter,
		ListAdapter,
	};
});
