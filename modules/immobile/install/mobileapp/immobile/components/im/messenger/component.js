// eslint-disable-next-line no-var
var REVISION = 19; // API revision - sync with im/lib/revision.php

/* region Environment variables */

// use in immobile/install/mobileapp/immobile/extensions/im/chat/messengercommon/extension.js:532
BX.message.LIMIT_ONLINE = BX.componentParameters.get('LIMIT_ONLINE', 1380);

/* endregion Environment variables */

/* region Clearing session variables after script reload */

if (typeof window.messenger !== 'undefined' && typeof window.messenger.destructor !== 'undefined')
{
	window.messenger.destructor();
}

/* endregion Clearing session variables after script reload */

(async () => {
	/* region import */
	const require = (ext) => jn.require(ext); // for IDE hints

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { get, isEqual } = require('utils/object');
	const { Feature } = require('feature');

	const { core } = require('im/messenger/core');
	await core.ready();

	const { restManager, RestManager } = require('im/messenger/lib/rest-manager');
	const { Settings } = require('im/messenger/lib/settings');

	const {
		MessagePullHandler,
		FilePullHandler,
		DialogPullHandler,
		UserPullHandler,
		DesktopPullHandler,
		NotificationPullHandler,
		OnlinePullHandler,
	} = require('im/messenger/provider/pull');

	const {
		AppStatus,
		EventType,
		RestMethod,
		FeatureFlag,
		UserRole,
	} = require('im/messenger/const');
	const {
		ConnectionService,
		SyncService,
		SendingService,
		QueueService,
	} = require('im/messenger/provider/service');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Logger } = require('im/messenger/lib/logger');

	const { Recent } = require('im/messenger/controller/recent');
	const { RecentView } = require('im/messenger/view/recent');
	const { Dialog } = require('im/messenger/controller/dialog');
	const { ChatCreator } = require('im/messenger/controller/chat-creator');
	const { Counters } = require('im/messenger/lib/counters');
	const { EntityReady } = require('entity-ready');
	const { Communication } = require('im/messenger/lib/integration/mobile/communication');
	const { Promotion } = require('im/messenger/lib/promotion');
	const { PushHandler } = require('im/messenger/provider/push');
	const { DialogCreator } = require('im/messenger/controller/dialog-creator');
	const { SidebarController } = require('im/messenger/controller/sidebar/sidebar-controller');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { RecentSelector } = require('im/messenger/controller/search/experimental');
	const { SmileManager } = require('im/messenger/lib/smile-manager');
	/* endregion import */

	class Messenger
	{
		/* region initiation */

		/**
		 * @class Messenger - mobile messenger entry point
		 *
		 * @property {boolean} isReady - flag that the messenger has finished initialization
		 * @property {boolean} isFirstLoad - flag that the messenger is loading for the first time
		 *
		 * @property {Object} store - vuex store
		 * @property {Object} storeManager - vuex store manager
		 *
		 * @property {Recent} recent - recent chat list controller
		 * @property {Dialog} dialog - chat controller
		 * @property {DialogSelector} dialogSelector - chat search controller
		 * @property {ChatCreator} chatCreator - chat creation dialog
		 * @property {RestManager} restManager - collects requests to initialize the messenger into a batch and executes it
		 */
		constructor()
		{
			this.isReady = false;
			this.isFirstLoad = true;
			this.refreshTimeout = null;
			this.refreshAfterErrorInterval = 10000;
			this.refreshErrorNoticeFlag = false;

			/**
			 * @type {CoreApplication}
			 */
			this.core = core;
			this.repository = this.core.getRepository();

			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = this.core.getStore();

			/**
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = this.core.getStoreManager();

			/**
			 * @type {RestManager}
			 */
			this.queueRestManager = new RestManager();

			/**
			 * @type {SyncService}
			 */
			this.syncService = null;

			/**
			 * @type {SendingService}
			 */
			this.sendingService = null;
			/**
			 * @type {QueueService}
			 */
			this.queueService = null;

			this.titleParams = {};
			this.appStatus = '';

			this.recent = null;
			this.dialog = null;
			/** @type {RecentSelector || DialogSelector} */
			this.searchSelector = null;
			this.chatCreator = null;
			this.dialogCreator = null;
			this.sidebar = null;
			this.communication = new Communication();
			this.visibilityManager = VisibilityManager.getInstance();

			this.onApplicationSetStatus = this.applicationSetStatusHandler.bind(this);
			this.chestnoPererisuemShapku = false;

			EntityReady.addCondition('chat', () => this.isReady);

			this.init();
		}

		init()
		{
			this.preloadAssets();
			this.initRequests();

			BX.onViewLoaded(async () => {
				this.initComponents();
				this.subscribeEvents();
				this.initPullHandlers();
				this.initServices();
				await this.initCurrentUser();
				await this.initQueueRequests();

				this.connectionService.updateStatus();

				EntityReady.wait('im.navigation')
					.then(() => this.executeStoredPullEvents())
					.catch((error) => Logger.error(error))
				;

				this.checkChatV2Support();
				this.refresh(false);
			});
		}

		checkChatV2Support()
		{
			if (Settings.isChatV2Supported === true)
			{
				return true;
			}

			if (MessengerParams.shouldShowChatV2UpdateHint())
			{
				this.showUnsupportedWidget({
					text: Loc.getMessage('IMMOBILE_MESSENGER_UPDATE_FOR_NEW_FEATURES_HINT'),
					isOldBuild: false,
				});
			}

			return false;
		}

		showUnsupportedWidget(props = {}, parentWidget = PageManager)
		{
			Feature.showDefaultUnsupportedWidget(props, parentWidget);
		}

		initRequests()
		{
			restManager.on(RestMethod.imRevisionGet, {}, this.checkRevision.bind(this));
		}

		initComponents()
		{
			this.recent = new Recent({
				view: new RecentView({
					ui: dialogList,
				}),
			});

			this.searchSelector = new RecentSelector(dialogList);

			this.chatCreator = new ChatCreator();

			if (Application.getApiVersion() >= 47)
			{
				this.dialogCreator = new DialogCreator();
			}
		}

		preloadAssets()
		{
			if (FeatureFlag.dialog.nativeSupported)
			{
				// TODO: generalize the approach to background caching
				Dialog.preloadAssets();
			}
		}

		subscribeEvents()
		{
			this.subscribeMessengerEvents();
			this.subscribeExternalEvents();
			this.subscribeStoreEvents();
		}

		subscribeMessengerEvents()
		{
			BX.addCustomEvent(EventType.messenger.openDialog, this.openDialog.bind(this));
			BX.addCustomEvent(EventType.messenger.openSidebar, this.openSidebar.bind(this));
			BX.addCustomEvent(EventType.messenger.openLine, this.openLine.bind(this));
			BX.addCustomEvent(EventType.messenger.getOpenDialogParams, this.getOpenDialogParams.bind(this));
			BX.addCustomEvent(EventType.messenger.getOpenLineParams, this.getOpenLineParams.bind(this));
			BX.addCustomEvent(EventType.messenger.showSearch, this.openChatSearch.bind(this));
			BX.addCustomEvent(EventType.messenger.hideSearch, this.closeChatSearch.bind(this));
			BX.addCustomEvent(EventType.messenger.createChat, this.openChatCreate.bind(this));
			BX.addCustomEvent(EventType.messenger.openNotifications, this.openNotifications.bind(this));
			BX.addCustomEvent(EventType.messenger.refresh, this.refresh.bind(this));
			BX.addCustomEvent(EventType.messenger.destroyDialog, this.destroyDialog.bind(this));
			BX.addCustomEvent(EventType.messenger.uploadFiles, this.uploadFiles.bind(this));
			BX.addCustomEvent(EventType.messenger.cancelFileUpload, this.cancelFileUpload.bind(this));
			BX.addCustomEvent(EventType.messenger.dialogAccessError, this.onDialogAccessError.bind(this));
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.chatDialog.initComplete, this.onChatDialogInitComplete.bind(this));
			BX.addCustomEvent(EventType.chatDialog.counterChange, this.onChatDialogCounterChange.bind(this));
			BX.addCustomEvent(EventType.chatDialog.accessError, this.onChatDialogAccessError.bind(this));
			BX.addCustomEvent(EventType.chatDialog.taskStatusSuccess, this.onTaskStatusSuccess.bind(this));

			BX.addCustomEvent(EventType.call.active, this.onCallActive.bind(this));
			BX.addCustomEvent(EventType.call.inactive, this.onCallInactive.bind(this));

			BX.addCustomEvent(EventType.notification.open, this.onNotificationOpen.bind(this));
			BX.addCustomEvent(EventType.notification.reload, this.onNotificationReload.bind(this));

			BX.addCustomEvent(EventType.app.activeBefore, this.onAppActiveBefore.bind(this));
			BX.addCustomEvent(EventType.app.paused, this.onAppPaused.bind(this));
			BX.addCustomEvent(EventType.app.active, this.onAppActive.bind(this));
			BX.addCustomEvent(EventType.app.failRestoreConnection, this.refresh.bind(this));

			BX.addCustomEvent(EventType.setting.chat.change, this.onChatSettingChange.bind(this));
		}

		subscribeStoreEvents()
		{
			this.storeManager.on('applicationModel/setStatus', this.onApplicationSetStatus);
		}

		unsubscribeStoreEvents()
		{
			this.storeManager.off('applicationModel/setStatus', this.onApplicationSetStatus);
		}

		applicationSetStatusHandler(mutation)
		{
			const statusKey = mutation.payload.data.status.name;
			const statusValue = mutation.payload.data.status.value;
			const wasAppOffline = this.appStatus === AppStatus.networkWaiting;
			const isAppOnline = (statusKey === AppStatus.networkWaiting && statusValue === false);
			this.buildQueueRequests();

			if (wasAppOffline && isAppOnline)
			{
				Logger.info('Messenger: The device went online from offline.');

				this.refresh();
			}

			// this.redrawHeader();
			// TODO delete please me start
			if (this.chestnoPererisuemShapku)
			{
				this.redrawHeader();
				this.appStatus = this.core.getAppStatus();

				return;
			}

			if (this.core.getAppStatus() === AppStatus.running)
			{
				this.redrawHeader();
				this.appStatus = this.core.getAppStatus();

				return;
			}

			if (!this.redrawTimeout)
			{
				this.redrawTimeout = setTimeout(() => {
					this.redrawHeader();

					this.redrawTimeout = null;
				}, 3000);

			}
			// TODO delete please me end

			this.appStatus = this.core.getAppStatus();
		}

		redrawHeader()
		{
			let headerTitle;
			let useProgress;

			const appStatus = this.core.getAppStatus();
			switch (appStatus)
			{
				case AppStatus.networkWaiting:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_HEADER_NETWORK_WAITING');
					useProgress = true;
					break;

				case AppStatus.connection:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_HEADER_CONNECTION');
					useProgress = true;
					break;

				case AppStatus.sync:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_HEADER_SYNC');
					useProgress = true;
					break;

				default:
					headerTitle = Loc.getMessage('IMMOBILE_MESSENGER_HEADER');
					useProgress = false;
					break;
			}

			const actualTitleParams = {
				text: headerTitle,
				useProgress,
				largeMode: true,
			};

			if (isEqual(this.titleParams, actualTitleParams))
			{
				return;
			}

			this.titleParams = actualTitleParams;
			dialogList.setTitle(this.titleParams);
		}

		initPullHandlers()
		{
			BX.PULL.subscribe(new MessagePullHandler());
			BX.PULL.subscribe(new FilePullHandler());
			BX.PULL.subscribe(new DialogPullHandler());
			BX.PULL.subscribe(new UserPullHandler());
			BX.PULL.subscribe(new DesktopPullHandler());
			BX.PULL.subscribe(new NotificationPullHandler());
			BX.PULL.subscribe(new OnlinePullHandler());
		}

		initServices()
		{
			this.connectionService = ConnectionService.getInstance();
			this.syncService = SyncService.getInstance();
			this.sendingService = SendingService.getInstance();
			this.queueService = QueueService.getInstance();
		}

		async initCurrentUser()
		{
			const currentUser = await this.core.getRepository().user.userTable.getById(this.core.getUserId());
			if (currentUser)
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', [currentUser]);
			}
		}

		async initQueueRequests()
		{
			const queueRequests = await this.core.getRepository().queue.getList();
			if (queueRequests.length > 0)
			{
				await this.store.dispatch('queueModel/add', queueRequests);
			}
		}

		executeStoredPullEvents()
		{
			if (!Settings.isLocalStorageEnabled)
			{
				PushHandler.updateList();
			}

			PushHandler.executeAction();
		}

		onAppActiveBefore()
		{
			BX.onViewLoaded(() => {

				if (!Settings.isLocalStorageEnabled)
				{
					MessengerEmitter.emit(EventType.dialog.external.disableScrollToBottom);

					PushHandler.updateList();
				}

				this.refresh()
					.finally(() => {
						MessengerEmitter.emit(EventType.dialog.external.scrollToFirstUnread);
					})
				;
			});
		}

		onAppActive()
		{
			BX.onViewLoaded(() => {
				PushHandler.executeAction();
			});
		}

		onAppPaused()
		{
			PushHandler.clearHistory();
		}

		async refresh(chestnoPererisuemShapku)
		{
			this.chestnoPererisuemShapku = chestnoPererisuemShapku ?? false;

			await this.core.setAppStatus(AppStatus.connection, true);
			this.smileManager = SmileManager.getInstance();
			SmileManager.init();

			await this.queueCallBatch();

			return restManager.callBatch()
				.then(() => this.afterRefresh())
				.catch((response) => this.afterRefreshError(response))
			;
		}

		buildQueueRequests()
		{
			const requests = this.store.getters['queueModel/getQueue'];
			if (requests && requests.length > 0)
			{
				const sortedRequests = requests.sort((a, b) => a.priority - b.priority);

				sortedRequests.forEach((req) => {
					this.queueRestManager.once(req.requestName, req.requestData);
				});
			}
		}

		queueCallBatch()
		{
			return this.queueRestManager.callBatch()
				.then((response) => this.clearRequestQueue(response, true))
				.catch((error) => this.clearRequestQueue(error, true));
		}

		clearRequestQueue(response, withTemporaryMessage = false)
		{
			return this.queueService.clearRequestByBatchResult(response, withTemporaryMessage);
		}

		async afterRefresh()
		{
			if (Settings.isLocalStorageEnabled)
			{
				await this.core.setAppStatus(AppStatus.sync, true);
			}

			await this.core.setAppStatus(AppStatus.connection, false);
			this.refreshErrorNoticeFlag = false;
			ChatTimer.stop('recent', 'error', true);

			Counters.update();

			if (!Settings.isLocalStorageEnabled)
			{
				return this.ready();
			}

			return this.syncService.sync()
				.then(() => this.ready())
				.catch((error) => {
					Logger.error('Messenger.afterRefresh error', error);
				})
			;
		}

		async afterRefreshError(response)
		{
			const firstErrorKey = Object.keys(response)[0];
			if (firstErrorKey)
			{
				const firstError = response[firstErrorKey].error();
				if (firstError.ex.error === 'REQUEST_CANCELED')
				{
					Logger.error('Messenger.afterRefreshError', firstError.ex);

					return;
				}
			}

			const secondsBeforeRefresh = this.refreshAfterErrorInterval / 1000;
			Logger.error(`Messenger: refresh error. Try again in ${secondsBeforeRefresh} seconds.`);

			clearTimeout(this.refreshTimeout);

			this.refreshTimeout = setTimeout(() => {
				if (!this.refreshErrorNoticeFlag && !Application.isBackground())
				{
					const notifyRefreshError = () => {
						this.refreshErrorNoticeFlag = true;

						InAppNotifier.showNotification({
							message: Loc.getMessage('IMMOBILE_MESSENGER_REFRESH_ERROR'),
							backgroundColor: '#E6000000',
							time: this.refreshAfterErrorInterval / 1000 - 2,
						});
					};

					ChatTimer.start('recent', 'error', 2000, notifyRefreshError);
				}

				Logger.warn('Messenger.refresh after error');
				this.refresh();
			}, this.refreshAfterErrorInterval);
		}

		async ready()
		{
			this.isReady = true;

			if (this.isFirstLoad)
			{
				Logger.warn('Messenger.ready');
				EntityReady.ready('chat');
			}

			this.isFirstLoad = false;

			return this.core.setAppStatus(AppStatus.running, true)
				.then(() => {
					MessengerEmitter.emit(EventType.messenger.afterRefreshSuccess);
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
		}

		/* endregion initiation */

		/* region event handlers */

		openDialog(options = {})
		{
			Logger.info('EventType.messenger.openDialog', options);
			const openDialogOptions = options;
			if (openDialogOptions.dialogId)
			{
				openDialogOptions.dialogId = openDialogOptions.dialogId.toString();
			}

			// TODO: transfer the list of calls to the model and transfer the work with calls to the integration class
			if (openDialogOptions.callId && !openDialogOptions.dialogId)
			{
				const call = this.recent.getCallById(`call${openDialogOptions.callId}`);
				if (!call)
				{
					return;
				}

				const dialogId = get(call, 'params.call.associatedEntity.id', null);
				if (!dialogId)
				{
					return;
				}

				openDialogOptions.dialogId = String(dialogId);
			}

			PageManager.getNavigator().makeTabActive();
			this.visibilityManager.checkIsDialogVisible(openDialogOptions.dialogId)
				.then((isVisible) => {
					if (isVisible)
					{
						return;
					}

					this.dialog = new Dialog();
					this.dialog.open(openDialogOptions);
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
		}

		/**
		 * desc Handler call open sidebar event
		 * @param {{dialogId: string|number}} params
		 */
		openSidebar(params)
		{
			Logger.info('EventType.messenger.openSidebar', params);
			const dialogModel = this.store.getters['dialoguesModel/getById'](params.dialogId);

			// if curren role guest then open sidebar is none for it
			if (dialogModel && dialogModel.role && dialogModel.role === UserRole.guest)
			{
				return;
			}

			this.sidebar = new SidebarController(params);
			this.sidebar.open();
		}

		destroyDialog(dialogId)
		{
			Logger.info('EventType.messenger.destroyDialog', dialogId);
			this.dialog.view.ui.back();
		}

		openLine(options)
		{
			Logger.info('EventType.messenger.openLine', options);

			this.dialog = new Dialog();
			this.dialog.openLine(options);
		}

		openNotifications()
		{
			if (!PageManager.getNavigator().isActiveTab())
			{
				PageManager.getNavigator().makeTabActive();
			}

			BX.postComponentEvent('onTabChange', ['notifications'], 'im.navigation');
		}

		openChatSearch()
		{
			Logger.log('EventType.messenger.showSearch');

			this.searchSelector.open();
		}

		closeChatSearch()
		{
			Logger.log('EventType.messenger.hideSearch');

			this.searchSelector.close();
		}

		openChatCreate()
		{
			Logger.log('EventType.messenger.createChat');
			if (this.dialogCreator !== null)
			{
				this.dialogCreator.open();

				return;
			}

			this.chatCreator.open();
		}

		onNotificationOpen()
		{
			Logger.log('EventType.notification.open');

			Counters.notificationCounter.reset();
			Counters.update();
		}

		onNotificationReload()
		{
			Logger.log('EventType.notification.reload');

			BX.postWebEvent('onBeforeNotificationsReload', {});
			Application.refreshNotifications();
		}

		onCallActive(call, callStatus)
		{
			Logger.log('EventType.call.active');

			this.recent.addCall(call, callStatus);
		}

		onCallInactive(callId)
		{
			Logger.log('EventType.call.inactive');

			this.recent.removeCallById(callId);
		}

		onTaskStatusSuccess(taskId, result)
		{
			Logger.log('EventType.chatDialog.taskStatusSuccess', taskId, result);
		}

		getOpenDialogParams(options = {})
		{
			const openDialogParamsResponseEvent = `${EventType.messenger.openDialogParams}::${options.dialogId}`;

			const params = Dialog.getOpenDialogParams(options);
			BX.postComponentEvent(openDialogParamsResponseEvent, [params]);
		}

		getOpenLineParams(options = {})
		{
			const openLineParamsResponseEvent = `${EventType.messenger.openLineParams}::${options.userCode}`;

			Dialog.getOpenLineParams(options)
				.then((params) => {
					BX.postComponentEvent(openLineParamsResponseEvent, [params]);
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
		}

		uploadFiles(options)
		{
			Logger.log('EventType.messenger.uploadFiles', options);

			const { dialogId, fileList } = options;
			const deviceFileList = [];
			const diskFileList = [];
			fileList.forEach((file) => {
				if (file.dataAttributes)
				{
					diskFileList.push(file);

					return;
				}

				deviceFileList.push(file);
			});

			if (dialogId && Type.isArrayFilled(deviceFileList))
			{
				this.sendingService.sendFilesFromDevice(dialogId, deviceFileList);
			}

			if (dialogId && Type.isArrayFilled(diskFileList))
			{
				this.sendingService.sendFilesFromDisk(dialogId, diskFileList);
			}
		}

		cancelFileUpload(options)
		{
			Logger.log('EventType.messenger.cancelFileUpload', options);

			const { messageId, fileId } = options;
			if (Type.isStringFilled(messageId) && Type.isStringFilled(fileId))
			{
				this.sendingService.cancelFileUpload(messageId, fileId);
			}
		}

		/**
		 * @private
		 * @param {{id: string, value: any}} setting
		 */
		onChatSettingChange(setting)
		{
			Logger.log('Messenger.Setting.Chat.Change:', setting);
		}

		/* region legacy dialog integration */

		onChatDialogInitComplete(event)
		{
			Logger.log('EventType.chatDialog.initComplete', event);

			Promotion.checkDialog(event.dialogId.toString());
		}

		onChatDialogCounterChange(event)
		{
			Logger.log('EventType.chatDialog.counterChange', event);

			const recentItem = ChatUtils.objectClone(this.store.getters['recentModel/getById'](event.dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.counter = event.counter;
			const dialogItem = ChatUtils.objectClone(this.store.getters['dialoguesModel/getById'](event.dialogId));
			if (dialogItem)
			{
				this.store.dispatch('dialoguesModel/update', {
					dialogId: event.dialogId,
					fields: {
						counter: event.counter,
					},
				});
			}

			this.store.dispatch('recentModel/set', [recentItem]);
		}

		onChatDialogAccessError()
		{
			Logger.warn('EventType.chatDialog.accessError');

			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_ACCESS_ERROR_TITLE'),
				message: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_ACCESS_ERROR_TEXT'),
				backgroundColor: '#E6000000',
				time: 3,
			});

			this.dialog.deleteCurrentDialog();
		}

		onDialogAccessError()
		{
			InAppNotifier.showNotification({
				title: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_ACCESS_ERROR_TITLE'),
				message: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_ACCESS_ERROR_TEXT'),
				backgroundColor: '#E6000000',
				time: 3,
			});
		}

		/* endregion legacy dialog integration */

		/* endregion event handlers */

		checkRevision(response)
		{
			const error = response.error();
			if (error)
			{
				Logger.error('Messenger.checkRevision', error);

				return true;
			}

			const actualRevision = response.data().mobile;
			if (!Type.isNumber(actualRevision) || REVISION >= actualRevision)
			{
				Logger.log('Messenger.checkRevision: current', REVISION, 'actual', actualRevision);

				return true;
			}

			Logger.warn(
				'Messenger.checkRevision: reload scripts because revision up',
				REVISION,
				' -> ',
				actualRevision,
			);

			reloadAllScripts();

			return false;
		}

		destructor()
		{
			this.unsubscribeStoreEvents();

			if (this.connectionService)
			{
				this.connectionService.destructor();
			}

			BX.listeners = {};

			// eslint-disable-next-line no-console
			console.warn('Messenger: Garbage collection after refresh complete');
		}
	}

	window.messenger = new Messenger();
})();
