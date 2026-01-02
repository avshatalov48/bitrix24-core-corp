/**
 * @module statemanager/redux/slices/settings
 */
jn.define('statemanager/redux/slices/settings', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { sliceName, settingsAdapter, initialState } = require('statemanager/redux/slices/settings/meta');

	const settingsSlice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			settingAdded: createSafeReducer((state, payload) => {
				settingsAdapter.addOne(state, payload);
			}),
			settingUpserted: createSafeReducer((state, payload) => {
				settingsAdapter.upsertOne(state, payload);
			}),
			settingRemoved: createSafeReducer((state, payload) => {
				settingsAdapter.removeOne(state, payload.id);
			}),
			settingUpdated: createSafeReducer((state, payload) => {
				settingsAdapter.updateOne(state, {
					id: payload.id,
					changes: payload.changes,
				});
			}),
			settingsUpserted: ((state, { payload }) => {
				settingsAdapter.upsertMany(state, payload);
			}),
		},
	});

	function createSafeReducer(reducerFunction)
	{
		return (state, action) => {
			const { payload } = action;
			if (payload.id)
			{
				reducerFunction(state, payload);
			}
		};
	}

	const {
		settingUpserted,
		settingAdded,
		settingUpdated,
		settingRemoved,
		settingsUpserted,
	} = settingsSlice.actions;

	ReducerRegistry.register(sliceName, settingsSlice.reducer);

	module.exports = {
		settingUpserted,
		settingAdded,
		settingUpdated,
		settingRemoved,
		settingsUpserted,
	};
});
