/**
 * @module statemanager/redux/slices/tariff-plan-restrictions/meta
 */
jn.define('statemanager/redux/slices/tariff-plan-restrictions/meta', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');

	const sliceName = 'mobile:tariffPlanRestrictions';
	const defaultState = {
		isLoaded: false,
		restrictions: {},
	};
	const initialState = StateCache.getReducerState(sliceName, defaultState);

	module.exports = {
		sliceName,
		initialState,
	};
});
