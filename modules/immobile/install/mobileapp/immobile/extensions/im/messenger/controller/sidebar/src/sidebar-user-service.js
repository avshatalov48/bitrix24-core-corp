/**
 * @module im/messenger/controller/sidebar/sidebar-user-service
 */
jn.define('im/messenger/controller/sidebar/sidebar-user-service', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { core } = require('im/messenger/core');
	const { ChatTitle, ChatAvatar, UserStatus } = require('im/messenger/lib/element');
	const { bookmarkAvatar } = require('im/messenger/assets/common');
	const { Moment } = require('utils/date');
	const { UserUtils } = require('im/messenger/lib/utils');

	/**
	 * @class SidebarUserService
	 */
	class SidebarUserService
	{
		constructor(dialogId, isNotes)
		{
			this.store = core.getStore();
			this.isNotes = isNotes;
			this.dialogId = dialogId;
		}

		/**
		 * @desc Get title and desc data by current dialogId
		 * @param {string} [id=this.dialogId]
		 * @param {boolean} [isNotes=this.isNotes]
		 * @return {object}
		 */
		getTitleDataById(id = this.dialogId, isNotes = this.isNotes)
		{
			if (isNotes)
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

		getStatusCrown()
		{
			return UserStatus.getStatusCrown();
		}

		isBotById(userId)
		{
			const userModelState = this.store.getters['usersModel/getById'](userId);
			if (!userModelState)
			{
				return false;
			}

			return userModelState.bot;
		}
	}

	module.exports = {
		SidebarUserService,
	};
});
