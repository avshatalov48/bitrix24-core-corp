/**
 * @module im/messenger/provider/services/recent/service
 */
jn.define('im/messenger/provider/services/recent/service', (require, exports, module) => {
	const { PageNavigation } = require('im/messenger/lib/page-navigation');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RecentRest } = require('im/messenger/provider/rest');
	const { PinService } = require('im/messenger/provider/services/recent/pin');
	const { HideService } = require('im/messenger/provider/services/recent/hide');
	const { Feature } = require('im/messenger/lib/feature');
	const { Logger } = require('im/messenger/lib/logger');
	const { clone } = require('utils/object');

	/**
	 * @class RecentService
	 */
	class RecentService
	{
		/** @type {PinService} */
		#pinService;
		/** @type {HideService} */
		#hideService;
		#lastMessageDate;

		constructor()
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
			/** @type {RecentRepository} */
			this.recentRepository = this.#getRepository('recent');
			/** @type {PageNavigation|{}} */
			this.pageNavigation = {};
			/** @type {string|null} */
			this.lastActivityDate = null;
			this.lastActivityDateFromServer = null;
			this.#lastMessageDate = null;

			this.isLoadingPageFromDb = false;
			this.hasMoreFromDb = true;

			this.initServices();
		}

		/**
		 * @param {string} name
		 */
		#getRepository(name)
		{
			return serviceLocator.get('core').getRepository()?.[name];
		}

		getRecentListRequestLimit()
		{
			return 50;
		}

		/**
		 * @private
		 */
		initServices()
		{
			this.pageNavigation = new PageNavigation(this.getPageNavigationOptions());
		}

		/**
		 * @param {ListByDialogTypeFilter} filterDb
		 * @return {Promise<{items: Array, users: Array, messages: Array, files: Array, hasMore: boolean}>}
		 */
		async getFirstPageFromDb(filterDb)
		{
			const recentList = await this.recentRepository.getListByDialogTypeFilter(filterDb);
			this.setLastActivityDateByItems(recentList.items);
			this.pageNavigation.hasNextPage = recentList.hasMore;
			this.hasMoreFromDb = recentList.hasMore;

			return recentList;
		}

		/**
		 * @return {Promise<any>}
		 */
		async getCopilotDataFromDb()
		{
			const copilotData = await serviceLocator.get('core').getRepository().copilot.getList();
			if (copilotData.length > 0)
			{
				await this.store.dispatch('dialoguesModel/copilotModel/setCollection', copilotData);
			}
		}

		/**
		 * @param {object} restOptions
		 * @return {Promise<any>}
		 */
		async getPageFromServer(restOptions)
		{
			const options = restOptions;
			if (this.pageNavigation.currentPage > 1)
			{
				options.lastActivityDate = this.lastActivityDateFromServer;
			}

			return RecentRest.getList(options);
		}

		/**
		 * @param {ListByDialogTypeFilter} filterDb
		 * @return {Promise<{
		 * items: Array<RecentStoredData>,
		 * users: Array<UserStoredData>,
		 * messages: Array,
		 * files: Array,
		 * hasMore: boolean
		 * }>}
		 */
		async getPageFromDb(filterDb = {})
		{
			if (Feature.isLocalStorageEnabled && this.isLoadingPageFromDb === false && this.hasMoreFromDb)
			{
				this.isLoadingPageFromDb = true;

				try
				{
					const result = await this.loadPageFromDb(this.lastActivityDate, filterDb);
					this.hasMoreFromDb = result.hasMore;

					this.pageNavigation.hasNextPage = this.hasMoreFromDb;

					return result;
				}
				catch (error)
				{
					Logger.error(`${this.constructor.name}.getPageFromDb catch`, error);
				}
				finally
				{
					this.isLoadingPageFromDb = false;
				}
			}

			return Promise.resolve({
				items: [],
				users: [],
				messages: [],
				files: [],
				hasMore: false,
			});
		}

		/**
		 * @param {string} lastActivityDate
		 * @param {ListByDialogTypeFilter} filterDb
		 * @return {Promise<{
		 * items: Array<RecentStoredData>,
		 * users: Array<UserStoredData>,
		 * messages: Array,
		 * files: Array,
		 * hasMore: boolean
		 * }>}
		 */
		async loadPageFromDb(lastActivityDate, filterDb)
		{
			Logger.log(`${this.constructor.name} loadPageFromDb, lastActivityDate`, lastActivityDate, filterDb);
			const result = await this.recentRepository.getListByDialogTypeFilter({ ...filterDb, lastActivityDate });
			this.setLastActivityDateByItems(result.items);

			return result;
		}

		/**
		 * @param {Array<object>} items
		 */
		setLastActivityDateByItems(items)
		{
			try
			{
				const lastActivityDateObj = items[items.length - 1]?.lastActivityDate;
				this.lastActivityDate = lastActivityDateObj?.toISOString() ?? null;
			}
			catch (error)
			{
				Logger.error(`${this.constructor.name}.setLastActivityDateByItems.catch:`, error);
			}
		}

		/**
		 * @param {number} lastMessageId
		 * @param {object} restOptions
		 * @return {Promise<any>}
		 */
		getChannelPageFromService(lastMessageId, restOptions)
		{
			const options = { limit: 50, ...restOptions };

			if (this.pageNavigation.currentPage > 1)
			{
				options.filter = {
					lastMessageId,
				};
			}

			return RecentRest.getChannelList(options);
		}

		/**
		 * @return {Promise<any>}
		 */
		async getCollabPageFromService()
		{
			const options = {
				limit: 50,
			};

			if (this.pageNavigation.currentPage > 1)
			{
				options.filter = {
					lastMessageDate: this.#lastMessageDate,
				};
			}

			const result = await RecentRest.getCollabList(options);

			this.#lastMessageDate = this.#getLastMessageDate(result.data());

			return result;
		}

		/**
		 * @return {object}
		 */
		getPageNavigationOptions()
		{
			return {
				currentPage: 1,
				itemsPerPage: 50,
				isPageLoading: true,
			};
		}

		/**
		 * @param {string|number} dialogId
		 */
		removeUnreadState(dialogId)
		{
			// eslint-disable-next-line max-len
			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			const unreadBeforeChange = recentItem.unread;

			this.setRecentModelWithCounters({
				id: dialogId,
				unread: false,
			});

			RecentRest.read({ dialogId }).catch((result) => {
				Logger.error(
					`${this.constructor.name}.removeUnreadState.recentRest.read is item read error`,
					result.error(),
				);

				this.setRecentModelWithCounters({
					id: dialogId,
					unread: unreadBeforeChange,
				});
			});
		}

		/**
		 * @param {object} recentFields
		 * @param {string|number} recentFields.id
		 * @param {boolean} recentFields.unread
		 */
		setRecentModelWithCounters(recentFields)
		{
			this.store.dispatch('recentModel/set', [recentFields])
				.then(() => {
					serviceLocator.get('tab-counters').update();
				})
				.catch((err) => Logger.error(
					`${this.constructor.name}.setRecentModelWithCounters.recentModel/set.catch:`,
					err,
				));
		}

		#getLastMessageDate(restResult)
		{
			const messages = this.#filterPinnedItemsMessages(restResult);
			if (messages.length === 0)
			{
				return '';
			}

			// comparing strings in atom format works correctly because the format is lexically sortable
			let firstMessageDate = messages[0].date;
			messages.forEach((message) => {
				if (message.date < firstMessageDate)
				{
					firstMessageDate = message.date;
				}
			});

			return firstMessageDate;
		}

		#filterPinnedItemsMessages(restResult)
		{
			const {
				messages,
				recentItems,
			} = restResult;

			return messages.filter((message) => {
				const chatId = message.chat_id;
				const recentItem = recentItems.find((item) => {
					return item.chatId === chatId;
				});

				return recentItem.pinned === false;
			});
		}

		get pinService()
		{
			this.#pinService = this.#pinService ?? new PinService();

			return this.#pinService;
		}

		get hideService()
		{
			this.#hideService = this.#hideService ?? new HideService();

			return this.#hideService;
		}

		pinChat(dialogId)
		{
			this.pinService.pinChat(dialogId);
		}

		unpinChat(dialogId)
		{
			this.pinService.unpinChat(dialogId);
		}

		hideChat(dialogId)
		{
			this.hideService.hideChat(dialogId);
		}
	}

	module.exports = {
		RecentService,
	};
});
