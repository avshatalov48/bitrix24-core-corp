/**
 * @module im/messenger/controller/search/experimental/provider
 */
jn.define('im/messenger/controller/search/experimental/provider', (require, exports, module) => {
	const { RecentConfig } = require('im/messenger/controller/search/experimental/config');
	const { RecentLocalSearchService } = require('im/messenger/controller/search/experimental/service/local-search-service');
	const { RecentServerSearchService } = require('im/messenger/controller/search/experimental/service/server-search-service');
	const { core } = require('im/messenger/core');
	const { DialogHelper, DateHelper } = require('im/messenger/lib/helper');
	const { DialogType } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { debounce } = require('utils/function');
	const { getWordsFromText } = require('im/messenger/controller/search/experimental/get-words-from-text');

	const nothing = () => {};

	class RecentProvider
	{
		/**
		 *
		 * @param {object} params
		 * @param {function(): void} params.loadLatestSearchProcessed
		 * @param {function(Array<string>): void} params.loadLatestSearchComplete return dialog ids latest elements
		 * @param {function(Array<string>, boolean): void} params.loadSearchProcessed return dialogId ids local elements
		 * and flag that load from server is started
		 * @param {function(Array<string>, string): void} params.loadSearchComplete return resulted dialog ids
		 */
		constructor(params)
		{
			/**
			 * @private
			 * @type {MessengerCoreStore}
			 */
			this.store = core.getStore();
			/**
			 * @private
			 * @type {RecentConfig}
			 */
			this.config = new RecentConfig();
			/**
			 * @private
			 * @type {RecentServerSearchService}
			 */
			this.serverService = new RecentServerSearchService(this.config);
			/**
			 * @private
			 * @type {RecentLocalSearchService}
			 */
			this.localService = new RecentLocalSearchService();

			/**
			 * @private
			 * @type {(function(Array<string>, string): Promise<Array<string>>)}
			 */
			this.searchOnServerDelayed = debounce(this.searchOnServer, 400, this);

			/**
			 * @private
			 * @type {number}
			 */
			this.minSearchSize = MessengerParams.get('MIN_SEARCH_SIZE', 3);
			/**
			 * @private
			 * @type {function(): void}
			 */
			this.loadLatestSearchProcessedCallback = params.loadLatestSearchProcessed ?? nothing;
			/**
			 * @private
			 * @type {function(Array<string>): void}
			 */
			this.loadLatestSearchCompleteCallback = params.loadLatestSearchComplete ?? nothing;
			/**
			 * @private
			 * @type {function(Array<string>, boolean): void}
			 */
			this.loadSearchProcessedCallback = params.loadSearchProcessed ?? nothing;
			/**
			 * @private
			 * @type {function(Array<string>, string): void}
			 */
			this.loadSearchCompleteCallBack = params.loadSearchComplete ?? nothing;
		}

		/**
		 * @param {string} text
		 */
		async doSearch(text)
		{
			if (text.length === 0)
			{
				this.loadSearchProcessedCallback([], false);

				return;
			}

			const wordsFromText = getWordsFromText(text);

			const localSearchingIds = this.sortByDate(this.localService.search(wordsFromText));

			const needSearchFromServer = text.length >= this.minSearchSize;

			this.loadSearchProcessedCallback(localSearchingIds, needSearchFromServer);

			if (!needSearchFromServer)
			{
				return;
			}

			void this.searchOnServerDelayed(wordsFromText, text, localSearchingIds);
		}

		/**
		 * @return {Promise<Array<string>>}
		 */
		async loadLatestSearch()
		{
			this.loadLatestSearchProcessedCallback();
			this.serverService.loadRecent()
				.then((recentIds) => {
					this.loadLatestSearchCompleteCallback(recentIds);
				})
				.catch(() => {}) // TODO check
			;
		}

		loadRecentUsers()
		{
			/**
			 * @type {Array<string>}
			 */
			const recentUsers = [];
			this.store.getters['recentModel/getSortedCollection']().forEach((recentItem) => {
				if (DialogHelper.isDialogId(recentItem.id))
				{
					return;
				}
				const user = this.store.getters['usersModel/getById'](recentItem.id);

				if (!user || user.bot || Number(user.id) === MessengerParams.getUserId())
				{
					return;
				}
				if (user)
				{
					recentUsers.push(user.id);
				}
			});

			return recentUsers;
		}

		async saveItemToRecent(dialogId)
		{
			return this.serverService.saveItemToRecent(dialogId);
		}

		/**
		 * @private
		 * @param {Array<string>} searchingWords
		 * @param {string} originalQuery
		 * @param {Array<string>} localSearchingIds
		 */

		searchOnServer(searchingWords, originalQuery, localSearchingIds)
		{
			this.serverService.search(searchingWords, originalQuery)
				.then((remoteDialogIds) => {
					const mergedDialogIds = this.merge(localSearchingIds, remoteDialogIds);
					const resultedDialogIds = this.sortByDate(mergedDialogIds);

					this.loadSearchCompleteCallBack(resultedDialogIds, originalQuery);
				})
			;
		}

		closeSession()
		{
			void this.store.dispatch('recentModel/searchModel/clear');
		}

		/**
		 * @private
		 * @param {Array<string>} dialogIds
		 * @return {Array<string>}
		 */
		sortByDate(dialogIds)
		{
			dialogIds.sort((firstId, secondId) => {
				const firstItem = this.store.getters['recentModel/getById'](firstId)
					?? this.store.getters['recentModel/searchModel/getById'](firstId)
				;
				const secondItem = this.store.getters['recentModel/getById'](secondId)
					?? this.store.getters['recentModel/searchModel/getById'](secondId)
				;
				const firstDate = DateHelper.cast(firstItem.date_update ?? null, null);
				const secondDate = DateHelper.cast(secondItem.date_update ?? null, null);

				if (!firstDate || !secondDate)
				{
					if (!firstDate && !secondDate)
					{
						if (this.isExtranet(firstId))
						{
							return 1;
						}

						if (this.isExtranet(secondId))
						{
							return -1;
						}

						return 0;
					}

					return firstDate ? -1 : 1;
				}

				return secondDate - firstDate;
			});

			return dialogIds;
		}

		/**
		 * @private
		 * @param {string} dialogId
		 * @return {boolean}
		 */
		isExtranet(dialogId)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return false;
			}

			if (dialog.type === DialogType.user)
			{
				const user = this.store.getters['usersModel/getById'](dialogId);

				return user && user.extranet;
			}

			return dialog.extranet;
		}

		/**
		 * @private
		 * @param {Array<string>} localDialogIds
		 * @param {Array<string>} remoteDialogIds
		 * @return {Array<string>}
		 */
		merge(localDialogIds, remoteDialogIds)
		{
			const result = [...localDialogIds];

			remoteDialogIds.forEach((remoteDialogId) => {
				if (!localDialogIds.includes(remoteDialogId))
				{
					result.push(remoteDialogId);
				}
			});

			return result;
		}
	}

	module.exports = { RecentProvider };
});
