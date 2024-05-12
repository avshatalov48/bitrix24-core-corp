/**
 * @module im/messenger/controller/search/experimental/service/server-search-service
 */

jn.define('im/messenger/controller/search/experimental/service/server-search-service', (require, exports, module) => {
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { runAction } = require('im/messenger/lib/rest');
	const { RecentSearchItem } = require('im/messenger/controller/search/experimental/search-item');
	const { StoreUpdater } = require('im/messenger/controller/search/experimental/store-updater');

	const ENTITY_ID = 'im-recent-v2';

	const logger = LoggerManager.getInstance().getLogger('recent-search');

	class RecentServerSearchService
	{
		/**
		 * @param {BaseSearchConfig} config
		 */
		constructor(config)
		{
			/**
			 * @private
			 * @type {BaseSearchConfig}
			 */
			this.config = config;

			/**
			 * @type {StoreUpdater}
			 */
			this.storeUpdater = new StoreUpdater();
		}

		/**
		 * @return Promise<Array<string>>
		 */
		async loadRecent()
		{
			return this.loadRecentRequest()
				.then((response) => {
					const { items, recentItems } = response.dialog;
					if (items.length === 0 || recentItems.length === 0)
					{
						return new Map();
					}
					const itemMap = this.createItemsMap(items);
					const itemsFromRecentItems = this.getItemsFromRecentItems(recentItems, itemMap);

					return this.processLoadRecentResponse(itemsFromRecentItems);
				})
				.then((processedItems) => {
					return this.getDialogIds(processedItems);
				})
				.catch((error) => {
					logger.error('RecentProvider.loadRecent error', error);
				})
			;
		}

		/**
		 * @private
		 * @return {Promise<RecentSearchResult>}
		 */
		async loadRecentRequest()
		{
			/**
			 * @type {RecentSearchResult}
			 */
			const response = await runAction(this.config.getLoadLatestResultEndpoint(), this.config.getConfig());
			logger.warn('RecentProvider.loadRecent response', response);

			return response;
		}

		/**
		 * @param {Array<string>} searchingWords
		 * @param {string} originalQuery
		 * @return {Promise<Array<string>>}
		 */
		async search(searchingWords, originalQuery)
		{
			return this.searchRequest(searchingWords, originalQuery)
				.then((response) => {
					logger.log('after resp', response);
					const { items } = response.dialog;
					const itemsCollection = this.createItemsMap(items);

					return this.processSearchResponse(itemsCollection);
				})
				.then((items) => {
					return this.getDialogIds(items);
				})
			;
		}

		async saveItemToRecent(dialogId)
		{
			const recentItems = [{ id: dialogId, entityId: ENTITY_ID }];

			const config = this.config.getConfig();
			config.json.recentItems = recentItems;

			return runAction(this.config.getSaveItemEndpoint(), config);
		}

		/**
		 * @private
		 * @param searchingWords
		 * @param originalQuery
		 * @return {Promise<RecentSearchResult>}
		 */
		async searchRequest(searchingWords, originalQuery)
		{
			const config = this.config.getConfig();
			config.json.searchQuery = {
				queryWords: searchingWords,
				query: originalQuery,
			};

			/**
			 * @type {RecentSearchResult}
			 */
			const response = await runAction(this.config.getSearchRequestEndpoint(), config);
			logger.warn('RecentProvider.search response', response);

			return response;
		}

		/**
		 * @private
		 * @param {Array<[string, string || number]>} recentItems
		 * @param {Map<string, RecentSearchItem>} items
		 * @return {Map<string, RecentSearchItem>}
		 */
		getItemsFromRecentItems(recentItems, items)
		{
			const filledRecentItems = new Map();
			recentItems.forEach((recentItem) => {
				const [, dialogId] = recentItem;
				const itemFromMap = items.get(dialogId.toString());
				if (itemFromMap)
				{
					filledRecentItems.set(itemFromMap.dialogId, itemFromMap);
				}
			});

			return filledRecentItems;
		}

		/**
		 * @private
		 * @param {Array<RecentProviderItem>} items
		 * @return {Map<string, RecentSearchItem>}
		 */
		createItemsMap(items)
		{
			const map = new Map();

			items.forEach((item) => {
				const mapItem = new RecentSearchItem(item);
				map.set(mapItem.dialogId, mapItem);
			});

			return map;
		}

		/**
		 * @private
		 * @param {Map<string, RecentSearchItem>} items
		 * @return {Promise<Map<string, RecentSearchItem>>}
		 */
		async processLoadRecentResponse(items)
		{
			await this.storeUpdater.update(items);

			return items;
		}

		/**
		 * @private
		 * @param {Map<string, RecentSearchItem>} items
		 * @return {Promise<Map<string, RecentSearchItem>>}
		 */
		async processSearchResponse(items)
		{
			await this.storeUpdater.update(items);
			await this.storeUpdater.updateSearchSession(items);

			return items;
		}

		/**
		 *
		 * @param {Map<string, RecentSearchItem>} items
		 * @return {Array<string>}
		 */
		getDialogIds(items)
		{
			return [...items.values()].map((item) => {
				return item.dialogId;
			});
		}
	}

	module.exports = { RecentServerSearchService };
});
