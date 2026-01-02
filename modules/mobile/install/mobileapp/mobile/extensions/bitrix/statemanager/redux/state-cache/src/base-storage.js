/**
 * @module statemanager/redux/state-cache/base-storage
 */
jn.define('statemanager/redux/state-cache/base-storage', (require, exports, module) => {
	/**
	 * @class BaseStorage
	 * @abstract
	 */
	class BaseStorage
	{
		/**
		 * @abstract
		 * @param {string} key
		 * @returns {Object|null}
		 */
		load(key)
		{}

		/**
		 * @abstract
		 * @param {string} key
		 * @param {Object} cache
		 */
		async save(key, cache)
		{}
	}

	module.exports = {
		BaseStorage,
	};
});
