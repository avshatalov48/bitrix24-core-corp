/**
 * @module statemanager/redux/slices/reactions/meta
 */
jn.define('statemanager/redux/slices/reactions/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');
	const { StateCache } = require('statemanager/redux/state-cache');

	const sliceName = 'mobile:reactions';
	const reactionsAdapter = createEntityAdapter({
		selectId: ({ entityType, entityId, reactionId }) => {
			return `${entityType}_${entityId}_${reactionId}`;
		},
	});
	const initialState = StateCache.getReducerState(sliceName, reactionsAdapter.getInitialState());

	module.exports = {
		sliceName,
		reactionsAdapter,
		initialState,
	};
});
