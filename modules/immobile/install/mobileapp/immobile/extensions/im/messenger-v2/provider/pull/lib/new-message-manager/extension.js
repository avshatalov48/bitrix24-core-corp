/**
 * @module im/messenger-v2/provider/pull/lib/new-message-manager
 */
jn.define('im/messenger-v2/provider/pull/lib/new-message-manager', (require, exports, module) => {
	/* global ChatMessengerCommon */
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogType, MessageStatus } = require('im/messenger/const');
	const { RecentDataConverter } = require('im/messenger/lib/converter/data/recent');

	/**
	 * @class NewMessageManager
	 */
	class NewMessageManager
	{
		/** @type {MessageAddParams} */
		#params;
		/**  @type {PullExtraParams} */
		#extra;

		constructor(params, extra = {})
		{
			this.#params = params;
			this.#extra = extra;
		}

		/**
		 * @return {Array<string>|null}
		 */
		getRecentTabs()
		{
			return this.#params.recentConfig?.sections;
		}

		/**
		 * @return {number}
		 */
		getChatId()
		{
			return this.#params.chatId;
		}

		/**
		 * @return {string}
		 */
		getDialogId()
		{
			return this.#params.dialogId;
		}

		/**
		 * @return {number}
		 */
		getParentChatId()
		{
			return this.getChat()?.parent_chat_id || 0;
		}

		/**
		 * @return {object}
		 */
		getChat()
		{
			const chatId = this.getChatId();

			return this.#params.chat?.[chatId];
		}

		/**
		 * @return {string}
		 */
		getChatType()
		{
			const chat = this.getChat();

			return chat?.type ?? '';
		}

		/**
		 * @return {object} // todo check types
		 */
		getMessage()
		{
			return this.#params.message;
		}

		/**
		 * @return {string}
		 */
		getPurifyMessageText()
		{
			return ChatMessengerCommon.purifyText(
				this.getMessage().text,
				this.getMessage().params,
			);
		}

		/**
		 * @return {number}
		 */
		getSenderId()
		{
			return this.getMessage().senderId;
		}

		/**
		 * @return {{id: number}}
		 */
		getSender()
		{
			const senderId = this.getSenderId();

			return this.#params.users?.[senderId] ?? { id: 0 };
		}

		/**
		 * @return {number}
		 */
		getCurrentUserId()
		{
			return MessengerParams.getUserId();
		}

		/**
		 * @return {RecentModelState}
		 */
		getPreparedRecentItem()
		{
			const message = this.getPreparedRecentMessage();

			return RecentDataConverter.fromPullToModel({
				id: this.getDialogId(),
				chat: this.getChat(),
				user: this.getSender(),
				lines: this.#params.lines,
				counter: this.#params.counter,
				liked: false,
				lastActivityDate: this.#params.dateLastActivity,
				message,
			});
		}

		/**
		 * @return {RecentModelState}
		 */
		getPreparedRecentItemByUserInvite()
		{
			return RecentDataConverter.fromPullUserInviteToModel(this.#params);
		}

		/**
		 * @return {RecentModelState}
		 */
		getPreparedRecentItemByUserUpdate()
		{
			return RecentDataConverter.fromPullUserUpdateToModel(this.#params);
		}

		getPreparedRecentMessage()
		{
			const messageStatus = this.getSenderId() === this.getCurrentUserId()
				? MessageStatus.received
				: ''
			;

			return {
				...this.getMessage(),
				status: messageStatus,
			};
		}

		/**
		 * @return {boolean}
		 */
		needToSkipMessageEvent()
		{
			return this.isLinesChat() || this.isCommentChat();
		}

		/**
		 * @return {boolean}
		 */
		isLinesChat()
		{
			return Boolean(this.#params.lines);
		}

		/**
		 * @return {boolean}
		 */
		isCommentChat()
		{
			return this.getChatType() === DialogType.comment;
		}

		/**
		 * @return {boolean}
		 */
		isChannelChat()
		{
			return [
				DialogType.generalChannel,
				DialogType.openChannel,
				DialogType.channel,
			].includes(this.getChatType());
		}

		/**
		 * @return {boolean}
		 */
		isCopilotChat()
		{
			return this.getChatType() === DialogType.copilot;
		}

		/**
		 * @return {boolean}
		 */
		isChannelListEvent()
		{
			return this.isChannelChat() && this.#extra.is_shared_event;
		}
	}

	module.exports = { NewMessageManager };
});
