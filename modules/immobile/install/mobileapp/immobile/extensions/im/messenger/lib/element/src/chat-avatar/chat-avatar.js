/**
 * @module im/messenger/lib/element/chat-avatar
 */
jn.define('im/messenger/lib/element/chat-avatar', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogType } = require('im/messenger/const');

	/**
	 * @class ChatAvatar
	 */
	class ChatAvatar
	{
		/**
		 *
		 * @param {string || number} dialogId
		 * @param {object} options
		 * @return {ChatAvatar}
		 */
		static createFromDialogId(dialogId, options = {})
		{
			return new this(dialogId, options);
		}

		constructor(dialogId, options = {})
		{
			this.store = serviceLocator.get('core').getStore();
			this.avatar = null;
			this.color = null;
			this.isSuperEllipseIcon = false;
			this.type = null;

			if (DialogHelper.isDialogId(dialogId))
			{
				this.createDialogAvatar(dialogId);
			}
			else
			{
				this.createUserAvatar(dialogId);
			}
		}

		static getImagePath()
		{
			return `${currentDomain}/bitrix/mobileapp/immobile/extensions/im/messenger/lib/element/src/chat-avatar/images/`;
		}

		createDialogAvatar(dialogId)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			if (!dialog)
			{
				return;
			}
			this.color = dialog.color;

			this.type = dialog.type;

			if ([DialogType.generalChannel, DialogType.openChannel, DialogType.channel].includes(dialog.type))
			{
				this.isSuperEllipseIcon = true;
			}

			if (dialog.chatId === MessengerParams.getGeneralChatId())
			{
				this.avatar = `${ChatAvatar.getImagePath()}avatar_general_chat.png`;

				return;
			}

			if (dialog.type === DialogType.generalChannel)
			{
				this.avatar = `${ChatAvatar.getImagePath()}avatar_general_channel.png`;

				return;
			}

			if (dialog.entityType === 'SUPPORT24_QUESTION')
			{
				this.avatar = `${ChatAvatar.getImagePath()}avatar_support_24.png`;

				return;
			}

			if (dialog.type === DialogType.copilot)
			{
				this.avatar = this.getCopilotRoleAvatar(dialog.dialogId) || `${ChatAvatar.getImagePath()}avatar_copilot_assistant.png`;

				return;
			}

			this.avatar = dialog.avatar;
		}

		createUserAvatar(userId)
		{
			const user = this.store.getters['usersModel/getById'](userId);
			if (!user)
			{
				return;
			}

			if (this.isUser(userId) && !user.lastActivityDate && !user.avatar)
			{
				this.avatar = `${ChatAvatar.getImagePath()}avatar_wait_air.png`;
				this.color = user.color;

				return;
			}

			this.avatar = user.avatar;
			this.color = user.color;
		}

		/**
		 * @return {ChatAvatarTitleParams}
		 */
		getTitleParams()
		{
			if (this.type === DialogType.comment)
			{
				return {};
			}

			const titleParams = {
				useLetterImage: true,
				isSuperEllipseIcon: this.isSuperEllipseIcon,
			};

			if (this.avatar)
			{
				titleParams.imageUrl = this.avatar;
			}

			if (this.color && this.avatar === '')
			{
				titleParams.imageColor = this.color;
			}

			return titleParams;
		}

		/**
		 *
		 * @return {string | null}
		 */
		getAvatarUrl()
		{
			return this.avatar;
		}

		/**
		 *
		 * @return {string | null}
		 */
		getColor()
		{
			return this.color;
		}

		getIsSuperEllipseIcon()
		{
			return this.isSuperEllipseIcon;
		}

		isUser(userId)
		{
			const user = this.store.getters['usersModel/getById'](userId);

			return !user.bot && !user.network && !user.connector;
		}

		/**
		 * @desc get name copilot role
		 * @param {string} dialogId
		 * @return {string|null}
		 * @private
		 */
		getCopilotRoleAvatar(dialogId)
		{
			const copilotMainRole = this.store.getters['dialoguesModel/copilotModel/getMainRoleByDialogId'](dialogId);

			return copilotMainRole?.avatar?.small ? encodeURI(copilotMainRole?.avatar?.small) : null;
		}
	}

	module.exports = {
		ChatAvatar,
	};
});
