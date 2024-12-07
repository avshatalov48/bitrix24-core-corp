/**
 * @module im/messenger/controller/sidebar/channel/sidebar-controller
 */
jn.define('im/messenger/controller/sidebar/channel/sidebar-controller', (require, exports, module) => {
	/* global InAppNotifier */
	const { isOnline } = require('device/connection');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Icon } = require('assets/icons');
	const { UIMenu } = require('layout/ui/menu');

	const { Theme } = require('im/lib/theme');

	const { buttonIcons } = require('im/messenger/assets/common');
	const {
		EventType,
		DialogType,
		SidebarContextMenuActionType,
	} = require('im/messenger/const');

	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { ButtonFactory } = require('im/messenger/lib/ui/base/buttons');
	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { showDeleteChannelAlert } = require('im/messenger/lib/ui/alert');

	const { AnalyticsService } = require('im/messenger/provider/service');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');

	const { ChannelSidebarView } = require('im/messenger/controller/sidebar/channel/sidebar-view');
	const { SidebarService } = require('im/messenger/controller/sidebar/chat/sidebar-service');
	const { SidebarRestService } = require('im/messenger/controller/sidebar/chat/sidebar-rest-service');
	const { SidebarUserService } = require('im/messenger/controller/sidebar/chat/sidebar-user-service');
	const { SidebarFilesService } = require('im/messenger/controller/sidebar/chat/tabs/files/service');
	const { SidebarLinksService } = require('im/messenger/controller/sidebar/chat/tabs/links/service');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--channel-sidebar-controller');

	class ChannelSidebarController
	{
		/**
		 * @constructor
		 * @param {object} options
		 * @param {string} options.dialogId
		 */
		constructor(options)
		{
			this.options = options;
			this.store = serviceLocator.get('core')
				.getStore();
			this.storeManager = serviceLocator.get('core')
				.getStoreManager();
			this.sidebarService = new SidebarService(this.store, options.dialogId);
			this.sidebarRestService = new SidebarRestService(options.dialogId);
			this.sidebarUserService = null;
			this.dialogId = options.dialogId;
			this.headerContextMenuButtons = [];

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
				border: { color: Theme.colors.bgSeparatorSecondary },
				text: { color: Theme.colors.base2 },
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
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_WIDGET_TITLE_CHANNEL'),
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
			this.bindListener();
			this.setUserService();
			this.setPermissions();
			this.createView();
			this.initTabsData();
			this.widget.showComponent(this.view);
			this.subscribeStoreEvents();
			this.subscribeWidgetEvents();
			this.subscribeBXCustomEvents();
			this.setHeaderButtons();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindListener()
		{
			this.onUpdateStore = this.onUpdateStore.bind(this);
			this.onCloseWidget = this.onCloseWidget.bind(this);
			this.onDestroySidebar = this.onDestroySidebar.bind(this);
			this.onDeleteChat = this.onDeleteChat.bind(this);
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
			logger.log(`${this.constructor.name}.subscribeWidgetEvents`);
			this.widget.on(EventType.view.close, this.onCloseWidget);
			this.widget.on(EventType.view.hidden, this.onCloseWidget);
		}

		subscribeBXCustomEvents()
		{
			logger.log(`${this.constructor.name}.subscribeBXCustomEvents`);
			BX.addCustomEvent('onDestroySidebar', this.onDestroySidebar);
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
			BX.removeCustomEvent('onDestroySidebar', this.onDestroySidebar);
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
				dialogId: this.dialogId,
				buttonElements: this.createButtons(),
				callbacks: {
					onClickInfoBLock: () => true,
				},
				restService: this.sidebarRestService,
				isSuperEllipseAvatar: this.isSuperEllipseAvatar(),
			};
		}

		setHeaderButtons()
		{
			this.prepareHeaderContextMenuButtons();

			if (!Type.isArrayFilled(this.headerContextMenuButtons))
			{
				return;
			}

			this.widget.setRightButtons([
				{
					type: this.constants.headerButton.more,
					id: this.constants.headerButton.more,
					testId: `SIDEBAR_HEADER_BUTTON_${this.constants.headerButton.more.toUpperCase()}`,
					callback: () => {
						this.createHeaderPopupMenu();
					},
				},
			]);
		}

		prepareHeaderContextMenuButtons()
		{
			const helper = DialogHelper.createByDialogId(this.dialogId);
			if (!helper)
			{
				Logger.error(`${this.constructor.name}.createHeaderPopupMenu: unknown dialogId`, this.dialogId);

				return;
			}

			if (helper.canBeDeleted)
			{
				this.headerContextMenuButtons.push(
					{
						id: SidebarContextMenuActionType.delete,
						title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_CONTEXT_HEADER_MENU_DELETE'),
						showIcon: true,
						iconName: Icon.TRASHCAN,
						testId: this.getContextMenuTestId(SidebarContextMenuActionType.delete),
						isDestructive: true,
						onItemSelected: this.onDeleteChatButtonClick.bind(this),
					},
				);
			}
		}

		createHeaderPopupMenu()
		{
			if (!Type.isArrayFilled(this.headerContextMenuButtons))
			{
				return;
			}

			const menu = new UIMenu(this.headerContextMenuButtons);
			menu.show();
		}

		/**
		 * @desc Creates layout elements for view block btns under info
		 * @return {Array<IconButton>}
		 */
		createButtons()
		{
			const disable = this.isGeneral || !this.isCanLeave;

			return [
				this.createMuteBtn(),
				ButtonFactory.createIconButton(
					{
						icon: buttonIcons.search(Theme.colors.base7),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_SEARCH'),
						callback: () => this.onClickSearchBtn(),
						disable: true,
						style: this.styleBtn,
					},
				),
				ButtonFactory.createIconButton(
					{
						icon: buttonIcons.logOutInline(disable ? Theme.colors.base7 : Theme.colors.base1),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_LEAVE'),
						callback: () => this.onClickLeaveBtn(),
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
		createMuteBtn(isMute = this.sidebarService.isMuteDialog())
		{
			return isMute ? ButtonFactory.createIconButton(
				{
					icon: buttonIcons.muteOffInline(),
					text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
					callback: () => this.onClickMuteBtn(),
					style: this.styleBtn,
				},
			) : ButtonFactory.createIconButton(
				{
					icon: buttonIcons.muteOnInline(),
					text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
					callback: () => this.onClickMuteBtn(),
					style: this.styleBtn,
				},
			);
		}

		onClickSearchBtn()
		{
			const locValue = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_NOTICE_COMING_SOON');
			InAppNotifier.showNotification(
				{
					backgroundColor: Theme.colors.baseBlackFixed,
					message: locValue,
				},
			);
		}

		onClickLeaveBtn()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			setTimeout(() => {
				navigator.notification.confirm(
					'',
					(buttonId) => {
						if (buttonId === 2)
						{
							this.onClickYesLeaveChannel();
						}
					},
					Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHANNEL_CONFIRM_TITLE'),
					[
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHAT_CONFIRM_NO'),
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHANNEL_CONFIRM_YES'),
					],
				);
			}, 10);
		}

		onClickYesLeaveChannel()
		{
			this.sidebarRestService.leaveChat()
				.then(
					(result) => {
						if (result)
						{
							try
							{
								PageManager.getNavigator().popTo('im.tabs')
									// eslint-disable-next-line promise/no-nesting
									.catch((err) => {
										logger.error(`${this.constructor.name}.onClickYesLeaveChannel.popTo.catch`, err);
										BX.onCustomEvent('onDestroySidebar');
										MessengerEmitter.emit(EventType.messenger.destroyDialog);
									});
							}
							catch (e)
							{
								logger.error(`${this.constructor.name}.onClickYesLeaveChannel.getNavigator()`, e);
								BX.onCustomEvent('onDestroySidebar');
								MessengerEmitter.emit(EventType.messenger.destroyDialog);
							}
						}
					},
				)
				.catch((err) => logger.error(`${this.constructor.name}.onClickYesLeaveChannel.sidebarRestService.leaveChat`, err));
		}

		onClickMuteBtn()
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

		onUpdateStore(event)
		{
			const { payload } = event;
			logger.info(`${this.constructor.name}.onUpdateStore---------->`, event);

			if (payload.actionName === 'changeMute' && Type.isBoolean(payload.data.fields.isMute))
			{
				this.changeMuteBtn(payload.data.fields.isMute);
			}
		}

		onDestroySidebar()
		{
			this.widget.back();
		}

		onDeleteChat({ dialogId })
		{
			if (String(this.dialogId) !== String(dialogId))
			{
				return;
			}

			this.widget.back();
		}

		onCloseWidget()
		{
			this.unsubscribeStoreEvents();
			this.unsubscribeBXCustomEvents();
			BX.onCustomEvent('onCloseSidebarWidget');
		}

		onDeleteChatButtonClick()
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

			this.sidebarRestService.deleteChat(this.dialogId)
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
		changeMuteBtn(isMute)
		{
			const res = this.createMuteBtn(isMute);
			BX.onCustomEvent('onChangeMuteBtn', res);
		}

		updateBtn()
		{
			const newStateBtn = this.createButtons();
			BX.onCustomEvent('onUpdateBtn', newStateBtn);
		}

		isSuperEllipseAvatar()
		{
			return true;
		}

		/**
		 * @param {string} id
		 * @returns {string}
		 */
		getContextMenuTestId(id)
		{
			return `SIDEBAR_CONTEXT_MENU_${id.toUpperCase()}`;
		}

		/**
		 * @property {object}
		 */
		constants = {
			headerButton: {
				more: 'more',
			},
		};
	}

	module.exports = { ChannelSidebarController };
});
