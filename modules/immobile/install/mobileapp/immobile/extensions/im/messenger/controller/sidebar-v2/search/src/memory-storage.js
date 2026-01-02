/**
 * @module im/messenger/controller/sidebar-v2/search/src/memory-storage
 */
jn.define('im/messenger/controller/sidebar-v2/search/src/memory-storage', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');
	const MEMORY_STORAGE_ID = 'SidebarSearch';
	const MEMORY_STORAGE_SEARCH_TEXT_KEY = 'searchText';

	/**
	 * @class SidebarSearchMemoryStorage
	 */
	class SidebarSearchMemoryStorage
	{
		/**
		 * @param {string} dialogId
		 */
		constructor(dialogId)
		{
			this.memoryStorage = new MemoryStorage(MEMORY_STORAGE_ID + dialogId);
		}

		/**
		 * @public
		 * @returns {Promise}
		 */
		static forget(dialogId)
		{
			return (new this(dialogId)).clear();
		}

		/**
		 * @returns {Promise}
		 */
		async clear()
		{
			return this.memoryStorage.set(MEMORY_STORAGE_SEARCH_TEXT_KEY, '');
		}

		/**
		 * @returns {Promise}
		 */
		setSearchText(text)
		{
			this.memoryStorage.set(MEMORY_STORAGE_SEARCH_TEXT_KEY, text);
		}

		getSearchText()
		{
			return this.memoryStorage.getSync(MEMORY_STORAGE_SEARCH_TEXT_KEY) ?? '';
		}
	}

	module.exports = { SidebarSearchMemoryStorage };
});
