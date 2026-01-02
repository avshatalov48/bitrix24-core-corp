/**
 * @module statemanager/redux/slices/gratitude/meta
 */
jn.define('statemanager/redux/slices/gratitude/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');
	const { StateCache } = require('statemanager/redux/state-cache');

	const sliceName = 'mobile:gratitude';
	const gratitudeAdapter = createEntityAdapter({
		selectId: ({ id }) => {
			return id;
		},
	});
	const initialState = StateCache.getReducerState(sliceName, gratitudeAdapter.getInitialState());

	module.exports = {
		sliceName,
		gratitudeAdapter,
		initialState,
	};
});
