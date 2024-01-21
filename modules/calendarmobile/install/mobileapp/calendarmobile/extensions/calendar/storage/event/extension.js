/**
 * @module calendar/storage/event
 */
jn.define('calendar/storage/event', (require, exports, module) => {
	const { EventAjax } = require('calendar/ajax/event');
	const { EventTable } = require('calendar/storage/inmemory/event');
	const { hashCode } = require('utils/hash');

	class EventStorage
	{
		constructor()
		{
			this.ajaxResolver = EventAjax;
		}

		/**
		 * @public
		 * @param params
		 * @type { onDataSynced: Function } params
		 * @returns {Promise<*>}
		 */
		async getList(params)
		{
			return this.query({
				endpoint: 'getList',
				queryParams: params,
			});
		}

		/**
		 * @public
		 * @param params
		 * @returns {Promise<*>}
		 */
		async getFilteredList(params)
		{
			return this.ajaxResolver.getFilteredList(params);
		}

		/**
		 * @private
		 * @param params
		 * @type { endpoint: string, queryParams: Object } params
		 * @returns {Promise<*>}
		 */
		async query(params)
		{
			const key = this.generateHash(params);
			const dataFromLocalStorage = await EventTable.get(key);
			this.syncDataWithRemoteStorage({
				key,
				dataFromLocalStorage,
				...params,
			});

			return dataFromLocalStorage;
		}

		/**
		 * @private
		 * @param params
		 * @returns {string}
		 */
		generateHash(params)
		{
			return hashCode(JSON.stringify(params)).toString();
		}

		/**
		 * @private
		 * @param {{endpoint: string, queryParams: {}, key: string, dataFromLocalStorage: {}|null}} params
		 */
		async syncDataWithRemoteStorage(params)
		{
			try
			{
				const { endpoint, queryParams, key } = params;
				const response = await this.ajaxResolver[endpoint](queryParams);

				if (response.errors && Array.isArray(response.errors) && response.errors.length > 0)
				{
					return;
				}

				if (response.data)
				{
					const dataFromRemoteStorage = response.data;
					// replace local with remote data
					EventTable.set(key, dataFromRemoteStorage);
					if (queryParams.onDataSynced)
					{
						queryParams.onDataSynced(dataFromRemoteStorage);
					}
				}
			}
			catch (e)
			{
				// eslint-disable-next-line no-console
				console.error('ERROR on EventStorage.syncDataWithRemoteStorage:', e);
			}
		}
	}

	module.exports = {
		EventStorage: new EventStorage(),
	};
});
