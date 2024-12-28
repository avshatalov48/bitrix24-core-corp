/**
 * @module im/messenger/controller/sidebar/channel/sidebar-controller
 */
jn.define('im/messenger/controller/sidebar/channel/sidebar-controller', (require, exports, module) => {
	/* global InAppNotifier */
	const { isOnline } = require('device/connection');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Theme } = require('im/lib/theme');
	const { Icon } = require('assets/icons');

	const { buttonIcons } = require('im/messenger/assets/common');
	const {
		EventType,
		DialogType,
		SidebarHeaderContextMenuActionType,
	} = require('im/messenger/const');

	const { UpdateChannel } = require('im/messenger/controller/chat-composer');

	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Logger } = require('im/messenger/lib/logger');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { ButtonFactory } = require('im/messenger/lib/ui/base/buttons');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { showDeleteChannelAlert, showLeaveChannelAlert } = require('im/messenger/lib/ui/alert');

	const { AnalyticsService } = require('im/messenger/provider/service');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');

	const { BaseSidebarController } = require('im/messenger/controller/sidebar/base/sidebar-controller');
	const { ChannelSidebarView } = require('im/messenger/controller/sidebar/channel/sidebar-view');
	const { SidebarService } = require('im/messenger/controller/sidebar/chat/sidebar-service');
	const { SidebarRestService } = require('im/messenger/controller/sidebar/chat/sidebar-rest-service');
	const { SidebarUserService } = require('im/messenger/controller/sidebar/chat/sidebar-user-service');
	const { ChannelParticipantsService } = require('im/messenger/controller/sidebar/channel/tabs/participants/participants-service');
	const { SidebarFilesService } = require('im/messenger/controller/sidebar/chat/tabs/files/service');
	const { SidebarLinksService } = require('im/messenger/controller/sidebar/chat/tabs/links/service');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--channel-sidebar-controller');

	class ChannelSidebarController extends BaseSidebarController
	{
		/**
		 * @constructor
		 * @param {object} options
		 * @param {string} options.dialogId
		 */
		constructor(options)
		{
			super(options);
			this.sidebarService = new SidebarService(this.store, this.dialogId);
			this.sidebarRestService = new SidebarRestService(this.dialogId);
			this.participantsService = new ChannelParticipantsService(options);
			this.sidebarUserService = null;

			/** @type {LayoutWidget | null} */
			this.dialogWidget = null;
		}

		/**
		 * @desc getter style btn
		 * @return {object}
		 */
		get styleBtn()
		{
			return {
				border: { color: Theme.colors.bgSeparatorPrimary },
				text: { color: Theme.colors.base1 },
			};
		}

		open(parentWidget = PageManager)
		{
			logger.log(`${this.constructor.name}.open`);

			this.createWidget(parentWidget);
		}

		createWidget(parentWidget = PageManager)
		{
			if (parentWidget !== PageManager)
			{
				this.dialogWidget = parentWidget;
			}

			parentWidget.openWidget(
				'layout',
				{
					titleParams: {
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_WIDGET_TITLE_CHANNEL'),
						type: 'entity',
					},
					rightButtons: this.getRightButtons(),
				},
			)
				.then(
					(widget) => {
						this.widget = widget;
						this.onWidgetReady();
					},
				)
				.catch((error) => {
					logger.error(`${this.constructor.name}.PageManager.openWidget.catch:`, error);
				});
		}

		async onWidgetReady()
		{
			logger.log(`${this.constructor.name}.onWidgetReady`);
			this.sidebarService.subscribeInitTabsData();
			this.sidebarService.initTabsData();
			this.sidebarService.setStore();
			this.bindMethods();
			this.setUserService();
			this.setPermissions();
			this.createView();
			this.initTabsData();
			this.widget.showComponent(this.view);
			this.subscribeStoreEvents();
			this.subscribeWidgetEvents();
			this.subscribeBXCustomEvents();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			super.bindMethods();
			this.onUpdateStore = this.onUpdateStore.bind(this);
			this.onCloseWidget = this.onCloseWidget.bind(this);
			this.onHiddenWidget = this.onHiddenWidget.bind(this);
		}

		setUserService()
		{
			this.sidebarUserService = new SidebarUserService(this.dialogId, false);
		}

		/**
		 * @desc Method setting permissions
		 * @void
		 */
		setPermissions()
		{
			const dialogState = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialogState)
			{
				this.isGeneral = dialogState.type === DialogType.generalChannel;
			}
			this.isCanLeave = ChatPermission.isCanLeaveFromChat(this.dialogId);
		}

		initTabsData()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const isInitedSidebar = this.store.getters['sidebarModel/isInited'](this.dialogId);

			if (dialogData && !isInitedSidebar)
			{
				Promise.all([
					new SidebarFilesService(dialogData.chatId).loadNextPage(),
					new SidebarLinksService(dialogData.chatId).loadNextPage(),
				])
					.catch((error) => {
						logger.error(`${this.constructor.name}.initTabsData:`, error);
					});
			}
		}

		createView()
		{
			this.view = new ChannelSidebarView(this.preparePropsSidebarView());
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.subscribeStoreEvents`);
			this.storeManager.on('sidebarModel/update', this.onUpdateStore);
			this.storeManager.on('dialoguesModel/update', this.onUpdateStore);
		}

		subscribeWidgetEvents()
		{
			super.subscribeWidgetEvents();
			logger.log(`${this.constructor.name}.subscribeWidgetEvents`);
			this.widget.on(EventType.view.close, this.onCloseWidget);
			this.widget.on(EventType.view.hidden, this.onHiddenWidget);
		}

		subscribeBXCustomEvents()
		{
			logger.log(`${this.constructor.name}.subscribeBXCustomEvents`);
			BX.addCustomEvent(EventType.sidebar.destroy, this.onDestroySidebar);
			BX.addCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeStoreEvents`);
			this.storeManager.off('sidebarModel/update', this.onUpdateStore);
			this.storeManager.off('dialoguesModel/update', this.onUpdateStore);
		}

		unsubscribeBXCustomEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeBXCustomEvents`);
			BX.removeCustomEvent(EventType.sidebar.destroy, this.onDestroySidebar);
			BX.removeCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		/**
		 * @desc Prepare data props for build view
		 * @return {ChannelSidebarViewProps}
		 */
		preparePropsSidebarView()
		{
			return {
				headData: {
					...this.sidebarUserService.getAvatarDataById(),
					...this.sidebarUserService.getTitleDataById(),
				},
				dialogType: this.getDialogModel()?.type,
				dialogId: this.dialogId,
				buttonElements: this.createButtons(),
				callbacks: {
					onClickInfoBLock: () => true,
				},
				restService: this.sidebarRestService,
				isSuperEllipseAvatar: this.isSuperEllipseAvatar(),
			};
		}

		/**
		 * @desc Creates layout elements for view block btns under info
		 * @return {Array<IconButton>}
		 */
		createButtons()
		{
			const disable = this.isGeneral || !this.isCanLeave;

			return [
				this.createMuteButton(),
				ButtonFactory.createIconButton(
					{
						icon: buttonIcons.search(Theme.colors.base7),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_SEARCH'),
						callback: () => this.onClickSearchButton(),
						disable: true,
						style: this.styleBtn,
					},
				),
				ButtonFactory.createIconButton(
					{
						icon: buttonIcons.logOutInline(disable ? Theme.colors.base7 : Theme.colors.base1),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_LEAVE'),
						callback: () => this.onClickLeaveButton(),
						disable,
						disableClick: disable,
						style: this.styleBtn,
					},
				),
			];
		}

		/**
		 * @desc Returns btn mute layout element ( muteOn or muteOff icon )
		 * @param {boolean} [isMute]
		 * @return {object|null}
		 */
		createMuteButton(isMute = this.sidebarService.isMuteDialog())
		{
			return isMute ? ButtonFactory.createIconButton(
				{
					icon: buttonIcons.muteOffInline(),
					text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
					callback: () => this.onClickMuteButton(),
					style: this.styleBtn,
				},
			) : ButtonFactory.createIconButton(
				{
					icon: buttonIcons.muteOnInline(),
					text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
					callback: () => this.onClickMuteButton(),
					style: this.styleBtn,
				},
			);
		}

		onClickSearchButton()
		{
			const locValue = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_NOTICE_COMING_SOON');
			InAppNotifier.showNotification(
				{
					backgroundColor: Theme.colors.baseBlackFixed,
					message: locValue,
				},
			);
		}

		onClickLeaveButton()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			showLeaveChannelAlert({
				leaveCallback: () => {
					this.onLeaveChannel();
				},
				cancelCallback: () => {
					AnalyticsService.getInstance().sendChatDeleteCanceled({
						dialogId: this.dialogId,
					});
				},
			});
		}

		onLeaveChannel()
		{
			this.participantsService.onClickLeaveChat()
				.catch((err) => logger.error(`${this.constructor.name}.onClickYesLeaveChannel`, err));
		}

		onClickMuteButton()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			const oldStateMute = this.sidebarService.isMuteDialog();
			this.store.dispatch('sidebarModel/changeMute', { dialogId: this.dialogId, isMute: !oldStateMute });

			if (oldStateMute)
			{
				this.sidebarService.muteService.unmuteChat(this.dialogId);
			}
			else
			{
				this.sidebarService.muteService.muteChat(this.dialogId);
			}
		}

		onHeaderMenuEditDialog()
		{
			logger.info(`${this.constructor.name}.onHeaderMenuEditDialog`);
			new UpdateChannel({ dialogId: this.dialogId }).openChannelView();

			AnalyticsService.getInstance().sendDialogEditHeaderMenuClick(this.dialogId);
		}

		onUpdateStore(event)
		{
			const { payload } = event;
			logger.info(`${this.constructor.name}.onUpdateStore---------->`, event);

			if (payload.actionName === 'changeMute' && Type.isBoolean(payload.data.fields.isMute))
			{
				this.changeMuteButton(payload.data.fields.isMute);
			}
		}

		onCloseWidget()
		{
			this.unsubscribeStoreEvents();
			this.unsubscribeBXCustomEvents();
			BX.onCustomEvent(EventType.sidebar.closeWidget);
		}

		onHiddenWidget()
		{
			logger.info(`${this.constructor.name}.onHiddenWidget`);
		}

		canLeave()
		{
			return true;
		}

		getHeaderButtonLeave()
		{
			return {
				id: SidebarHeaderContextMenuActionType.leave,
				title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_HEADER_MENU_UNSUBSCRIBE'),
				showIcon: true,
				iconName: Icon.LOG_OUT,
				testId: this.getContextMenuTestId(SidebarHeaderContextMenuActionType.leave),
				onItemSelected: this.onClickLeaveButton.bind(this),
			};
		}

		onHeaderMenuDeleteDialog()
		{
			showDeleteChannelAlert({
				deleteCallback: () => {
					this.deleteChat();
				},
				cancelCallback: () => {
					AnalyticsService.getInstance().sendChatDeleteCanceled({
						dialogId: this.dialogId,
					});
				},
			});

			AnalyticsService.getInstance().sendChatDeletePopupShown({
				dialogId: this.dialogId,
			});
		}

		async deleteChat()
		{
			const { type } = this.store.getters['dialoguesModel/getById'](this.dialogId);

			this.sidebarRestService.deleteChat()
				.then(async () => {
					MessengerEmitter.broadcast(EventType.dialog.external.delete, {
						dialogId: this.dialogId,
						chatType: type,
						shouldShowAlert: false,
						deleteByCurrentUserFromMobile: true,
					});
					const recentProvider = new RecentDataProvider();
					recentProvider.delete({ dialogId: this.dialogId })
						.catch((error) => {
							logger.error(`${this.constructor.name}.deleteChat delete recent error`, error);
						})
					;
					const chatProvider = new ChatDataProvider();
					chatProvider.delete({ dialogId: this.dialogId })
						.catch((error) => {
							logger.error(`${this.constructor.name}.deleteChat error`, error);
						})
					;

					this.widget.back();
					if (this.dialogWidget)
					{
						this.dialogWidget.back();
					}

					Notification.showToast(ToastType.deleteChannel);
				})
				.catch((error) => {
					Logger.error(error);
					Notification.showErrorToast();
				})
			;

			AnalyticsService.getInstance().sendChatDeleteConfirmed({
				dialogId: this.dialogId,
			});
		}

		/**
		 * @desc Changed icon in btn mute ( muteOn or muteOff )
		 * @param {boolean} [isMute]
		 * @void
		 */
		changeMuteButton(isMute)
		{
			const muteButton = this.createMuteButton(isMute);
			BX.onCustomEvent(EventType.sidebar.changeMuteButton, muteButton);
		}

		updateAllButton()
		{
			const newStateButton = this.createButtons();
			BX.onCustomEvent(EventType.sidebar.updateAllButton, newStateButton);
		}

		isSuperEllipseAvatar()
		{
			return true;
		}

		getDialogModel()
		{
			return this.store.getters['dialoguesModel/getById'](this.dialogId);
		}
	}

	module.exports = { ChannelSidebarController };
});
