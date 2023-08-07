/**
 * @module crm/state-storage/model
 */
jn.define('crm/state-storage/model', (require, exports, module) => {
	const { activityCountersModel } = require('crm/state-storage/model/activity-counters');
	const { categoryCountersModel } = require('crm/state-storage/model/category-counters');
	const { conversionWizardModel } = require('crm/state-storage/model/conversion-wizard');

	module.exports = { categoryCountersModel, conversionWizardModel, activityCountersModel };
});
