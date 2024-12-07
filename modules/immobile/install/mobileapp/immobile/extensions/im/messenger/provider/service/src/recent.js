/**
 * @module im/messenger/provider/service/recent
 */
jn.define('im/messenger/provider/service/recent', (require, exports, module) => {
	const { uniqBy } = require('utils/array');
	const { PageNavigation } = require('im/messenger/lib/page-navigation');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RecentRest } = require('im/messenger/provider/rest');
	const { Counters } = require('im/messenger/lib/counters');
	const { Feature } = require('im/messenger/lib/feature');
	const { Logger } = require('im/messenger/lib/logger');
	const { clone } = require('utils/object');

	/**
	 * @class RecentService
	 */
	class RecentService
	{
		constructor()
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
			this.recentRepository = serviceLocator.get('core').getRepository().recent;
			/**
			 * @type {PageNavigation|{}}
			 */
			this.pageNavigation = {};
			/**
			 * @type {string|null}
			 */
			this.lastActivityDate = null;
			this.lastActivityDateFromServer = null;

			this.isLoadingPageFromDb = false;
			this.hasMoreFromDb = true;

			this.initServices();
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
			const recentPinnedList = await this.recentRepository.getPinnedListByDialogTypeFilter(filterDb);
			const recentList = await this.recentRepository.getListByDialogTypeFilter(filterDb);
			this.setLastActivityDateByItems(recentList.items);
			this.pageNavigation.hasNextPage = recentList.hasMore;
			this.hasMoreFromDb = recentList.hasMore;

			return {
				items: uniqBy([...recentPinnedList.items, ...recentList.items], 'id'),
				users: uniqBy([...recentPinnedList.users, ...recentList.users], 'id'),
				messages: uniqBy([...recentPinnedList.messages, ...recentList.messages], 'id'),
				files: uniqBy([...recentPinnedList.files, ...recentList.files], 'id'),
				hasMore: recentList.hasMore,
			};
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
		 * @return {Promise<any>}
		 */
		async getPageFromDb(filterDb = {})
		{
			if (Feature.isLocalStorageEnabled && this.isLoadingPageFromDb === false && this.hasMoreFromDb)
			{
				this.isLoadingPageFromDb = true;

				try
				{
					this.hasMoreFromDb = await this.loadPageFromDb(this.lastActivityDate, filterDb);
					this.pageNavigation.hasNextPage = this.hasMoreFromDb;
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
		}

		/**
		 * @param {string} lastActivityDate
		 * @param {ListByDialogTypeFilter} filterDb
		 * @return {Promise<hasMore:boolean>}
		 */
		async loadPageFromDb(lastActivityDate, filterDb)
		{
			Logger.log(`${this.constructor.name} loadPageFromDb, lastActivityDate`, lastActivityDate, filterDb);
			const result = await this.recentRepository.getListByDialogTypeFilter({ ...filterDb, lastActivityDate });
			await this.updateModelsByDbResult(result);
			this.setLastActivityDateByItems(result.items);

			return result.hasMore;
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
		 * @param {{items: Array, users: Array, messages: Array, files: Array, hasMore: boolean}} result
		 * @return {Promise<void>}
		 */
		async updateModelsByDbResult(result)
		{
			try
			{
				const dialogues = result.items.map((item) => item.chat);
				await this.store.dispatch('dialoguesModel/setCollectionFromLocalDatabase', dialogues);
				await this.store.dispatch('usersModel/merge', result.users);
				await this.store.dispatch('messagesModel/store', result.messages);
				await this.store.dispatch('filesModel/setFromLocalDatabase', result.files);
				await this.store.dispatch('recentModel/set', result.items);
			}
			catch (error)
			{
				Logger.error(`${this.constructor.name}.updateModelsByDbResult.catch:`, error);
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
		 * @param {number} lastMessageId
		 * @param {object} restOptions
		 * @return {Promise<any>}
		 */
		getCollabPageFromService(lastMessageId, restOptions)
		{
			const options = { limit: 50, ...restOptions };

			if (this.pageNavigation.currentPage > 1)
			{
				options.filter = {
					lastMessageId,
				};
			}

			// TODO: implement collab rest
			return RecentRest.getChannelList(options);
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
				Logger.error(`${this.constructor.name}.removeUnreadState.recentRest.read is item read error`, result.error());

				this.setRecentModelWithCounters({
					id: dialogId,
					unread: unreadBeforeChange,
				});
			});
		}

		/**
		 * @param {object} params
		 * @param {string|number} params.id
		 * @param {boolean} params.unread
		 */
		setRecentModelWithCounters(params)
		{
			this.store.dispatch('recentModel/set', [params])
				.then(() => {
					Counters.update();
				})
				.catch((err) => Logger.error(`${this.constructor.name}.setRecentModelWithCounters.recentModel/set.catch:`, err));
		}
	}

	module.exports = {
		RecentService,
	};
});
