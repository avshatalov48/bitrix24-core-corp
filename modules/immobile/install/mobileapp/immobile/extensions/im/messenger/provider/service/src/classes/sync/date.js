/**
 * @module im/messenger/provider/service/classes/sync/date
 */
jn.define('im/messenger/provider/service/classes/sync/date', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		DateHelper,
	} = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sync-service');
	const SECOND = 1000;
	const MINUTE = 60 * SECOND;

	/**
	 * @class DateService
	 */
	class DateService
	{
		/**
		 * @return {DateService}
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
			this.store = serviceLocator.get('core').getStore();
			this.optionRepository = serviceLocator.get('core').getRepository().option;
		}

		async updateLastSyncDate()
		{
			const lastSyncDate = new Date(Date.now() - 2 * MINUTE);
			logger.warn('DateService: last sync date update', lastSyncDate);

			return this.setLastSyncDate(lastSyncDate);
		}

		async setLastSyncDate(date)
		{
			const lastSyncDate = DateHelper.cast(date, null);
			if (!lastSyncDate)
			{
				return Promise.reject(new Error(`SyncService.setLastSyncDate error: Invalid date : ${date}`));
			}

			return this.optionRepository.set('SYNC_SERVICE_LAST_DATE', lastSyncDate.toISOString());
		}

		async getLastSyncDate()
		{
			const currentDate = new Date();

			return this.optionRepository.get('SYNC_SERVICE_LAST_DATE', (currentDate).toISOString());
		}
	}

	module.exports = {
		DateService,
	};
});
