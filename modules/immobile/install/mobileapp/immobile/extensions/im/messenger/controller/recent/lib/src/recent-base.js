/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/lib/recent-base
 */
jn.define('im/messenger/controller/recent/lib/recent-base', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { RecentRenderer } = require('im/messenger/controller/recent/lib/renderer');
	const { ItemAction } = require('im/messenger/controller/recent/lib/item-action');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { Feature } = require('im/messenger/lib/feature');
	const { RecentService } = require('im/messenger/provider/service');
	const { Worker } = require('im/messenger/lib/helper');
	const { DialogType, EventType } = require('im/messenger/const');
	const { Counters } = require('im/messenger/lib/counters');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DraftCache } = require('im/messenger/cache');
	const { Logger } = require('im/messenger/lib/logger');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');

	/**
	 * @class BaseRecent
	 */
	class BaseRecent
	{
		constructor(options = {})
		{
			/**
			 * @type {Logger}
			 */
			this.logger = options.logger || Logger;

			/**
			 * @type {RecentView}
			 */
			this.view = options.view;

			/**
			 * @type {Array<CallItem>}
			 */
			this.callList = [];

			/**
			 * @type {ItemAction|{}}
			 */
			this.itemAction = {};

			/**
			 * @type {MessengerInitService}
			 */
			this.messagerInitService = serviceLocator.get('messenger-init-service');

			/**
			 * @type {RecentService|{}}
			 */
			this.recentService = {};
		}

		async init()
		{
			this.initView();
			this.initStore();
			this.initServices();
			await this.fillStoreFromCache();
			this.bindMethods();
			this.subscribeStoreEvents();
			this.subscribeMessengerEvents();
			this.subscribeInitCounters();
			this.subscribeViewEvents();
			this.initItemAction();
			await this.drawCacheItems();
			this.initWorker();
			this.subscribeInitMessengerEvent();
		}

		initView()
		{
			if (this.view)
			{
				this.renderer = new RecentRenderer({
					view: this.view,
				});
			}
			else
			{
				throw new Error(`${this.constructor.name} options.view is required`);
			}
		}

		initStore()
		{
			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
		}

		initServices()
		{
			this.recentService = new RecentService();
		}

		/**
		 * @return {Promise<any>}
		 */
		async fillStoreFromCache()
		{
			await this.fillDraftStore();
		}

		async fillDraftStore()
		{
			const draftState = DraftCache.get();
			if (draftState)
			{
				await this.store.dispatch('draftModel/setState', draftState);
			}
		}

		bindMethods()
		{
			this.loadPage = this.loadPage.bind(this);
			this.stopRefreshing = this.stopRefreshing.bind(this);
			this.renderInstant = this.renderInstant.bind(this);
			this.subscribeInitMessengerEvent = this.subscribeInitMessengerEvent.bind(this);
			this.updatePageFromServer = this.updatePageFromServer.bind(this);
		}

		subscribeStoreEvents()
		{}

		subscribeMessengerEvents()
		{}

		subscribeViewEvents()
		{}

		subscribeInitMessengerEvent()
		{
			this.messagerInitService.onInit(this.updatePageFromServer);
		}

		subscribeInitCounters()
		{
			Counters.subscribeInitMessengerEvent();
		}

		initItemAction()
		{
			this.itemAction = new ItemAction();
		}

		async drawCacheItems()
		{
			if (Feature.isLocalStorageEnabled)
			{
				await this.fillStoreFromFirstDbPage();
			}

			let firstPage = clone(
				this.store.getters['recentModel/getRecentPage'](1, this.recentService.pageNavigation.itemsPerPage),
			);

			if (firstPage.length === 0)
			{
				this.view.setItems([...this.callList]);
				if (!this.view.isLoaderShown)
				{
					this.view.showLoader();
				}

				return;
			}

			firstPage = RecentConverter.toList(firstPage);

			this.view.setItems([...this.callList, ...firstPage]);

			this.recentService.pageNavigation.isPageLoading = false;
			if (this.recentService.hasMoreFromDb && !this.view.isLoaderShown)
			{
				this.view.showLoader();
				// This is a solution to add a loader before scrolling in advance. After scrolling, the loader will hide
			}
		}

		/**
		 * @return {Promise<{items: Array, users: Array, hasMore: boolean}>}
		 */
		async fillStoreFromFirstDbPage()
		{
			const recentList = await this.getFirstPageFromDb();
			await this.getSubDataFromDb();
			this.logger.info(`${this.constructor.name}.drawCacheItems.recentList:`, recentList);
			const dialogues = recentList.items.map((item) => item.chat);
			await this.store.dispatch('dialoguesModel/setCollectionFromLocalDatabase', dialogues);
			if (recentList?.users.length > 0)
			{
				const collection = recentList.users.reduce((acc, user) => {
					// eslint-disable-next-line no-param-reassign
					acc[user.id] = user;

					return acc;
				}, {});

				await this.store.dispatch('usersModel/setState', { collection });
			}

			if (recentList?.messages.length > 0)
			{
				await this.store.dispatch('messagesModel/store', recentList?.messages);
			}

			if (recentList?.files.length > 0)
			{
				await this.store.dispatch('filesModel/setFromLocalDatabase', recentList?.files);
			}

			await this.store.dispatch('recentModel/setState', { collection: recentList.items });

			return recentList;
		}

		/**
		 * @return {Promise<{items: Array, users: Array, messages: Array, files: Array, hasMore: boolean}>}
		 */
		async getFirstPageFromDb()
		{
			return this.recentService.getFirstPageFromDb(this.getDbFilter());
		}

		/**
		 * @return {Promise<{any}>}
		 */
		async getSubDataFromDb()
		{
			return new Promise((resolve, reject) => {
				resolve(true);
			});
		}

		initWorker()
		{
			this.loadPageAfterErrorWorker = new Worker({
				frequency: 5000,
				callback: this.getWorkerCallBack(),
			});
		}

		/**
		 * @return {Function}
		 */
		getWorkerCallBack()
		{
			return this.loadPageFromServerHandler.bind(this);
		}

		loadNextPage()
		{
			this.loadPage()
				.catch((error) => {
					this.logger.error(`${this.constructor.name}.loadNextPage.loadPage catch:`, error);
				});
		}

		async loadPage()
		{
			const isHasNextPage = this.recentService.pageNavigation.hasNextPage;
			if (!isHasNextPage)
			{
				return;
			}

			await this.loadPageFromDbHandler();
			await this.loadPageFromServerHandler();
		}

		async loadPageFromDbHandler()
		{
			if (Feature.isLocalStorageEnabled && this.recentService.hasMoreFromDb)
			{
				await this.getPageFromDb();
				this.renderInstant();
				if (this.recentService.hasMoreFromDb === false)
				{
					this.view.hideLoader();
				}
			}
		}

		async loadPageFromServerHandler()
		{
			this.recentService.pageNavigation.isPageLoading = true;
			this.getPageFromServer()
				.then((response) => this.pageHandler(response.data()))
				.catch(() => {
					this.logger.error(
						`${this.constructor.name}.loadPage.getPageFromServer: page ${this.recentService.pageNavigation.currentPage} loading error, try again in ${this.loadPageAfterErrorWorker.frequency / 1000} seconds.`,
					);

					if (!this.loadPageAfterErrorWorker.isHasOnce())
					{
						this.loadPageAfterErrorWorker.startOnce();
					}
				})
			;
		}

		/**
		 * @param {
		 * immobileTabChatLoadResult.recentList
		 * | immobileTabCopilotLoadResult.recentList
		 * | immobileTabChannelLoadResult.recentList
		 * } data.recentList
		 */
		updatePageFromServer(data)
		{
			const recentList = data.recentList;

			if (Type.isNil(recentList) || !Type.isPlainObject(recentList))
			{
				this.logger.error(`${this.constructor.name}.updatePageFromServer`, recentList);

				return;
			}

			this.pageHandler(recentList)
				.then(() => {
					if (recentList.hasMore && !this.view.isLoaderShown)
					{
						this.view.showLoader();
					}
				})
				.catch((err) => this.logger.error(`${this.constructor.name}.pageHandler.catch:`, err));
		}

		/**
		 * @param {object} data
		 */
		pageHandler(data)
		{
			return new Promise((resolve) => {
				this.logger.info(`${this.constructor.name}.pageHandler data:`, data);
				this.recentService.pageNavigation.turnPage();

				if (Type.isBoolean(data.hasMore))
				{
					this.recentService.pageNavigation.hasNextPage = data.hasMore;
				}

				if (data.items.length > 0)
				{
					const lastItem = data.items[data.items.length - 1];
					const lastActivityDate = lastItem.date_last_activity ?? lastItem.message.date;
					this.recentService.lastActivityDateFromServer = lastActivityDate;
					this.recentService.lastActivityDate = new Date(lastActivityDate).toISOString();
				}
				else
				{
					this.view.hideLoader();
				}

				this.saveRecentData(data.items)
					.then(() => {
						this.recentService.pageNavigation.isPageLoading = false;

						this.renderInstant();
						this.checkEmpty();

						resolve();
					})
					.catch((error) => {
						this.logger.error(`${this.constructor.name}.saveRecentData error:`, error);
					})
				;
			});
		}

		departmentColleaguesGetHandler()
		{}

		/**
		 * @param {Array<object>} items
		 */
		addItems(items)
		{
			if (!Type.isArrayFilled(items))
			{
				return;
			}

			this.renderer.do('add', items);
			if (!this.recentService.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.renderer.nextTick(() => this.view.hideLoader());
			}

			this.checkEmpty();
		}

		/**
		 * @param {Array<object>} items
		 */
		updateItems(items)
		{
			if (!Type.isArrayFilled(items))
			{
				return;
			}

			this.renderer.do('update', items);
			if (!this.recentService.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.view.hideLoader();
			}

			this.checkEmpty();
		}

		/**
		 * @param {Array<object>} recentItems
		 * @return {Promise<any>}
		 */
		async saveRecentData(recentItems)
		{
			const modelData = this.prepareDataForModels(recentItems);

			const usersPromise = await this.store.dispatch('usersModel/set', modelData.users);
			const dialoguesPromise = await this.store.dispatch('dialoguesModel/set', modelData.dialogues);
			const recentPromise = await this.store.dispatch('recentModel/set', modelData.recent);

			if (this.recentService.pageNavigation.currentPage === 1)
			{
				const recentIndex = [];
				modelData.recent.forEach((item) => recentIndex.push(item.id.toString()));

				const idListForDeleteFromCache = [];
				this.store.getters['recentModel/getCollection']()
					.forEach((item) => {
						if (!recentIndex.includes(item.id.toString()))
						{
							idListForDeleteFromCache.push(item.id);
						}
					});

				idListForDeleteFromCache.forEach((id) => {
					this.store.dispatch('recentModel/deleteFromModel', { id });
				});

				await this.saveShareDialogCache(modelData.recent);
			}

			return Promise.all([usersPromise, dialoguesPromise, recentPromise]);
		}

		/**
		 * @param {Array} recentItems
		 * @return {Promise}
		 */
		saveShareDialogCache(recentItems)
		{
			return ShareDialogCache.saveRecentItemList(recentItems);
		}

		/**
		 * @return {Boolean}
		 */
		checkEmpty()
		{
			if (this.store.getters['recentModel/isEmpty']())
			{
				this.showWelcomeScreen();

				return true;
			}

			this.view.hideWelcomeScreen();

			return false;
		}

		showWelcomeScreen()
		{
			this.view.showWelcomeScreen();
		}

		stopRefreshing()
		{
			this.logger.info(`${this.constructor.name}.stopRefreshing`);
			this.view.stopRefreshing();
		}

		renderInstant()
		{
			this.logger.info(`${this.constructor.name}.renderInstant`);
			this.renderer.render();
		}

		/**
		 * @param {string|number} dialogId
		 * @param {string|null} [componentCode=null]
		 * @param checkComponentCode
		 */
		openDialog(dialogId, componentCode = null, checkComponentCode = false)
		{
			this.recentService.removeUnreadState(dialogId);

			MessengerEmitter.emit(
				EventType.messenger.openDialog,
				{
					dialogId,
					checkComponentCode,
				},
				componentCode,
			);
		}

		/**
		 * @return {Promise<any>}
		 */
		getPageFromDb()
		{
			return this.recentService.getPageFromDb(this.getDbFilter());
		}

		/**
		 * @return {Promise<any>}
		 */
		getPageFromServer()
		{
			return this.recentService.getPageFromServer(this.getRestListOptions());
		}

		/**
		 * @return {object}
		 */
		getRestListOptions()
		{
			return { skipOpenlines: true, onlyCopilot: false };
		}

		/**
		 * @return {ListByDialogTypeFilter}
		 */
		getDbFilter()
		{
			return { exceptDialogTypes: [DialogType.copilot], limit: this.recentService.getRecentListRequestLimit() };
		}

		/**
		 * @param {Array<object>} items
		 * @return {object}
		 */
		// eslint-disable-next-line sonarjs/cognitive-complexity
		prepareDataForModels(items)
		{
			const result = {
				users: [],
				dialogues: [],
				recent: [],
			};

			items.forEach((item) => {
				if (item.user && item.user.id > 0)
				{
					result.users.push(item.user);
				}
				let dialogItem = {};

				if (item.chat)
				{
					dialogItem = {
						...item.chat,
						counter: item.counter,
						dialogId: item.id,
					};
					if (item.message)
					{
						dialogItem.lastMessageId = item.message.id;
					}
				}

				const isUserDialog = item.type === DialogType.user || item.type === DialogType.private;
				if (isUserDialog && item.user)
				{
					dialogItem = {
						dialogId: item.user.id,
						avatar: item.user.avatar,
						color: item.user.color,
						name: item.user.name,
						type: DialogType.user,
						counter: item.counter,

						// required to update the added column in the b_im_dialog table
						permissions: ChatPermission.getActionGroupsByChatType(DialogType.user),
					};
					if (item.message)
					{
						dialogItem.lastMessageId = item.message.id;
					}

					// for new users the chatId is 0 for some reason
					if (item.chat_id > 0)
					{
						dialogItem.chatId = item.chat_id;
					}
				}

				if (item.last_id)
				{
					dialogItem.last_id = item.last_id;
				}

				result.dialogues.push(dialogItem);

				result.recent.push({
					...item,
					avatar: item.avatar.url,
					color: item.avatar.color,
					counter: dialogItem.counter,
				});
			});

			return result;
		}

		recentDeleteHandler(mutation)
		{
			this.renderer.removeFromQueue(mutation.payload.data.id);

			this.view.removeItem({ id: mutation.payload.data.id });
			if (!this.recentService.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.view.hideLoader();
			}
			Counters.update();

			this.checkEmpty();
		}
	}

	module.exports = {
		BaseRecent,
	};
});
