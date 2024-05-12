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
	const { PageNavigation } = require('im/messenger/lib/page-navigation');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { Feature } = require('im/messenger/lib/feature');
	const { CountersService } = require('im/messenger/provider/service');
	const { Worker } = require('im/messenger/lib/helper');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RestMethod, EventType, DialogType } = require('im/messenger/const');
	const { Counters } = require('im/messenger/lib/counters');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { RecentRest } = require('im/messenger/provider/rest');
	const { DraftCache } = require('im/messenger/cache');
	const { Logger } = require('im/messenger/lib/logger');

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
			 * @type {string|null}
			 */
			this.lastMessageDate = null;

			/**
			 * @type {Array<CallItem>}
			 */
			this.callList = [];

			/**
			 * @type {ItemAction|{}}
			 */
			this.itemAction = {};

			/**
			 * @type {PageNavigation|{}}
			 */
			this.pageNavigation = {};

			/**
			 * @type {CountersService|{}}
			 */
			this.countersService = {};
		}

		async init()
		{
			this.initView();
			this.initStore();
			this.initServices();
			await this.fillStoreFromCache();
			this.initRequests();
			this.bindMethods();
			this.subscribeStoreEvents();
			this.subscribeMessengerEvents();
			this.subscribeViewEvents();
			this.initItemAction();
			await this.drawCacheItems();
			this.countersLoad();
			this.initWorker();
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
				throw new Error(`${this.getClassName()} options.view is required`);
			}
		}

		initStore()
		{
			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.recentRepository = serviceLocator.get('core').getRepository().recent;
		}

		initServices()
		{
			this.pageNavigation = new PageNavigation(this.getOptionPageNavigation());
			this.countersService = CountersService.getInstance();
		}

		/**
		 * @return {object}
		 */
		getOptionPageNavigation()
		{
			return {
				currentPage: 1,
				itemsPerPage: 50,
				isPageLoading: true,
			};
		}

		/**
		 * @return {Promise<any>}
		 */
		async fillStoreFromCache()
		{
			return Promise.resolve(true);
		}

		/**
		 * @param {object} cache
		 * @return {Promise<any>}
		 */
		async fillStore(cache)
		{
			if (cache && cache.users)
			{
				await this.fillUserStore(cache);
			}

			if (cache && cache.dialogues)
			{
				await this.fillDialogStore(cache);
			}

			if (cache && cache.recent)
			{
				await this.fillRecentStore(cache);
			}

			const draftState = DraftCache.get();
			if (draftState)
			{
				await this.store.dispatch('draftModel/setState', draftState);
			}
		}

		async fillUserStore(cache)
		{
			let usersCache = cache.users;
			if (Feature.isLocalStorageEnabled)
			{
				const userIdList = Object.keys(cache.users.collection);
				const userList = await serviceLocator.get('core').getRepository().user.userTable.getListByIds(userIdList);
				if (userList.items.length > 0)
				{
					const dbUserCache = {
						collection: {},
					};

					userList.items.forEach((user) => {
						dbUserCache.collection[user.id] = user;
					});

					usersCache = dbUserCache;
				}
			}

			return this.store.dispatch('usersModel/setState', usersCache);
		}

		async fillDialogStore(cache)
		{
			let dialogCache = cache.dialogues;
			if (Feature.isLocalStorageEnabled)
			{
				const dialogIdList = Object.keys(cache.dialogues.collection);
				const dialogList = await serviceLocator.get('core').getRepository().dialog.dialogTable.getListByDialogIds(dialogIdList);
				if (dialogList.items.length > 0)
				{
					const dbDialogCache = {
						collection: {},
					};

					dialogList.items.forEach((dialog) => {
						dbDialogCache.collection[dialog.dialogId] = dialog;
					});

					dialogCache = dbDialogCache;
				}
			}

			return this.store.dispatch('dialoguesModel/setState', dialogCache);
		}

		async fillRecentStore(cache)
		{
			// invalidation of recent elements without dialog
			// eslint-disable-next-line no-param-reassign
			cache.recent.collection = cache.recent.collection.filter((recentItem) => {
				if (cache.dialogues.collection[recentItem.id])
				{
					return true;
				}

				this.logger.error(
					`${this.getClassName()}.save: there is no dialog ${recentItem.id} in model`,
					cache.recent,
					cache.dialogues,
					cache.users,
				);

				return false;
			});

			return this.store.dispatch('recentModel/setState', cache.recent);
		}

		initRequests()
		{
			restManager.on(
				RestMethod.imRecentList,
				this.getRestRecentListOptions(),
				this.firstPageHandler.bind(this),
			);

			restManager.once(
				RestMethod.imDepartmentColleaguesGet,
				this.getRestDepartmentOptions(),
				this.departmentColleaguesGetHandler.bind(this),
			);

			this.countersInitRequest();
		}

		/**
		 * @return {object}
		 */
		getRestRecentListOptions()
		{
			return { SKIP_OPENLINES: 'Y' };
		}

		/**
		 * @return {object}
		 */
		getRestDepartmentOptions()
		{
			return {
				USER_DATA: 'Y',
				LIMIT: 50,
			};
		}

		countersInitRequest()
		{
			Counters.initRequests();
		}

		bindMethods()
		{
			this.firstPageHandler = this.firstPageHandler.bind(this);
			this.loadPage = this.loadPage.bind(this);
			this.departmentColleaguesGetHandler = this.departmentColleaguesGetHandler.bind(this);
			this.stopRefreshing = this.stopRefreshing.bind(this);
			this.renderInstant = this.renderInstant.bind(this);
		}

		subscribeStoreEvents()
		{}

		subscribeMessengerEvents()
		{}

		subscribeViewEvents()
		{}

		initItemAction()
		{
			this.itemAction = new ItemAction();
		}

		async drawCacheItems()
		{
			if (Feature.isLocalStorageEnabled)
			{
				const recentList = await this.recentRepository.getList();
				this.logger.info(`${this.getClassName()}.drawCacheItems.recentList:`, recentList);
			}

			let firstPage = clone(
				this.store.getters['recentModel/getRecentPage'](1, this.pageNavigation.itemsPerPage),
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
		}

		countersLoad()
		{
			this.countersService.load()
				.catch((err) => this.logger.error(`${this.getClassName()}.init.countersLoad.catch:`, err));
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
			return this.loadPage;
		}

		loadNextPage()
		{
			this.pageNavigation.turnPage();

			this.loadPage();
		}

		firstPageHandler(response)
		{
			const error = response.error();
			if (error)
			{
				this.logger.error(`${this.getClassName()}.firstPageHandler`, error);

				return;
			}

			this.pageHandler(response)
				.then(() => {
					if (response.data().hasMore && !this.view.isLoaderShown)
					{
						this.view.showLoader();
					}
				})
				.catch((err) => this.logger.error(`${this.getClassName()}.pageHandler.catch:`, err));
		}

		loadPage()
		{
			this.pageNavigation.isPageLoading = true;

			this.getPageFromService()
				.then((response) => this.pageHandler(response))
				.catch(() => {
					this.logger.error(
						`${this.getClassName()}.loadPage: page ${this.pageNavigation.currentPage} loading error, try again in ${this.loadPageAfterErrorWorker.frequency / 1000} seconds.`,
					);
					this.loadPageAfterErrorWorker.startOnce();
				})
			;
		}

		/**
		 * @param {object} response
		 */
		pageHandler(response)
		{
			return new Promise((resolve) => {
				const data = response.data();
				this.logger.info(`${this.getClassName()}.pageHandler data:`, data);

				if (data.hasMore === false)
				{
					this.pageNavigation.hasNextPage = false;
				}

				if (data.items.length > 0)
				{
					this.lastMessageDate = data.items[data.items.length - 1].message.date;
				}
				else
				{
					this.view.hideLoader();
				}

				this.saveRecentItems(data.items)
					.then(() => {
						this.pageNavigation.isPageLoading = false;

						this.renderInstant();
						this.checkEmpty();

						resolve();
					})
					.catch((error) => {
						this.logger.error(`${this.getClassName()}.saveRecentItems error:`, error);
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
			if (!this.pageNavigation.hasNextPage && this.view.isLoaderShown)
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
			if (!this.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.renderer.nextTick(() => this.view.hideLoader());
			}

			this.checkEmpty();
		}

		/**
		 * @param {Array<object>} recentItems
		 * @return {Promise<any>}
		 */
		async saveRecentItems(recentItems)
		{
			const modelData = this.prepareDataForModels(recentItems);

			const usersPromise = await this.store.dispatch('usersModel/set', modelData.users);
			const dialoguesPromise = await this.store.dispatch('dialoguesModel/set', modelData.dialogues);
			const recentPromise = await this.store.dispatch('recentModel/set', modelData.recent);

			if (this.pageNavigation.currentPage === 1)
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
					this.store.dispatch('recentModel/delete', { id });
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
			this.logger.info(`${this.getClassName()}.stopRefreshing`);
			this.view.stopRefreshing();
		}

		renderInstant()
		{
			this.logger.info(`${this.getClassName()}.renderInstant`);
			this.renderer.render();
		}

		/**
		 * @param {string|number} dialogId
		 * @param {string|null} [componentCode=null]
		 */
		openDialog(dialogId, componentCode = null)
		{
			this.removeUnreadState(dialogId);

			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId }, componentCode);
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
				this.logger.error(`${this.getClassName()}.removeUnreadState.recentRest.read is item read error`, result.error());

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
				.catch((err) => this.logger.error(`${this.getClassName()}.setRecentModelWithCounters.recentModel/set.catch:`, err));
		}

		/**
		 * @return {Promise<any>}
		 */
		getPageFromService()
		{
			const options = this.getRestListOptions();

			if (this.pageNavigation.currentPage > 1)
			{
				options.lastMessageDate = this.lastMessageDate;
			}

			return RecentRest.getList(options);
		}

		/**
		 * @return {object}
		 */
		getRestListOptions()
		{
			return { skipOpenlines: true, onlyCopilot: false };
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

		/**
		 * @desc get class name for logger
		 * @return {string}
		 */
		getClassName()
		{
			return this.constructor.name;
		}
	}

	module.exports = {
		BaseRecent,
	};
});
