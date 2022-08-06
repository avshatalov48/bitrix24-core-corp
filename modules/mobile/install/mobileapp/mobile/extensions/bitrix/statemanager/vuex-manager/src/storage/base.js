/* eslint-disable flowtype/require-return-type */

/**
 * @module statemanager/vuex-manager/storage/base
 */
jn.define('statemanager/vuex-manager/storage/base', (require, exports, module) => {

	/**
	 * @class StateStorage
	 * This class used to save state to the cache and to get state from the cache.
	 */
	class StateStorage
	{
		constructor(options = {})
		{
			if (typeof options.key !== 'string' || options.key === '')
			{
				throw new Error('StateStorage: options.key must be filled string.');
			}

			this.key = options.key;
		}

		setState(state)
		{
			throw new Error('StateStorage: setState() must be override in subclass.');
		}

		getState()
		{
			throw new Error('StateStorage: getState() must be override in subclass.');
		}

		clearState()
		{
			throw new Error('StateStorage: clearState() must be override in subclass.');
		}
	}

	module.exports = { StateStorage };
});
