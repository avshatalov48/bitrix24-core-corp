/**
 * @module statemanager/redux/slices/tariff-plan-restrictions
 */
jn.define('statemanager/redux/slices/tariff-plan-restrictions', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');

	const { sliceName, initialState } = require('statemanager/redux/slices/tariff-plan-restrictions/meta');
	const { fetch } = require('statemanager/redux/slices/tariff-plan-restrictions/thunk');
	const { fetchFulfilled } = require('statemanager/redux/slices/tariff-plan-restrictions/extra-reducer');
	const {
		selectIsLoaded,
		selectRestrictions,
		selectFeatureRestrictions,
	} = require('statemanager/redux/slices/tariff-plan-restrictions/selector');
	const { loadTariffPlanRestrictions } = require('statemanager/redux/slices/tariff-plan-restrictions/tools');

	const tariffPlanRestrictionsSlice = createSlice({
		initialState,
		name: sliceName,
		reducers: {},
		extraReducers: (builder) => builder.addCase(fetch.fulfilled, fetchFulfilled),
	});

	ReducerRegistry.register(sliceName, tariffPlanRestrictionsSlice.reducer);

	module.exports = {
		selectIsLoaded,
		selectRestrictions,
		selectFeatureRestrictions,
		loadTariffPlanRestrictions,
	};
});
