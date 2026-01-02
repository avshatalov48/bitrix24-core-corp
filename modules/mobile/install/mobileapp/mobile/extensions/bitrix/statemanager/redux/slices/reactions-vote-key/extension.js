/**
 * @module statemanager/redux/slices/reactions-vote-key
 */
jn.define('statemanager/redux/slices/reactions-vote-key', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice, createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const {
		keySliceName,
		initialState,
		reactionsVoteSignTokenAdapter,
	} = require('statemanager/redux/slices/reactions-vote-key/meta');
	const {
		selectAll,
	} = reactionsVoteSignTokenAdapter.getSelectors((state) => state[keySliceName]);

	const selectVoteSignTokensByTaskId = createDraftSafeSelector(
		(state, entityId) => {
			return selectAll(state).filter((item) => item.entityId === entityId && item.entityType === 'TASK');
		},
		(reactionsVoteSignTokenSlice) => reactionsVoteSignTokenSlice,
	);

	const selectVoteSignTokenByEntityId = createDraftSafeSelector(
		(state, entityType, entityId) => {
			const keysSlice = state[keySliceName];
			const keyId = `${entityType}_${entityId}_voteSignToken`;
			const keyEntry = keysSlice.entities[keyId];

			return keyEntry ? keyEntry.voteSignToken : null;
		},
		(voteSignToken) => voteSignToken,
	);

	const selectIsVoteSignTokenLoaded = createDraftSafeSelector(
		(state, entityType, entityId) => selectVoteSignTokenByEntityId(state, entityType, entityId),
		(voteSignToken) => Boolean(voteSignToken),
	);

	const reactionsVoteSignTokenSlice = createSlice({
		name: keySliceName,
		initialState,
		reducers: {
			reactionsVoteSignTokenUpserted: (state, { payload }) => {
				if (payload.entityType && payload.entityId)
				{
					reactionsVoteSignTokenAdapter.upsertOne(state, payload);
				}
			},
		},
	});

	const { reactionsVoteSignTokenUpserted } = reactionsVoteSignTokenSlice.actions;

	ReducerRegistry.register(keySliceName, reactionsVoteSignTokenSlice.reducer);

	module.exports = {
		reactionsVoteSignTokenUpserted,
		selectVoteSignTokensByTaskId,
		selectVoteSignTokenByEntityId,
		selectIsVoteSignTokenLoaded,
	};
});
