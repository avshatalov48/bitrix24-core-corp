/**
 * @module statemanager/redux/slices/tariff-plan-restrictions/selector
 */
jn.define('statemanager/redux/slices/tariff-plan-restrictions/selector', (require, exports, module) => {
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const { sliceName } = require('statemanager/redux/slices/tariff-plan-restrictions/meta');

	const selectIsLoaded = createDraftSafeSelector(
		(state) => state[sliceName],
		(slice) => Boolean(slice.isLoaded),
	);

	const selectRestrictions = createDraftSafeSelector(
		selectIsLoaded,
		(state) => state[sliceName].restrictions,
		(isLoaded, restrictions) => (isLoaded ? restrictions : {}),
	);

	const selectFeatureRestrictions = createDraftSafeSelector(
		selectRestrictions,
		(state, featureId) => featureId,
		(restrictions, featureId) => (restrictions[featureId] ?? {}),
	);

	module.exports = {
		selectIsLoaded,
		selectRestrictions,
		selectFeatureRestrictions,
	};
});
