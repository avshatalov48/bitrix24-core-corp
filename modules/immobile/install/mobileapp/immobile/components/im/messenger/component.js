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

(() => {
	/* region import */
	const require = (ext) => jn.require(ext); // for IDE hints

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { get } = require('utils/object');
	const { core } = require('im/messenger/core');
	const { restManager } = require('im/messenger/lib/rest-manager');

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
		EventType,
		RestMethod,
		FeatureFlag,
	} = require('im/messenger/const');
	const {
		SendingService,
	} = require('im/messenger/provider/service');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Logger } = require('im/messenger/lib/logger');
	const { SoftLoader } = require('im/messenger/lib/helper');

	const { Recent } = require('im/messenger/controller/recent');
	const { RecentView } = require('im/messenger/view/recent');
	const { Dialog } = require('im/messenger/controller/dialog');
	const { DialogSelector } = require('im/messenger/controller/dialog-selector');
	const { ChatCreator } = require('im/messenger/controller/chat-creator');
	const { Counters } = require('im/messenger/lib/counters');
	const { EntityReady } = require('entity-ready');
	const { Communication } = require('im/messenger/lib/integration/mobile/communication');
	const { Promotion } = require('im/messenger/lib/promotion');
	const { PushHandler } = require('im/messenger/provider/push');
	const { SelectorDialogListAdapter } = require('im/chat/selector/adapter/dialog-list');
	const { DialogCreator } = require('im/messenger/controller/dialog-creator');
	const { SidebarController } = require('im/messenger/controller/sidebar/sidebar-controller');
	const { VisibilityManager } = require('im/messenger/lib/visibility-manager');
	const { RecentSelector } = require('im/messenger/controller/search/experimental');
	const { MessengerParams } = require('im/messenger/lib/params');
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
			if (FeatureFlag.isBetaVersion)
			{
				Logger.enable('log');
			}

			Logger.enable('info');
			Logger.enable('warn');
			Logger.enable('error');

			this.isReady = false;
			this.isFirstLoad = true;
			this.refreshTimeout = null;
			this.refreshAfterErrorInterval = 10000;
			this.refreshErrorNoticeFlag = false;

			this.visibilityManager = VisibilityManager.getInstance();

			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = null;
			/**
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = null;
			this.sendingService = null;

			this.recent = null;
			this.dialog = null;
			this.dialogSelector = null;
			/** @type {RecentSelector} */
			this.recentSelector = null;
			this.chatCreator = null;
			this.dialogCreator = null;
			this.sidebar = null;
			this.communication = new Communication();
			this.loader = new SoftLoader({
				safeDisplayTime: 500,
				onShow: this.showProgress.bind(this),
				onHide: this.hideProgress.bind(this),
			});

			EntityReady.addCondition('chat', () => this.isReady);

			this.init();
		}

		init()
		{
			this.preloadAssets();

			this.core = core;
			this.core
				.ready()
				.then(() => {
					this.store = this.core.getStore();
					this.storeManager = this.core.getStoreManager();

					this.initRequests();

					BX.onViewLoaded(() => {
						this.initComponents();
						this.subscribeEvents();
						this.initPullHandlers();
						this.sendingService = SendingService.getInstance();

						EntityReady.wait('im.navigation')
							.then(() => this.executeStoredPullEvents())
							.catch((error) => Logger.error(error))
						;

						this.refresh();
					});
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
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

			const chatSettings = Application.storage.getObject('settings.chat', {
				chatBetaEnable: false,
			});
			if (MessengerParams.isBetaAvailable() && chatSettings.chatBetaEnable)
			{
				this.recentSelector = new RecentSelector(dialogList);
			}
			else
			{
				this.dialogSelector = new DialogSelector({
					view: new SelectorDialogListAdapter(dialogList),
				});
			}

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
			BX.addCustomEvent(EventType.messenger.closeDialog, this.closeDialog.bind(this));
			BX.addCustomEvent(EventType.messenger.destroyDialog, this.destroyDialog.bind(this));
			BX.addCustomEvent(EventType.messenger.uploadFiles, this.uploadFiles.bind(this));
			BX.addCustomEvent(EventType.messenger.cancelFileUpload, this.cancelFileUpload.bind(this));
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

		executeStoredPullEvents()
		{
			PushHandler.updateList();
			PushHandler.executeAction();
		}

		onAppActiveBefore()
		{
			BX.onViewLoaded(() => {
				PushHandler.updateList();

				this.refresh();
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

		refresh()
		{
			this.loader.show();

			restManager.callBatch()
				.then((response) => this.afterRefresh(response))
				.catch((response) => this.afterRefreshError(response))
			;
		}

		afterRefresh(response)
		{
			this.loader.hide();

			this.refreshErrorNoticeFlag = false;
			ChatTimer.stop('recent', 'error', true);

			Counters.update();

			this.ready();
		}

		afterRefreshError(response)
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

		ready()
		{
			this.isReady = true;

			if (this.isFirstLoad)
			{
				Logger.warn('Messenger.ready');
				EntityReady.ready('chat');
			}

			this.isFirstLoad = false;

			MessengerEmitter.emit(EventType.messenger.afterRefreshSuccess);
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
			this.sidebar = new SidebarController(params);
			this.sidebar.open();
		}

		closeDialog(dialogId)
		{
			Logger.info('EventType.messenger.closeDialog', dialogId);
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

			if (this.recentSelector)
			{
				this.recentSelector.open();

				return;
			}

			this.dialogSelector.open();
		}

		closeChatSearch()
		{
			Logger.log('EventType.messenger.hideSearch');

			if (this.recentSelector)
			{
				this.recentSelector.close();
			}
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

			this.dialog.deleteCurrentDialog();
		}

		/* endregion legacy dialog integration */

		/* endregion event handlers */

		showProgress()
		{
			dialogList.setTitle({
				text: Loc.getMessage('COMPONENT_TITLE'),
				useProgress: true,
				largeMode: true,
			});
		}

		hideProgress()
		{
			dialogList.setTitle({
				text: Loc.getMessage('COMPONENT_TITLE'),
				useProgress: false,
				largeMode: true,
			});
		}

		// TODO: Remove after database manager implementation
		initCache()
		{
			const recentState = RecentCache.get();
			const usersState = UsersCache.get();
			const filesState = FilesCache.get();

			const cachePromiseList = [];

			if (recentState)
			{
				cachePromiseList.push(this.store.dispatch('recentModel/setState', recentState));
			}

			if (usersState)
			{
				cachePromiseList.push(this.store.dispatch('usersModel/setState', usersState));
			}

			if (filesState)
			{
				cachePromiseList.push(this.store.dispatch('filesModel/setState', filesState));
			}

			return Promise.all(cachePromiseList);
		}

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
				Logger.warn('Messenger.checkRevision: current', REVISION, 'actual', actualRevision);

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
			BX.listeners = {};

			// eslint-disable-next-line no-console
			console.warn('Messenger: Garbage collection after refresh complete');
		}
	}

	window.messenger = new Messenger();
})();
