/**
 * @module storage/manager
 */
jn.define('storage/manager', (require, exports, module) => {
	const { VuexManager } = require('statemanager/vuex-manager');

	/**
	 * @class BaseManager
	 */
	class BaseManager
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

		// todo remove after refactoring
		subscribe(mutation, action)
		{
			this.eventManager.on(mutation, action);

			return this;
		}

		subscribeOnChange(action, mutation)
		{
			this.eventManager.on(mutation, action);

			return this;
		}

		unsubscribe(mutation, action)
		{
			this.eventManager.off(mutation, action);

			return this;
		}

		getData()
		{
			throw new Error('Method "getData" must be implemented.');
		}
	}

	module.exports = { BaseManager };
});
