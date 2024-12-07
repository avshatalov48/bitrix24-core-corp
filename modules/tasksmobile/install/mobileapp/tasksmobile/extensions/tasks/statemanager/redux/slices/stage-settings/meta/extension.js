/**
 * @module tasks/statemanager/redux/slices/stage-settings/meta
 */
jn.define('tasks/statemanager/redux/slices/stage-settings/meta', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'tasks:stage';
	const adapter = createEntityAdapter({});
	const initialState = StateCache.getReducerState(sliceName, adapter.getInitialState());

	module.exports = {
		sliceName,
		adapter,
		initialState,
	};
});
