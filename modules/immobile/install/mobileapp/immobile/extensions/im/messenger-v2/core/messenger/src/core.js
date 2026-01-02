/**
 * @module im/messenger-v2/core/messenger/core
 */
jn.define('im/messenger-v2/core/messenger/core', (require, exports, module) => {
	const { CoreApplication } = require('im/messenger/core/base');

	const { ReadMessageQueueRepository } = require('im/messenger/db/repository');
	const { updateDatabase, Version } = require('im/messenger/db/update');

	const { recentModel } = require('im/messenger-v2/model');

	const { Feature } = require('im/messenger/lib/feature');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('core');

	/**
	 * @class MessengerCore
	 */
	class MessengerCore extends CoreApplication
	{
		async initDatabase()
		{
			if (!this.config.localStorage.enable)
			{
				Feature.disableLocalStorage();
			}

			if (this.config.localStorage.readOnly)
			{
				Feature.enableLocalStorageReadOnlyMode();
			}
			else
			{
				Feature.disableLocalStorageReadOnlyMode();
			}

			const isClearDatabase = await this.isClearDatabase();

			await this.updateDatabase();

			this.initRepository();

			if (isClearDatabase)
			{
				this.createDatabaseTableInstances();
			}
		}

		createRepository()
		{
			super.createRepository();

			this.repository.readMessageQueue = new ReadMessageQueueRepository();

			const baseDrop = this.repository.drop;

			this.repository.drop = () => {
				baseDrop();

				this.repository.readMessageQueue.queueTable.drop();
			};
		}

		/**
		 * @return {object}
		 */
		getStoreModules()
		{
			const storeModules = super.getStoreModules();

			return {
				...storeModules,
				recentModel,
			};
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		getMessengerStore()
		{
			return this.getStore();
		}

		/**
		 * @return {Promise<boolean>}
		 */
		async isClearDatabase()
		{
			const version = new Version();
			try
			{
				const versionData = await version.getWithoutCache();
				logger.log(`MessengerCore.isClearDatabase = ${versionData === 0}, versionData:`, versionData);

				return versionData === 0;
			}
			catch (error)
			{
				logger.warn(error);

				return false;
			}
		}

		async updateDatabase()
		{
			if (!Feature.isLocalStorageEnabled)
			{
				return false;
			}

			return updateDatabase();
		}
	}

	module.exports = { MessengerCore };
});
