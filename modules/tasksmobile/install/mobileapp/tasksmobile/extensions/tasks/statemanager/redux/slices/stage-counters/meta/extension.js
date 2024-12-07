/**
 * @module tasks/statemanager/redux/slices/stage-counters/meta
 */
jn.define('tasks/statemanager/redux/slices/stage-counters/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');
	const { StateCache } = require('statemanager/redux/state-cache');

	const sliceName = 'tasks:stageCounters';
	const allStagesId = 'total';
	const adapter = createEntityAdapter({});
	const initialState = StateCache.getReducerState(sliceName, adapter.getInitialState());

	module.exports = {
		allStagesId,
		sliceName,
		adapter,
		initialState,
	};
});
