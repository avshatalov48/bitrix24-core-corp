/**
 * @module im/messenger-v2/provider/services/sync/loader
 */
jn.define('im/messenger-v2/provider/services/sync/loader', (require, exports, module) => {
	const { Type } = require('type');
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	const SYNC_LIST_PAGE_LIMIT = 500;

	/**
	 * @class SyncLoader
	 */
	class SyncLoader
	{
		static instance = null;

		/**
		 * @return {SyncLoader}
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
			this.logger = getLoggerWithContext('sync-service', this);
		}

		/**
		 * @param {Object} params
		 * @param {string} params.fromDate
		 * @param {string} params.fromServerDate
		 * @param {?number} params.lastId
		 * @returns {Promise<{SyncListResult}>}
		 */
		async loadPage(params)
		{
			const syncRequestOptions = this.prepareRequestOptions(params);

			try
			{
				this.logger.log('loadPage request data:', syncRequestOptions);
				const result = await runAction(RestMethod.imV2SyncList, { data: syncRequestOptions });
				this.logger.info('loadPage result:', result);

				return result;
			}
			catch (error)
			{
				this.logger.error('loadPage catch:', error);

				throw error;
			}
		}

		/**
		 * @param {Object} params
		 * @param {string} params.fromDate
		 * @param {string} params.fromServerDate
		 * @param {?number} params.lastId
		 * @return {{lastDate:string, limit:number, lastId?:number}}
		 */
		prepareRequestOptions({ fromDate, fromServerDate, lastId })
		{
			const lastDate = Type.isStringFilled(fromServerDate) ? fromServerDate : fromDate;
			const syncListOptions = {
				lastDate,
				limit: SYNC_LIST_PAGE_LIMIT,
			};

			if (Type.isNumber(lastId))
			{
				syncListOptions.lastId = lastId;
			}

			return syncListOptions;
		}
	}

	module.exports = {
		SyncLoader,
	};
});
