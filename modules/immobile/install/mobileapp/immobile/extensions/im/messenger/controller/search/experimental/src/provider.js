/**
 * @module im/messenger/controller/search/experimental/provider
 */
jn.define('im/messenger/controller/search/experimental/provider', (require, exports, module) => {
	const { RecentConfig } = require('im/messenger/controller/search/experimental/config');
	const { RecentLocalSearchService } = require('im/messenger/controller/search/experimental/service/local-search-service');
	const { RecentServerSearchService } = require('im/messenger/controller/search/experimental/service/server-search-service');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
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
			this.store = serviceLocator.get('core').getStore();
			/**
			 * @private
			 * @type {MessengerCoreStoreManager}
			 */
			this.messengerStore = serviceLocator.get('core').getMessengerStore();
			/**
			 * @protected
			 * @type {RecentConfig}
			 */
			this.config = null;
			/**
			 * @protected
			 * @type {RecentServerSearchService}
			 */
			this.serverService = null;
			/**
			 * @protected
			 * @type {RecentLocalSearchService}
			 */
			this.localService = null;

			/**
			 * @protected
			 * @type {(function(Array<string>, string): Promise<Array<string>>)}
			 */
			this.searchOnServerDelayed = debounce(this.searchOnServer, 400, this);

			/**
			 * @protected
			 * @type {number}
			 */
			this.minSearchSize = MessengerParams.get('SEARCH_MIN_SIZE', 3);
			/**
			 * @protected
			 * @type {function(): void}
			 */
			this.loadLatestSearchProcessedCallback = params.loadLatestSearchProcessed ?? nothing;
			/**
			 * @protected
			 * @type {function(Array<string>): void}
			 */
			this.loadLatestSearchCompleteCallback = params.loadLatestSearchComplete ?? nothing;
			/**
			 * @protected
			 * @type {function(Array<string>, boolean): void}
			 */
			this.loadSearchProcessedCallback = params.loadSearchProcessed ?? nothing;
			/**
			 * @protected
			 * @type {function(Array<string>, string): void}
			 */
			this.loadSearchCompleteCallBack = params.loadSearchComplete ?? nothing;

			this.initConfig();
			this.initServices();
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
		 * @protected
		 */
		initServices()
		{
			/**
			 * @protected
			 * @type {RecentServerSearchService}
			 */
			this.serverService = new RecentServerSearchService(this.config);
			/**
			 * @protected
			 * @type {RecentLocalSearchService}
			 */
			this.localService = new RecentLocalSearchService();
		}

		/**
		 * @protected
		 */
		initConfig()
		{
			/**
			 * @protected
			 * @type {RecentConfig}
			 */
			this.config = new RecentConfig();
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
				const firstDate = DateHelper.cast(firstItem.dateMessage ?? null, null);
				const secondDate = DateHelper.cast(secondItem.dateMessage ?? null, null);

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
