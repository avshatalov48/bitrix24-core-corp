/**
 * @module tasks/statemanager/redux/slices/tasks-results/meta
 */
jn.define('tasks/statemanager/redux/slices/tasks-results/meta', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'tasks:tasksResults';
	const tasksResultsAdapter = createEntityAdapter();
	const initialState = StateCache.getReducerState(sliceName, tasksResultsAdapter.getInitialState());

	module.exports = {
		sliceName,
		tasksResultsAdapter,
		initialState,
	};
});
