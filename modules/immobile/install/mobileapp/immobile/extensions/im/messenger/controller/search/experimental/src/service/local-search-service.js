/**
 * @module im/messenger/controller/search/experimental/service/local-search-service
 */
jn.define('im/messenger/controller/search/experimental/service/local-search-service', (require, exports, module) => {
	const { compareWords } = require('utils/string');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogType } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
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
		 * @return {Promise<Array<string>>}
		 */
		async search(queryWords)
		{
			const searchDbResult = await this.searchInLocalDb(queryWords.join(' '));
			void await this.#setChatsToStorage(searchDbResult);
			const recentStoreCollection = this.getItemsFromRecentStore(queryWords);

			return this.getDialogIds(recentStoreCollection);
		}

		/**
		 * @description The local database is being searched. The result is recorded in the storage
		 * @param {string} query
		 * @return {Promise<Array<DialoguesModelState>>}
		 */
		async searchInLocalDb(query)
		{
			const recentRepository = serviceLocator.get('core').getRepository().recent;
			const searchDbResult = await recentRepository.searchByText({
				searchText: query,
			});

			return searchDbResult.items;
		}

		/**
		 *
		 * @param queryWords
		 * @return {Map<string, RecentLocalItem>}
		 */
		getItemsFromRecentStore(queryWords)
		{
			const recentItems = this.getRecentItemsForSearch();

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
		getRecentItemsForSearch()
		{
			const recentItems = this.getRecentListSearchItems();
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
		getRecentListSearchItems()
		{
			const recentList = this.store.getters['recentModel/getCollection']().map((item) => {
				return this.prepareRecentItem(item);
			});

			// TODO: MessengerV2 move to config
			const exceptDialogTypes = {
				[DialogType.tasksTask]: true,
			};

			return recentList.filter((item) => {
				return !exceptDialogTypes[item.dialog.type];
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

		/**
		 * @param {Array<DialoguesModelState>} dialogues
		 * @returns {Promise<void>}
		 */
		async #setChatsToStorage(dialogues)
		{
			const chats = dialogues.map((dialog) => dialog.chat);
			await this.store.dispatch('dialoguesModel/set', chats);

			const userRepository = serviceLocator.get('core').getRepository().user;
			const userIds = chats
				.filter((chat) => DialogHelper.isChatId(chat.dialogId))
				.map((chat) => Number(chat.dialogId));
			const users = await userRepository.userTable.getListByIds(userIds);
			await this.store.dispatch('usersModel/setFromLocalDatabase', users.items);

			const recentItems = dialogues.map((dialog) => this.prepareRecentItem(dialog));
			await this.store.dispatch('recentModel/searchModel/set', recentItems);
		}
	}

	module.exports = { RecentLocalSearchService };
});
