/**
 * @module crm/state-storage
 */
jn.define('crm/state-storage', (require, exports, module) => {
	const stateModels = require('crm/state-storage/model');
	const {
		CategoryCountersStoreManager,
		ActivityCountersStoreManager,
		ConversionWizardStoreManager,
	} = require('crm/state-storage/manager');

	const { StateStorage } = require('storage/state-storage');

	const stateStorage = new StateStorage({ stateModels });

	module.exports = {
		StateStorage: stateStorage,
		CategoryCountersStoreManager: new CategoryCountersStoreManager(stateStorage),
		ActivityCountersStoreManager: new ActivityCountersStoreManager(stateStorage),
		ConversionWizardStoreManager: new ConversionWizardStoreManager(stateStorage),
	};
});
