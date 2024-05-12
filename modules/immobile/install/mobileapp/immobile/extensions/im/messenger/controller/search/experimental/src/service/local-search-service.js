/**
 * @module im/messenger/controller/search/experimental/service/local-search-service
 */
jn.define('im/messenger/controller/search/experimental/service/local-search-service', (require, exports, module) => {
	const { compareWords } = require('utils/string');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogType } = require('im/messenger/const');
	const { getWordsFromText } = require('im/messenger/controller/search/experimental/get-words-from-text');

	class RecentLocalSearchService
	{
		constructor()
		{
			/**
			 * @private
			 * @type {MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {Array<string>} queryWords
		 * @return {Array<string>}
		 */
		search(queryWords)
		{
			const recentCollection = this.getItemsFromRecent(queryWords);

			return this.getDialogIds(recentCollection);
		}

		/**
		 *
		 * @param queryWords
		 * @return {Map<string, RecentLocalItem>}
		 */
		getItemsFromRecent(queryWords)
		{
			const recentItems = this.getAllRecentItems();

			const foundItems = new Map();
			recentItems.forEach((recentItem) => {
				if (this.searchByQueryWords(recentItem, queryWords))
				{
					foundItems.set(recentItem.dialogId, recentItem);
				}
			});

			return foundItems;
		}

		/**
		 * @private
		 * @return {Array<RecentLocalItem>}
		 */
		getAllRecentItems()
		{
			const recentItems = this.getRecentListItems();
			const searchSessionItems = this.getSearchSessionListItems();

			const itemsMap = new Map();
			const mergedArray = [...recentItems, ...searchSessionItems];

			for (const recentItem of mergedArray)
			{
				if (!itemsMap.has(recentItem.dialogId))
				{
					itemsMap.set(recentItem.dialogId, recentItem);
				}
			}

			return [...itemsMap.values()];
		}

		/**
		 * @private
		 * @return {Array<RecentLocalItem>}
		 */
		getRecentListItems()
		{
			return this.store.getters['recentModel/getCollection']().map((item) => {
				return this.prepareRecentItem(item);
			});
		}

		/**
		 * @private
		 * @return {Array<RecentLocalItem>}
		 */
		getSearchSessionListItems()
		{
			return this.store.getters['recentModel/searchModel/getCollection']().map((item) => {
				return this.prepareRecentItem(item);
			});
		}

		/**
		 * @private
		 * @param {RecentModelState || RecentSearchModelState} item
		 * @return {RecentLocalItem}
		 */
		prepareRecentItem(item)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](item.id, true);
			const isUser = dialog.type === DialogType.user;

			const recentItem = {
				dialogId: item.id,
				dialog,
				dateMessage: item.dateMessage,
			};

			if (isUser)
			{
				recentItem.user = this.store.getters['usersModel/getById'](item.id, true);
			}

			return recentItem;
		}

		/**
		 * @param {RecentLocalItem} recentItem
		 * @param queryWords
		 * @return {boolean}
		 */
		searchByQueryWords(recentItem, queryWords)
		{
			if (recentItem.user)
			{
				return this.searchByUserFields(recentItem, queryWords);
			}

			return this.searchByDialogFields(recentItem, queryWords);
		}

		/**
		 * @private
		 * @param {RecentLocalItem} recentItem
		 * @param {Array<string>} queryWords
		 * @return {boolean}
		 */
		searchByDialogFields(recentItem, queryWords)
		{
			const searchField = [];

			if (recentItem.dialog.name)
			{
				const dialogNameWords = getWordsFromText(recentItem.dialog.name.toLocaleLowerCase(env.languageId));
				searchField.push(...dialogNameWords);
			}

			return this.doesItemMatchQuery(searchField, queryWords);
		}

		/**
		 * @private
		 * @param {RecentLocalItem} recentItem
		 * @param {Array<string>} queryWords
		 * @return {boolean}
		 */
		searchByUserFields(recentItem, queryWords)
		{
			const searchField = [];

			if (recentItem.user.name)
			{
				const userNameWords = getWordsFromText(recentItem.user.name.toLocaleLowerCase(env.languageId));
				searchField.push(...userNameWords);
			}

			if (recentItem.user.workPosition)
			{
				const workPositionWords = getWordsFromText(recentItem.user.workPosition.toLocaleLowerCase(env.languageId));
				searchField.push(...workPositionWords);
			}

			return this.doesItemMatchQuery(searchField, queryWords);
		}

		/**
		 * @param {Array<string>} fieldsForSearch
		 * @param {Array<string>} queryWords
		 * @return {boolean}
		 */
		doesItemMatchQuery(fieldsForSearch, queryWords)
		{
			let found = 0;
			queryWords.forEach((queryWord) => {
				let queryWordsMatchCount = 0;
				fieldsForSearch.forEach((field) => {
					if (compareWords(queryWord, field))
					{
						queryWordsMatchCount++;
					}
				});
				if (queryWordsMatchCount > 0)
				{
					found++;
				}
			});

			return found >= queryWords.length;
		}

		/**
		 * @param {Map<string, RecentLocalItem>} recentCollection
		 * @return {Array<string>}
		 */
		getDialogIds(recentCollection)
		{
			return [...recentCollection.values()].map((item) => {
				return item.dialogId.toString();
			});
		}
	}

	module.exports = { RecentLocalSearchService };
});
