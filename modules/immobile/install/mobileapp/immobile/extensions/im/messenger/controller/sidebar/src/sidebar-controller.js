/**
 * @module im/messenger/controller/sidebar/sidebar-controller
 */
jn.define('im/messenger/controller/sidebar/sidebar-controller', (require, exports, module) => {
	const { SidebarView } = require('im/messenger/controller/sidebar/sidebar-view');
	const { SidebarTabView } = require('im/messenger/controller/sidebar/tab-view');
	const { SidebarServices } = require('im/messenger/controller/sidebar/sidebar-services');
	const { ChatTitle, ChatAvatar, UserStatus } = require('im/messenger/lib/element');
	const { buttonIcons, bookmarkAvatar } = require('im/messenger/assets/common');
	const { ButtonFactory } = require('im/messenger/lib/ui/base/buttons');
	const { chevronRight } = require('assets/common');
	const { core } = require('im/messenger/core');
	const { Type } = require('type');
	const { Logger } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Moment } = require('utils/date');
	const { EventType } = require('im/messenger/const');
	const { Loc } = require('loc');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { Calls } = require('im/messenger/lib/integration/immobile/calls');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');
	const { UserAdd } = require('im/messenger/controller/user-add');

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
			this.store = core.getStore();
			this.storeManager = core.getStoreManager();
			this.services = new SidebarServices(this.store, options.dialogId);
			this.dialogId = options.dialogId;
			this.isDisableCallBtn = false;
			this.isGroupDialog = false;
			this.isNotes = false;
			this.isBot = false;
			this.isNetwork = false;
		}

		/**
		 * @desc getter style btn
		 * @return {object}
		 */
		get styleBtn()
		{
			return this.isGroupDialog ? { width: 83.75 } : { width: 113 };
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
			this.services.setStore();
			this.bindListener();
			this.setEntitySidebar();
			await this.setPermissions();
			await this.prepareData();
			await this.createView();
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
			this.onUpdateParticipants = this.onUpdateParticipants.bind(this);
			this.onClickBtnParticipantsAdd = this.onClickBtnParticipantsAdd.bind(this);
			this.onClickBtnParticipantsDelete = this.onClickBtnParticipantsDelete.bind(this);
			this.onAddParticipantInBackDrop = this.onAddParticipantInBackDrop.bind(this);
			this.onChangeUserCounter = this.onChangeUserCounter.bind(this);
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
					dialogData = await this.services.getDialogById();
				}

				this.permission = ChatPermission.isCanCall(dialogData, true);
				this.isDisableCallBtn = !this.permission.isCanCall;
				this.isCanRemoveParticipants = ChatPermission.isCanRemoveParticipants(dialogData);
				// this permission while is the static true, but in future is may being dynamical
				this.isCanAddParticipants = true;
			}
			else
			{
				if (this.isNotes) // sidebar 'my notes' not has call btn and tab participants
				{
					return;
				}

				let userData = this.store.getters['usersModel/getUserById'](this.dialogId);
				if (!userData || Type.isBoolean(userData.lastActivityDate) || Type.isUndefined(userData.lastActivityDate))
				{
					userData = await this.services.getUserById();
				}

				this.permission = UserPermission.isCanCall(userData, true);
				this.isDisableCallBtn = !this.permission.isCanCall;
				this.isCanAddParticipants = true;
				this.isBot = this.permission.isBot;
				this.isNetwork = this.permission.isNetwork;
			}
		}

		/**
		 * @desc Method prepare need data controller, before create view
		 * @return void
		 */
		async prepareData()
		{
			if (this.isCorrectUserCounter())
			{
				await this.setParticipantsFromStore(false);
			}
			else
			{
				this.onUpdateParticipants();
			}
		}

		async createView()
		{
			this.createTabView();
			this.view = new SidebarView(this.preparePropsSidebarView());
		}

		createTabView()
		{
			const tabItems = this.buildTabsData();

			this.tabView = new SidebarTabView({
				tabItems,
				permissions: {
					isCanAddParticipants: this.isCanAddParticipants,
					isCanRemoveParticipants: this.isCanRemoveParticipants,
				},
				selectedTab: tabItems[0],
				participantsCache: this.services.participantsMapCache,
				loc: {
					removeParticipants: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ITEM_ACTION_REMOVE'),
				},
			});
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
		}

		subscribeViewEvents()
		{
			Logger.log('Sidebar.subscribeViewEvents');
			this.tabView.on('updateParticipants', this.onUpdateParticipants);
			this.tabView.on('clickBtnParticipantsAdd', this.onClickBtnParticipantsAdd);
			this.tabView.on('clickBtnParticipantsDelete', this.onClickBtnParticipantsDelete);
		}

		subscribeBXCustomEvents()
		{
			Logger.log('Sidebar.subscribeBXCustomEvents');
			BX.addCustomEvent(EventType.call.active, this.onCallActive);
			BX.addCustomEvent(EventType.call.inactive, this.onCallInactive);
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
			this.tabView.off('updateParticipants', this.onUpdateParticipants);
			this.tabView.off('clickBtnParticipantsAdd', this.onClickBtnParticipantsAdd);
			this.tabView.off('clickBtnParticipantsDelete', this.onClickBtnParticipantsDelete);
		}

		unsubscribeBXCustomEvents()
		{
			Logger.log('Sidebar.unsubscribeBXCustomEvents');
			BX.removeCustomEvent(EventType.call.active, this.onCallActive);
			BX.removeCustomEvent(EventType.call.inactive, this.onCallInactive);
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
				headData: {
					...this.getAvatarDataById(),
					...this.getTitleDataById(),
				},
				userData: this.buildUserData(),
				dialogData: { ...(this.isGroupDialog && this.buildDialogData()) },
				buttonElements: this.createButtons(),
				tabView: this.tabView,
				callbacks: {
					onClickInfoBLock: () => this.onClickInfoBLock(),
				},
			};
		}

		/**
		 * @desc Creates layout elements for view block btns under info
		 * @return {object}
		 */
		createButtons()
		{
			const isShowCallBtn = !this.isNotes
				&& !this.permission.isBot
				&& !this.permission.isNetwork
				&& !this.permission.isYou
				&& this.permission.isHTTPS
				&& (this.isGroupDialog ? this.permission.isEntityType : true);

			return [
				isShowCallBtn
					? ButtonFactory.createIconButton(
						{
							icon: this.isDisableCallBtn ? buttonIcons.video('#C9CCD0') : buttonIcons.video(),
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
							icon: this.isDisableCallBtn ? buttonIcons.calling('#C9CCD0') : buttonIcons.calling(),
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
						icon: buttonIcons.search('#C9CCD0'),
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
		createMuteBtn(isMite = this.services.isMuteDialog())
		{
			if (this.isGroupDialog)
			{
				return isMite ? ButtonFactory.createIconButton(
					{
						icon: buttonIcons.muteOff(),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
						callback: () => this.onClickMuteBtn(),
						style: this.styleBtn,
					},
				) : ButtonFactory.createIconButton(
					{
						icon: buttonIcons.muteOn(),
						text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_BTN_MUTE'),
						callback: () => this.onClickMuteBtn(),
						style: this.styleBtn,
					},
				);
			}

			return null;
		}

		/**
		 * @desc Get avatar data by current dialogId ( url or color )
		 * @param {string} [id=this.dialogId]
		 * @return {object}
		 */
		getAvatarDataById(id = this.dialogId)
		{
			if (this.isNotes)
			{
				return {
					svg: {
						content: bookmarkAvatar(),
					},
				};
			}

			const chatAvatar = ChatAvatar.createFromDialogId(id);

			return chatAvatar.getTitleParams();
		}

		/**
		 * @desc Get title and desc data by current dialogId
		 * @param {string} [id=this.dialogId]
		 * @return {object}
		 */
		getTitleDataById(id = this.dialogId)
		{
			if (this.isNotes)
			{
				return {
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PROFILE_TITLE_NOTES'),
					desc: null,
				};
			}

			const chatTitle = ChatTitle.createFromDialogId(id);

			return {
				title: chatTitle.getTitle(),
				desc: chatTitle.getDescription(),
			};
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
					statusSvg: this.getUserStatus(),
				};
			}

			const userModelData = this.store.getters['usersModel/getUserById'](this.dialogId);

			return {
				...this.getUserLastTime(),
				statusSvg: this.getUserStatus(),
				department: this.getUserDepartmentName(userModelData.departmentName),
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
			const userData = this.store.getters['usersModel/getUserById'](this.dialogId);
			const isOnline = this.isUserOnline(userData.lastActivityDate);
			if (isOnline === false)
			{
				const lastActivityDate = userData.lastActivityDate ? new Moment(userData.lastActivityDate) : null;
				const lastActivityText = userData.gender
					? Loc.getMessage(`IMMOBILE_DIALOG_SIDEBAR_USER_TO_BE_${userData.gender}`)
					: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_USER_TO_BE');

				return {
					lastActivityDate,
					lastActivityText,
				};
			}

			return {};
		}

		/**
		 * @desc Get svg string for content image by dialog/user id
		 * @param {string} [id=this.dialogId]
		 * @return {string}
		 */
		getUserStatus(id = this.dialogId)
		{
			const userData = this.store.getters['usersModel/getUserById'](id);
			const isOnline = this.isUserOnline(userData.lastActivityDate);
			const statusUrl = this.getUserStatusUrlById(id);

			if (isOnline)
			{
				return statusUrl;
			}

			if (userData.status !== 'online')
			{
				return statusUrl;
			}

			return '';
		}

		/**
		 * @desc Get users department name
		 * @param {string} departmentName
		 * @return {string|null}
		 */
		getUserDepartmentName(departmentName)
		{
			if (Type.isBoolean(departmentName) || Type.isUndefined(departmentName) || departmentName.length <= 1)
			{
				this.services.getUserDepartment().then((department) => {
					const newState = { ...this.view.state.userData, department };
					this.updateStateView({ userData: newState });
				}).catch((err) => {
					Logger.error(err);
				});

				return null;
			}

			return departmentName;
		}

		/**
		 * @desc Get svg string for content image by dialog/user id
		 * @param {string} [id=this.dialogId]
		 * @return {string}
		 */
		getUserStatusUrlById(id = this.dialogId)
		{
			return UserStatus.getStatusByUserId(id);
		}

		/**
		 * @desc Build dialog data for use in view
		 * @param {object} data
		 * @param {number} [data.userCounter]
		 * @return {object}
		 */
		buildDialogData(data = {})
		{
			const countParticipants = data.userCounter ? data.userCounter : this.getCountParticipants();

			return {
				userCounter: countParticipants || 0,
				userCounterLocal: this.createUserCounterLabel(countParticipants),
			};
		}

		/**
		 * @desc Returns count participants
		 * @return {number}
		 */
		getCountParticipants()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);

			return dialogData ? dialogData.userCounter : 0;
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
		 * @desc Create string for label user counter by number
		 * @param {number} userCounter
		 * @return {string}
		 */
		createUserCounterLabel(userCounter)
		{
			return Loc.getMessagePlural(
				'IMMOBILE_DIALOG_SIDEBAR_USER_COUNTER',
				userCounter,
				{
					'#COUNT#': userCounter,
				},
			);
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
					message: locValue,
				},
			);
		}

		onClickMuteBtn()
		{
			const oldStateMute = this.services.isMuteDialog();
			this.store.dispatch('sidebarModel/changeMute', { dialogId: this.dialogId, isMute: !oldStateMute });

			if (oldStateMute)
			{
				this.services.muteService.unmuteChat(this.dialogId);
			}
			else
			{
				this.services.muteService.muteChat(this.dialogId);
			}
		}

		onUpdateStore(event)
		{
			const { payload } = event;
			Logger.info('Sidebar.onUpdateStore---------->', event);

			if (payload.actionName === 'changeMute')
			{
				const oldStateMute = this.services.isMuteDialog();
				if (payload.fields.isMute !== oldStateMute)
				{
					this.changeMuteBtn(payload.fields.isMute);
				}
			}

			if (payload.actionName === 'addParticipants')
			{
				this.setParticipantsFromStore();
			}

			if (payload.actionName === 'removeParticipants')
			{
				if (!Type.isUndefined(payload.removeData) && Type.isArray(payload.removeData))
				{
					let isHasId = false;
					payload.removeData.forEach((userId) => {
						isHasId = this.services.checkDeletedUserFromCache(userId);
					});

					if (!isHasId)
					{
						this.setParticipantsFromStore();
					}
				}

				return;
			}

			if (payload.actionName === 'updateUserCounter')
			{
				this.onChangeUserCounter(payload.fields.userCounter).catch(
					(error) => {
						Logger.error('onChangeUserCounter error', error);
					},
				);
			}
		}

		onUpdateParticipants()
		{
			Logger.log('onUpdateParticipants');
			this.buildFreshParticipantsData().then(
				(data) => this.setParticipants(data),
			).catch(
				(error) => {
					Logger.error('error', error);
				},
			);
		}

		onClickBtnParticipantsAdd()
		{
			UserAdd.open(
				{
					dialogId: this.dialogId,
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_TITLE'),
					textRightBtn: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_NAME_BTN'),
					callback: {
						onAddUser: this.onAddParticipantInBackDrop,
					},
					widgetOptions: { mediumPositionPercent: 65 },
				},
			);
		}

		async onClickBtnParticipantsDelete(deletedUser)
		{
			Logger.log('onClickBtnParticipantsDelete');
			if (!deletedUser)
			{
				return;
			}

			this.services.addDeletedUserToCache(deletedUser.id);

			const oldCache = this.services.participantsMapCache.get('participants');
			this.putParticipantsCache(oldCache.filter((user) => user.id !== deletedUser.id));

			const newUserCounter = this.view.state.dialogData.userCounter - 1;
			this.store.dispatch('dialoguesModel/updateUserCounter', {
				dialogId: this.dialogId,
				userCounter: newUserCounter,
			});

			this.services.deleteParticipant(deletedUser.id)
				.then(
					(result) => {
						if (!result)
						{
							include('InAppNotifier');
							// eslint-disable-next-line no-undef
							InAppNotifier.showNotification(
								{
									message: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ITEM_ACTION_REMOVE_ERROR_MSGVER_1'),
								},
							);
							this.onUpdateParticipants();
						}
					},
				)
				.catch(
					(err) => {
						Logger.error(err);
					},
				);
		}

		onAddParticipantInBackDrop()
		{
			Logger.log('onAddParticipantInBackDrop');
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
		}

		/**
		 * @desc Changed icon in btn mute ( muteOn or muteOff )
		 * @param {boolean} [isMute]
		 * @void
		 */
		changeMuteBtn(isMute)
		{
			const bttnsArr = this.view.state.buttonElements;
			bttnsArr[2] = this.createMuteBtn(isMute);
			this.updateStateView({ buttonElements: bttnsArr }).catch((er) => Logger.error(er));
		}

		updateBtn()
		{
			const newStateBtn = this.createButtons();
			this.updateStateView({ buttonElements: newStateBtn }).catch((er) => Logger.error(er));
		}

		/**
		 * @desc Handler state view by counter user with check
		 * @param {number} newCount
		 * @void
		 */
		async onChangeUserCounter(newCount)
		{
			const oldCount = this.view.state.dialogData.userCounter;
			if (newCount !== oldCount)
			{
				const data = { dialogData: this.buildDialogData({ userCounter: newCount }) };
				await this.updateStateView(data);

				if (newCount === 1)
				{
					this.setPermissions().then(() => this.updateBtn()).catch((err) => Logger.error(err));
				}

				if (newCount >= 2 && oldCount === 1)
				{
					this.setPermissions().then(() => this.updateBtn()).catch((err) => Logger.error(err));
				}
			}
		}

		/**
		 * @desc Set state in view Sidebar
		 * @param {object} newState
		 * @return {Promise}
		 * @void
		 */
		updateStateView(newState)
		{
			return new Promise(
				(resolve) => {
					this.view.setState(newState, () => {
						resolve();
					});
				},
			);
		}

		/**
		 * @desc Set state in view Sidebar tabs
		 * @param {object} newState
		 * @void
		 */
		updateStateTabView(newState)
		{
			this.tabView.setState(newState);
		}

		/**
		 * @desc Set state in view Sidebar participants tab
		 * @param {object} newState
		 * @void
		 */
		updateStateParticipantsTabView(newState)
		{
			this.tabView.participantsTab.setState(newState);
		}

		/**
		 * @desc Set participants and counter in depends on views
		 * @param {Array<Object>} data
		 * @param {boolean} [forceState=true] - is prop for refresh state in view
		 * @void
		 */
		async setParticipants(data, forceState = true)
		{
			const participants = data.sort((a, b) => b.isAdmin - a.isAdmin || b.isYou - a.isYou);
			this.putParticipantsCache(participants);

			if (!forceState)
			{
				return;
			}

			const newState = { participants, isRefreshing: false };
			if (this.isGroupDialog)
			{
				await this.onChangeUserCounter(participants.length);
			}

			this.updateStateParticipantsTabView(newState);
		}

		/**
		 * @desc put participants data in map cache
		 * @return {object[]} participants
		 */
		putParticipantsCache(participants)
		{
			this.services.participantsMapCache.set('participants', participants);
		}

		/**
		 * @desc Build tabs data by object
		 * @return {object[]}
		 */
		buildTabsData()
		{
			const defaultTabs = [
				{
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_PARTICIPANTS'),
					counter: 0,
					id: 'participants',
				},
				// TODO uncommit this when layouts and scenery are ready
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_TASKS'), counter: 1, id: 'tasks' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_MEETINGS'), counter: 2, id: 'meetings' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_LINKS'), counter: 3, id: '3' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_MEDIA'), counter: 4, id: '4' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_FILES'), counter: 5, id: '5' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_AUDIO'), counter: 6, id: '6' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_SAVE'), counter: 7, id: '7' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_BRIEFS'), counter: 8, id: '8' },
				// { title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_TAB_SIGN'), counter: 9, id: '9' },
			];

			if (this.isNotes)
			{
				return defaultTabs.filter((tab) => tab.id !== 'participants');
			}

			return defaultTabs;
		}

		/**
		 * @desc setting participants data from store ( dialoguesModel/usersModel )
		 * @param {boolean} [forceState=true] - is prop for refresh state in view
		 * @return void
		 */
		async setParticipantsFromStore(forceState = true)
		{
			const dialogState = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogState)
			{
				return;
			}

			let usersData;
			if (this.isGroupDialog)
			{
				usersData = dialogState.participants.map(
					(userId) => this.store.getters['usersModel/getUserById'](userId),
				);
			}
			else
			{
				usersData = [dialogState, this.store.getters['usersModel/getUserById'](MessengerParams.getUserId())];
			}
			const data = await this.buildParticipantsData(usersData);
			await this.setParticipants(data, forceState);
		}

		/**
		 * @desc Build participants data from server by rest call
		 * @return {Promise<object>}
		 */
		async buildFreshParticipantsData()
		{
			const users = await this.restGetParticipantsList();
			const currentUserId = MessengerParams.getUserId();

			let ownerId = currentUserId;
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialogData)
			{
				ownerId = dialogData.owner;
			}

			return Promise.all(users.map(async (user) => {
				return this.prepareUserData(user, currentUserId, ownerId);
			}));
		}

		/**
		 * @desc Build participants data from done list
		 * @param {Array<UserState||undefined>} users
		 * @return {Promise<array>}
		 */
		buildParticipantsData(users)
		{
			if (!users || users.length === 0)
			{
				return Promise.resolve([]);
			}

			const currentUserId = MessengerParams.getUserId();
			let ownerId = currentUserId;
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialogData)
			{
				ownerId = dialogData.owner;
			}

			return Promise.all(users.map(async (user) => {
				return this.prepareUserData(user, currentUserId, ownerId);
			}));
		}

		/**
		 * @desc Get rest call participants list from service
		 * @return {Promise<object>}
		 */
		async restGetParticipantsList()
		{
			return this.services.getParticipantList();
		}

		/**
		 * @desc Returns prepared user-item object for tab listview participants
		 * @param {object} user - users data
		 * @param {number} currentUserId - for check is me
		 * @param {string} ownerId - chat admin id
		 * @return {Promise<object>}
		 */
		prepareUserData(user, currentUserId, ownerId)
		{
			return new Promise((resolve) => {
				const userTitle = this.getTitleDataById(user.id);
				const isYou = currentUserId === user.id;
				const isYouTitle = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_IS_YOU');
				const userAvatar = this.getAvatarDataById(user.id);
				const statusSvg = this.getUserStatus(user.id);
				const isAdmin = this.isGroupDialog ? ownerId === user.id : false;
				const crownStatus = isAdmin ? UserStatus.getStatusCrown() : null;

				resolve({
					id: user.id,
					title: userTitle.title,
					isYouTitle: isYou ? isYouTitle : null,
					desc: userTitle.desc,
					imageUrl: userAvatar.imageUrl,
					imageColor: userAvatar.imageColor,
					statusSvg,
					crownStatus,
					isAdmin,
					isYou,
				});
			});
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
