/* eslint-disable es/no-nullish-coalescing-operators */

/**
 * @module im/messenger/provider/service/classes/chat-data-extractor
 */
jn.define('im/messenger/provider/service/classes/chat-data-extractor', (require, exports, module) => {
	const { UserManager } = require('im/messenger/lib/user-manager');

	/**
	 * @class ChatDataExtractor
	 */
	class ChatDataExtractor
	{
		restResult;

		constructor(restResult)
		{
			this.restResult = restResult;
		}

		getChatId()
		{
			return this.restResult.chat.id;
		}

		getDialogId()
		{
			return this.restResult.chat.dialogId;
		}

		isOpenlinesChat()
		{
			return this.restResult.chat.type === ChatType.lines;
		}

		getMainChat()
		{
			return {
				...this.restResult.chat,
				hasPrevPage: this.restResult.hasPrevPage,
				hasNextPage: this.restResult.hasNextPage,
			};
		}

		getChats()
		{
			const mainChat = {
				...this.restResult.chat,
				hasPrevPage: this.restResult.hasPrevPage,
				hasNextPage: this.restResult.hasNextPage,
			};
			const chats = {
				[this.restResult.chat.dialogId]: mainChat,
			};
			this.restResult.users.forEach((user) => {
				if (chats[user.id])
				{
					chats[user.id] = { ...chats[user.id], ...UserManager.getDialogForUser(user) };
				}
				else
				{
					chats[user.id] = UserManager.getDialogForUser(user);
				}
			});

			return Object.values(chats);
		}

		getFiles()
		{
			return this.restResult.files ?? [];
		}

		getUsers()
		{
			return this.restResult.users ?? [];
		}

		getAdditionalUsers()
		{
			return this.restResult.usersShort ?? [];
		}

		getMessages()
		{
			return this.restResult.messages ?? [];
		}

		/**
		 * @return {Array<RawMessage>}
		 */
		getMessagesToStore()
		{
			return this.restResult.additionalMessages ?? [];
		}

		/**
		 *
		 * @return {Array<RawPin>}
		 */
		getPins()
		{
			return this.restResult.pins ?? [];
		}

		getPinnedMessages()
		{
			const result = [];
			this.getPins().forEach((pin) => {
				const pinnedMessage = this.getMessagesToStore()
					.find((message) => message.id === pin.messageId)
				;

				if (pinnedMessage)
				{
					result.push(pinnedMessage);
				}
			});

			return result;
		}

		getPinnedMessageIds()
		{
			const pinnedMessageIds = [];
			const pins = this.restResult.pins ?? [];
			pins.forEach((pin) => {
				pinnedMessageIds.push(pin.messageId);
			});

			return pinnedMessageIds;
		}

		getReactions()
		{
			return this.restResult.reactions ?? [];
		}
	}

	module.exports = {
		ChatDataExtractor,
	};
});
