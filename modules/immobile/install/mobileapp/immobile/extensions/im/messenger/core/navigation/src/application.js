/**
 * @module im/messenger/core/navigation/application
 */
jn.define('im/messenger/core/navigation/application', (require, exports, module) => {
	const { EntityReady } = require('entity-ready');

	const { WaitingEntity } = require('im/messenger/const');

	const { CoreApplication } = require('im/messenger/core/base');

	const { ReadMessageQueueRepository } = require('im/messenger/db/repository');
	const { updateDatabase, Version } = require('im/messenger/db/update');

	const { Feature } = require('im/messenger/lib/feature');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('core');

	/**
	 * @class NavigationApplication
	 */
	class NavigationApplication extends CoreApplication
	{
		getWaitingEntityId()
		{
			return WaitingEntity.core.navigation;
		}

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
		 * @return {Promise<boolean>}
		 */
		async isClearDatabase()
		{
			const version = new Version();
			try
			{
				const versionData = await version.getWithoutCache();
				logger.log(`CoreApplication.isClearDatabase = ${versionData === 0}, versionData:`, versionData);

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
				return Promise.resolve();
			}

			return updateDatabase();
		}
	}

	module.exports = { NavigationApplication };
});
