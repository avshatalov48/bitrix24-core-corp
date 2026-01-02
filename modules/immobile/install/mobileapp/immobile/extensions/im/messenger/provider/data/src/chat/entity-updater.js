/**
 * @module im/messenger/provider/data/chat/entity-updater
 */
jn.define('im/messenger/provider/data/chat/entity-updater', (require, exports, module) => {
	const { BaseDataProvider } = require('im/messenger/provider/data/base');

	/**
	 * @class ChatUpdater
	 */
	class ChatUpdater extends BaseDataProvider
	{
		/**
		 * @returns {Promise<void>}
		 */
		async clearCounters()
		{
			const modelPromise = this.#clearCountersFromModel();

			const databasePromise = this.#clearCountersFromDatabase();

			await Promise.all([modelPromise, databasePromise]);
		}

		/**
		 * @returns {Promise<any>}
		 */
		async #clearCountersFromModel()
		{
			return Promise.all([
				this.store.dispatch('dialoguesModel/clearAllCounters'),
				this.store.dispatch('commentModel/clearAllCounters'),
			]);
		}

		/**
		 * @returns {Promise<void>}
		 */
		async #clearCountersFromDatabase()
		{
			return this.repository.dialog.clearCounters();
		}
	}

	module.exports = { ChatUpdater };
});
