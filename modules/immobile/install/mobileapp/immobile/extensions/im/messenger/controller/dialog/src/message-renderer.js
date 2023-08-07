/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/dialog/message-renderer
 */
jn.define('im/messenger/controller/dialog/message-renderer', (require, exports, module) => {
	const { Type } = require('type');
	const { clone, isEqual } = require('utils/object');
	const { Logger } = require('im/messenger/lib/logger');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { Uuid } = require('utils/uuid');
	const {
		DateSeparatorMessage,
		UnreadSeparatorMessage,
	} = require('im/messenger/lib/element');

	/**
	 * @class MessageRenderer
	 */
	class MessageRenderer
	{
		constructor({ store, view, chatId, dialogId })
		{
			this.view = view;
			this.chatId = chatId;
			this.dialogId = dialogId;
			this.store = store;

			this.messageList = [];
			this.viewMessageCollection = {};
			this.messageIdCollection = new Set();
			this.unreadSeparatorAdded = false;
		}

		render(messageList)
		{
			if (this.messageList.length === 0)
			{
				this.setMessageList(messageList);

				return;
			}

			const newMessageList = [];
			const updateMessageList = [];

			messageList.forEach((message) => {
				const updateRealMessage = this.messageIdCollection.has(message.id);
				const updateTemplateMessage = this.messageIdCollection.has(message.uuid);
				if (updateRealMessage || updateTemplateMessage)
				{
					updateMessageList.push(message);

					return;
				}

				newMessageList.push(message);
			});

			if (newMessageList.length > 0)
			{
				this.addMessageList(newMessageList);
			}

			if (updateMessageList.length > 0)
			{
				this.updateMessageList(updateMessageList);
			}
		}

		delete(messageIdList)
		{
			Logger.info('MessageRenderer.delete:', messageIdList);

			messageIdList.forEach((id) => {
				delete this.viewMessageCollection[id];
				this.messageIdCollection.delete(id);
				this.messageList = this.messageList.filter((message) => message.id !== id);
			});

			this.view.removeMessagesByIds(messageIdList);
		}

		/**
		 * @private
		 */
		setMessageList(messageList)
		{
			Logger.info('MessageRenderer.setMessageList:', messageList);
			this.messageList = messageList.reverse();

			this.updateMessageIndex(this.messageList);

			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList);
			this.view.unreadSeparatorAdded = this.unreadSeparatorAdded;
			this.view.setMessages(viewMessageListWithTemplate);
			viewMessageListWithTemplate.forEach((message) => {
				this.viewMessageCollection[message.id] = message;
			});
		}

		/**
		 * @private
		 */
		addMessageList(messageList)
		{
			// TODO: refactor
			const isHistoryList = (
				Type.isNumber(this.messageList[0].id)
				&& Type.isNumber(messageList[0].id)
				&& messageList[0].id < this.messageList[0].id
			);

			const isTemplateMessage = Uuid.isV4(messageList[0].id);
			if (isHistoryList)
			{
				this.addMessageListBefore(messageList);
			}
			else if (isTemplateMessage)
			{
				this.addMessageListAfter(messageList);
			}
			else
			{
				this.addMessageListAfter(messageList);
			}
		}

		/**
		 * @private
		 */
		addMessageListBefore(messageList)
		{
			Logger.info('MessageRenderer.addMessageListBefore:', messageList);
			// eslint-disable-next-line no-param-reassign
			messageList = messageList.reverse();

			this.messageList.push(...messageList);
			this.updateMessageIndex(messageList);

			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList);
			this.view.pushMessages(viewMessageListWithTemplate);
			viewMessageListWithTemplate.forEach((message) => {
				this.viewMessageCollection[message.id] = message;
			});
		}

		/**
		 * @private
		 */
		addMessageListAfter(messageList)
		{
			Logger.info('MessageRenderer.addMessageListAfter:', messageList);

			this.messageList.push(...messageList);
			this.updateMessageIndex(messageList);

			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList.reverse());
			this.view.addMessages(viewMessageListWithTemplate);
			viewMessageListWithTemplate.forEach((message) => {
				this.viewMessageCollection[message.id] = message;
			});
		}

		/**
		 * @private
		 */
		updateMessageList(messageList)
		{
			Logger.info('MessageRenderer.updateMessageList:', messageList);

			messageList.forEach((message) => {
				if (this.messageIdCollection.has(message.uuid))
				{
					this.messageIdCollection.delete(message.uuid);
					this.messageIdCollection.add(message.id);

					this.messageList = this.messageList.map((listMessage) => {
						if (listMessage.id === message.uuid)
						{
							return message;
						}

						return listMessage;
					});
				}
			});

			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			viewMessageList.forEach((message, index) => {
				const isMessageChanged = !isEqual(message, this.viewMessageCollection[message.id]);
				if (isMessageChanged)
				{
					this.view.updateMessageById(message.id, message);
					this.viewMessageCollection[message.id] = message;
				}

				const isTemplateMessageChanged = !isEqual(message, this.viewMessageCollection[messageList[index].uuid]);
				if (isTemplateMessageChanged)
				{
					this.view.updateMessageById(messageList[index].uuid, message);
					this.viewMessageCollection[messageList[index].uuid] = message;
				}

				if (!isMessageChanged)
				{
					Logger.log('MessageRenderer.updateMessageList: Nothing changed');
				}
			});
		}

		/**
		 * @private
		 */
		updateMessageIndex(messageList)
		{
			messageList.forEach((message) => {
				this.messageIdCollection.add(message.id);
			});
		}

		/**
		 * @private
		 */
		addTemplateMessagesToList(messageList)
		{
			const messageListWithTemplate = [];

			messageList.reverse().forEach((message, index, messageList) => {
				const isOldestMessage = index === 0;
				if (isOldestMessage)
				{
					const oldestMessage = this.getMessage(message.id);
					if (!oldestMessage)
					{
						return;
					}

					if (oldestMessage.unread && this.unreadSeparatorAdded === false)
					{
						messageListWithTemplate.push(new UnreadSeparatorMessage());
						this.unreadSeparatorAdded = true;
					}

					messageListWithTemplate.push(message);

					return;
				}

				const isNewestMessage = index === messageList.length - 1;
				if (isNewestMessage)
				{
					const previousMessage = this.getMessage(messageList[index - 1].id);
					const newestMessage = this.getMessage(messageList[index].id);
					if (!previousMessage || !newestMessage)
					{
						return;
					}

					const previousMessageDate = this.toDateCode(previousMessage.date);
					const newestMessageDate = this.toDateCode(newestMessage.date);
					if (previousMessageDate !== newestMessageDate)
					{
						messageListWithTemplate.push(this.getSeparator(newestMessage.date));
					}

					if (newestMessage.unread && this.unreadSeparatorAdded === false)
					{
						messageListWithTemplate.push(new UnreadSeparatorMessage());
						this.unreadSeparatorAdded = true;
					}

					messageListWithTemplate.push(message);

					return;
				}

				const previousMessage = this.getMessage(messageList[index - 1].id);
				const currentMessage = this.getMessage(messageList[index].id);
				if (!previousMessage || !currentMessage)
				{
					return;
				}

				if (!previousMessage.unread && currentMessage.unread && this.unreadSeparatorAdded === false)
				{
					messageListWithTemplate.push(new UnreadSeparatorMessage());
					this.unreadSeparatorAdded = true;
				}

				const previousMessageDate = this.toDateCode(previousMessage.date);
				const currentMessageDate = this.toDateCode(currentMessage.date);
				if (previousMessageDate !== currentMessageDate)
				{
					messageListWithTemplate.push(this.getSeparator(currentMessage.date));
				}

				messageListWithTemplate.push(message);
			});

			return messageListWithTemplate.reverse();
		}

		/**
		 * @private
		 */
		getMessage(messageId)
		{
			return this.store.getters['messagesModel/getMessageById'](messageId);
		}

		/**
		 * @private
		 */
		toDateCode(date)
		{
			return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
		}

		/**
		 * @private
		 */
		getSeparator(date)
		{
			const id = `template-separator-${this.toDateCode(date)}`;

			return new DateSeparatorMessage(id, date);
		}
	}

	module.exports = {
		MessageRenderer,
	};
});
