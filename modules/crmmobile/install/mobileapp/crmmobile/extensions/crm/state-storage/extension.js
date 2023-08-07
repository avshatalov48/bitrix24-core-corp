/**
 * @module crm/state-storage
 */
jn.define('crm/state-storage', (require, exports, module) => {
	const { createStore } = require('statemanager/vuex');
	const stateModels = require('crm/state-storage/model');
	const {
		CategoryCountersStoreManager,
		ActivityCountersStoreManager,
		ConversionWizardStoreManager,
	} = require('crm/state-storage/manager');

	/**
	 * @class StateStorage
	 */
	class StateStorage
	{
		constructor()
		{
			this.store = createStore({
				modules: stateModels,
			});
		}

		subscribe(handler)
		{
			return this.store.subscribe(handler);
		}
	}

	const stateStorage = new StateStorage();

	module.exports = {
		StateStorage: stateStorage,
		CategoryCountersStoreManager: new CategoryCountersStoreManager(stateStorage),
		ActivityCountersStoreManager: new ActivityCountersStoreManager(stateStorage),
		ConversionWizardStoreManager: new ConversionWizardStoreManager(stateStorage),
	};
});
