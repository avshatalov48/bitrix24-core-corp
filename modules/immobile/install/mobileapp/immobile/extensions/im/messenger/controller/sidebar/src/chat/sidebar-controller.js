/**
 * @module im/messenger/controller/sidebar/chat/sidebar-controller
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-controller', (require, exports, module) => {
	/* global InAppNotifier */
	const { isOnline } = require('device/connection');

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { chevronRight } = require('assets/common');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-controller');
	const { Moment } = require('utils/date');
	const { UIMenu } = require('layout/ui/menu');
	const { CopilotRoleSelector } = require('layout/ui/copilot-role-selector');

	const { Theme } = require('im/lib/theme');

	const { buttonIcons } = require('im/messenger/assets/common');
	const {
		EventType,
		DialogType,
		SidebarContextMenuActionType,
		Analytics,
	} = require('im/messenger/const');

	const { UserProfile } = require('im/messenger/controller/user-profile');

	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { Notification, ToastType } = require('im/messenger/lib/ui/notification');
	const { showDeleteChatAlert } = require('im/messenger/lib/ui/alert');
	const { ButtonFactory } = require('im/messenger/lib/ui/base/buttons');

	const { AnalyticsService } = require('im/messenger/provider/service');
	const { ChatDataProvider, RecentDataProvider } = require('im/messenger/provider/data');

	const { SidebarView } = require('im/messenger/controller/sidebar/chat/sidebar-view');
	const { SidebarService } = require('im/messenger/controller/sidebar/chat/sidebar-service');
	const { SidebarRestService } = require('im/messenger/controller/sidebar/chat/sidebar-rest-service');
	const { SidebarUserService } = require('im/messenger/controller/sidebar/chat/sidebar-user-service');

	class ChatSidebarController
	{
		/**
		 * @constructor
		 * @param {object} options
		 * @param {string} options.dialogId
		 */
		constructor(options)
		{
			this.options = options;
			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.sidebarService = new SidebarService(this.store, options.dialogId);
			this.sidebarRestService = new SidebarRestService(options.dialogId);
			this.sidebarUserService = null;
			this.dialogId = options.dialogId;
			this.isDisableCallBtn = false;
			this.isGroupDialog = false;
			this.isNotes = false;
			this.isBot = false;
			this.isNetwork = false;
			this.isCopilot = false;
			this.headerContextMenuButtons = [];
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
			logger.log('Sidebar.Controller.open');
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
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_WIDGET_TITLE'),
				},
			).then(
				(widget) => {
					this.widget = widget;
					this.onWidgetReady();
				},
			).catch((error) => {
				logger.error('error', error);
			});
		}

		async onWidgetReady()
		{
			logger.log('Sidebar.onWidgetReady');
			this.sidebarService.subscribeInitTabsData();
			this.sidebarService.initTabsData();
			this.sidebarService.setStore();
			this.bindListener();
			this.setEntitySidebar();
			this.setUserService();
			await this.setPermissions();
			this.createView();
			this.widget.showComponent(this.view);
			this.subscribeStoreEvents();
			this.subscribeWidgetEvents();
			this.subscribeViewEvents();
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
			this.onHiddenWidget = this.onHiddenWidget.bind(this);
			this.onDestroySidebar = this.onDestroySidebar.bind(this);
			this.onCallActive = this.onCallActive.bind(this);
			this.onUpdatePlanLimits = this.onUpdatePlanLimits.bind(this);
			this.onCallInactive = this.onCallInactive.bind(this);
			this.onDeleteChat = this.onDeleteChat.bind(this);
		}

		/**
		 * @desc Method set entity sidebar ( group, notes )
		 * @void
		 */
		setEntitySidebar()
		{
			let dialogIdValue = this.dialogId;
			this.isGroupDialog = DialogHelper.isDialogId(dialogIdValue);

			const currentUserId = MessengerParams.getUserId();
			if (!this.isGroupDialog)
			{
				if (Type.isString(this.dialogId))
				{
					dialogIdValue = Number(dialogIdValue);
				}

				if (dialogIdValue === currentUserId)
				{
					this.isNotes = true;
				}
			}

			const dialogState = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialogState)
			{
				this.isCopilot = dialogState.type === DialogType.copilot;
			}
		}

		setUserService()
		{
			this.sidebarUserService = new SidebarUserService(this.dialogId, this.isNotes);
		}

		/**
		 * @desc Method setting permissions for user calls and  manage participants
		 * @void
		 */
		async setPermissions()
		{
			if (this.isGroupDialog)
			{
				let dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
				if (!dialogData)
				{
					dialogData = await this.sidebarRestService.getDialogById();
				}

				this.permission = ChatPermission.isCanCall(dialogData, true);
				this.isDisableCallBtn = !this.permission.isCanCall;
			}
			else
			{
				if (this.isNotes) // sidebar 'my notes' not has call btn and tab participants
				{
					return;
				}

				let userData = this.store.getters['usersModel/getById'](this.dialogId);
				if (!userData || Type.isBoolean(userData.lastActivityDate) || Type.isUndefined(userData.lastActivityDate))
				{
					userData = await this.sidebarRestService.getUserById();
				}

				this.permission = UserPermission.isCanCall(userData, true);
				this.isDisableCallBtn = !this.permission.isCanCall;
				this.isBot = this.permission.isBot;
				this.isNetwork = this.permission.isNetwork;
			}
		}

		createView()
		{
			this.view = new SidebarView(this.preparePropsSidebarView());
		}

		subscribeStoreEvents()
		{
			logger.log('Sidebar.subscribeStoreEvents');
			this.storeManager.on('sidebarModel/update', this.onUpdateStore);
			this.storeManager.on('dialoguesModel/update', this.onUpdateStore);
		}

		subscribeWidgetEvents()
		{
			logger.log('Sidebar.subscribeWidgetEvents');
			this.widget.on(EventType.view.close, this.onCloseWidget);
			this.widget.on(EventType.view.hidden, this.onHiddenWidget);
		}

		subscribeViewEvents()
		{
			logger.log('Sidebar.subscribeViewEvents');
		}

		subscribeBXCustomEvents()
		{
			logger.log('Sidebar.subscribeBXCustomEvents');
			BX.addCustomEvent(EventType.messenger.updatePlanLimitsData, this.onUpdatePlanLimits);
			BX.addCustomEvent(EventType.call.active, this.onCallActive);
			BX.addCustomEvent(EventType.call.inactive, this.onCallInactive);
			BX.addCustomEvent('onDestroySidebar', this.onDestroySidebar);
			BX.addCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		unsubscribeStoreEvents()
		{
			logger.log('Sidebar.unsubscribeStoreEvents');
			this.storeManager.off('sidebarModel/update', this.onUpdateStore);
			this.storeManager.off('dialoguesModel/update', this.onUpdateStore);
		}

		unsubscribeViewEvents()
		{
			logger.log('Sidebar.unsubscribeViewEvents');
		}

		unsubscribeBXCustomEvents()
		{
			logger.log('Sidebar.unsubscribeBXCustomEvents');
			BX.removeCustomEvent(EventType.call.active, this.onCallActive);
			BX.removeCustomEvent(EventType.call.inactive, this.onCallInactive);
			BX.removeCustomEvent('onDestroySidebar', this.onDestroySidebar);
			BX.removeCustomEvent(EventType.dialog.external.delete, this.onDeleteChat);
		}

		/**
		 * @desc Prepare data props for build view
		 * @return {SidebarViewProps}
		 */
		preparePropsSidebarView()
		{
			return {
				isGroupDialog: this.isGroupDialog,
				isNotes: this.isNotes,
				isBot: this.isBot,
				isCopilot: this.isCopilot,
				headData: {
					...this.sidebarUserService.getAvatarDataById(),
					...this.sidebarUserService.getTitleDataById(),
				},
				userData: this.buildUserData(),
				dialogId: this.dialogId,
				buttonElements: this.createButtons(),
				callbacks: {
					onClickInfoBLock: () => this.onClickInfoBLock(),
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

			this.contexMenu = new UIMenu(this.headerContextMenuButtons);
			this.contexMenu.show();
		}

		/**
		 * @desc Creates layout elements for view block btns under info
		 * @return {Array<IconButton>}
		 */
		createButtons()
		{
			const isShowCallBtn = !this.isNotes
				&& !this.isCopilot
				&& !this.permission.isBot
				&& !this.permission.isNetwork
				&& !this.permission.isYou
				&& this.permission.isHTTPS
				&& (this.isGroupDialog ? this.permission.isEntityType : true);

			return [
				this.isCopilot
					? ButtonFactory.createIconButton(
						{
							icon: buttonIcons.copilotInline(),
							text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_ROLE'),
							callback: () => this.onClickRoleBtn(),
							disable: false,
							style: this.styleBtn,
						},
					)
					: null,
				isShowCallBtn
					? ButtonFactory.createIconButton(
						{
							icon: this.isDisableCallBtn ? buttonIcons.videoInline(Theme.colors.base7) : buttonIcons.videoInline(),
							text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_VIDEO'),
							callback: () => this.onClickVideoBtn(),
							disable: this.isDisableCallBtn,
							disableClick: this.isDisableCallBtn,
							style: this.styleBtn,
						},
					)
					: null,
				isShowCallBtn
					? ButtonFactory.createIconButton(
						{
							icon: this.isDisableCallBtn ? buttonIcons.callingInline(Theme.colors.base7) : buttonIcons.callingInline(),
							text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_CALL'),
							callback: () => this.onClickCallBtn(),
							disable: this.isDisableCallBtn,
							disableClick: this.isDisableCallBtn,
							style: this.styleBtn,
						},
					)
					: null,
				this.createMuteBtn(),
				ButtonFactory.createIconButton(
					{
						icon: buttonIcons.search(Theme.colors.base7),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_SEARCH'),
						callback: () => this.onClickSearchBtn(),
						disable: true,
						style: this.isNotes ? { width: '100%' } : this.styleBtn,
					},
				),
				// TODO should uncomment, when the layout and logic is ready
				// ButtonFactory.createIconButton(
				// 	{
				// 		icon: buttonIcons.more(),
				// 		text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MORE'),
				// 		callback: () => logger.log('IMMOBILE_DIALOG_SIDEBAR_BTN_MORE'),
				// 		disable: true,
				// 		style: this.styleBtn,
				// 	}
				// ),
			].filter((btn) => !Type.isNull(btn));
		}

		/**
		 * @desc Returns btn mute layout element ( muteOn or muteOff icon )
		 * @param {boolean} [isMite]
		 * @return {object|null}
		 */
		createMuteBtn(isMite = this.sidebarService.isMuteDialog())
		{
			if (this.isGroupDialog)
			{
				return isMite ? ButtonFactory.createIconButton(
					{
						icon: buttonIcons.muteOffInline(),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
						callback: () => this.onClickMuteBtn(),
						style: this.styleBtn,
						id: 'mute',
					},
				) : ButtonFactory.createIconButton(
					{
						icon: buttonIcons.muteOnInline(),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
						callback: () => this.onClickMuteBtn(),
						style: this.styleBtn,
						id: 'mute',
					},
				);
			}

			return null;
		}

		/**
		 * @desc Build user data for use in view
		 * @return {object}
		 */
		buildUserData()
		{
			if (this.isGroupDialog || this.isNotes)
			{
				return {};
			}

			if (this.isBot)
			{
				return {
					statusSvg: this.sidebarUserService.getUserStatus(),
				};
			}

			const userModelData = this.store.getters['usersModel/getById'](this.dialogId);

			return {
				lastActivityDate: this.getUserLastTime(),
				statusSvg: this.sidebarUserService.getUserStatus(),
				departmentName: userModelData.departmentName || '',
				chevron: chevronRight(),
				userModelData,
			};
		}

		/**
		 * @desc Returns is online by hold in 200 seconds
		 * @return {object}
		 */
		isUserOnline(lastActivity)
		{
			const holdLastActivity = new Moment().timestamp - new Moment(lastActivity).timestamp;

			return holdLastActivity < 200;
		}

		/**
		 * @desc Get user last activity data by store
		 * @return {object}
		 */
		getUserLastTime()
		{
			const userData = this.store.getters['usersModel/getById'](this.dialogId);
			const isOnline = this.isUserOnline(userData.lastActivityDate);
			if (isOnline === false)
			{
				return userData.lastActivityDate ? new Moment(userData.lastActivityDate) : null;
			}

			return null;
		}

		/**
		 * @desc Returns check is equal user counter with participants length
		 * @return {boolean}
		 */
		isCorrectUserCounter()
		{
			const dialogState = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogState)
			{
				return false;
			}

			return dialogState.userCounter === dialogState.participants.length;
		}

		/**
		 * @desc Handler on click by info block sidebar view
		 */
		onClickInfoBLock()
		{
			if (!this.isGroupDialog && !this.isBot)
			{
				this.callUserProfile();
			}
		}

		/**
		 * @desc Method call user profile component
		 */
		callUserProfile()
		{
			UserProfile.show(this.dialogId, {
				backdrop: true,
				openingDialogId: this.dialogId,
			});
		}

		/** Handler block  */
		onClickVideoBtn()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			if (this.permission.isCanCall)
			{
				Calls.sendAnalyticsEvent(this.dialogId, Analytics.Element.videocall, Analytics.Section.chatSidebar);
				Calls.createVideoCall(this.dialogId);
			}
			else
			{
				const errorCode = this.getErrorCodePermissions();
				const locValue = Loc.getMessage(`IMMOBILE_DIALOG_SIDEBAR_NOTICE_CALL_ERROR_${errorCode}`);
				InAppNotifier.showNotification(
					{
						backgroundColor: Theme.colors.baseBlackFixed,
						message: locValue,
					},
				);
			}
		}

		onClickRoleBtn()
		{
			logger.info(`${this.constructor.name}.onClickRoleBtn`);
			CopilotRoleSelector.open({
				showOpenFeedbackItem: true,
				openWidgetConfig: {
					backdrop: {
						mediumPositionPercent: 75,
						horizontalSwipeAllowed: false,
						onlyMediumPosition: false,
					},
				},
				skipButtonText: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_WIDGET_CHANGE_ROLE_BTN'),
			})
				.then((result) => {
					if (result?.role?.code)
					{
						this.sidebarRestService.changeCopilotRole(result?.role?.code || null);
					}
				})
				.catch((error) => logger.error(error));
		}

		onClickCallBtn()
		{
			if (!isOnline())
			{
				Notification.showOfflineToast();

				return;
			}

			if (this.permission.isCanCall)
			{
				Calls.sendAnalyticsEvent(this.dialogId, Analytics.Element.audiocall, Analytics.Section.chatSidebar);
				Calls.createAudioCall(this.dialogId);
			}
			else
			{
				const errorCode = this.getErrorCodePermissions();
				const locValue = Loc.getMessage(`IMMOBILE_DIALOG_SIDEBAR_NOTICE_CALL_ERROR_${errorCode}`);
				InAppNotifier.showNotification(
					{
						backgroundColor: Theme.colors.baseBlackFixed,
						message: locValue,
					},
				);
			}
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
			logger.info('Sidebar.onUpdateStore---------->', event);

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

			this.contexMenu?.hide?.();
			this.widget.back();
		}

		onUpdatePlanLimits()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const isHistoryLimitExceeded = !MessengerParams.isFullChatHistoryAvailable();
			this.store.dispatch('sidebarModel/setHistoryLimitExceeded', { chatId: dialogData?.chatId, isHistoryLimitExceeded });
		}

		onCallActive()
		{
			if (!this.isDisableCallBtn)
			{
				this.isDisableCallBtn = true;
				this.updateBtn();
			}
		}

		onCallInactive()
		{
			if (this.isDisableCallBtn)
			{
				this.isDisableCallBtn = false;
				this.updateBtn();
			}
		}

		onCloseWidget()
		{
			this.onHiddenWidget();
			BX.removeCustomEvent(EventType.messenger.updatePlanLimitsData, this.onUpdatePlanLimits);
		}

		onHiddenWidget()
		{
			this.unsubscribeStoreEvents();
			this.unsubscribeViewEvents();
			this.unsubscribeBXCustomEvents();
			BX.onCustomEvent('onCloseSidebarWidget');
		}

		onDeleteChatButtonClick()
		{
			showDeleteChatAlert({
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
					// delete chat from other messenger contexts
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
							logger.error(`${this.constructor.name}.deleteChat delete chat error`, error);
						})
					;

					this.widget.back();
					if (this.dialogWidget)
					{
						this.dialogWidget.back();
					}

					Notification.showToast(ToastType.deleteChat);
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

		/**
		 * @desc Returns error code by check need permissions
		 * @return {string}
		 */
		getErrorCodePermissions()
		{
			const { needPermissions } = this.constants;
			const needPermissionsData = this.isGroupDialog ? needPermissions.toCallChat : needPermissions.toCallUser;
			let err = 'DEFAULT';
			for (const [permission, needValue] of Object.entries(needPermissionsData))
			{
				const currValue = this.permission[permission];
				if (currValue !== needValue)
				{
					err = permission.slice(2).toUpperCase();
					break;
				}
			}

			return err;
		}

		/**
		 * @desc Returns height for empty row any device ( example for float btn )
		 * @return {number}
		 * @private
		 */
		getHeightEmptyRow()
		{
			if (Application.getPlatform() !== 'ios')
			{
				return 110;
			}
			const deviceHeight = device.screen.height || 810;
			const refHeightDevice = 844;
			const refHeightRow = 75;
			const refPercentAttitude = 1.57;
			const percentOffsetHeightDevice = (refHeightDevice - deviceHeight) / (refHeightDevice / 100);
			const percentOffsetHeightRow = percentOffsetHeightDevice * refPercentAttitude;

			return refHeightRow - (percentOffsetHeightRow * refHeightRow / 100);
		}

		isSuperEllipseAvatar()
		{
			return false;
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
			needPermissions: {
				toCallChat: {
					isMoreOne: true,
					isLimit: false,
				},
				toCallUser: {
					isLive: true,
				},
			},
			headerButton: {
				more: 'more',
			},
		};
	}

	module.exports = { ChatSidebarController };
});
