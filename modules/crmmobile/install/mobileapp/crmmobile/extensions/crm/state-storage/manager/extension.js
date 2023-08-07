/**
 * @module crm/state-storage/manager
 */
jn.define('crm/state-storage/manager', (require, exports, module) => {
	const { ActivityCountersStoreManager } = require('crm/state-storage/manager/activity-counters');
	const { CategoryCountersStoreManager } = require('crm/state-storage/manager/category-counters');
	const { ConversionWizardStoreManager } = require('crm/state-storage/manager/conversion-wizard');

	module.exports = { ActivityCountersStoreManager, CategoryCountersStoreManager, ConversionWizardStoreManager };
});
