/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

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
			this._unreadSeparatorAdded = false;
		}

		render(messageList)
		{
			if (this.messageList.length === 0)
			{
				this._setMessageList(messageList);

				return;
			}

			const newMessageList = [];
			const updateMessageList = [];

			messageList.forEach(message => {
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
				this._addMessageList(newMessageList);
			}

			if (updateMessageList.length > 0)
			{
				this._updateMessageList(updateMessageList);
			}
		}

		_setMessageList(messageList)
		{
			Logger.info('MessageRenderer._setMessageList:', messageList);
			this.messageList = messageList.reverse();

			this._updateMessageIndex(this.messageList);

			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this._addTemplateMessagesToList(viewMessageList);
			this.view.unreadSeparatorAdded = this._unreadSeparatorAdded;
			this.view.setMessages(viewMessageListWithTemplate);
			viewMessageListWithTemplate.forEach(message => {
				this.viewMessageCollection[message.id] = message;
			});
		}

		_addMessageList(messageList)
		{
			//TODO: refactor
			const isHistoryList = (
				Type.isNumber(this.messageList[0].id)
				&& Type.isNumber(messageList[0].id)
				&& messageList[0].id < this.messageList[0].id
			);

			const isTemplateMessage = Uuid.isV4(messageList[0].id);
			if (isHistoryList)
			{
				this._addMessageListBefore(messageList);
			}
			else if (isTemplateMessage)
			{
				this._addMessageListAfter(messageList);
			}
			else
			{
				this._addMessageListAfter(messageList);
			}
		}

		_addMessageListBefore(messageList)
		{
			Logger.info('MessageRenderer._addMessageListBefore:', messageList);
			messageList = messageList.reverse();

			this.messageList.push(...messageList);
			this._updateMessageIndex(messageList);

			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this._addTemplateMessagesToList(viewMessageList);
			this.view.pushMessages(viewMessageListWithTemplate);
			viewMessageListWithTemplate.forEach(message => {
				this.viewMessageCollection[message.id] = message;
			});
		}

		_addMessageListAfter(messageList)
		{
			Logger.info('MessageRenderer._addMessageListAfter:', messageList);

			this.messageList.push(...messageList);
			this._updateMessageIndex(messageList);

			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this._addTemplateMessagesToList(viewMessageList.reverse());
			this.view.addMessages(viewMessageListWithTemplate);
			viewMessageListWithTemplate.forEach(message => {
				this.viewMessageCollection[message.id] = message;
			});
		}

		_updateMessageList(messageList)
		{
			Logger.info('MessageRenderer._updateMessageList:', messageList);

			messageList.forEach(message => {
				if (this.messageIdCollection.has(message.uuid))
				{
					this.messageIdCollection.delete(message.uuid);
					this.messageIdCollection.add(message.id);

					this.messageList = this.messageList.map(listMessage => {
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
					Logger.log('MessageRenderer._updateMessageList: Nothing changed');
				}
			});
		}

		_updateMessageIndex(messageList)
		{
			messageList.forEach(message => {
				this.messageIdCollection.add(message.id);
			});
		}

		_addTemplateMessagesToList(messageList)
		{
			const messageListWithTemplate = [];

			messageList.reverse().forEach((message, index, messageList) => {
				const isOldestMessage = index === 0;
				if (isOldestMessage)
				{
					const oldestMessage = this._getMessage(message.id);
					if (!oldestMessage)
					{
						return;
					}

					if (oldestMessage.unread && this._unreadSeparatorAdded === false)
					{
						messageListWithTemplate.push(new UnreadSeparatorMessage());
						this._unreadSeparatorAdded = true;
					}

					messageListWithTemplate.push(message);

					return;
				}

				const isNewestMessage = index === messageList.length - 1;
				if (isNewestMessage)
				{
					const previousMessage = this._getMessage(messageList[index - 1].id);
					const newestMessage = this._getMessage(messageList[index].id);
					if (!previousMessage || !newestMessage)
					{
						return;
					}

					const previousMessageDate = this._toDateCode(previousMessage.date);
					const newestMessageDate = this._toDateCode(newestMessage.date);
					if (previousMessageDate !== newestMessageDate)
					{
						messageListWithTemplate.push(this._getSeparator(newestMessage.date));
					}

					if (newestMessage.unread && this._unreadSeparatorAdded === false)
					{
						messageListWithTemplate.push(new UnreadSeparatorMessage());
						this._unreadSeparatorAdded = true;
					}

					messageListWithTemplate.push(message);

					return;
				}

				const previousMessage = this._getMessage(messageList[index - 1].id);
				const currentMessage = this._getMessage(messageList[index].id);
				if (!previousMessage || !currentMessage)
				{
					return;
				}

				if (!previousMessage.unread && currentMessage.unread && this._unreadSeparatorAdded === false)
				{
					messageListWithTemplate.push(new UnreadSeparatorMessage());
					this._unreadSeparatorAdded = true;
				}

				const previousMessageDate = this._toDateCode(previousMessage.date);
				const currentMessageDate = this._toDateCode(currentMessage.date);
				if (previousMessageDate !== currentMessageDate)
				{
					messageListWithTemplate.push(this._getSeparator(currentMessage.date));
				}

				messageListWithTemplate.push(message);
			});

			return messageListWithTemplate.reverse();
		}

		_getMessage(messageId)
		{
			return this.store.getters['messagesModel/getMessageById'](messageId);
		}

		_toDateCode(date)
		{
			return date.getFullYear() + '-' + date.getMonth() + '-' + date.getDate();
		}

		_getSeparator(date)
		{
			const id = 'template-separator-' + this._toDateCode(date);

			return new DateSeparatorMessage(id, date);
		}
	}

	module.exports = {
		MessageRenderer,
	};
});
