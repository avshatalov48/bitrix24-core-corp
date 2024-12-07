/**
 * @module im/messenger/controller/sidebar/chat/sidebar-user-service
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-user-service', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ChatTitle, ChatAvatar, UserStatus } = require('im/messenger/lib/element');
	const { bookmarkAvatar } = require('im/messenger/assets/common');
	const { Moment } = require('utils/date');
	const { UserUtils } = require('im/messenger/lib/utils');
	const { BotCode, DialogType } = require('im/messenger/const');

	/**
	 * @class SidebarUserService
	 */
	class SidebarUserService
	{
		constructor(dialogId, isNotes)
		{
			this.store = serviceLocator.get('core')
				.getStore();
			this.isNotes = isNotes;
			this.dialogId = dialogId;
		}

		/**
		 * @desc Get title and desc data by current dialogId
		 * @param {string} [id=this.dialogId]
		 * @param {boolean} [isCopilot=false]
		 * @param {boolean} [isNotes=this.isNotes]
		 * @return {object}
		 */
		getTitleDataById(id = this.dialogId, isCopilot = false, isNotes = this.isNotes)
		{
			if (isNotes)
			{
				return {
					title: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PROFILE_TITLE_NOTES'),
					desc: null,
				};
			}

			const chatTitle = ChatTitle.createFromDialogId(id);
			const titleData = {
				title: chatTitle.getTitle(),
				desc: chatTitle.getDescription(),
			};

			if (isCopilot)
			{
				const dialogModelState = this.store.getters['dialoguesModel/copilotModel/getByDialogId'](this.dialogId);
				chatTitle.description = Type.isStringFilled(dialogModelState?.aiProvider)
					? dialogModelState?.aiProvider : chatTitle.description;
				titleData.title = chatTitle.getTitle();
				titleData.desc = chatTitle.getDescription();

				return titleData;
			}

			const dialogModelState = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (dialogModelState && dialogModelState.type === DialogType.comment)
			{
				chatTitle.description = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_DESC_CHANNEL');
				titleData.desc = chatTitle.getDescription();

				const parentDialog = this.store.getters['dialoguesModel/getByChatId'](dialogModelState.parentChatId);
				titleData.subDesc = parentDialog?.name ?? '';
			}

			return titleData;
		}

		/**
		 * @desc Get avatar data by current dialogId ( url or color )
		 * @param {string} [id=this.dialogId]
		 * @param {boolean} [isNotes=this.isNotes]
		 * @return {object}
		 */
		getAvatarDataById(id = this.dialogId, isNotes = this.isNotes)
		{
			if (isNotes)
			{
				return {
					svg: {
						content: bookmarkAvatar(),
					},
				};
			}

			if (this.isCopilotBotById(id))
			{
				const copilotModelState = this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](this.dialogId);
				if (copilotModelState?.avatar?.small)
				{
					const url = copilotModelState?.avatar?.small || copilotModelState?.avatar?.medium;

					return { imageUrl: encodeURI(url) };
				}
			}

			if (this.isCopilotDialogById(id))
			{
				const copilotModelState = this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](this.dialogId);
				if (copilotModelState?.avatar?.large || copilotModelState?.avatar?.medium)
				{
					const url = copilotModelState?.avatar?.large || copilotModelState?.avatar?.medium;

					return { imageUrl: encodeURI(url) };
				}
			}

			const chatAvatar = ChatAvatar.createFromDialogId(id);

			return chatAvatar.getTitleParams();
		}

		/**
		 * @desc Get svg string for content image by dialog/user id
		 * @param {string} [id=this.dialogId]
		 * @return {string}
		 */
		getUserStatus(id = this.dialogId)
		{
			// const userData = this.store.getters['usersModel/getById'](id);
			// const isOnline = this.isUserOnline(userData.lastActivityDate); // TODO this experimental solution, may be disabled
			return this.getUserStatusUrlById(id);
		}

		/**
		 * @desc Returns is online by hold in 10 seconds
		 * @param {string} lastActivity
		 * @return {object}
		 */
		isUserOnline(lastActivity)
		{
			if (Type.isUndefined(lastActivity) || Type.isNull(lastActivity))
			{
				return false;
			}

			return (new UserUtils()).isOnline(new Moment(lastActivity));
		}

		/**
		 * @desc Get svg string for content image by dialog/user id
		 * @param {string} [id=this.dialogId]
		 * @return {string}
		 */
		getUserStatusUrlById(id = this.dialogId)
		{
			return UserStatus.getStatusByUserId(id, false);
		}

		/**
		 * @desc Get svg status
		 * @param {boolean} isAdmin
		 * @return {string}
		 */
		getStatusCrown(isAdmin)
		{
			if (isAdmin)
			{
				return UserStatus.getStatusCrown();
			}

			return UserStatus.getStatusGreenCrown();
		}

		/**
		 * @desc check is bot user by id
		 * @param {number} userId
		 * @return {boolean}
		 */
		isBotById(userId)
		{
			const userModelState = this.store.getters['usersModel/getById'](userId);
			if (!userModelState)
			{
				return false;
			}

			return userModelState.bot;
		}

		/**
		 * @desc check is bot copilot user by id
		 * @param {number} userId
		 * @return {boolean}
		 */
		isCopilotBotById(userId)
		{
			const userModelState = this.store.getters['usersModel/getById'](userId);
			if (!userModelState)
			{
				return false;
			}

			return userModelState.bot && userModelState.botData && userModelState.botData.code === BotCode.copilot;
		}

		/**
		 * @desc check is copilot dialog by id
		 * @param {number|string} dialogId
		 * @return {boolean}
		 */
		isCopilotDialogById(dialogId = this.dialogId)
		{
			const dialogState = this.store.getters['dialoguesModel/getById'](dialogId);
			if (dialogState)
			{
				return dialogState.type === DialogType.copilot;
			}

			return false;
		}
	}

	module.exports = {
		SidebarUserService,
	};
});
