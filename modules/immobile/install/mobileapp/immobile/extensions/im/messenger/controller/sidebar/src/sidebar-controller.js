/**
 * @module im/messenger/controller/sidebar/sidebar-controller
 */
jn.define('im/messenger/controller/sidebar/sidebar-controller', (require, exports, module) => {
	const { SidebarView } = require('im/messenger/controller/sidebar/sidebar-view');
	const { SidebarService } = require('im/messenger/controller/sidebar/sidebar-service');
	const { SidebarRestService } = require('im/messenger/controller/sidebar/sidebar-rest-service');
	const { SidebarUserService } = require('im/messenger/controller/sidebar/sidebar-user-service');
	const { buttonIcons } = require('im/messenger/assets/common');
	const { ButtonFactory } = require('im/messenger/lib/ui/base/buttons');
	const { chevronRight } = require('assets/common');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Type } = require('type');
	const { Logger } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Moment } = require('utils/date');
	const { EventType, DialogType } = require('im/messenger/const');
	const { Loc } = require('loc');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');
	const AppTheme = require('apptheme');

	class SidebarController
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
		}

		/**
		 * @desc getter style btn
		 * @return {object}
		 */
		get styleBtn()
		{
			const style = this.isGroupDialog ? { width: 83.75 } : { width: 113 };

			if (this.isCopilot)
			{
				style.border = { color: AppTheme.colors.accentSoftCopilot };
				style.text = { color: AppTheme.colors.accentMainCopilot };
			}

			return style;
		}

		open()
		{
			Logger.log('Sidebar.Controller.open');
			this.createWidget();
		}

		createWidget()
		{
			PageManager.openWidget(
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
				Logger.error('error', error);
			});
		}

		async onWidgetReady()
		{
			Logger.log('Sidebar.onWidgetReady');
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
			this.onCallActive = this.onCallActive.bind(this);
			this.onCallInactive = this.onCallInactive.bind(this);
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
			Logger.log('Sidebar.subscribeStoreEvents');
			this.storeManager.on('sidebarModel/update', this.onUpdateStore);
			this.storeManager.on('dialoguesModel/update', this.onUpdateStore);
		}

		subscribeWidgetEvents()
		{
			Logger.log('Sidebar.subscribeWidgetEvents');
			this.widget.on(EventType.view.close, this.onCloseWidget);
			this.widget.on(EventType.view.hidden, this.onCloseWidget);
		}

		subscribeViewEvents()
		{
			Logger.log('Sidebar.subscribeViewEvents');
		}

		subscribeBXCustomEvents()
		{
			Logger.log('Sidebar.subscribeBXCustomEvents');
			BX.addCustomEvent(EventType.call.active, this.onCallActive);
			BX.addCustomEvent(EventType.call.inactive, this.onCallInactive);
			BX.addCustomEvent('onDestroySidebar', this.onDestroySidebar);
		}

		unsubscribeStoreEvents()
		{
			Logger.log('Sidebar.unsubscribeStoreEvents');
			this.storeManager.off('sidebarModel/update', this.onUpdateStore);
			this.storeManager.off('dialoguesModel/update', this.onUpdateStore);
		}

		unsubscribeViewEvents()
		{
			Logger.log('Sidebar.unsubscribeViewEvents');
		}

		unsubscribeBXCustomEvents()
		{
			Logger.log('Sidebar.unsubscribeBXCustomEvents');
			BX.removeCustomEvent(EventType.call.active, this.onCallActive);
			BX.removeCustomEvent(EventType.call.inactive, this.onCallInactive);
			BX.removeCustomEvent('onDestroySidebar', this.onDestroySidebar);
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

		/**
		 * @desc Creates layout elements for view block btns under info
		 * @return {object}
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
				isShowCallBtn
					? ButtonFactory.createIconButton(
						{
							icon: this.isDisableCallBtn ? buttonIcons.video(AppTheme.colors.base7) : buttonIcons.video(),
							text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_VIDEO'),
							callback: () => this.onClickVideoBtn(),
							disable: this.isDisableCallBtn,
							style: this.styleBtn,
						},
					)
					: null,
				isShowCallBtn
					? ButtonFactory.createIconButton(
						{
							icon: this.isDisableCallBtn ? buttonIcons.calling(AppTheme.colors.base7) : buttonIcons.calling(),
							text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_CALL'),
							callback: () => this.onClickCallBtn(),
							disable: this.isDisableCallBtn,
							style: this.styleBtn,
						},
					)
					: null,
				this.createMuteBtn(),
				ButtonFactory.createIconButton(
					{
						icon: buttonIcons.search(AppTheme.colors.base7),
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
				// 		callback: () => Logger.log('IMMOBILE_DIALOG_SIDEBAR_BTN_MORE'),
				// 		disable: true,
				// 		style: this.styleBtn,
				// 	}
				// ),
			];
		}

		/**
		 * @desc Returns btn mute layout element ( muteOn or muteOff icon )
		 * @param {boolean} [isMite]
		 * @return {object|null}
		 */
		createMuteBtn(isMite = this.sidebarService.isMuteDialog())
		{
			const color = this.isCopilot ? AppTheme.colors.accentMainCopilot : AppTheme.colors.accentMainPrimaryalt;
			if (this.isGroupDialog)
			{
				return isMite ? ButtonFactory.createIconButton(
					{
						icon: buttonIcons.muteOff(color),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
						callback: () => this.onClickMuteBtn(),
						style: this.styleBtn,
					},
				) : ButtonFactory.createIconButton(
					{
						icon: buttonIcons.muteOn(color),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
						callback: () => this.onClickMuteBtn(),
						style: this.styleBtn,
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
			UserProfile.show(this.dialogId, { backdrop: true });
		}

		/** Handler block  */
		onClickVideoBtn()
		{
			if (this.permission.isCanCall)
			{
				Calls.createVideoCall(this.dialogId);
			}
			else
			{
				const errorCode = this.getErrorCodePermissions();
				const locValue = Loc.getMessage(`IMMOBILE_DIALOG_SIDEBAR_NOTICE_CALL_ERROR_${errorCode}`);
				InAppNotifier.showNotification(
					{
						backgroundColor: AppTheme.colors.baseBlackFixed,
						message: locValue,
					},
				);
			}
		}

		onClickCallBtn()
		{
			if (this.permission.isCanCall)
			{
				Calls.createAudioCall(this.dialogId);
			}
			else
			{
				const errorCode = this.getErrorCodePermissions();
				const locValue = Loc.getMessage(`IMMOBILE_DIALOG_SIDEBAR_NOTICE_CALL_ERROR_${errorCode}`);
				InAppNotifier.showNotification(
					{
						backgroundColor: AppTheme.colors.baseBlackFixed,
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
					backgroundColor: AppTheme.colors.baseBlackFixed,
					message: locValue,
				},
			);
		}

		onClickMuteBtn()
		{
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
			Logger.info('Sidebar.onUpdateStore---------->', event);

			if (payload.actionName === 'changeMute' && Type.isBoolean(payload.data.fields.isMute))
			{
				this.changeMuteBtn(payload.data.fields.isMute);
			}
		}

		onDestroySidebar()
		{
			this.widget.back();
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
			this.unsubscribeStoreEvents();
			this.unsubscribeViewEvents();
			this.unsubscribeBXCustomEvents();
			BX.onCustomEvent('onCloseSidebarWidget');
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
		};
	}

	module.exports = { SidebarController };
});
