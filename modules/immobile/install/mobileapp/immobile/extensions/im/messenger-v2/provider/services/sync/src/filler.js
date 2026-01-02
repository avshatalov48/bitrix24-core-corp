/**
 * @module im/messenger-v2/provider/services/sync/filler
 */
jn.define('im/messenger-v2/provider/services/sync/filler', (require, exports, module) => {
	const { SyncFillerDatabase } = require('im/messenger/provider/services/sync/fillers/database');
	const { SyncFillerStore } = require('im/messenger-v2/provider/services/sync/fillers/store');

	/**
	 * @class SyncFiller
	 */
	class SyncFiller
	{
		static instance = null;

		/**
		 * @return {SyncFiller}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			this.storeFiller = new SyncFillerStore();
			this.databaseFiller = new SyncFillerDatabase();
		}

		/**
		 * @param {SyncListResult} result
		 * @param {boolean} isBackgroundSync
		 * @returns {Promise<*>}
		 */
		async applyData(result, isBackgroundSync)
		{
			if (isBackgroundSync)
			{
				return this.databaseFiller.fillDataWithoutEmit(result);
			}

			return Promise.all([
				this.storeFiller.fillDataWithoutEmit(result),
				this.databaseFiller.fillDataWithoutEmit(result),
			]);
		}
	}

	module.exports = {
		SyncFiller,
	};
});
