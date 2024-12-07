/**
 * @module statemanager/redux/slices/tariff-plan-restrictions/tools
 */
jn.define('statemanager/redux/slices/tariff-plan-restrictions/tools', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { selectIsLoaded } = require('statemanager/redux/slices/tariff-plan-restrictions/selector');
	const { fetch } = require('statemanager/redux/slices/tariff-plan-restrictions/thunk');

	const loadTariffPlanRestrictions = (isForceLoad = false) => {
		return (!isForceLoad && selectIsLoaded(store.getState()) ? Promise.resolve() : dispatch(fetch()));
	};

	module.exports = { loadTariffPlanRestrictions };
});
