/**
 * @module im/messenger/provider/pull/lib/recent/base/message-manager
 */
jn.define('im/messenger/provider/pull/lib/recent/base/message-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType, MessageStatus } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RecentConverter } = require('im/messenger/lib/converter');
	/* global ChatMessengerCommon */

	/**
	 * @class BaseRecentMessageManager
	 */
	class BaseRecentMessageManager
	{
		/** @type {MessageAddParams} */
		params;
		/**  @type {PullExtraParams} */
		extra;

		constructor(params, extra = {})
		{
			this.params = params;
			this.extra = extra;

			this.pureMessageText = null;
		}

		/**
		 * @abstract
		 * @return {boolean}
		 */
		needToSkipMessageEvent()
		{
			throw new Error('needToSkipMessageEvent not implemented');
		}

		async updateRecent()
		{
			if (this.needToSkipMessageEvent())
			{
				return;
			}

			const message = this.prepareRecentMessage();

			const recentItem = RecentConverter.fromPushToModel({
				id: this.getDialogId(),
				chat: this.getChat(),
				user: this.getSender(),
				lines: this.params.lines,
				counter: this.params.counter,
				liked: false,
				lastActivityDate: this.params.dateLastActivity,
				message,
			});

			const store = serviceLocator.get('core').getStore();

			await store.dispatch('recentModel/set', [recentItem]);
		}

		prepareRecentMessage()
		{
			const messageStatus = this.getSenderId === this.getCurrentUserId()
				? MessageStatus.received
				: ''
			;

			return {
				...this.getMessage(),
				status: messageStatus,
			};
		}

		getMessageText()
		{
			return ChatMessengerCommon.purifyText(
				this.getMessage().text,
				this.getMessage().params,
			);
		}

		getMessage()
		{
			return this.params.message;
		}

		getChatId()
		{
			return this.params.chatId;
		}

		getParentChatId()
		{
			return this.getChat()?.parent_chat_id || 0;
		}

		getChat()
		{
			const chatId = this.getChatId();

			return this.params.chat?.[chatId];
		}

		getChatType()
		{
			const chat = this.getChat();

			return chat?.type ?? '';
		}

		getDialogId()
		{
			return this.params.dialogId;
		}

		getSender()
		{
			const senderId = this.getSenderId();

			return this.params.users?.[senderId] ?? { id: 0 };
		}

		/**
		 * @return {number}
		 */
		getSenderId()
		{
			return this.getMessage().senderId;
		}

		getCurrentUserId()
		{
			return serviceLocator.get('core').getUserId();
		}

		isLinesChat()
		{
			return Boolean(this.params.lines);
		}

		isCommentChat()
		{
			return this.getChatType() === DialogType.comment;
		}

		isChannelChat()
		{
			return [DialogType.channel, DialogType.openChannel, DialogType.generalChannel].includes(this.getChatType());
		}

		isOpenChannelChat()
		{
			return this.getChatType() === DialogType.openChannel;
		}

		isCopilotChat()
		{
			return this.getChatType() === DialogType.copilot;
		}

		isCollabChat()
		{
			return this.getChatType() === DialogType.collab;
		}

		isUserInChat()
		{
			if (Type.isArray(this.params.userInChat))
			{
				return true;
			}

			const chatUsers = this.params.userInChat[this.getChatId()];

			return chatUsers.includes(this.getCurrentUserId());
		}

		/**
		 * it is necessary to check the pull shared events that came by tags
		 * (open channels without current user in channel tab and comment chats in channel dialog)
		 *
		 * @see ChannelMessenger.extendWatch
		 * @see PullWatchManager.subscribe
		 * @return {boolean}
		 */
		isSharedEvent()
		{
			return this.extra.is_shared_event === true;
		}

		isCurrentChannelChatOpened()
		{
			return serviceLocator.get('core').getStore().getters['applicationModel/isDialogOpen'](this.getDialogId());
		}

		isChannelListEvent()
		{
			return this.isChannelChat() && this.isSharedEvent();
		}
	}

	module.exports = { BaseRecentMessageManager };
});
