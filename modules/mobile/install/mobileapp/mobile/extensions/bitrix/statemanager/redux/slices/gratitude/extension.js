/**
 * @module statemanager/redux/slices/gratitude
 */
jn.define('statemanager/redux/slices/gratitude', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { sliceName, gratitudeAdapter, initialState } = require('statemanager/redux/slices/gratitude/meta');
	const {
		selectById,
		selectGratitudesByOwnerId,
		selectGratitudeByPostId,
	} = require('statemanager/redux/slices/gratitude/selector');

	const gratitudeSlice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			gratitudesUpserted: (state, { payload }) => {
				gratitudeAdapter.upsertMany(state, payload);
			},
			gratitudeUpserted: (state, { payload }) => {
				gratitudeAdapter.upsertOne(state, payload);
			},
			gratitudeUpdated: (state, { payload }) => {
				gratitudeAdapter.updateOne(state, payload);
			},
			gratitudesAdded: (state, { payload }) => {
				gratitudeAdapter.addMany(state, payload);
			},
			gratitudeAdded: (state, { payload }) => {
				gratitudeAdapter.addOne(state, payload);
			},
			gratitudeRemoved: (state, { payload }) => {
				gratitudeAdapter.removeOne(state, payload);
			},
		},
	});

	const {
		gratitudesUpserted,
		gratitudeUpserted,
		gratitudeUpdated,
		gratitudesAdded,
		gratitudeAdded,
		gratitudeRemoved,
	} = gratitudeSlice.actions;

	ReducerRegistry.register(sliceName, gratitudeSlice.reducer);

	module.exports = {
		gratitudesUpserted,
		gratitudeUpserted,
		gratitudeUpdated,
		gratitudesAdded,
		gratitudeAdded,
		gratitudeRemoved,
		selectGratitudesByOwnerId,
		selectGratitudeByPostId,
		selectById,
	};
});
