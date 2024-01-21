/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/recent
 */
jn.define('im/messenger/controller/recent/recent', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');

	const { core } = require('im/messenger/core');
	const { Counters } = require('im/messenger/lib/counters');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { ItemAction } = require('im/messenger/controller/recent/item-action');
	const { Worker } = require('im/messenger/lib/helper');
	const { PageNavigation } = require('im/messenger/lib/page-navigation');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { restManager } = require('im/messenger/lib/rest-manager');
	const { RecentRenderer } = require('im/messenger/controller/recent/renderer');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { Settings } = require('im/messenger/lib/settings');
	const {
		EventType,
		RestMethod,
		DialogType,
	} = require('im/messenger/const');
	const {
		RecentRest,
		DialogRest,
	} = require('im/messenger/provider/rest');
	const {
		CountersService,
	} = require('im/messenger/provider/service');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('recent--recent');

	/**
	 * @class Recent
	 *
	 * @property {RecentView} view
	 */
	class Recent
	{
		/* region Init */

		constructor(options = {})
		{
			if (options.view)
			{
				this.view = options.view;

				this.renderer = new RecentRenderer({
					view: this.view,
				});
			}
			else
			{
				throw new Error('DialogList: options.view is required');
			}

			this.store = core.getStore();
			this.storeManager = core.getStoreManager();

			this.lastMessageDate = null;
			this.isFirstLoad = true;
			this.swipeHelperShowLimit = 2;

			this.settings = Application.storage.getObject('settings.messenger.recent', {
				swipeHelperShowCounter: 0,
			});

			this.pageNavigation = new PageNavigation({
				currentPage: 1,
				itemsPerPage: 50,
				isPageLoading: true,
			});

			this.callList = [];

			/**
			 * @private
			 * @type {CountersService}
			 */
			this.countersService = CountersService.getInstance();
			this.recentRepository = core.getRepository().recent;

			/** @private */
			this.recentAddHandler = this.recentAddHandler.bind(this);
			/** @private */
			this.recentUpdateHandler = this.recentUpdateHandler.bind(this);
			/** @private */
			this.recentDeleteHandler = this.recentDeleteHandler.bind(this);
			/** @private */
			this.dialogUpdateHandler = this.dialogUpdateHandler.bind(this);

			this.init();
		}

		async init()
		{
			this.initRequests();
			this.subscribeStoreEvents();
			this.subscribeMessengerEvents();
			this.subscribeViewEvents();

			this.itemAction = new ItemAction();

			this.drawCacheItems();
			this.countersService.load();

			this.loadPageAfterErrorWorker = new Worker({
				frequency: 5000,
				callback: this.loadPage.bind(this),
			});
		}

		initRequests()
		{
			restManager.on(
				RestMethod.imRecentList,
				{ SKIP_OPENLINES: 'Y' },
				this.handleFirstPage.bind(this),
			);

			restManager.once(
				RestMethod.imDepartmentColleaguesGet,
				{
					USER_DATA: 'Y',
					LIMIT: 50,
				},
				this.handleDepartmentColleaguesGet.bind(this),
			);

			Counters.initRequests();
		}

		subscribeViewEvents()
		{
			this.view
				.on(EventType.recent.itemSelected, this.onItemSelected.bind(this))
				.on(EventType.recent.searchShow, this.onShowSearchDialog.bind(this))
				.on(EventType.recent.searchHide, this.onHideSearchDialog.bind(this))
				.on(EventType.recent.loadNextPage, this.onLoadNextPage.bind(this))
				.on(EventType.recent.itemAction, this.onItemAction.bind(this))
				.on(EventType.recent.createChat, this.onCreateChat.bind(this))
				.on(EventType.recent.readAll, this.onReadAll.bind(this))
				.on(EventType.recent.refresh, this.onRefresh.bind(this))
			;
		}

		subscribeStoreEvents()
		{
			this.storeManager
				.on('recentModel/add', this.recentAddHandler)
				.on('recentModel/update', this.recentUpdateHandler)
				.on('recentModel/delete', this.recentDeleteHandler)
				.on('dialoguesModel/add', this.dialogUpdateHandler)
				.on('dialoguesModel/update', this.dialogUpdateHandler)
			;
		}

		subscribeMessengerEvents()
		{
			BX.addCustomEvent(EventType.messenger.afterRefreshSuccess, this.stopRefreshing.bind(this));
			BX.addCustomEvent(EventType.messenger.renderRecent, this.renderInstant.bind(this));
		}

		/* endregion Init */

		/* region Events */

		onItemSelected(recentItem)
		{
			if (recentItem.params.disableTap)
			{
				return;
			}

			if (recentItem.params.type === 'call')
			{
				if (recentItem.params.canJoin)
				{
					this.joinCall(recentItem.params.call.id);
				}
				else
				{
					this.openDialog(recentItem.params.call.associatedEntity.id);
				}

				return;
			}

			this.openDialog(recentItem.id);
		}

		onShowSearchDialog()
		{
			MessengerEmitter.emit(EventType.messenger.showSearch);
		}

		onHideSearchDialog()
		{
			MessengerEmitter.emit(EventType.messenger.hideSearch);
		}

		onLoadNextPage()
		{
			const canLoadNextPage = !this.pageNavigation.isPageLoading && this.pageNavigation.hasNextPage;
			if (!canLoadNextPage)
			{
				return;
			}

			this.loadNextPage();
		}

		onItemAction(event)
		{
			const action = event.action.identifier;
			const itemId = event.item.params.id;

			this.itemAction.do(action, itemId);
		}

		onCreateChat()
		{
			MessengerEmitter.emit(EventType.messenger.createChat);
		}

		onReadAll()
		{
			this.store.dispatch('dialoguesModel/clearAllCounters')
				.then(() => {
					return this.store.dispatch('recentModel/clearAllCounters');
				})
				.then(() => {
					this.renderer.render();

					Counters.update();

					return DialogRest.readAllMessages();
				})
				.then((result) => {
					logger.log('DialogRest.readAllMessages result:', result);
				})
				.catch((error) => {
					logger.error('DialogRest.readAllMessages error:', error);
				})
			;
		}

		onRefresh()
		{
			MessengerEmitter.emit(EventType.messenger.refresh);
		}

		openDialog(dialogId)
		{
			this.removeUnreadState(dialogId);

			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId });
		}

		joinCall(callId)
		{
			Calls.joinCall(callId);
		}

		addCall(call, callStatus)
		{
			if (
				call.associatedEntity.advanced.entityType === 'VIDEOCONF'
				&& call.associatedEntity.advanced.entityData1 === 'BROADCAST'
			)
			{
				callStatus = 'remote';
			}

			const callItem = RecentConverter.toCallItem(callStatus, call);

			this.saveCall(callItem);
			this.drawCall(callItem);
		}

		saveCall(call)
		{
			const elementIndex = this.callList.findIndex((element) => element.id === call.id);
			if (elementIndex >= 0)
			{
				this.callList[elementIndex] = call;

				return;
			}

			this.callList.push(call);
		}

		getCallById(callId)
		{
			return this.callList.find((call) => call.id === callId);
		}

		drawCall(callItem)
		{
			this.view.findItem({ id: callItem.id }, (item) => {
				if (item)
				{
					this.view.updateItem({ id: callItem.id }, callItem);

					return;
				}

				this.view.addItems([callItem]);
			});
		}

		removeCallById(id)
		{
			this.view.removeItem({ id: `call${id}` });
		}

		/* endregion Events */

		loadNextPage()
		{
			this.pageNavigation.turnPage();

			this.loadPage();
		}

		loadPage()
		{
			this.pageNavigation.isPageLoading = true;

			this.getPageFromService()
				.then((response) => this.handlePage(response))
				.catch(() => {
					logger.error(
						`Recent: page ${
						 this.pageNavigation.currentPage
						} loading error, try again in ${
						 this.loadPageAfterErrorWorker.frequency / 1000
						 } seconds.`,
					);

					this.loadPageAfterErrorWorker.startOnce();
				})
			;
		}

		getPageFromService()
		{
			const options = {
				skipOpenlines: true,
			};

			if (this.pageNavigation.currentPage > 1)
			{
				options.lastMessageDate = this.lastMessageDate;
			}

			return RecentRest.getList(options);
		}

		handleFirstPage(response)
		{
			const error = response.error();
			if (error)
			{
				logger.error('Recent.handleFirstPage', error);

				return;
			}

			this.handlePage(response).then(() => {
				if (response.data().hasMore && !this.view.isLoaderShown)
				{
					this.view.showLoader();
				}
			});
		}

		handlePage(response)
		{
			return new Promise((resolve) => {
				const data = response.data();

				logger.info('Recent.handlePage', 'page: ', this.pageNavigation.currentPage, 'data:', data);

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

						const isEmpty = this.checkEmpty();
						if (
							this.isFirstLoad
							&& !isEmpty
							&& this.settings.swipeHelperShowCounter < this.swipeHelperShowLimit
						)
						{
							this.enableSwipeHelperPromo();
						}

						this.isFirstLoad = false;

						resolve();
					})
					.catch((error) => {
						logger.error('Recent.saveRecentItems error: ', error);
					})
				;
			});
		}

		async drawCacheItems()
		{
			if (Settings.isLocalStorageEnabled)
			{
				const recentList = await this.recentRepository.getList();
				logger.warn('recentList:', recentList);
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

		recentAddHandler(mutation, state)
		{
			const recentList = [];
			const recentItemList = clone(mutation.payload.data.recentItemList);

			recentItemList.forEach((item) => recentList.push(item.fields));

			this.addItems(recentList);
		}

		recentUpdateHandler(mutation, state)
		{
			const recentList = [];

			mutation.payload.data.recentItemList.forEach((item) => {
				recentList.push(clone(this.store.state.recentModel.collection[item.index]));
			});

			this.updateItems(recentList);
		}

		recentDeleteHandler(mutation, state)
		{
			this.renderer.removeFromQueue(mutation.payload.data.id);

			this.view.removeItem({ id: mutation.payload.data.id });
			if (!this.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.view.hideLoader();
			}

			this.checkEmpty();
		}

		dialogUpdateHandler(mutation)
		{
			const dialogId = mutation.payload.data.dialogId;
			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (recentItem)
			{
				this.updateItems([recentItem]);
			}
		}

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
				// TODO local counters
				// dialogItem = this.invalidateOutdatedDialogData(dialogItem);

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
		 * @param {Partial<DialoguesModelState>} dialogItem
		 * @return {Partial<DialoguesModelState>}
		 */
		invalidateOutdatedDialogData(dialogItem)
		{
			const existingItem = this.store.getters['dialoguesModel/getById'](dialogItem.dialogId);

			if (!existingItem)
			{
				return dialogItem;
			}

			if (dialogItem.lastMessageId && dialogItem.counter && dialogItem.lastMessageId <= existingItem.lastMessageId)
			{
				return {
					...dialogItem,
					counter: existingItem.counter,
				};
			}

			return dialogItem;
		}

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

				ShareDialogCache.saveRecentItemList(modelData.recent);
			}

			return Promise.all([usersPromise, dialoguesPromise, recentPromise]);
		}

		handleDepartmentColleaguesGet(response)
		{
			const error = response.error();
			if (error)
			{
				logger.error('Recent.handleDepartmentColleaguesGet', error);

				return;
			}

			const userList = response.data();

			logger.log('Recent.handleDepartmentColleaguesGet', userList);

			this.store.dispatch('usersModel/set', userList);
		}

		enableSwipeHelperPromo()
		{
			const firstItem = clone(this.store.getters['recentModel/getCollection']()[0]);
			if (!firstItem)
			{
				return;
			}

			this.view.updateItem({ id: firstItem.id }, { showSwipeActions: true });

			this.settings.swipeHelperShowCounter++;
			Application.storage.setObject('settings.messenger.recent', this.settings);
		}

		checkEmpty()
		{
			if (this.store.getters['recentModel/isEmpty']())
			{
				this.view.showWelcomeScreen();

				return true;
			}

			this.view.hideWelcomeScreen();

			return false;
		}

		stopRefreshing()
		{
			this.view.stopRefreshing();
		}

		renderInstant()
		{
			logger.log('Recent.renderInstant');

			this.renderer.render();
		}

		removeUnreadState(dialogId)
		{
			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			const unreadBeforeChange = recentItem.unread;

			this.store.dispatch('recentModel/set', [{
				id: dialogId,
				unread: false,
			}]).then(() => {
				Counters.update();
			});

			RecentRest.read({ dialogId }).catch((result) => {
				logger.error('Recent item read error: ', result.error());

				this.store.dispatch('recentModel/set', [{
					id: dialogId,
					unread: unreadBeforeChange,
				}]).then(() => {
					Counters.update();
				});
			});
		}
	}

	module.exports = { Recent };
});
