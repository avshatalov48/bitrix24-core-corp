/**
 * @module im/messenger/controller/sidebar-v2/controller/base
 */
jn.define('im/messenger/controller/sidebar-v2/controller/base', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { EventType, Analytics } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatService } = require('im/messenger/provider/services/chat');
	const { RecentService } = require('im/messenger/provider/services/recent');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');
	const { RestManager } = require('im/messenger/lib/rest-manager');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { SidebarBaseView } = require('im/messenger/controller/sidebar-v2/controller/base/src/view');
	const { SidebarPermissionManager } = require(
		'im/messenger/controller/sidebar-v2/controller/base/src/permission-manager',
	);
	const { WidgetNavigator } = require('im/messenger/controller/sidebar-v2/controller/base/src/widget-navigator');
	const {
		SidebarContextMenuActionId,
		SidebarContextMenuActionPosition,
		SIDEBAR_DEFAULT_TOAST_OFFSET,
		SidebarContextMenuActionSection,
	} = require('im/messenger/controller/sidebar-v2/const');
	const { UIMenu } = require('layout/ui/menu');
	const { Icon } = require('assets/icons');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { DialogTextHelper } = require('im/messenger/controller/dialog/lib/helper/text');
	const { SidebarSearch } = require('im/messenger/controller/sidebar-v2/search');
	const { isOnline } = require('device/connection');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { AutoDeleteMessages } = require('im/messenger/lib/messages-auto-delete');

	class SidebarBaseController
	{
		// region initialization

		/**
		 * @public
		 * @param {SidebarControllerProps} props
		 * @return {Promise<SidebarBaseController>}
		 */
		async init(props)
		{
			this.props = props;
			this.dialogId = props.dialogId;
			this.dialogLocator = props.dialogLocator;
			this.config = props.config;

			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.restManager = new RestManager();

			await this.actualizeStore();
			this.dialogHelper = DialogHelper.createByDialogId(this.dialogId);

			this.widget = null;
			this.dialogWidget = null;
			this.widgetNavigator = new WidgetNavigator({
				dialogId: this.dialogId,
				dialogLocator: this.dialogLocator,
			});

			/** @type {SidebarPermissionManager} */
			this.permissionManager = this.createPermissionManager({
				dialogId: this.dialogId,
				dialogHelper: this.dialogHelper,
			});

			this.headerContextMenu = null;

			/** @type {SidebarContextMenuItem[]} */
			this.headerContextMenuItems = this.#prepareHeaderContextMenuItem();

			/** @type {SidebarBaseTab[]} */
			this.tabs = this.createTabs();

			/** @type {SidebarBaseView|null} */
			this.view = null;
			this.logger = LoggerManager.getInstance().getLogger(`SidebarV2.${this.constructor.name}`);
			this.analyticsService = AnalyticsService.getInstance();
			this.chatService = new ChatService();
			this.recentService = new RecentService();

			return this;
		}

		/**
		 * Use this hook only to fetch <b>strictly required</b> data from backend
		 * and put them into Vuex-store before widget opens.
		 * @protected
		 * @return {Promise<void>}
		 */
		async actualizeStore()
		{
			return Promise.resolve();
		}

		/**
		 * @protected
		 * @param {SidebarPermissionManagerDefaultProps} defaultProps
		 * @return {SidebarPermissionManager}
		 */
		createPermissionManager(defaultProps)
		{
			return new SidebarPermissionManager(defaultProps);
		}

		/**
		 * @public
		 * @param {PageManager} parentWidget
		 */
		async open(parentWidget = PageManager)
		{
			this.logger.log('Open');

			if (parentWidget !== PageManager)
			{
				this.dialogWidget = parentWidget;
			}

			parentWidget.openWidget(
				'layout',
				{
					titleParams: {
						text: this.getWidgetTitle(),
						type: 'entity',
					},
					rightButtons: this.getRightButtons(),
				},
			).then((widget) => {
				this.widget = widget;
				this.widgetNavigator.initWidgets({
					sidebarWidget: this.widget,
					dialogWidget: this.dialogWidget,
				});
				this.onWidgetReady();
			}).catch((error) => {
				this.logger.error('openWidget.error:', error);
			});
		}

		/**
		 * @protected
		 */
		onWidgetReady()
		{
			this.logger.log('onWidgetReady');
			this.bindMethods();
			this.loadInitialData();
			this.markSidebarModelInited();
			this.view = this.createView(this.prepareViewProps());
			this.widget.showComponent(this.view);
			this.subscribeStoreEvents();
			this.subscribeWidgetEvents();
			this.subscribeViewEvents();
			this.subscribeBXCustomEvents();
		}

		/**
		 * @protected
		 * @return {SidebarViewDefaultProps}
		 */
		prepareViewProps()
		{
			return {
				dialogId: this.dialogId,
				primaryActionButtons: this.#preparePrimaryActionButtons(),
				tabs: this.getTabs(),
				widget: this.widget,
				widgetNavigator: this.widgetNavigator,
				widgetTitle: this.getWidgetTitle(),
				isMessagesAutoDeleteDelayEnabled: this.dialogHelper.isMessagesAutoDeleteDelayEnabled,
				chatTitle: ChatTitle.createFromDialogId(this.dialogId, {
					ignoreInputActions: true,
				}),
				customAvatarProps: this.config.avatarProps,
			};
		}

		/**
		 * @private
		 */
		refreshView()
		{
			this.view.refresh(this.prepareViewProps());
		}

		bindMethods()
		{
			this.onStoreUpdate = this.onStoreUpdate.bind(this);
			this.onWidgetClose = this.onWidgetClose.bind(this);
			this.onWidgetTitleClick = this.onWidgetTitleClick.bind(this);
			this.onWidgetBarButtonTap = this.onWidgetBarButtonTap.bind(this);
			this.onUpdatePlanLimits = this.onUpdatePlanLimits.bind(this);
			this.onDestroySidebar = this.onDestroySidebar.bind(this);
			this.onDeleteChat = this.onDeleteChat.bind(this);
		}

		/**
		 * @protected
		 * We need this to allow process pull events, related to current sidebar.
		 */
		markSidebarModelInited()
		{
			this.store.dispatch('sidebarModel/set', {
				dialogId: this.dialogId,
				isMute: this.dialogHelper.isMuted,
			});
		}

		/**
		 * @protected
		 * @abstract
		 * @param {SidebarViewDefaultProps} defaultProps
		 * @return {SidebarBaseView}
		 */
		createView(defaultProps)
		{
		}

		subscribeStoreEvents()
		{
			this.storeManager.on('sidebarModel/update', this.onStoreUpdate);
			this.storeManager.on('dialoguesModel/update', this.onStoreUpdate);
		}

		unsubscribeStoreEvents()
		{
			this.storeManager.off('sidebarModel/update', this.onStoreUpdate);
			this.storeManager.off('dialoguesModel/update', this.onStoreUpdate);
		}

		subscribeWidgetEvents()
		{
			this.widget.on(EventType.view.close, this.onWidgetClose);
			this.widget.on(EventType.view.titleClick, this.onWidgetTitleClick);
			this.widget.on(EventType.view.barButtonTap, this.onWidgetBarButtonTap);
		}

		subscribeViewEvents()
		{
		}

		unsubscribeViewEvents()
		{
		}

		subscribeBXCustomEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
			BX.addCustomEvent(EventType.sidebar.destroy, this.onDestroySidebar);
			BX.addCustomEvent(EventType.messenger.updatePlanLimitsData, this.onUpdatePlanLimits);
		}

		unsubscribeBXCustomEvents()
		{
			BX.removeCustomEvent(EventType.messenger.updatePlanLimitsData, this.onUpdatePlanLimits);
			BX.removeCustomEvent(EventType.sidebar.destroy, this.onDestroySidebar);
			BX.removeCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		// endregion

		// region title and context menu

		/**
		 * @protected
		 * @abstract
		 * @return {string}
		 */
		getWidgetTitle()
		{
		}

		/**
		 * @protected
		 * @return {SidebarWidgetHeaderButton[]}
		 */
		getRightButtons()
		{
			const buttons = [];
			if (this.headerContextMenuItems.length > 0)
			{
				buttons.push({
					type: SidebarContextMenuActionId.MORE,
					id: SidebarContextMenuActionId.MORE,
					testId: 'SIDEBAR_HEADER_BUTTON_MORE',
				});
			}

			return buttons;
		}

		/**
		 * Override this to make custom context menus for specific chat types.
		 * @protected
		 * @return {SidebarContextMenuItem[]}
		 */
		getHeaderContextMenuItems()
		{
			const items = [
				this.permissionManager.canPin() ? this.getHeaderContextMenuItemPin() : null,
				this.permissionManager.canEdit() ? this.getHeaderContextMenuItemEdit() : null,
				this.permissionManager.canCopyLink() ? this.getHeaderContextMenuItemCopyLink() : null,
				this.permissionManager.canLeave() ? this.getHeaderContextMenuItemLeave() : null,
				this.permissionManager.canClearHistory() ? this.getHeaderContextMenuItemClearHistory() : null,
				this.permissionManager.canDelete() ? this.getHeaderContextMenuItemDelete() : null,
				this.permissionManager.canHide() ? this.getHeaderContextMenuItemHide() : null,
			];

			return items.filter(Boolean);
		}

		/**
		 * @return {SidebarContextMenuItem[]}
		 */
		#prepareHeaderContextMenuItem()
		{
			const { headerContextMenuItems } = this.config;
			const sortedHeaderContextMenuItem = this.getHeaderContextMenuItems().sort((a, b) => a.sort - b.sort);

			if (Type.isArrayFilled(headerContextMenuItems))
			{
				return sortedHeaderContextMenuItem.filter((item) => headerContextMenuItems.includes(item.id));
			}

			return sortedHeaderContextMenuItem;
		}

		/**
		 * @protected
		 * @return {SidebarContextMenuItem}
		 */
		getHeaderContextMenuItemClearHistory()
		{
			return {
				id: SidebarContextMenuActionId.CLEAR_HISTORY,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_CLEAR_HISTORY'),
				icon: Icon.CIRCLE_CROSS,
				testId: 'sidebar-context-menu-item-clear-history',
				sort: SidebarContextMenuActionPosition.BOTTOM,
				nextMenu: {
					icon: Icon.ARROW_TO_THE_LEFT,
					sections: [{ id: SidebarContextMenuActionSection.CLEAR_HISTORY }],
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_CLEAR_HISTORY'),
					items: [
						{
							id: SidebarContextMenuActionId.CLEAR_HISTORY_FOR_ME,
							sectionCode: SidebarContextMenuActionSection.CLEAR_HISTORY,
							title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_CLEAR_HISTORY_FOR_ME'),
							testId: 'sidebar-context-menu-item-clear-history-for-me',
							sort: SidebarContextMenuActionPosition.TOP,
							onItemSelected: () => this.handleClearHistoryForMeDialogAction(),
						},
						{
							id: SidebarContextMenuActionId.CLEAR_HISTORY_FOR_ALL,
							sectionCode: SidebarContextMenuActionSection.CLEAR_HISTORY,
							title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_CLEAR_HISTORY_FOR_ALL'),
							testId: 'sidebar-context-menu-item-clear-history-for-all',
							sort: SidebarContextMenuActionPosition.TOP,
							onItemSelected: () => this.handleClearHistoryForAllDialogAction(),
						},
					],
				},
			};
		}

		/**
		 * @protected
		 * @return {SidebarContextMenuItem}
		 */
		getHeaderContextMenuItemPin()
		{
			if (this.dialogHelper.isPinned)
			{
				return {
					id: SidebarContextMenuActionId.UNPIN,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_UNPIN'),
					icon: Icon.UNPIN,
					testId: 'sidebar-context-menu-item-unpin',
					sort: SidebarContextMenuActionPosition.TOP,
					onItemSelected: () => this.handleUnpinDialogAction(),
				};
			}

			return {
				id: SidebarContextMenuActionId.PIN,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_PIN'),
				icon: Icon.PIN,
				testId: 'sidebar-context-menu-item-pin',
				sort: SidebarContextMenuActionPosition.TOP,
				onItemSelected: () => this.handlePinDialogAction(),
			};
		}

		/**
		 * @protected
		 * @return {SidebarContextMenuItem}
		 */
		getHeaderContextMenuItemEdit()
		{
			return {
				id: SidebarContextMenuActionId.EDIT,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_EDIT'),
				icon: Icon.EDIT_SIZE_M,
				testId: 'sidebar-context-menu-item-edit',
				sort: SidebarContextMenuActionPosition.TOP,
				onItemSelected: () => this.handleEditDialogAction(),
			};
		}

		/**
		 * @protected
		 * @return {SidebarContextMenuItem}
		 */
		getHeaderContextMenuItemCopyLink()
		{
			return {
				id: SidebarContextMenuActionId.COPY_LINK,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_COPY_LINK'),
				icon: Icon.LINK,
				testId: 'sidebar-context-menu-copy-link',
				sort: SidebarContextMenuActionPosition.TOP,
				onItemSelected: this.handleOnCopyLinkAction,
			};
		}

		/**
		 * @protected
		 * @return {SidebarContextMenuItem}
		 */
		getHeaderContextMenuItemLeave()
		{
			return {
				id: SidebarContextMenuActionId.LEAVE,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_LEAVE'),
				icon: Icon.LOG_OUT,
				testId: 'sidebar-context-menu-leave',
				sort: SidebarContextMenuActionPosition.BOTTOM,
				onItemSelected: () => this.handleLeaveDialogAction(),
			};
		}

		/**
		 * @protected
		 * @return {SidebarContextMenuItem}
		 */
		getHeaderContextMenuItemDelete()
		{
			return {
				id: SidebarContextMenuActionId.DELETE,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_REMOVE'),
				icon: Icon.TRASHCAN,
				testId: 'sidebar-context-menu-delete',
				sort: SidebarContextMenuActionPosition.BOTTOM,
				isDestructive: true,
				onItemSelected: () => this.handleDeleteDialogAction(),
			};
		}

		/**
		 * @protected
		 * @return {SidebarContextMenuItem}
		 */
		getHeaderContextMenuItemHide()
		{
			return {
				id: SidebarContextMenuActionId.HIDE,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_HIDE'),
				icon: Icon.BOX_WITH_LID,
				testId: 'sidebar-context-menu-item-hide',
				sort: SidebarContextMenuActionPosition.BOTTOM,
				onItemSelected: () => this.handleHideDialogAction(),
			};
		}

		/**
		 * @private
		 * @return {void}
		 */
		showHeaderContextMenu()
		{
			if (this.headerContextMenuItems.length === 0)
			{
				return;
			}

			if (!this.headerContextMenu)
			{
				this.headerContextMenu = new UIMenu(this.headerContextMenuItems);
			}

			this.headerContextMenu.show();
		}

		/**
		 * @private
		 * @return {void}
		 */
		hideHeaderContextMenu()
		{
			this.headerContextMenu?.hide();
		}

		handleOnCopyLinkAction = () => {
			const link = this.dialogHelper.chatLink;

			DialogTextHelper.copyToClipboard(
				link,
				{
					notificationText: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_COPY_LINK_SUCCESS'),
					notificationIcon: Icon.LINK,
					toastOffset: SIDEBAR_DEFAULT_TOAST_OFFSET,
				},
				true,
			);
		};

		handlePinDialogAction()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast({ offset: SIDEBAR_DEFAULT_TOAST_OFFSET });

				return;
			}

			this.recentService.pinChat(this.dialogId);
			this.#updateHeaderContextMenuItems();
		}

		handleUnpinDialogAction()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast({ offset: SIDEBAR_DEFAULT_TOAST_OFFSET });

				return;
			}

			this.recentService.unpinChat(this.dialogId);
			this.#updateHeaderContextMenuItems();
		}

		handleHideDialogAction()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast({ offset: SIDEBAR_DEFAULT_TOAST_OFFSET });

				return;
			}

			this.recentService.hideChat(this.dialogId);
		}

		handleEditDialogAction()
		{}

		handleLeaveDialogAction()
		{}

		handleDeleteDialogAction()
		{}

		handleClearHistoryForMeDialogAction()
		{}

		handleClearHistoryForAllDialogAction()
		{}

		/**
		 * @return {void}
		 */
		#updateHeaderContextMenuItems()
		{
			this.headerContextMenuItems = this.getHeaderContextMenuItems().sort((a, b) => a.sort - b.sort);
			if (this.headerContextMenuItems.length === 0 || !this.headerContextMenu)
			{
				return;
			}

			this.headerContextMenu.setProvider(() => this.headerContextMenuItems);
		}

		// endregion

		// region primary actions

		/**
		 * @abstract
		 * @return {SidebarPrimaryActionButton[]}
		 */
		getPrimaryActionButtons()
		{
			return [];
		}

		/**
		 * @return {SidebarPrimaryActionButton[]}
		 */
		#preparePrimaryActionButtons()
		{
			const { primaryActionButtons } = this.config;
			const filteredPrimaryActionButtons = this.getPrimaryActionButtons().filter(Boolean);

			if (Type.isArrayFilled(primaryActionButtons))
			{
				return filteredPrimaryActionButtons.filter((item) => primaryActionButtons.includes(item.id));
			}

			return filteredPrimaryActionButtons;
		}

		/**
		 * @protected
		 */
		async handleSearchAction()
		{
			const search = new SidebarSearch({
				dialogId: this.dialogId,
				dialogLocator: this.dialogLocator,
			});
			await this.widgetNavigator.backToChat();
			search.open(this.dialogWidget);
		}

		async handleVideoCallAction()
		{
			return this.handleCommonCallAction((callManager) => {
				callManager.sendAnalyticsEvent(this.dialogId, Analytics.Element.videocall, Analytics.Section.chatSidebar);
				callManager.createVideoCall(this.dialogId);
			});
		}

		async handleAudioCallAction()
		{
			return this.handleCommonCallAction((callManager) => {
				callManager.sendAnalyticsEvent(this.dialogId, Analytics.Element.audiocall, Analytics.Section.chatSidebar);
				callManager.createAudioCall(this.dialogId);
			});
		}

		async handleCommonCallAction(callFn)
		{
			if (!isOnline())
			{
				Notification.showOfflineToast({ offset: SIDEBAR_DEFAULT_TOAST_OFFSET });

				return;
			}

			if (this.permissionManager.canCall())
			{
				try
				{
					const { CallManager } = await requireLazy('im:messenger/lib/integration/callmobile/call-manager');
					callFn(CallManager.getInstance());
				}
				catch (err)
				{
					this.logger.error('Init call unexpected error', err);
					Notification.showErrorToast({
						message: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CALL_UNKNOWN_ERROR'),
						offset: SIDEBAR_DEFAULT_TOAST_OFFSET,
					});
				}
			}
			else
			{
				const reason = this.permissionManager.getCallForbiddenReason();
				const message = reason || Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CALL_UNKNOWN_ERROR');

				this.logger.error('Init call permission error', reason);
				Notification.showErrorToast({
					message,
					offset: SIDEBAR_DEFAULT_TOAST_OFFSET,
				});
			}
		}

		handleToggleMuteAction()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast({ offset: SIDEBAR_DEFAULT_TOAST_OFFSET });

				return;
			}

			if (this.dialogHelper.isMuted)
			{
				this.chatService.unmuteChat(this.dialogId);
			}
			else
			{
				this.chatService.muteChat(this.dialogId);
			}
		}

		handleToggleAutoDeleteAction(ref)
		{
			const toastParams = { offset: SIDEBAR_DEFAULT_TOAST_OFFSET };
			const manager = new AutoDeleteMessages(this.dialogId, { toastParams });

			void manager.openMessagesAutoDeleteMenu({ targetRef: ref });
		}

		// endregion

		// region tabs and initial data

		/**
		 * @public
		 * @return {SidebarBaseTab[]}
		 */
		getTabs()
		{
			return this.tabs;
		}

		/**
		 * @protected
		 * @abstract
		 * @return {SidebarBaseTab[]}
		 */
		createTabs()
		{
			return [];
		}

		/**
		 * @protected
		 * @return {SidebarDataProvider[]}
		 */
		getInitialDataProviders()
		{
			return [];
		}

		/**
		 * @returns SidebarTabProps
		 */
		getTabsProps()
		{
			return {
				dialogId: this.dialogId,
				dialogLocator: this.dialogLocator,
				widgetNavigator: this.widgetNavigator,
			};
		}

		/**
		 * @private
		 */
		loadInitialData()
		{
			const dataProviders = [
				...this.tabs.map((tab) => tab.getDataProvider()),
				...this.getInitialDataProviders(),
			];

			dataProviders.forEach((dataProvider) => {
				const method = dataProvider.getInitialQueryMethod();
				if (method !== '')
				{
					this.restManager.once(
						dataProvider.getInitialQueryMethod(),
						dataProvider.getInitialQueryParams(),
						dataProvider.getInitialQueryHandler(),
					);
				}
			});

			this.restManager.callBatch().catch((err) => {
				this.logger.error('Initial data and tabs content batch failed', err);
			});
		}

		// endregion

		// region widget interactions

		onWidgetClose()
		{
			BX.onCustomEvent(EventType.sidebar.closeWidget);
			this.unsubscribeStoreEvents();
			this.unsubscribeViewEvents();
			this.unsubscribeBXCustomEvents();
		}

		onWidgetTitleClick()
		{
			this.view.scrollToBegin();
		}

		onWidgetBarButtonTap(id)
		{
			if (id === SidebarContextMenuActionId.MORE)
			{
				this.showHeaderContextMenu();
			}
		}

		// endregion

		// region handle external events/signals

		onUpdatePlanLimits()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const isHistoryLimitExceeded = !MessengerParams.isFullChatHistoryAvailable();
			this.store.dispatch(
				'sidebarModel/setHistoryLimitExceeded',
				{ chatId: dialogData?.chatId, isHistoryLimitExceeded },
			);
		}

		onDestroySidebar()
		{
			void this.widgetNavigator.backToChat();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		onDeleteChat({ dialogId })
		{
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}
			this.hideHeaderContextMenu();
			void this.widgetNavigator.backToDialogs();
		}

		onStoreUpdate(event)
		{
			this.logger.info('onStoreUpdate', event);
			const updatedDialogId = event?.payload?.data?.dialogId;
			if (this.dialogId === updatedDialogId)
			{
				this.dialogHelper = DialogHelper.createByDialogId(this.dialogId);
				this.refreshView();
			}
		}

		// endregion
	}

	module.exports = { SidebarBaseController, SidebarBaseView, SidebarPermissionManager };
});
