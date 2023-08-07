/**
 * @module crm/state-storage/manager/base
 */
jn.define('crm/state-storage/manager/base', (require, exports, module) => {
	const { VuexManager } = require('statemanager/vuex-manager');

	/**
	 * @class Base
	 */
	class Base
	{
		constructor({ store })
		{
			this.store = store;
			this.eventManager = this.createVuexManager(store);
		}

		storeOptions()
		{
			return null;
		}

		createVuexManager(store)
		{
			const eventManager = new VuexManager(store);
			const storeOptions = this.storeOptions();

			if (storeOptions && storeOptions.storeName)
			{
				eventManager.enableMultiContext(storeOptions);
			}

			return eventManager.build();
		}

		subscribe(mutation, action)
		{
			this.eventManager.on(mutation, action);

			return this;
		}

		unsubscribe(mutation, action)
		{
			this.eventManager.off(mutation, action);

			return this;
		}
	}

	module.exports = { Base };
});
