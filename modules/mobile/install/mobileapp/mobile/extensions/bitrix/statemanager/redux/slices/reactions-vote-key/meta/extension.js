/**
 * @module statemanager/redux/slices/reactions-vote-key/meta
 */
jn.define('statemanager/redux/slices/reactions-vote-key/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');
	const { StateCache } = require('statemanager/redux/state-cache');

	const keySliceName = 'mobile:reactionsVoteSignToken';
	const reactionsVoteSignTokenAdapter = createEntityAdapter({
		selectId: ({ entityType, entityId }) => {
			return `${entityType}_${entityId}_voteSignToken`;
		},
	});
	const initialState = StateCache.getReducerState(keySliceName, reactionsVoteSignTokenAdapter.getInitialState());

	module.exports = {
		keySliceName,
		reactionsVoteSignTokenAdapter,
		initialState,
	};
});
