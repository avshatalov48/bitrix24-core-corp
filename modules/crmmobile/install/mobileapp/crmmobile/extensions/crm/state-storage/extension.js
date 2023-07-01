/**
 * @module crm/state-storage
 */
jn.define('crm/state-storage', (require, exports, module) => {
	const { createStore } = require('statemanager/vuex');

	const { categoryCountersModel } = require('crm/state-storage/model/category-counters');
	const { CategoryCountersStoreManager } = require('crm/state-storage/manager/category-counters');

	const { activityCountersModel } = require('crm/state-storage/model/activity-counters');
	const { ActivityCountersStoreManager } = require('crm/state-storage/manager/activity-counters');

	/**
	 * @class StateStorage
	 */
	class StateStorage
	{
		constructor()
		{
			this.store = createStore({
				modules: {
					categoryCountersModel,
					activityCountersModel,
				},
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
	};
});
