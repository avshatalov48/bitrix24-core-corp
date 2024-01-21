/**
 * @module tasks/statemanager/redux/slices/kanban-settings/meta
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/meta', (require, exports, module) =>
{
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'tasks:kanban';
	const adapter = createEntityAdapter({});

	module.exports = {
		sliceName,
		adapter,
	};
});
