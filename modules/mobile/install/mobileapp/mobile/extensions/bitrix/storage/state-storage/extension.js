/**
 * @module storage/state-storage
 */
jn.define('storage/state-storage', (require, exports, module) => {
	const { createStore } = require('statemanager/vuex');

	/**
	 * @class StateStorage
	 */
	class StateStorage
	{
		constructor({ stateModels })
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

	module.exports = { StateStorage };
});
