/* eslint-disable es/no-optional-chaining */
/* eslint-disable no-await-in-loop */

/**
 * @module im/messenger/provider/service/classes/message-context-creator
 */
jn.define('im/messenger/provider/service/classes/message-context-creator', (require, exports, module) => {
	const { Type } = require('type');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('message-context-creator');

	/**
	 * @class MessageContextCreator
	 */
	class MessageContextCreator
	{
		constructor()
		{
			/**
			 * @type {DialogRepository}
			 */
			this.dialogRepository = serviceLocator.get('core').getRepository().dialog;
			/**
			 * @type {MessageRepository}
			 */
			this.messageRepository = serviceLocator.get('core').getRepository().message;
		}

		async createMessageDoublyLinkedListForDialog(dialog, messageList)
		{
			if (!Type.isArrayFilled(messageList))
			{
				return messageList;
			}

			logger.info('MessageContextCreator.createMessageDoublyLinkedListForDialog: ', messageList);

			const messageLinkedList = messageList.map((message, index, list) => {
				const linkedMessage = message;
				const isFirstMessage = index === 0;
				if (isFirstMessage)
				{
					if (list.length > 1)
					{
						const nextMessage = list[index + 1];
						linkedMessage.nextId = nextMessage.id;
					}

					return linkedMessage;
				}

				const previousMessage = list[index - 1];
				const isLastMessage = index === list.length - 1;
				if (isLastMessage)
				{
					linkedMessage.previousId = previousMessage.id;

					return linkedMessage;
				}

				const nextMessage = list[index + 1];
				linkedMessage.previousId = previousMessage.id;
				linkedMessage.nextId = nextMessage.id;

				return linkedMessage;
			});

			// because the first message in list has no previousId
			// but for the very first chat message this is how it should be
			if (dialog.hasPrevPage)
			{
				messageLinkedList.shift();
			}

			logger.info('MessageContextCreator.createMessageDoublyLinkedListForDialog: messageLinkedList: ', messageLinkedList);

			return messageLinkedList;
		}

		async createMessageLinkedListForSyncResult(messageList, dialogIds)
		{
			if (!Type.isArrayFilled(messageList))
			{
				return messageList;
			}

			logger.info('MessageContextCreator.createMessageLinkedListForSyncResult: ', messageList, dialogIds);

			const messageListWithDialogIds = this.#fillMessagesWithDialogId(messageList, dialogIds);
			const messagesByChats = this.#splitMessagesByChats(messageListWithDialogIds);
			const messageLinkedListByChats = await this.#createMessageLinkedListByChats(messagesByChats);
			const messageLinkedList = this.#joinMessagesByChatsToList(messageLinkedListByChats);

			logger.info('MessageContextCreator.createMessageLinkedListForSyncResult: messageLinkedList: ', messageLinkedList);

			return messageLinkedList;
		}

		#fillMessagesWithDialogId(messageList, dialogIds)
		{
			const messageListWithDialogIds = messageList.map((message) => {
				const messageWithDialogId = message;
				messageWithDialogId.dialogId = dialogIds[message.chat_id];

				return messageWithDialogId;
			});

			logger.log('MessageContextCreator.#fillMessagesWithDialogId: result: ', messageListWithDialogIds);

			return messageListWithDialogIds;
		}

		#splitMessagesByChats(messageList)
		{
			const chatMessageCollection = {};
			messageList.forEach((message) => {
				if (!chatMessageCollection[message.dialogId])
				{
					chatMessageCollection[message.dialogId] = [];
				}

				chatMessageCollection[message.dialogId].push(message);
			});

			logger.log('MessageContextCreator.#splitMessagesByChats: result: ', chatMessageCollection);

			return chatMessageCollection;
		}

		/**
		 * @param {Map<string, Array<object>>} messagesByChats
		 */
		async #createMessageLinkedListByChats(messagesByChats)
		{
			const dialogIdList = Object.keys(messagesByChats);
			const dialogList = await this.dialogRepository.getWasCompletelySyncByIdList(dialogIdList);
			const dialogCollection = {};
			dialogList.items.forEach((dialog) => {
				dialogCollection[dialog.dialogId] = dialog;
			});

			const messageListByChats = {};
			const firstMessageIdCollection = {};
			Object.entries(messagesByChats).forEach(([dialogId, messageList]) => {
				messageListByChats[dialogId] = messageList.sort((a, b) => a.id - b.id);

				const firstMessage = messageListByChats[dialogId][0];
				firstMessageIdCollection[firstMessage.id] = true;
			});

			for (const dialogId of dialogIdList)
			{
				for (const [index, message] of Object.entries(messageListByChats[dialogId]))
				{
					const chatMessageList = messageListByChats[dialogId];
					const messageIndex = Number(index);
					messageListByChats[dialogId][messageIndex] = await this.#addLinkedFieldsToMessage(
						dialogCollection,
						message,
						messageIndex,
						chatMessageList,
					);
				}
			}

			logger.log('MessageContextCreator.#createMessageLinkedListByChats: result: ', messageListByChats);

			return messageListByChats;
		}

		#joinMessagesByChatsToList(messageLinkedListByChats)
		{
			const messageList = [];
			Object.entries(messageLinkedListByChats).forEach(([dialogId, messageLinkedList]) => {
				messageList.push(...messageLinkedList);
			});

			logger.log('MessageContextCreator.#joinMessagesByChatsToList: result: ', messageList);

			return messageList;
		}

		async #addLinkedFieldsToMessage(dialogCollection, message, messageIndex, list)
		{
			const newMessage = message;
			const messageDialog = dialogCollection[message.dialogId];

			const isTopMessage = messageIndex === 0;
			let wasMessageDialogCompletelySync = false;
			if (messageDialog)
			{
				wasMessageDialogCompletelySync = messageDialog.wasCompletelySync;
			}

			if (isTopMessage && wasMessageDialogCompletelySync)
			{
				const isNewMessage = messageDialog.lastSyncMessageId < message.id;
				if (isNewMessage)
				{
					newMessage.previousId = messageDialog.lastSyncMessageId;
				}
				else
				{
					newMessage.previousId = await this.#getPreviousMessageId(newMessage.id);
				}

				const nextMessage = list[messageIndex + 1];
				newMessage.nextId = nextMessage ? nextMessage.id : 0;

				return newMessage;
			}

			if (isTopMessage && !wasMessageDialogCompletelySync)
			{
				return newMessage;
			}

			const previousMessage = list[messageIndex - 1];
			newMessage.previousId = previousMessage ? previousMessage.id : 0;

			const nextMessage = list[messageIndex + 1];
			newMessage.nextId = nextMessage ? nextMessage.id : 0;

			return newMessage;
		}

		async #getPreviousMessageId(messageId)
		{
			const message = await this.#getMessageById(messageId);

			return message?.previousId;
		}

		async #getMessageById(messageId)
		{
			return this.messageRepository.messageTable.getById(messageId);
		}
	}

	module.exports = {
		MessageContextCreator,
	};
});
