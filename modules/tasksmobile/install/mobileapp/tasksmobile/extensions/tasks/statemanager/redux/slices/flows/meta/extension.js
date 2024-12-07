/**
 * @module tasks/statemanager/redux/slices/flows/meta
 */
jn.define('tasks/statemanager/redux/slices/flows/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');
	const { StateCache } = require('statemanager/redux/state-cache');

	const sliceName = 'tasks:flows';
	const entityAdapter = createEntityAdapter({});
	const initialState = StateCache.getReducerState(sliceName, entityAdapter.getInitialState());

	module.exports = {
		sliceName,
		entityAdapter,
		initialState,
	};
});
