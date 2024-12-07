/**
 * @module tariff-plan-restriction
 */
jn.define('tariff-plan-restriction', (require, exports, module) => {
	const { getFeatureRestriction } = require('tariff-plan-restriction/feature');
	const { loadTariffPlanRestrictions } = require('statemanager/redux/slices/tariff-plan-restrictions');

	const tariffPlanRestrictionsReady = () => loadTariffPlanRestrictions();

	module.exports = {
		getFeatureRestriction,
		tariffPlanRestrictionsReady,
	};
});
