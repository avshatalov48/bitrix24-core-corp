/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/recent
 */
jn.define('im/messenger/controller/recent/recent', (require, exports, module) => {

	const { Logger } = jn.require('im/messenger/lib/logger');
	const { Controller } = jn.require('im/messenger/controller/base');
	const { ItemAction } = jn.require('im/messenger/controller/recent/item-action');
	const { Worker } = jn.require('im/messenger/lib/helper');
	const { PageNavigation } = jn.require('im/messenger/lib/page-navigation');
	const { RecentConverter } = jn.require('im/messenger/lib/converter');
	const { RestManager } = jn.require('im/messenger/lib/rest-manager');
	const { RecentRenderer } = jn.require('im/messenger/controller/recent/renderer');
	const { ShareDialogCache } = jn.require('im/messenger/cache/share-dialog');
	const {
		EventType,
		RestMethod,
	} = jn.require('im/messenger/const');
	const {
		RecentService,
		DialogService,
	} = jn.require('im/messenger/service');

	/**
	 * @class Recent
	 *
	 * @property {RecentView} view
	 */
	class Recent extends Controller
	{
		/* region Init */

		constructor(options = {})
		{
			super(options);

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

			this.initRequests();
			this.subscribeStoreEvents();
			this.subscribeMessengerEvents();
			this.subscribeViewEvents();

			this.itemAction = new ItemAction();

			this.callList = [];

			this.drawCacheItems();

			this.updateStatusWorker = new Worker({
				frequency: 59000,
				callback: this.updateUserStatuses.bind(this),
			});

			this.loadPageAfterErrorWorker = new Worker({
				frequency: 5000,
				callback: this.loadPage.bind(this),
			});

			this.updateStatusWorker.start();
		}

		initRequests()
		{
			RestManager.on(
				RestMethod.imRecentList,
				{ SKIP_OPENLINES: 'Y' },
				this.handleFirstPage.bind(this)
			);

			RestManager.once(
				RestMethod.imDepartmentColleaguesGet,
				{
					USER_DATA: 'Y',
					LIMIT: 50
				},
				this.handleDepartmentColleaguesGet.bind(this)
			);
		}

		subscribeViewEvents()
		{
			this.view
				.on(EventType.recent.itemSelected, this.onItemSelected.bind(this))
				.on(EventType.recent.searchShow, this.onShowSearchDialog.bind(this))
				.on(EventType.recent.loadNextPage, this.onLoadNextPage.bind(this))
				.on(EventType.recent.itemAction, this.onItemAction.bind(this))
				.on(EventType.recent.createChat, this.onCreateChat.bind(this))
				.on(EventType.recent.readAll, this.onReadAll.bind(this))
				.on(EventType.recent.refresh, this.onRefresh.bind(this))
			;
		}

		subscribeStoreEvents()
		{
			MessengerStoreManager
				.on('recentModel/add', this.drawItems.bind(this))
				.on('recentModel/update', this.redrawItems.bind(this))
				.on('recentModel/delete', this.deleteItem.bind(this))
			;
		}

		subscribeMessengerEvents()
		{
			this.onMessengerEvent(EventType.messenger.afterRefreshSuccess, this.stopRefreshing.bind(this));
			this.onMessengerEvent(EventType.messenger.renderRecent, this.renderInstant.bind(this));
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
					this.joinCall({
						callId: recentItem.params.call.id,
					});
				}
				else
				{
					this.openDialog({
						dialogId: recentItem.params.call.associatedEntity.id,
					});
				}

				return;
			}
			
			this.openDialog({
				dialogId: recentItem.id,
			});
		}

		onShowSearchDialog()
		{
			this.emitMessengerEvent(EventType.messenger.showSearch);
		}

		onLoadNextPage()
		{
			const canLoadNextPage =
				!this.pageNavigation.isPageLoading
				&& this.pageNavigation.hasNextPage
			;

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
			this.emitMessengerEvent(EventType.messenger.createChat);
		}

		onReadAll()
		{
			DialogService.readAllMessages()
				.then(result => {
					Logger.log('DialogService.readAllMessages result:', result);

					MessengerStore.dispatch('recentModel/clearAllCounters');
				})
				.catch(error => {
					Logger.error('DialogService.readAllMessages error:', error);
				})
			;
		}

		onRefresh()
		{
			this.emitMessengerEvent(EventType.messenger.refresh);
		}

		openDialog(options)
		{
			const eventData = {
				dialogId: options.dialogId,
			};

			this.emitMessengerEvent(EventType.messenger.openDialog, eventData);
		}

		joinCall(options)
		{
			const eventData = {
				callId: options.callId,
			};

			this.emitMessengerEvent(EventType.messenger.joinCall, eventData);
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

			const callItem = RecentConverter.toCallListItem(callStatus, call);

			this.saveCall(callItem);
			this.drawCall(callItem);
		}

		saveCall(call)
		{
			const elementIndex = this.callList.findIndex(element => element.id === call.id);
			if (elementIndex >= 0)
			{
				this.callList[elementIndex] = call;
				return;
			}

			this.callList.push(call);
		}

		drawCall(callItem)
		{
			this.view.findItem({ id: callItem.id }, (item) => {
				if (item)
				{
					this.view.updateItem({ id: callItem.id }, callItem);
					return;
				}

				this.view.addItems([ callItem ]);
			});
		}

		removeCallById(id)
		{
			this.view.removeItem({ id: 'call' + id });
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
					Logger.error(
						'Recent: page '
						+ this.pageNavigation.currentPage
						+' loading error, try again in '
						+ this.loadPageAfterErrorWorker.frequency / 1000
						+ ' seconds.'
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

			return RecentService.getList(options);
		}

		handleFirstPage(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Recent.handleFirstPage', error);

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

				Logger.info('Recent.handlePage', 'page: ', this.pageNavigation.currentPage, 'data:', data);

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
						Logger.error('Recent.saveRecentItems error: ', error);
					})
				;
			});
		}

		drawCacheItems()
		{
			let firstPage = ChatUtils.objectClone(
				MessengerStore.getters['recentModel/getRecentPage'](1, this.pageNavigation.itemsPerPage)
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

		drawItems(mutation, state)
		{
			const recentList = [];
			const payload = ChatUtils.objectClone(mutation.payload);

			payload.forEach(item => recentList.push(item.fields));

			this.renderer.do('add', recentList);
			if (!this.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.renderer.nextTick(() => this.view.hideLoader());
			}

			this.checkEmpty();
		}

		redrawItems(mutation, state)
		{
			const recentList = [];

			mutation.payload.forEach((item) => {
				recentList.push(ChatUtils.objectClone(MessengerStore.state.recentModel.collection[item.index]));
			});

			this.renderer.do('update', recentList);
			if (!this.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.renderer.nextTick(() => this.view.hideLoader());
			}

			this.checkEmpty();
		}

		deleteItem(mutation, state)
		{
			this.renderer.removeFromQueue(mutation.payload.id);

			this.view.removeItem({'params.id' : mutation.payload.id});
			if (!this.pageNavigation.hasNextPage && this.view.isLoaderShown)
			{
				this.view.hideLoader();
			}

			this.checkEmpty();
		}

		prepareDataForModels(items)
		{
			const result = {
				users: [],
				dialogues: [],
				recent: []
			};

			items.forEach(item => {
				if (item.user && item.user.id > 0)
				{
					result.users.push(item.user);
				}
				if (item.chat)
				{
					result.dialogues.push({
						...item.chat,
						...{ dialogId: item.id },
					});
				}

				result.recent.push({
					...item,
					avatar: item.avatar.url,
					color: item.avatar.color,
				});
			});

			return result;
		}

		saveRecentItems(recentItems)
		{
			const modelData = this.prepareDataForModels(recentItems);

			const usersPromise = MessengerStore.dispatch('usersModel/set', modelData.users);
			const dialoguesPromise = MessengerStore.dispatch('dialoguesModel/set', modelData.dialogues);
			const recentPromise = MessengerStore.dispatch('recentModel/set', modelData.recent);

			if (this.pageNavigation.currentPage === 1)
			{
				const recentIndex = [];
				modelData.recent.forEach(item => recentIndex.push(item.id.toString()));

				const idListForDeleteFromCache = [];
				MessengerStore.getters['recentModel/getCollection']
					.forEach((item) => {
						if (!recentIndex.includes(item.id.toString()))
						{
							idListForDeleteFromCache.push(item.id);
						}
					});

				idListForDeleteFromCache.forEach(id => {
					MessengerStore.dispatch('recentModel/delete', { id });
				});

				ShareDialogCache.saveRecentItemList(modelData.recent)
					.then((cache) => {
						Logger.log('Recent: Saving recent items for the share dialog is successful.', cache);
					})
					.catch((cache) => {
						Logger.log('Recent: Saving recent items for share dialog failed.', modelData.recent, cache);
					})
				;
			}

			return Promise.all([usersPromise, dialoguesPromise, recentPromise]);
		}

		handleDepartmentColleaguesGet(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Recent.handleDepartmentColleaguesGet', error);

				return;
			}

			const userList = response.data();

			Logger.log('Recent.handleDepartmentColleaguesGet', userList);

			MessengerStore.dispatch('usersModel/set', userList);
		}

		enableSwipeHelperPromo()
		{
			const firstItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getCollection'][0]);
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
			if (MessengerStore.getters['recentModel/isEmpty'])
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
			Logger.log('Recent.renderInstant');

			this.renderer.render();
		}

		updateUserStatuses()
		{
			const timeStart = new Date();
			const recentDialogIdList = Object.keys(MessengerStore.getters['recentModel/getCollection']);
			const listUpdate = [];

			recentDialogIdList.forEach(dialogId => {
				const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
				if(
					!recentItem
					|| recentItem.type !== 'user'
				)
				{
					return;
				}

				const currentStatus = ChatDataConverter.getUserImageCode(recentItem);
				if (recentItem.user.status !== currentStatus)
				{
					recentItem.user.status = currentStatus;

					MessengerStore.dispatch('recentModel/set', [ recentItem ]);
					listUpdate.push(recentItem);
				}
			});

			const executeTime = Date.now() - timeStart;
			if (listUpdate.length > 0 || executeTime > 3000)
			{
				Logger.info(
					'Recent.updateListStatus: '
					+ listUpdate.length
					+ ' items will be update. time:'
					+ executeTime
					+ 'ms.'
				);
			}
		}
	}

	module.exports = { Recent };
});
