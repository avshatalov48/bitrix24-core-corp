/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/dialog/message-renderer
 */
jn.define('im/messenger/controller/dialog/message-renderer', (require, exports, module) => {
	const { Type } = require('type');
	const { clone, isEqual } = require('utils/object');

	const { core } = require('im/messenger/core');
	const { DialogType } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { Uuid } = require('utils/uuid');
	const {
		Message,
		DateSeparatorMessage,
		UnreadSeparatorMessage,
	} = require('im/messenger/lib/element');

	/**
	 * @class MessageRenderer
	 */
	class MessageRenderer
	{
		constructor({ view, chatId, dialogId })
		{
			this.view = view;
			this.chatId = chatId;
			this.dialogId = dialogId;

			this.store = core.getStore();
			this.messageList = [];
			this.viewMessageCollection = {};
			this.messageIdCollection = new Set();
			this.messageIdsStack = [];
			this.unreadSeparatorAdded = false;
			this.idAfterUnreadSeparatorMessage = '0';
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
				const updateTemplateMessage = this.messageIdCollection.has(message.templateId);
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
				const messageModel = this.getMessageModelByAnyId(id);
				if (!messageModel)
				{
					return;
				}
				const messageViewItem = this.viewMessageCollection[id];
				const packAuthorMessages = this.getPackAuthorNeighborMessages(messageModel);
				const packWithoutDeletedMessage = packAuthorMessages.filter((message) => message.id !== messageViewItem.id);
				const formattedMessages = this.processBottomNearbyMessages(packWithoutDeletedMessage.reverse());

				delete this.viewMessageCollection[id];
				this.messageIdCollection.delete(id);
				this.deleteIdFromStack(messageModel);
				this.messageList = this.messageList.filter((message) => message.id !== id);

				this.view.removeMessagesByIds(messageIdList);

				const bottomMessage = this.getBottomMessage();

				for (const message of formattedMessages)
				{
					if (message.id === bottomMessage.id)
					{
						message.setMarginBottom(4);
					}
				}

				this.updateViewMessages(formattedMessages);
			});
		}

		/**
		 * @private
		 */
		setMessageList(messageList)
		{
			Logger.info('MessageRenderer.setMessageList:', messageList);
			this.messageList = messageList.reverse();

			this.updateMessageIndex(this.messageList);
			this.putMessageIdToStack(clone(messageList).reverse());

			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList);
			const viewMessageListToSet = this.processNearbyMessagesList(viewMessageListWithTemplate);
			this.view.unreadSeparatorAdded = this.unreadSeparatorAdded;
			this.view.setMessages(viewMessageListToSet);
			viewMessageListToSet.forEach((message) => {
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
			this.putMessageIdToStackStart(clone(messageList));
			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList);

			const packNearbyMessages = [
				this.getNextMessage(viewMessageListWithTemplate[0].id),
				...viewMessageListWithTemplate,
			];
			const viewMessageListToPush = this.processTopNearbyMessages(packNearbyMessages);

			const updateMessage = viewMessageListToPush.shift();
			this.updateViewMessages([updateMessage]);
			this.view.pushMessages(viewMessageListToPush);

			viewMessageListWithTemplate.forEach((message) => {
				this.viewMessageCollection[message.id] = message;
			});
		}

		/**
		 * @desc Add a message to the end of the view message collection
		 * @param {Array<MessagesModelState>} messageList
		 * @private
		 */
		addMessageListAfter(messageList)
		{
			Logger.info('MessageRenderer.addMessageListAfter:', messageList);
			this.updateMessageIndex(messageList);
			this.putMessageIdToStack(messageList);
			const viewMessageList = DialogConverter.createMessageList(clone(messageList));
			const viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList.reverse());
			const packAuthorPreviousMessages = this.getPackAuthorPreviousMessages(messageList[0]);
			this.messageList.push(...messageList);

			const packAuthorMessages = [...viewMessageListWithTemplate, ...packAuthorPreviousMessages];
			const viewMessageListToAdd = this.processBottomNearbyMessages(packAuthorMessages);
			if (packAuthorPreviousMessages.length > 0)
			{
				const updateMessageList = viewMessageListToAdd.slice(viewMessageListWithTemplate.length);
				this.updateViewMessages(updateMessageList);
				const addMessageList = viewMessageListToAdd.slice(0, viewMessageListWithTemplate.length);
				this.view.addMessages(addMessageList);
				viewMessageListWithTemplate.forEach((message) => {
					this.viewMessageCollection[message.id] = message;
				});
			}
			else
			{
				this.view.addMessages(viewMessageListToAdd);
				viewMessageListWithTemplate.forEach((message) => {
					this.viewMessageCollection[message.id] = message;
				});
			}
		}

		/**
		 * @desc force update view message by list
		 * @param {Array<Message>} viewMessageList
		 * @private
		 */
		updateViewMessages(viewMessageList)
		{
			viewMessageList.forEach((message) => {
				this.view.updateMessageById(message.id, message);
				this.viewMessageCollection[message.id] = message;
			});
		}

		/**
		 * @desc Returns previous pack messages ( find by authorId )
		 * @param {MessagesModelState} currentModelMessage
		 * @return {Array<Message>} packMessage
		 * @private
		 */
		getPackAuthorPreviousMessages(currentModelMessage)
		{
			const indexMessage = this.messageIdsStack.findIndex(
				(id) => id === currentModelMessage.id || id === currentModelMessage.templateId,
			);

			if (indexMessage === -1)
			{
				return [];
			}
			const packPreviousMessage = [];
			for (let i = indexMessage - 1; i >= 0; i--)
			{
				const messageId = this.messageIdsStack[i];
				const modelMessage = this.getMessageModelByAnyId(messageId);
				if (modelMessage === null)
				{
					break;
				}

				if (modelMessage.authorId !== currentModelMessage.authorId)
				{
					break;
				}

				if (String(this.messageIdsStack[i]) === this.idAfterUnreadSeparatorMessage)
				{
					packPreviousMessage.push(this.viewMessageCollection[modelMessage.id]
						|| this.viewMessageCollection[modelMessage.templateId]);
					break;
				}
				packPreviousMessage.push(this.viewMessageCollection[modelMessage.id]
					|| this.viewMessageCollection[modelMessage.templateId]);
			}

			return packPreviousMessage;
		}

		/**
		 * @desc Returns neighboring pack messages ( find by authorId )
		 * @param {MessagesModelState} currentModelMessage
		 * @return {Array<Message>} packMessages
		 * @private
		 */
		getPackAuthorNeighborMessages(currentModelMessage)
		{
			const { id: modelMessageId, templateId: modelMessageTemplateId, authorId } = currentModelMessage;
			const indexMessage = this.messageIdsStack.findIndex(
				(id) => id === modelMessageId || id === modelMessageTemplateId,
			);

			if (indexMessage === -1)
			{
				return [];
			}

			const centerMessage = this.viewMessageCollection[modelMessageId]
				|| this.viewMessageCollection[modelMessageTemplateId];
			const packMessage = [centerMessage]; // push center message on start iterable
			for (let i = indexMessage + 1; i < this.messageIdsStack.length; i++) // get newest messages
			{
				const modelMessage = this.getMessageModelByAnyId(this.messageIdsStack[i]);
				if (modelMessage === null)
				{
					break;
				}

				if (modelMessage.authorId !== authorId
					|| String(this.messageIdsStack[i]) === this.idAfterUnreadSeparatorMessage)
				{
					break;
				}

				const viewMessage = this.viewMessageCollection[modelMessage.id]
					|| this.viewMessageCollection[modelMessage.templateId];
				packMessage.push(viewMessage);
			}

			// if current message id === idAfterUnreadSeparatorMessage, than don`t find oldest message
			if (String(modelMessageId) === this.idAfterUnreadSeparatorMessage
				|| modelMessageTemplateId === this.idAfterUnreadSeparatorMessage)
			{
				return packMessage;
			}

			for (let i = indexMessage - 1; i >= 0; i--) // get oldest messages
			{
				if (modelMessageId === this.messageIdsStack[i])
				{
					continue;
				}

				const modelMessage = this.getMessageModelByAnyId(this.messageIdsStack[i]);
				if (modelMessage === null)
				{
					break;
				}

				if (modelMessage.authorId !== authorId)
				{
					break;
				}

				if (String(this.messageIdsStack[i]) === this.idAfterUnreadSeparatorMessage)
				{
					packMessage.unshift(this.viewMessageCollection[modelMessage.id]
						|| this.viewMessageCollection[modelMessage.templateId]);
					break;
				}
				packMessage.unshift(this.viewMessageCollection[modelMessage.id]
					|| this.viewMessageCollection[modelMessage.templateId]);
			}

			return packMessage;
		}

		/**
		 * @desc Returns previous message by id (if current message is after unread return null )
		 * @param {number} currentMessageId
		 * @return {null|Message}
		 * @private
		 */
		getPreviousMessage(currentMessageId)
		{
			if (String(currentMessageId) === this.idAfterUnreadSeparatorMessage)
			{
				return this.viewMessageCollection['template-separator-unread'];
			}

			const messageModel = this.getMessageModelByAnyId(currentMessageId);
			if (!messageModel)
			{
				return null;
			}

			const indexId = this.messageIdsStack.findIndex(
				(id) => id === currentMessageId || id === messageModel.id || id === messageModel.templateId,
			);

			return indexId === -1 ? null : this.viewMessageCollection[this.messageIdsStack[indexId - 1]];
		}

		/**
		 * @desc Returns next message by id ( if next is system message or break - than return null )
		 * @param {number} currentMessageId
		 * @return {Message|null}
		 * @private
		 */
		getNextMessage(currentMessageId)
		{
			const messageModel = this.getMessageModelByAnyId(currentMessageId);
			if (!messageModel)
			{
				return null;
			}

			const indexMessage = this.messageIdsStack.findIndex(
				(id) => id === currentMessageId || id === messageModel.id || id === messageModel.templateId,
			);

			if (indexMessage === -1)
			{
				return null;
			}

			return this.viewMessageCollection[this.messageIdsStack[indexMessage + 1]];
		}

		/**
		 * @desc Return bottom (last) message from messageIdCollection
		 * @return {Message}
		 * @private
		 */
		getBottomMessage()
		{
			return this.viewMessageCollection[this.messageIdsStack[this.messageIdsStack.length - 1]];
		}

		/**
		 * @private
		 */
		updateMessageList(messageList)
		{
			Logger.info('MessageRenderer.updateMessageList:', messageList);

			messageList.forEach((message) => {
				if (this.messageIdCollection.has(message.templateId))
				{
					this.messageIdCollection.delete(message.templateId);
					this.messageIdCollection.add(message.id);
					this.messageList = this.messageList.map((listMessage) => {
						if (listMessage.id === message.templateId)
						{
							return message;
						}

						return listMessage;
					});
				}
			});

			messageList.forEach((messageListItem) => {
				const viewMessageItem = DialogConverter.createMessage(messageListItem);
				const packAuthorMessages = this.getPackAuthorNeighborMessages(messageListItem);
				const indexPackMessage = packAuthorMessages.findIndex(
					(message) => message.id === viewMessageItem.id || message.id === messageListItem.templateId,
				);

				const formattedMessage = this.processNearbyMessages(
					packAuthorMessages[indexPackMessage - 1] || this.getPreviousMessage(messageListItem.id),
					viewMessageItem,
					packAuthorMessages[indexPackMessage + 1] || this.getNextMessage(messageListItem.id),
				);

				const isMessageChanged = !isEqual(formattedMessage, this.viewMessageCollection[formattedMessage.id]);
				if (isMessageChanged)
				{
					this.view.updateMessageById(formattedMessage.id, formattedMessage);
					this.viewMessageCollection[formattedMessage.id] = formattedMessage;
				}

				const isTemplateMessageChanged = !isEqual(
					formattedMessage,
					this.viewMessageCollection[messageListItem.templateId],
				);
				if (isTemplateMessageChanged)
				{
					this.view.updateMessageById(messageListItem.templateId, formattedMessage);
					this.viewMessageCollection[messageListItem.templateId] = formattedMessage;
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
		 * @desc Method push id to the end ( upper ) stack
		 * @param {Array<MessagesModelState>} messageList
		 * @private
		 */
		putMessageIdToStack(messageList)
		{
			messageList.forEach((message) => {
				this.messageIdsStack.push(message.id);
			});
		}

		/**
		 * @desc Method unshift id to the start ( down ) stack
		 * @param {Array<MessagesModelState>} messageList
		 * @private
		 */
		putMessageIdToStackStart(messageList)
		{
			messageList.forEach((message) => {
				this.messageIdsStack.unshift(message.id);
			});
		}

		/**
		 * @desc Method push message id after pointed id
		 * @param {string|number} pointedId
		 * @param {string|number} messageId
		 * @return {boolean}
		 * @private
		 */
		putMessageIdToStackById(pointedId, messageId)
		{
			const indexId = this.messageIdsStack.findIndex((id) => id === pointedId || id === Number(pointedId));
			if (indexId === -1)
			{
				return false;
			}

			const beforeId = this.messageIdsStack.slice(0, indexId);
			const afterId = this.messageIdsStack.slice(indexId);
			this.messageIdsStack = [...beforeId, messageId, ...afterId];

			return true;
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
						this.idAfterUnreadSeparatorMessage = String(message.id);
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
						this.idAfterUnreadSeparatorMessage = String(messageList[index].id);
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
					this.idAfterUnreadSeparatorMessage = String(messageList[index].id);
				}

				const previousMessageDate = this.toDateCode(previousMessage.date);
				const currentMessageDate = this.toDateCode(currentMessage.date);
				if (previousMessageDate !== currentMessageDate)
				{
					const dateSeparatorSystemMessage = this.getSeparator(currentMessage.date);
					messageListWithTemplate.push(dateSeparatorSystemMessage);
					this.putMessageIdToStackById(message.id, dateSeparatorSystemMessage.id);
				}

				messageListWithTemplate.push(message);
			});

			messageListWithTemplate.forEach((messageTemplate) => {
				if (messageTemplate.type === this.constants.messageType.systemText)
				{
					this.prepareSystemMessage(messageTemplate);
				}
			});

			return messageListWithTemplate.reverse();
		}

		/**
		 * @private
		 */
		processNearbyMessagesList(messageList)
		{
			return messageList
				.reverse()
				.map((message, index, list) => {
					const previousMessage = list[index - 1];
					const nextMessage = list[index + 1];

					return this.processNearbyMessages(previousMessage, message, nextMessage);
				})
				.reverse()
			;
		}

		/**
		 * @private
		 */
		processTopNearbyMessages(messageList)
		{
			return messageList
				.reverse()
				.map((message, index, list) => {
					const previousMessage = list[index - 1];
					const nextMessage = list[index + 1] || this.getNextMessage(message.id);

					return this.processNearbyMessages(previousMessage, message, nextMessage);
				})
				.reverse()
			;
		}

		/**
		 * @private
		 */
		processBottomNearbyMessages(messageList)
		{
			return messageList
				.reverse()
				.map((message, index, list) => {
					const previousMessage = index === 0 ? this.getPreviousMessage(message.id) : list[index - 1];
					const nextMessage = list[index + 1];

					return this.processNearbyMessages(previousMessage, message, nextMessage);
				})
				.reverse()
			;
		}

		/**
		 * @private
		 */
		processNearbyMessages(previousMessage, message, nextMessage)
		{
			if (
				!(message instanceof Message)
				|| message instanceof UnreadSeparatorMessage
				|| message instanceof DateSeparatorMessage
			)
			{
				return message;
			}

			if (!previousMessage)
			{
				message.setAuthorTopMessage(false);
				message.setAuthorBottomMessage(true);

				if (!nextMessage)
				{
					message.setAuthorTopMessage(true);
					message.setAuthorBottomMessage(true);
				}

				if (message.type === this.constants.messageType.systemText)
				{
					this.prepareSystemMessage(message);
				}

				return message;
			}

			const isPrivateDialog = this.getDialog().type === DialogType.user;
			const previousModelMessage = this.getMessage(previousMessage.id);
			const modelMessage = this.getMessage(message.id);
			if (!nextMessage)
			{
				message.setAuthorTopMessage(true);
				message.setAuthorBottomMessage(true);

				if (previousModelMessage.authorId === modelMessage.authorId
					&& previousModelMessage.id !== modelMessage.id)
				{
					message.setAuthorTopMessage(false);
					message.setShowUsername(modelMessage, false);
				}

				if (isPrivateDialog)
				{
					this.preparePrivateMessage(message, modelMessage);
				}

				if (message.type === this.constants.messageType.systemText)
				{
					this.prepareSystemMessage(message);
				}

				return message;
			}

			const nextModelMessage = this.getMessage(nextMessage.id);

			/** username block */
			if (previousModelMessage.authorId === modelMessage.authorId
				&& previousModelMessage.id !== modelMessage.id
				&& previousModelMessage.id !== nextModelMessage.id)
			{
				message.setShowUsername(modelMessage, false);
			}

			/** margins block */
			this.setMargins(previousModelMessage, modelMessage, nextModelMessage, message);

			/** avatar block */
			if (nextModelMessage.authorId === modelMessage.authorId)
			{
				message.setShowAvatar(modelMessage, false);
				message.setAuthorBottomMessage(false);
				message.setAuthorTopMessage(false);
			}

			if (nextModelMessage.authorId !== modelMessage.authorId)
			{
				message.setShowAvatar(modelMessage, true);
			}

			/** tail block */
			if (previousModelMessage.authorId !== modelMessage.authorId)
			{
				message.setAuthorTopMessage(true);
				message.setAuthorBottomMessage(false);
			}

			if (nextModelMessage.authorId === modelMessage.authorId
				&& previousModelMessage.authorId === modelMessage.authorId)
			{
				message.setAuthorTopMessage(false);
				message.setAuthorBottomMessage(false);
			}

			if (nextModelMessage.authorId !== modelMessage.authorId)
			{
				message.setAuthorBottomMessage(true);
			}

			const isYourMessage = modelMessage.authorId === core.getUserId();
			if (isYourMessage)
			{
				message.setShowAvatar(modelMessage, false);
			}

			if (isPrivateDialog)
			{
				this.preparePrivateMessage(message, modelMessage);
			}

			if (message.type === this.constants.messageType.systemText)
			{
				this.prepareSystemMessage(message);
			}

			return message;
		}

		/**
		 * @desc Set property for message in private chat
		 * @param {Message} message
		 * @param {MessagesModelState} modelMessage
		 * @private
		 */
		preparePrivateMessage(message, modelMessage)
		{
			message.setShowAvatar(modelMessage, false);
			message.setAvatarUri(null);
			message.setShowUsername(modelMessage, false);
		}

		/**
		 * @desc Set property for system message with type 'system-text' (manage participant, change title and other)
		 * @param {Message} message
		 * @private
		 */
		prepareSystemMessage(message)
		{
			message.setShowAvatarForce(false);
			message.setAvatarUri(null);
		}

		/**
		 * @desc Set margins to message by his position
		 * @param {MessagesModelState} previousModelMessage
		 * @param {MessagesModelState} modelMessage
		 * @param {MessagesModelState} nextModelMessage
		 * @param {Message} message
		 * @private
		 */
		setMargins(previousModelMessage, modelMessage, nextModelMessage, message)
		{
			if (
				previousModelMessage.authorId !== modelMessage.authorId
				&& nextModelMessage.authorId !== modelMessage.authorId
			) // for alone message
			{
				message.setMarginTop(4);
				message.setMarginBottom(4);
			}

			if (
				previousModelMessage.authorId !== modelMessage.authorId
				&& nextModelMessage.authorId === modelMessage.authorId
			) // for first message in group
			{
				message.setMarginTop(4);
				message.setMarginBottom(0);
			}

			if (
				previousModelMessage.authorId === modelMessage.authorId
				&& nextModelMessage.authorId === modelMessage.authorId
			) // for message of the middle
			{
				message.setMarginTop(4);
				message.setMarginBottom(0);
			}

			if (
				previousModelMessage.authorId === modelMessage.authorId
				&& nextModelMessage.authorId !== modelMessage.authorId
			) // for ended message in group
			{
				message.setMarginTop(4);
				message.setMarginBottom(4);
			}
		}

		/**
		 * @private
		 */
		getMessage(messageId)
		{
			return this.store.getters['messagesModel/getMessageById'](messageId);
		}

		/**
		 * @desc Find model by id or templateId from store or current context
		 * @param {String|Number} messageId
		 * @return {MessagesModelState|null}
		 * @private
		 */
		getMessageModelByAnyId(messageId)
		{
			let modelMessage = this.store.getters['messagesModel/getMessageById'](messageId);
			if (Type.isUndefined(modelMessage) || !('id' in modelMessage)) // getMessageById may returns {}
			{
				modelMessage = this.store.getters['messagesModel/getMessageByTemplateId'](messageId);
				if (!modelMessage)
				{
					modelMessage = this.messageList.find(
						(message) => message.id === messageId || message.templateId === messageId,
					);

					if (!modelMessage)
					{
						return null;
					}
				}
			}

			return modelMessage;
		}

		/**
		 * @desc Remove id from this.messageIdsStack with filter and mutation current data
		 * @param {MessagesModelState} messageModel
		 * @private
		 */
		deleteIdFromStack(messageModel)
		{
			const indexDeletedMessage = this.messageIdsStack.findIndex(
				(idFromStack) => idFromStack === messageModel.id || idFromStack === messageModel.templateId,
			);
			this.messageIdsStack = this.messageIdsStack.filter((el, index) => index !== indexDeletedMessage);
		}

		/**
		 * @private
		 */
		getDialog()
		{
			return this.store.getters['dialoguesModel/getById'](this.dialogId);
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

		constants = {
			messageType: {
				systemText: 'system-text',
			},
		};
	}

	module.exports = {
		MessageRenderer,
	};
});
