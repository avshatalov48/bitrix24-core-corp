/**
 * @module tasks/statemanager/redux/slices/kanban-settings/meta
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');
	const { StateCache } = require('statemanager/redux/state-cache');

	const sliceName = 'tasks:kanban';
	const adapter = createEntityAdapter({});
	const initialState = StateCache.getReducerState(sliceName, adapter.getInitialState());

	module.exports = {
		sliceName,
		adapter,
		initialState,
	};
});
