/**
 * @module tasks/statemanager/redux/slices/tasks/meta
 */
jn.define('tasks/statemanager/redux/slices/tasks/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'tasks:tasks';
	const tasksAdapter = createEntityAdapter();

	module.exports = {
		sliceName,
		tasksAdapter,
	};
});
