/**
 * @module im/messenger/controller/sidebar/chat/tabs/participants/participants-service
 */
jn.define('im/messenger/controller/sidebar/chat/tabs/participants/participants-service', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--participants-service');
	const { MapCache } = require('im/messenger/cache');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { SidebarUserService } = require('im/messenger/controller/sidebar/chat/sidebar-user-service');
	const { SidebarRestService } = require('im/messenger/controller/sidebar/chat/sidebar-rest-service');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, DialogType, BBCode, ComponentCode } = require('im/messenger/const');

	/**
	 * @class ParticipantsService
	 */
	class ParticipantsService
	{
		/**
		 * @constructor
		 * @param {SidebarParticipantsViewProps} props
		 */
		constructor(props)
		{
			this.core = serviceLocator.get('core');
			this.store = this.core.getStore();
			this.dialogId = props.dialogId;
			this.isGroupDialog = DialogHelper.isDialogId(this.dialogId);
			this.sidebarUserService = new SidebarUserService(this.dialogId);
			this.sidebarRestService = new SidebarRestService(this.dialogId);
			this.participantsCache = new MapCache(35000);

			this.onClickLeaveChat = this.onClickLeaveChat.bind(this);
			this.onClickAddManager = this.onClickAddManager.bind(this);
			this.onClickRemoveManager = this.onClickRemoveManager.bind(this);
			this.onClickPingUser = this.onClickPingUser.bind(this);
		}

		/**
		 * @desc Get participants from a store or rest query
		 * @return {array}
		 */
		getParticipants()
		{
			const participants = this.getParticipantsFromStore();
			if (participants.length === 0)
			{
				this.getRestParticipantsData();
			}

			return participants;
		}

		/**
		 * @desc Set participants data from store ( dialoguesModel/usersModel )
		 * @return Array<Object>
		 */
		getParticipantsFromStore()
		{
			const dialogState = this.store.getters['dialoguesModel/getById'](this.dialogId);

			if (!dialogState && !Type.isArray(dialogState.participants))
			{
				return [];
			}

			// @see bugfix 0202206
			// if (dialogState.participants.length !== dialogState.userCounter)
			// {
			// 	return [];
			// }

			let usersData = [];
			if (this.isGroupDialog)
			{
				if (dialogState.lastLoadParticipantId === 0)
				{
					return [];
				}

				usersData = dialogState.participants.map(
					(userId) => this.store.getters['usersModel/getById'](userId),
				);

				for (const user of usersData)
				{
					if (Type.isUndefined(user))
					{
						this.store.dispatch('dialoguesModel/update', {
							dialogId: this.dialogId,
							fields: {
								lastLoadParticipantId: 0,
								participants: [],
							},
						});

						return [];
					}
				}
			}
			else
			{
				usersData = [dialogState, this.store.getters['usersModel/getById'](MessengerParams.getUserId())];
			}

			if (Type.isArray(usersData) && usersData.length === 0)
			{
				return [];
			}

			const data = this.buildParticipantsData(usersData);

			return this.setParticipants(data);
		}

		/**
		 * @desc Call rest method 'RestMethod.imDialogUsersList'
		 * @return void
		 */
		getRestParticipantsData()
		{
			this.sidebarRestService.getParticipantList().catch((r) => logger.error(r));
		}

		/**
		 * @desc Build participants data from done list
		 * @param {Array<UserState||undefined>} users
		 * @return {Object[]}
		 */
		buildParticipantsData(users)
		{
			if (!users || users.length === 0)
			{
				return [];
			}

			const currentUserId = MessengerParams.getUserId();
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const youTitle = this.getYouTitle();

			return users.map((user) => {
				return this.prepareUserData(user, currentUserId, dialogData, youTitle);
			});
		}

		/**
		 * @desc Returns prepared user-item object for tab listview participants
		 * @param {object} user - users data
		 * @param {number} currentUserId - for check is me
		 * @param {?DialoguesModelState} dialogData
		 * @param {string} youTitle
		 * @return {object}
		 */
		prepareUserData(user, currentUserId, dialogData, youTitle)
		{
			const ownerId = Type.isNumber(dialogData?.owner) ? dialogData?.owner : currentUserId;
			const isCopilot = this.sidebarUserService.isCopilotBotById(user.id);
			const userTitle = this.sidebarUserService.getTitleDataById(user.id, isCopilot);
			const isYou = currentUserId === user.id;
			const userAvatar = this.sidebarUserService.getAvatarDataById(user.id);
			const statusSvg = this.sidebarUserService.getUserStatus(user.id);
			const isAdmin = this.isGroupDialog ? ownerId === user.id : false;
			const isManager = dialogData?.managerList.includes(user.id);
			const crownStatus = (isAdmin || isManager) ? this.sidebarUserService.getStatusCrown(isAdmin) : null;
			let userId = user.id;
			if (Type.isUndefined(userId) && user.type === DialogType.user)
			{
				userId = parseInt(user.dialogId, 10);
			}

			return {
				id: userId,
				title: userTitle.title,
				isYouTitle: isYou ? youTitle : null,
				desc: userTitle.desc,
				imageUrl: userAvatar.imageUrl,
				imageColor: userAvatar.imageColor,
				statusSvg,
				crownStatus,
				isAdmin,
				isYou,
				isCopilot,
				isManager,
				isSuperEllipseAvatar: this.isSuperEllipseAvatar(),
			};
		}

		/**
		 * @return {string}
		 */
		getYouTitle()
		{
			return Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_IS_YOU');
		}

		/**
		 * @desc Set participants in depends on views
		 * @param {Array<Object>} data
		 * @return Array<Object>
		 */
		setParticipants(data)
		{
			const participants = data.sort((a, b) => b.isAdmin - a.isAdmin || b.isYou - a.isYou);
			this.putParticipantsCache(participants);

			return participants;
		}

		/**
		 * @desc put participants data in map cache
		 * @return {object[]} participants
		 */
		putParticipantsCache(participants)
		{
			this.participantsCache.set('participants', participants);
		}

		/**
		 * @desc Delete participants with rest call
		 * @param {number} userId
		 * @return Promise
		 */
		deleteParticipant(userId)
		{
			return this.sidebarRestService.deleteParticipant(userId);
		}

		/**
		 * @desc Get user counter from dialog store
		 * @return {number} userCounter
		 */
		getUserCounter()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);

			return dialogData.userCounter;
		}

		/**
		 * @desc check is bot user by id
		 * @param {number} userId
		 * @return {boolean}
		 */
		isBotById(userId)
		{
			return this.sidebarUserService.isBotById(userId);
		}

		/**
		 * @desc Handler on click open notes user from participants menu
		 * @return void
		 */
		onClickGetNotes()
		{
			MessengerEmitter.emit(
				EventType.messenger.openDialog,
				{ dialogId: MessengerParams.getUserId() },
				ComponentCode.imMessenger,
			);
		}

		/**
		 * @desc Handler on click leave chat from participants menu
		 * @return Promise
		 */
		onClickLeaveChat()
		{
			return this.sidebarRestService.leaveChat()
				.then(
					(result) => {
						if (result)
						{
							try
							{
								PageManager.getNavigator().popTo('im.tabs')
									// eslint-disable-next-line promise/no-nesting
									.catch((err) => {
										logger.error(`${this.constructor.name}.onClickLeaveChat.popTo.catch error`, err);
										BX.onCustomEvent(EventType.sidebar.destroy);
										MessengerEmitter.emit(EventType.messenger.destroyDialog);
									});
							}
							catch (e)
							{
								logger.error(`${this.constructor.name}.onClickLeaveChat.getNavigator()`, e);
								BX.onCustomEvent(EventType.sidebar.destroy);
								MessengerEmitter.emit(EventType.messenger.destroyDialog);
							}
						}
					},
				);
		}

		/**
		 * @desc Handler on click send user from participants menu
		 * @param {number} userId
		 * @return void
		 */
		onClickSendMessage(userId)
		{
			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: userId }, ComponentCode.imMessenger);
		}

		/**
		 * @desc Handler on click mention user from participants menu
		 * @param {number|string} userId
		 * @return void
		 */
		onClickPingUser(userId)
		{
			const dialogCode = serviceLocator.get(this.dialogId)?.dialogCode;
			try
			{
				PageManager.getNavigator().popTo(dialogCode)
					.then(() => {
						BX.onCustomEvent('onDestroySidebar');
						BX.onCustomEvent(EventType.dialog.external.mention, [userId, BBCode.user, this.dialogId]);
					})
					.catch((err) => {
						logger.error('ParticipantsService.onClickPingUser.popTo.catch error', err);
					});
			}
			catch (e)
			{
				logger.error(`${this.constructor.name}.onClickPingUser.getNavigator()`, e);
			}
		}

		isSuperEllipseAvatar()
		{
			return false;
		}

		/**
		 * @desc Handler add manager
		 * @param {number} userId
		 * @void
		 * @private
		 */
		onClickAddManager(userId)
		{
			logger.log(`${this.constructor.name}.onClickAddManager.userId:`, userId);
			this.sidebarRestService.addManager(userId)
				.catch((error) => logger.log(`${this.constructor.name}.sidebarRestService.addManager.catch:`, error));
		}

		/**
		 * @desc Handler remove manager
		 * @param {number} userId
		 * @void
		 * @private
		 */
		onClickRemoveManager(userId)
		{
			logger.log(`${this.constructor.name}.onClickRemoveManager.userId:`, userId);
			this.sidebarRestService.removeManager(userId)
				.catch((error) => logger.log(`${this.constructor.name}.sidebarRestService.removeManager.catch:`, error));
		}
	}

	module.exports = {
		ParticipantsService,
	};
});
