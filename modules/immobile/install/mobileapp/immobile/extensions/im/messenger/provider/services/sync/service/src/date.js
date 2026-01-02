/**
 * @module im/messenger/provider/services/sync/service/date
 */
jn.define('im/messenger/provider/services/sync/service/date', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const {
		DateHelper,
	} = require('im/messenger/lib/helper');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('sync-service');

	const SECOND = 1000;
	const MINUTE = 60 * SECOND;
	const LAST_SYNC_DATE_OPTION = 'SYNC_SERVICE_LAST_DATE';
	const LAST_SYNC_SERVER_DATE_OPTION = 'SYNC_SERVICE_LAST_SERVER_DATE';

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

			return this.optionRepository.set(LAST_SYNC_DATE_OPTION, lastSyncDate.toISOString());
		}

		/**
		 * @return {Promise<string>}
		 */
		async getLastSyncDate()
		{
			const currentDate = new Date();

			return this.optionRepository.get(LAST_SYNC_DATE_OPTION, (currentDate).toISOString());
		}

		/**
		 * @param {string} date
		 */
		async setLastSyncServerDate(date)
		{
			return this.optionRepository.set(LAST_SYNC_SERVER_DATE_OPTION, date);
		}

		/**
		 * @return {Promise<string>}
		 */
		async getLastSyncServerDate()
		{
			const currentDate = new Date();

			return this.optionRepository.get(LAST_SYNC_SERVER_DATE_OPTION, (currentDate).toISOString());
		}
	}

	module.exports = {
		DateService,
	};
});
