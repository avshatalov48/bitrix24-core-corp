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
		 * @returns {Object}
		 */
		load()
		{}

		/**
		 * @abstract
		 * @param {Object} cache
		 */
		async save(cache)
		{}
	}

	module.exports = {
		BaseStorage,
	};
});
