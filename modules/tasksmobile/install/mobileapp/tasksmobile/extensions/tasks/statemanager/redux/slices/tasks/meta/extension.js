/**
 * @module tasks/statemanager/redux/slices/tasks/meta
 */
jn.define('tasks/statemanager/redux/slices/tasks/meta', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'tasks:tasks';
	const tasksAdapter = createEntityAdapter();
	const initialState = StateCache.getReducerState(sliceName, tasksAdapter.getInitialState());

	module.exports = {
		sliceName,
		tasksAdapter,
		initialState,
	};
});
