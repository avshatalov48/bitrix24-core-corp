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
			this.eventManager = new VuexManager(this.store)
				// .enableMultiContext({
				// 	storeName: 'crm.kanban.category-counters',
				// })
				.build()
			;
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
