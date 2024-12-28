/**
 * @module disk/statemanager/redux/slices/files
 */
jn.define('disk/statemanager/redux/slices/files', (require, exports, module) => {
	const { isOffline } = require('device/connection');

	const { createSlice } = require('statemanager/redux/toolkit');
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');

	const { sliceName, fileListAdapter } = require('disk/statemanager/redux/slices/files/meta');
	const {
		rename,
		remove,
		move,
		copy,
	} = require('disk/statemanager/redux/slices/files/thunk');
	const {
		renamePending,
		renameFulfilled,
		removePending,
		removeFulfilled,
		movePending,
		moveFulfilled,
		copyPending,
		copyFulfilled,
	} = require('disk/statemanager/redux/slices/files/extra-reducer');
	const { FileModel } = require('disk/statemanager/redux/slices/files/model/file');

	const preparePayload = (files) => {
		return files.map((file) => FileModel.prepareReduxFileFromServerFile(file));
	};

	const fileListSlice = createSlice({
		name: sliceName,
		initialState: fileListAdapter.getInitialState(),
		reducers: {
			filesUpsertedFromServer: {
				reducer: fileListAdapter.upsertMany,
				prepare: (files) => ({
					payload: preparePayload(files),
				}),
			},
			filesAddedFromServer: {
				reducer: fileListAdapter.addMany,
				prepare: (files) => ({
					payload: preparePayload(files),
				}),
			},
			filesUpserted: {
				reducer: fileListAdapter.upsertMany,
			},
			filesAdded: {
				reducer: fileListAdapter.upsertMany,
			},
			markAsRemoved: (state, { payload }) => {
				if (isOffline())
				{
					return;
				}

				const { objectId } = payload;
				const object = state.entities[objectId];

				fileListAdapter.upsertOne(state, {
					...object,
					isRemoved: true,
				});
			},
			unmarkAsRemoved: (state, { payload }) => {
				if (isOffline())
				{
					return;
				}

				const { objectId } = payload;
				const object = state.entities[objectId];

				fileListAdapter.upsertOne(state, {
					...object,
					isRemoved: false,
				});
			},
			setRights: (state, { payload }) => {
				const { objectId, rights } = payload;
				const object = state.entities[objectId];

				fileListAdapter.upsertOne(state, {
					...object,
					rights,
				});
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(rename.pending, renamePending)
				.addCase(rename.fulfilled, renameFulfilled)
				.addCase(remove.pending, removePending)
				.addCase(remove.fulfilled, removeFulfilled)
				.addCase(move.pending, movePending)
				.addCase(move.fulfilled, moveFulfilled)
				.addCase(copy.pending, copyPending)
				.addCase(copy.fulfilled, copyFulfilled);
		},
	});

	const { reducer: fileListReducer, actions } = fileListSlice;
	const {
		filesUpsertedFromServer,
		filesUpserted,
		filesAddedFromServer,
		filesAdded,
		markAsRemoved,
		unmarkAsRemoved,
		setRights,
	} = actions;

	ReducerRegistry.register(sliceName, fileListReducer);

	module.exports = {
		filesUpsertedFromServer,
		filesUpserted,
		filesAddedFromServer,
		filesAdded,
		markAsRemoved,
		unmarkAsRemoved,
		setRights,
	};
});
