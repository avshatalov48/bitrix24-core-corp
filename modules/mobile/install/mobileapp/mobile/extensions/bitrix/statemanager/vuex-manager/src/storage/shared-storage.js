/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module statemanager/vuex-manager/storage/shared-storage
 */
jn.define('statemanager/vuex-manager/storage/shared-storage', (require, exports, module) => {

	const { StateStorage } = require('statemanager/vuex-manager/storage/base');

	/**
	 * @class SharedStorage
	 * This class used to save state to the SharedStorage and to get state from the SharedStorage.
	 */
	class SharedStorage extends StateStorage
	{
		constructor(options = {})
		{
			super(options);

			this._storage = Application.sharedStorage(this.key);
		}

		setState(state)
		{
			this._storage.set('state', JSON.stringify(state));
		}

		getState()
		{
			const state = this._storage.get('state');
			if (!state)
			{
				return null;
			}

			return JSON.parse(state);
		}

		clearState()
		{
			this._storage.clear();
		}
	}

	module.exports = { SharedStorage };
});
