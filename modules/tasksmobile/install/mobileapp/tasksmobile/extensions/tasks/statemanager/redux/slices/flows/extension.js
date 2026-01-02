/**
 * @module tasks/statemanager/redux/slices/flows
 */
jn.define('tasks/statemanager/redux/slices/flows', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { sliceName, initialState } = require('tasks/statemanager/redux/slices/flows/meta');
	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
	} = require('tasks/statemanager/redux/slices/flows/src/selector');

	const {
		upsertFlows,
		addFlows,
	} = require('tasks/statemanager/redux/slices/flows/src/action');

	const {
		flowsUpserted,
		flowsAdded,
	} = require('tasks/statemanager/redux/slices/flows/src/reducer');

	const {
		flowTogglePin,
	} = require('tasks/statemanager/redux/slices/flows/src/thunk');

	const {
		flowTogglePinPending,
		flowTogglePinRejected,
	} = require('tasks/statemanager/redux/slices/flows/src/extra-reducer');

	const slice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			flowsAdded,
			flowsUpserted,
		},
		extraReducers: (builder) => {
			builder
				.addCase(flowTogglePin.pending, flowTogglePinPending)
				.addCase(flowTogglePin.rejected, flowTogglePinRejected)
			;
		},
	});
	ReducerRegistry.register(sliceName, slice.reducer);

	module.exports = {
		// actions
		upsertFlows,
		addFlows,

		// selectors
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,

		// thunks
		flowTogglePin,
	};
});
