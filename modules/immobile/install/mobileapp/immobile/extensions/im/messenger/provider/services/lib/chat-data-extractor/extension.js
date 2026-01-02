/* eslint-disable es/no-nullish-coalescing-operators */

/**
 * @module im/messenger/provider/services/lib/chat-data-extractor
 */
jn.define('im/messenger/provider/services/lib/chat-data-extractor', (require, exports, module) => {
	const { Type } = require('type');

	const { UserManager } = require('im/messenger/lib/user-manager');
	const { DialogType } = require('im/messenger/const');
	const { CounterHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class ChatDataExtractor
	 */
	class ChatDataExtractor
	{
		restResult;

		constructor(restResult)
		{
			this.restResult = restResult;

			// im.v2.Chat.Message.tail and im.v2.Chat.load does not guarantee that the messages in the batch will be sorted
			// but this is important for forming a linked list
			if (Type.isObject(this.restResult) && Type.isArrayFilled(this.restResult.messages))
			{
				this.restResult.messages.sort((a, b) => a.id - b.id);
			}
		}

		getChatId()
		{
			return this.restResult.chat.id;
		}

		getDialogId()
		{
			return this.restResult.chat.dialogId;
		}

		getMessagesAutoDeleteDelay()
		{
			return this.restResult.messagesAutoDeleteConfigs?.[0]?.delay ?? 0;
		}

		isOpenlinesChat()
		{
			return this.restResult.chat.type === DialogType.lines;
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
				messagesAutoDeleteDelay: this.getMessagesAutoDeleteDelay(),
				recentConfig: this.getRecentConfig(),
				tariffRestrictions: this.getTariffRestrictions(),
				hasPrevPage: this.restResult.hasPrevPage,
				hasNextPage: this.restResult.hasNextPage,
			};

			const storedDialogData = serviceLocator.get('core').getStore()
				.getters['dialoguesModel/getById'](mainChat.dialogId)
			;

			if (storedDialogData && storedDialogData.lastReadId > mainChat.lastId)
			{
				delete mainChat.counter;
			}

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

		getTariffRestrictions()
		{
			return this.restResult?.tariffRestrictions;
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

		getCommentInfo()
		{
			return this.restResult.commentInfo ?? [];
		}

		getCollabInfo()
		{
			return {
				dialogId: this.getMainChat()?.dialogId ?? 0,
				...this.restResult.collabInfo,
			};
		}

		getReactions()
		{
			if (!Type.isArrayFilled(this.restResult.reactions))
			{
				return [];
			}

			const dialogId = this.getMainChat()?.dialogId ?? 0;

			return this.restResult.reactions.map((reaction) => {
				// eslint-disable-next-line no-param-reassign
				reaction.dialogId = dialogId;

				return reaction;
			});
		}

		getCopilot()
		{
			return this.restResult.copilot ?? {};
		}

		getRecentConfig()
		{
			return this.restResult.recentConfig ?? null;
		}

		/**
		 * @return {Array<CounterModelState>}
		 */
		getCounterState()
		{
			const chat = this.getMainChat();

			const storedDialogData = serviceLocator.get('core').getStore()
				.getters['dialoguesModel/getById'](chat.dialogId)
			;

			if (storedDialogData && storedDialogData.lastReadId > chat.lastId)
			{
				return [];
			}

			return [{
				chatId: chat.id,
				counter: chat.counter,
				type: CounterHelper.getCounterTypeByDialogType(chat.type),
				disabled: CounterHelper.getDisabledByMuteList(chat.muteList ?? chat.mute_list),
				parentChatId: chat.parentChatId ?? 0,
			}];
		}
	}

	module.exports = {
		ChatDataExtractor,
	};
});
