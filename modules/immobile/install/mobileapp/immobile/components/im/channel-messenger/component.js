// jn.require('im/messenger/lib/dev/action-timer');

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
	/* global dialogList, ChatTimer, InAppNotifier, ChatUtils, reloadAllScripts */
	/* region import */
	const require = (ext) => jn.require(ext); // for IDE hints

	const DialogList = dialogList;
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { isEqual } = require('utils/object');
	const { ChannelApplication } = require('im/messenger/core/channel');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EntityReady } = require('entity-ready');
	const { Logger } = require('im/messenger/lib/logger');
	const {
		AppStatus,
		EventType,
		RestMethod,
		ComponentCode,
		NavigationTab,
		ViewName,
	} = require('im/messenger/const');

	const core = new ChannelApplication({
		localStorage: {
			enable: true,
			readOnly: true,
		},
	});
	try
	{
		await core.init();
	}
	catch (error)
	{
		Logger.error('CopilotApplication init error: ', error);
	}
	serviceLocator.add('core', core);

	const emitter = new JNEventEmitter();
	serviceLocator.add('emitter', emitter);

	const { MessengerInitService } = require('im/messenger/provider/service/messenger-init');
	const channelInitService = new MessengerInitService({
		actionName: RestMethod.immobileTabChannelLoad,
	});
	serviceLocator.add('messenger-init-service', channelInitService);

	const { Feature } = require('im/messenger/lib/feature');

	const {
		ChannelMessagePullHandler,
		ChannelDialogPullHandler,
		ChannelFilePullHandler,
	} = require('im/messenger/provider/pull/channel');
	const { SidebarPullHandler } = require('im/messenger/provider/pull/sidebar');
	const { SyncFillerChannel } = require('im/messenger/provider/service');

	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	const { ChannelRecent } = require('im/messenger/controller/recent/channel');
	const { RecentView } = require('im/messenger/view/recent');
	const { Dialog } = require('im/messenger/controller/dialog/chat');
	const { Counters } = require('im/messenger/lib/counters');
	const { runAction } = require('im/messenger/lib/rest');
	const { Communication } = require('im/messenger/lib/integration/mobile/communication');
	const { RecentSelector } = require('im/messenger/controller/search/experimental');
	const { SmileManager } = require('im/messenger/lib/smile-manager');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerBase } = require('im/messenger/component/messenger-base');
	/* endregion import */

	class ChannelMessenger extends MessengerBase
	{
		/* region initiation */

		/**
		 * @class ChannelMessenger - mobile messenger entry point
		 *
		 * @property {boolean} isReady - flag that the messenger has finished initialization
		 * @property {boolean} isFirstLoad - flag that the messenger is loading for the first time
		 *
		 * @property {Object} store - vuex store
		 * @property {Object} storeManager - vuex store manager
		 *
		 * @property {ChannelRecent} recent - recent chat list controller
		 * @property {Dialog} dialog - chat controller
		 * @property {DialogSelector} dialogSelector - chat search controller
		 * @property {ChatCreator} chatCreator - chat creation dialog
		 * @property {RestManager} restManager - collects requests to initialize the messenger into a batch and executes it
		 */
		constructor()
		{
			super();
			this.refreshAfterErrorInterval = 10000;

			this.communication = new Communication();

			this.extendWatchTimerId = null;
			this.currentTab = MessengerParams.get('FIRST_TAB_ID', NavigationTab.imMessenger);
			EntityReady.addCondition('channel-messenger', () => this.isReady);
		}

		initCore()
		{
			this.serviceLocator = serviceLocator;
			/**
			 * @type {CoreApplication}
			 */
			this.core = this.serviceLocator.get('core');
			this.repository = this.core.getRepository();

			/**
			 * @type {MessengerCoreStore}
			 */
			this.store = this.core.getStore();

			/**
			 * @type {MessengerInitService}
			 */
			this.channelInitService = this.serviceLocator.get('messenger-init-service');

			/**
			 * @type {MessengerCoreStoreManager}
			 */
			this.storeManager = this.core.getStoreManager();
		}

		/**
		 * @override
		 */
		checkChatV2Support()
		{
			return true;
		}

		initRequests()
		{
			this.channelInitService.onInit(this.checkRevision.bind(this));
		}

		/**
		 * @override
		 */
		async initComponents()
		{
			this.recent = new ChannelRecent({
				view: new RecentView({
					ui: DialogList,
					viewName: ViewName.recent,
				}),
			});
			await this.recent.init();

			this.searchSelector = new RecentSelector(DialogList);
		}

		/**
		 * @override
		 */
		bindMethods()
		{
			super.bindMethods();
			this.openDialog = this.openDialog.bind(this);
			this.getOpenDialogParams = this.getOpenDialogParams.bind(this);
			this.openChatSearch = this.openChatSearch.bind(this);
			this.closeChatSearch = this.closeChatSearch.bind(this);
			this.refresh = this.refresh.bind(this);

			this.onChatDialogCounterChange = this.onChatDialogCounterChange.bind(this);
			this.onTaskStatusSuccess = this.onTaskStatusSuccess.bind(this);
			this.onAppActiveBefore = this.onAppActiveBefore.bind(this);
			this.onChatSettingChange = this.onChatSettingChange.bind(this);
			this.onAppStatusChange = this.onAppStatusChange.bind(this);
			this.onTabChanged = this.onTabChanged.bind(this);
		}

		/**
		 * @override
		 */
		subscribeMessengerEvents()
		{
			BX.addCustomEvent(EventType.messenger.getOpenDialogParams, this.getOpenDialogParams);
			BX.addCustomEvent(EventType.messenger.showSearch, this.openChatSearch);
			BX.addCustomEvent(EventType.messenger.hideSearch, this.closeChatSearch);
			BX.addCustomEvent(EventType.messenger.refresh, this.refresh);
			BX.addCustomEvent(EventType.messenger.openDialog, this.openDialog);
		}

		unsubscribeMessengerEvents()
		{
			BX.removeCustomEvent(EventType.messenger.openDialog, this.openDialog);
			BX.removeCustomEvent(EventType.messenger.getOpenDialogParams, this.getOpenDialogParams);
			BX.removeCustomEvent(EventType.messenger.showSearch, this.openChatSearch);
			BX.removeCustomEvent(EventType.messenger.hideSearch, this.closeChatSearch);
			BX.removeCustomEvent(EventType.messenger.refresh, this.refresh);
		}

		/**
		 * @override
		 */
		subscribeExternalEvents()
		{
			super.subscribeExternalEvents();
			BX.addCustomEvent(EventType.chatDialog.counterChange, this.onChatDialogCounterChange);
			BX.addCustomEvent(EventType.chatDialog.taskStatusSuccess, this.onTaskStatusSuccess);
			BX.addCustomEvent(EventType.app.activeBefore, this.onAppActiveBefore);
			BX.addCustomEvent(EventType.app.failRestoreConnection, this.refresh);
			BX.addCustomEvent(EventType.setting.chat.change, this.onChatSettingChange);
			BX.addCustomEvent(EventType.app.changeStatus, this.onAppStatusChange);
			BX.addCustomEvent(EventType.navigation.tabChanged, this.onTabChanged);
		}

		/**
		 * @override
		 */
		unsubscribeExternalEvents()
		{
			super.unsubscribeExternalEvents();
			BX.removeCustomEvent(EventType.chatDialog.counterChange, this.onChatDialogCounterChange);
			BX.removeCustomEvent(EventType.chatDialog.taskStatusSuccess, this.onTaskStatusSuccess);
			BX.removeCustomEvent(EventType.app.activeBefore, this.onAppActiveBefore);
			BX.removeCustomEvent(EventType.app.failRestoreConnection, this.refresh);
			BX.removeCustomEvent(EventType.app.changeStatus, this.onAppStatusChange);
			BX.removeCustomEvent(EventType.setting.chat.change, this.onChatSettingChange);
		}

		/**
		 * @override
		 */
		initCustomServices()
		{
			this.syncFillerService = new SyncFillerChannel();
		}

		/**
		 * @override
		 */
		redrawHeader()
		{
			let headerTitle = '';
			let useProgress = false;

			const appStatus = this.core.getAppStatus();
			switch (appStatus)
			{
				case AppStatus.networkWaiting:
					headerTitle = Loc.getMessage('IMMOBILE_COMMON_MESSENGER_HEADER_NETWORK_WAITING');
					useProgress = true;
					break;

				case AppStatus.connection:
					headerTitle = Loc.getMessage('IMMOBILE_COMMON_MESSENGER_HEADER_CONNECTION');
					useProgress = true;
					break;

				case AppStatus.sync:
					headerTitle = Loc.getMessage('IMMOBILE_COMMON_MESSENGER_HEADER_SYNC');
					useProgress = true;
					break;

				default:
					// headerTitle = Loc.getMessage('IMMOBILE_COMMON_MESSENGER_HEADER');
					headerTitle = MessengerParams.getMessengerTitle();
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
			DialogList.setTitle(this.titleParams);
		}

		/**
		 * @override
		 */
		initPullHandlers()
		{
			BX.PULL.subscribe(new ChannelFilePullHandler());
			BX.PULL.subscribe(new ChannelMessagePullHandler());
			BX.PULL.subscribe(new ChannelDialogPullHandler());
			BX.PULL.subscribe(new SidebarPullHandler());
		}

		/**
		 * @override
		 */
		async initCurrentUser()
		{
			const currentUser = await this.core.getRepository().user.userTable.getById(this.core.getUserId());
			if (currentUser)
			{
				await this.store.dispatch('usersModel/setFromLocalDatabase', [currentUser]);
			}
		}

		/**
		 * @override
		 */
		async initQueueRequests()
		{
			const queueRequests = await this.core.getRepository().queue.getList();
			if (queueRequests.length > 0)
			{
				await this.store.dispatch('queueModel/add', queueRequests);
			}
		}

		onAppActiveBefore()
		{
			BX.onViewLoaded(() => {
				this.clearExtendWatchInterval();
				if (!Feature.isLocalStorageEnabled)
				{
					MessengerEmitter.emit(EventType.dialog.external.disableScrollToBottom);
				}

				this.refresh()
					.finally(() => {
						MessengerEmitter.emit(EventType.dialog.external.scrollToFirstUnread);
					})
				;
			});
		}

		/**
		 * @override
		 */
		async refresh()
		{
			await this.core.setAppStatus(AppStatus.connection, true);
			this.smileManager = SmileManager.getInstance();
			await SmileManager.init();

			if (PageManager.getNavigator().isActiveTab())
			{
				this.extendWatch();
				this.setExtendWatchInterval();
			}

			await this.queueCallBatch();

			return this.channelInitService.runAction(this.getBaseInitRestMethods())
				.then(() => {
					this.afterRefresh();
				})
				.catch((response) => {
					this.afterRefreshError(response);
				})
			;
		}

		setExtendWatchInterval()
		{
			if (this.extendWatchTimerId)
			{
				return;
			}

			this.extendWatchTimerId = setInterval(
				() => {
					if (this.extendWatchTimerId
					&& (
						!PageManager.getNavigator().isActiveTab()
						|| this.currentTab !== NavigationTab.imChannelMessenger
					)
					)
					{
						this.clearExtendWatchInterval();

						return;
					}

					this.extendWatch()
						.catch((error) => {
							this.clearExtendWatchInterval();
							Logger.error('ChannelMessenger.extendWatch error', error);
						});
				},
				600_000,
			);
		}

		clearExtendWatchInterval()
		{
			if (this.extendWatchTimerId)
			{
				clearInterval(this.extendWatchTimerId);
				this.extendWatchTimerId = null;
			}
		}

		async extendWatch()
		{
			return runAction('im.v2.Recent.Channel.extendPullWatch', {
				data: {},
			});
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
			await this.core.setAppStatus(AppStatus.connection, false);
			this.refreshErrorNoticeFlag = false;
			ChatTimer.stop('recent', 'error', true);

			Counters.update();

			return this.ready();
		}

		async afterRefreshError(response)
		{
			Logger.error('CopilotMessenger.afterRefreshError', response);
			const errorList = Type.isArray(response) ? response : [response];

			for (const error of errorList)
			{
				if (error?.code === 'REQUEST_CANCELED')
				{
					return;
				}
			}

			const secondsBeforeRefresh = this.refreshAfterErrorInterval / 1000;
			Logger.error(`ChannelMessenger: refresh error. Try again in ${secondsBeforeRefresh} seconds.`);

			clearTimeout(this.refreshTimeout);

			this.refreshTimeout = setTimeout(() => {
				if (!this.refreshErrorNoticeFlag && !Application.isBackground())
				{
					const notifyRefreshError = () => {
						this.refreshErrorNoticeFlag = true;

						InAppNotifier.showNotification({
							message: Loc.getMessage('IMMOBILE_COMMON_MESSENGER_REFRESH_ERROR'),
							backgroundColor: '#E6000000',
							time: this.refreshAfterErrorInterval / 1000 - 2,
						});
					};

					ChatTimer.start('recent', 'error', 2000, notifyRefreshError);
				}

				Logger.warn('ChannelMessenger.refresh after error');
				this.clearExtendWatchInterval();
				this.refresh();
			}, this.refreshAfterErrorInterval);
		}

		async ready()
		{
			this.isReady = true;

			if (this.isFirstLoad)
			{
				Logger.warn('ChannelMessenger.ready');
				EntityReady.ready('channel-messenger');
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
			Logger.info('ChannelMessenger.openDialog', options);
			const openDialogOptions = options;
			if (openDialogOptions.dialogId)
			{
				openDialogOptions.dialogId = openDialogOptions.dialogId.toString();
			}

			PageManager.getNavigator().makeTabActive();
			this.visibilityManager.checkIsDialogVisible({ dialogId: openDialogOptions.dialogId })
				.then((isVisible) => {
					if (isVisible)
					{
						return;
					}

					this.dialog = new Dialog();
					this.dialog.open(openDialogOptions);

					this.recent.view.renderChatCreateButton();
				})
				.catch((error) => {
					Logger.error(error);
				})
			;
		}

		openChatSearch()
		{
			Logger.log('ChannelMessenger.showSearch');

			this.searchSelector.open();
		}

		closeChatSearch()
		{
			Logger.log('ChannelMessenger.hideSearch');

			this.searchSelector.close();
		}

		onTaskStatusSuccess(taskId, result)
		{
			Logger.log('ChannelMessenger.chatDialog.taskStatusSuccess', taskId, result);
		}

		getOpenDialogParams(options = {})
		{
			const openDialogParamsResponseEvent = `${EventType.messenger.openDialogParams}::${options.dialogId}`;

			const params = Dialog.getOpenDialogParams(options);
			BX.postComponentEvent(openDialogParamsResponseEvent, [params], ComponentCode.imChannelMessenger);
		}

		/**
		 * @private
		 * @param {{id: string, value: any}} setting
		 */
		onChatSettingChange(setting)
		{
			Logger.log('ChannelMessenger.Setting.Chat.Change:', setting);
		}

		/* region legacy dialog integration */

		onChatDialogCounterChange(event)
		{
			Logger.log('ChannelMessenger.chatDialog.counterChange', event);

			const recentItem = ChatUtils.objectClone(
				this.store.getters['recentModel/getById'](event.dialogId),
			);

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

		onTabChanged({ newTab, previousTab })
		{
			Logger.log('ChannelMessenger.onTabChanged', newTab, this.extendWatchTimerId);

			this.currentTab = newTab;
			if (newTab !== NavigationTab.imChannelMessenger)
			{
				return;
			}

			if (this.extendWatchTimerId)
			{
				return;
			}

			this.refresh();
		}
		/* endregion legacy dialog integration */

		/* endregion event handlers */

		/**
		 * @param {immobileTabChannelLoadResult} data
		 */
		checkRevision(data)
		{
			const revision = data?.mobileRevision;
			if (!Type.isNumber(revision) || REVISION >= revision)
			{
				Logger.log('ChannelMessenger.checkRevision: current', REVISION, 'actual', revision);

				return true;
			}

			Logger.warn(
				'ChannelMessenger.checkRevision: reload scripts because revision up',
				REVISION,
				' -> ',
				revision,
			);

			reloadAllScripts();

			return false;
		}

		destructor()
		{
			this.unsubscribeStoreEvents();
			this.unsubscribeMessengerEvents();
			this.unsubscribeExternalEvents();

			if (this.connectionService)
			{
				this.connectionService.destructor();
			}

			BX.listeners = {};
			Logger.warn('ChannelMessenger: Garbage collection after refresh complete');
		}

		executeStoredPullEvents()
		{}

		/**
		 * @param {{name: string, value: string}} event
		 */
		onAppStatusChange(event)
		{
			core.setAppStatus(event.name, event.value);
		}
	}

	window.messenger = new ChannelMessenger();
})().catch((error) => {
	console.error('Messenger init error', error);
});
