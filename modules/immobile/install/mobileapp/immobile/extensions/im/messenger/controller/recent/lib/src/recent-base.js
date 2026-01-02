/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/lib/recent-base
 */
jn.define('im/messenger/controller/recent/lib/recent-base', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { uniqBy } = require('utils/array');
	const { RecentRenderer } = require('im/messenger/controller/recent/lib/renderer');
	const { ItemAction } = require('im/messenger/controller/recent/lib/item-action');
	const { RecentUiConverter } = require('im/messenger/lib/converter/ui/recent');
	const { Feature } = require('im/messenger/lib/feature');
	const { RecentService } = require('im/messenger/provider/services/recent');
	const { Worker } = require('im/messenger/lib/helper');
	const { DialogType, EventType, NavigationTabByComponent, ComponentCode } = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Logger } = require('im/messenger/lib/logger');
	const { CounterHelper, DialogHelper } = require('im/messenger/lib/helper');
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
			this.messengerInitService = serviceLocator.get('messenger-init-service');

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

		bindMethods()
		{
			this.recentAddHandler = this.recentAddHandler.bind(this);
			this.recentUpdateHandler = this.recentUpdateHandler.bind(this);
			this.recentDeleteHandler = this.recentDeleteHandler.bind(this);
			this.dialogUpdateHandler = this.dialogUpdateHandler.bind(this);
			this.updateSingleAnchorHandler = this.updateSingleAnchorHandler.bind(this);
			this.updateMultipleAnchorsHandler = this.updateMultipleAnchorsHandler.bind(this);
			this.dialogReadAllCountersHandler = this.dialogReadAllCountersHandler.bind(this);

			this.loadPage = this.loadPage.bind(this);
			this.stopRefreshing = this.stopRefreshing.bind(this);

			this.renderInstant = this.renderInstant.bind(this);
			this.stopRefreshing = this.stopRefreshing.bind(this);
			this.refreshHandler = this.refreshHandler.bind(this);

			this.subscribeInitMessengerEvent = this.subscribeInitMessengerEvent.bind(this);
			this.loadNextPageHandler = this.loadNextPageHandler.bind(this);
			this.initMessengerHandler = this.initMessengerHandler.bind(this);
		}

		subscribeStoreEvents()
		{
			this.storeManager
				.on('recentModel/add', this.recentAddHandler)
				.on('recentModel/update', this.recentUpdateHandler)
				.on('recentModel/delete', this.recentDeleteHandler)
				.on('dialoguesModel/add', this.dialogUpdateHandler)
				.on('dialoguesModel/update', this.dialogUpdateHandler)
				.on('dialoguesModel/clearAllCounters', this.dialogReadAllCountersHandler)
				.on('anchorModel/add', this.updateSingleAnchorHandler)
				.on('anchorModel/delete', this.updateSingleAnchorHandler)
				.on('anchorModel/deleteMany', this.updateMultipleAnchorsHandler)
			;
		}

		subscribeMessengerEvents()
		{
			BX.addCustomEvent(EventType.messenger.afterRefreshSuccess, this.stopRefreshing);
			BX.addCustomEvent(EventType.messenger.renderRecent, this.renderInstant);
		}

		subscribeViewEvents()
		{
			this.view
				.on(EventType.recent.refresh, this.refreshHandler)
				.on(EventType.recent.loadNextPage, this.loadNextPageHandler.bind(this))
				.on(EventType.recent.itemAction, this.onItemAction.bind(this))
			;
		}

		refreshHandler()
		{
			MessengerEmitter.emit(EventType.messenger.refresh, true);
		}

		subscribeInitMessengerEvent()
		{
			if (Feature.isChatBetaEnabled && Feature.isLocalStorageEnabled)
			{
				this.messengerInitService.onceOnInit(this.initMessengerHandler);

				return;
			}

			this.messengerInitService.onInit(this.initMessengerHandler);
		}

		initMessengerHandler(data)
		{
			void this.updatePageFromServer(data);
		}

		subscribeInitCounters()
		{
			serviceLocator.get('tab-counters').subscribeInitMessengerEvent();
		}

		initItemAction()
		{
			this.itemAction = new ItemAction();
		}

		onItemAction(event)
		{
			const action = event.action.identifier;
			const itemId = event.item.params.id;

			this.itemAction.do(action, itemId);
		}

		async drawCacheItems()
		{
			if (Feature.isLocalStorageEnabled)
			{
				await this.fillStoreFromFirstDbPage();
			}
			const firstPage = clone(
				this.store.getters['recentModel/getRecentPage'](1, this.recentService.pageNavigation.itemsPerPage),
			);

			const uniqueCallList = uniqBy(this.callList, (item) => String(item.id));

			if (firstPage.length === 0)
			{
				this.view.setItems([...uniqueCallList]);
				if (!this.view.isLoaderShown)
				{
					this.view.showLoader();
				}

				return;
			}

			const convertedFirstPage = RecentUiConverter.toList(firstPage);

			const uniqueConvertedFirstPage = uniqBy(convertedFirstPage, (item) => String(item.id));

			this.view.setItems([...uniqueCallList, ...uniqueConvertedFirstPage]);

			this.recentService.pageNavigation.isPageLoading = false;
			if (this.recentService.hasMoreFromDb && !this.view.isLoaderShown)
			{
				this.view.showLoader();
				// This is a solution to add a loader before scrolling in advance. After scrolling, the loader will hide
			}
		}

		/**
		 * @return {Promise<RecentList>}
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
				await this.store.dispatch('usersModel/setFromLocalDatabase', recentList?.users);
			}

			if (recentList?.messages.length > 0)
			{
				await this.store.dispatch('messagesModel/store', recentList?.messages);
			}

			if (recentList?.files.length > 0)
			{
				await this.store.dispatch('filesModel/setFromLocalDatabase', recentList?.files);
			}

			if (recentList?.draft.length > 0)
			{
				await this.store.dispatch('draftModel/setFromLocalDatabase', recentList?.draft);
			}

			await this.store.dispatch('recentModel/setState', { collection: recentList.items });

			return recentList;
		}

		/**
		 * @return {Promise<RecentList>}
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

		loadNextPageHandler()
		{
			this.loadNextPage();
		}

		loadNextPage()
		{
			this.loadPage().catch((error) => {
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
				const result = await this.getPageFromDb();
				await this.updateStoreByDbPageResult(result);
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
		 * | immobileTabCollabLoadResult.recentList
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
		 * @abstract
		 * @param {object} recentData
		 */
		pageHandler(recentData)
		{
			return new Promise((resolve) => {
				this.logger.info(`${this.constructor.name}.pageHandler recentData:`, recentData);
				this.recentService.pageNavigation.turnPage();

				if (Type.isBoolean(recentData.hasMore))
				{
					this.recentService.pageNavigation.hasNextPage = recentData.hasMore;
				}

				if (recentData.items.length > 0)
				{
					const lastItem = recentData.items[recentData.items.length - 1];
					const lastActivityDate = lastItem.date_last_activity ?? lastItem.message.date;
					this.recentService.lastActivityDateFromServer = lastActivityDate;
					this.recentService.lastActivityDate = new Date(lastActivityDate).toISOString();
				}
				else
				{
					this.view.hideLoader();
				}

				this.saveRecentData(recentData)
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

		recentAddHandler(mutation)
		{
			const recentList = [];
			const recentItemList = clone(mutation.payload.data.recentItemList);

			recentItemList.forEach((item) => recentList.push(item.fields));

			this.addItems(recentList);
		}

		recentUpdateHandler(mutation)
		{
			const recentList = [];
			mutation.payload.data.recentItemList.forEach((item) => {
				recentList.push(clone(this.store.getters['recentModel/getCollection']()[item.index]));
			});

			this.updateItems(recentList);
		}

		updateSingleAnchorHandler(mutation)
		{
			const { anchor } = mutation.payload.data;
			this.updateRecentAnchor(anchor);
		}

		updateMultipleAnchorsHandler(mutation)
		{
			const { anchorList } = mutation.payload.data;
			if (!Type.isArrayFilled(anchorList))
			{
				return;
			}

			anchorList.forEach((anchor) => {
				this.updateRecentAnchor(anchor);
			});
		}

		updateRecentAnchor(anchor)
		{
			if (!anchor.chatId)
			{
				return;
			}

			const dialogHelper = DialogHelper.createByChatId(anchor.chatId);
			if (!dialogHelper)
			{
				return;
			}

			const recentItem = this.store.getters['recentModel/getById'](dialogHelper.dialogId);
			if (recentItem)
			{
				this.updateItems([recentItem]);
			}
		}

		dialogUpdateHandler(mutation)
		{
			const dialogId = mutation.payload.data.dialogId;
			const recentItem = clone(this.store.getters['recentModel/getById'](String(dialogId)));
			if (recentItem)
			{
				this.updateItems([recentItem]);
			}
		}

		/**
		 * @param {MutationPayload<DialoguesClearAllCountersData, DialoguesClearAllCountersActions>} mutation.payload
		 */
		dialogReadAllCountersHandler(mutation)
		{
			const dialogIdList = mutation.payload.data.affectedDialogs;

			if (!Type.isArrayFilled(dialogIdList))
			{
				return;
			}
			const recentItemList = [];

			dialogIdList.forEach((dialogId) => {
				const recentItem = this.store.getters['recentModel/getById'](String(dialogId));
				if (recentItem)
				{
					recentItemList.push(clone(recentItem));
				}
			});

			this.updateItems(recentItemList);
		}

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
		 * @param {Array<RecentModelState>} items
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
		 * @param {object} item
		 * @param {boolean} [isCallItem=false]
		 * @return {boolean}
		 */
		removeItem(item, isCallItem = false)
		{
			if (Type.isNil(item))
			{
				return;
			}

			this.renderer.removeFromQueue(item.id);

			if (isCallItem)
			{
				this.renderer.do('removeCall', item);
			}
			else
			{
				const actionName = Feature.isAsyncRecentOperationsAvailable ? 'removeItemsByIds' : 'remove';
				this.renderer.do(actionName, item);
			}

			if (!this.recentService.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.view.hideLoader();
			}

			serviceLocator.get('tab-counters').update();

			this.checkEmpty();
		}

		/**
		 * @param {object} item
		 * @param {boolean} [useRecentUiConverter=false]
		 */
		upsert(item, useRecentUiConverter = true)
		{
			if (Type.isNil(item))
			{
				return;
			}

			const callback = (foundItem) => {
				if (foundItem)
				{
					const actionName = useRecentUiConverter ? 'update' : 'updatePreparedItems';
					this.renderer.do(actionName, item);
					if (!this.recentService.pageNavigation.hasNextPage && this.view.isLoaderShown)
					{
						this.view.hideLoader();
					}

					return;
				}

				if (useRecentUiConverter)
				{
					this.renderer.do('add', item);
				}
				else
				{
					this.renderer.do('addPreparedItems', item);
				}

				if (!this.recentService.pageNavigation.hasNextPage && this.view.isLoaderShown)
				{
					this.renderer.nextTick(() => this.view.hideLoader());
				}
				this.checkEmpty();
			};

			const actionName = Feature.isAsyncRecentOperationsAvailable ? 'findItemById' : 'findItem';
			this.renderer.do(actionName, { id: item.id }, { callback });
		}

		/**
		 * @param {object} data
		 * @return {Promise<any>}
		 */
		async saveRecentData(data)
		{
			return Promise.resolve();
		}

		/**
		 * @param {Array<object>} items
		 */
		deleteItemsFromStore(items)
		{
			const dialogIdListFromServer = [];
			items.forEach((item) => dialogIdListFromServer.push(item.id.toString()));

			const idListForDeleteFromCache = [];
			this.store.getters['recentModel/getCollection']().forEach((dialogId) => {
				if (!dialogIdListFromServer.includes(dialogId))
				{
					idListForDeleteFromCache.push(dialogId);
				}
			});

			idListForDeleteFromCache.forEach((id) => {
				this.store.dispatch('recentModel/deleteFromModel', { id });
			});
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
				EventType.navigation.broadCastEventCheckTabPreload,
				{
					broadCastEvent: EventType.messenger.openDialog,
					toTab: NavigationTabByComponent[componentCode],
					data: {
						dialogId,
						checkComponentCode,
					},
				},
				ComponentCode.imNavigation,
			);
		}

		/**
		 * @return {Promise<{
		 * items: Array<RecentStoredData>,
		 * users: Array<UserStoredData>,
		 * messages: Array,
		 * files: Array,
		 * hasMore: boolean
		 * }>}
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
			const exceptDialogTypes = [
				DialogType.lines,
				DialogType.comment,
			];

			return {
				exceptDialogTypes,
				limit: this.recentService.getRecentListRequestLimit(),
			};
		}

		/**
		 * @param {{items: Array, users: Array, messages: Array, files: Array, hasMore: boolean}} result
		 * @return {Promise<void>}
		 */
		async updateStoreByDbPageResult(result)
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
				Logger.error(`${this.constructor.name}.updateStoreByDbPageResult.catch:`, error);
			}
		}

		/**
		 * @param {immobileTabChatLoadResult['recentList']} recentData
		 * @return {object}
		 */
		prepareDataForModels(recentData)
		{
			const result = {
				users: [],
				dialogues: [],
				recent: [],
				copilot: [],
				counterState: [],
			};

			const messagesAutoDeleteConfigs = {};
			recentData.messagesAutoDeleteConfigs.forEach((item) => {
				messagesAutoDeleteConfigs[item.chatId] = item;
			});

			const copilotChats = {};
			recentData.copilot.chats?.forEach((copilotChat) => {
				copilotChats[copilotChat.dialogId] = true;
			});

			recentData.items.forEach((item) => {
				if (item.user && item.user.id > 0) // this important check for "anonymous" user type, not removing
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
						type: DialogType.private,
						counter: item.counter,

						// required to update the added column in the b_im_dialog table
						permissions: ChatPermission.getActionGroupsByChatType(DialogType.user),
					};

					if (!Type.isUndefined(item.chat?.text_field_enabled)
						|| !Type.isUndefined(item.chat?.background_id)
					)
					{
						dialogItem.textFieldEnabled = item.chat.text_field_enabled;
						dialogItem.backgroundId = item.chat.background_id;
					}

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

				if (messagesAutoDeleteConfigs[item.chat_id])
				{
					dialogItem.messagesAutoDeleteDelay = messagesAutoDeleteConfigs[item.chat_id].delay;
				}

				result.dialogues.push(dialogItem);

				result.recent.push(
					this.prepareRecentItem(item),
				);

				result.counterState.push({
					chatId: dialogItem.chatId ?? dialogItem.id ?? 0,
					type: CounterHelper.getCounterTypeByDialogType(dialogItem.type),
					parentChatId: dialogItem.parent_chat_id ?? 0,
					counter: dialogItem.counter,
				});

				if (copilotChats[item.id])
				{
					copilotChats[item.id] = item;
				}
			});

			Object.entries(copilotChats).forEach(([dialogId, chat]) => {
				const chats = recentData.copilot.chats?.find((chat) => chat.dialogId === dialogId);
				const roles = recentData.copilot.roles;
				const copilotMessages = recentData.copilot.messages?.find((message) => message.id === chat.message.id);

				const copilotItem = {
					dialogId,
					chats: [chats],
					messages: [copilotMessages],
					aiProvider: '',
					roles,
				};

				result.copilot.push(copilotItem);
			});

			result.users = this.getUniqueUsers(result.users);

			return result;
		}

		recentDeleteHandler(mutation)
		{
			this.removeItem({ id: mutation.payload.data.id });
		}

		/**
		 * @param {Array<UsersModelState>} users
		 * @return {Array<UsersModelState>}
		 */
		getUniqueUsers(users)
		{
			try
			{
				return uniqBy(users, 'id');
			}
			catch (error)
			{
				console.error(`${this.constructor.name}.getUniqueUsers catch:`, error);

				return users;
			}
		}

		/**
		 * @param {RecentItemData} recenItem
		 * @return {RecentItemData}
		 */
		prepareRecentItem(recenItem)
		{
			const preparedRecentItem = {
				...recenItem,
				avatar: recenItem.avatar.url,
				color: recenItem.avatar.color,
				counter: recenItem.counter,
			};

			if (Type.isPlainObject(preparedRecentItem.message.file) && preparedRecentItem.message.file?.id)
			{
				preparedRecentItem.message.params = { withFile: [String(preparedRecentItem.message.file.id)] };
			}

			return preparedRecentItem;
		}
	}

	module.exports = {
		BaseRecent,
	};
});
