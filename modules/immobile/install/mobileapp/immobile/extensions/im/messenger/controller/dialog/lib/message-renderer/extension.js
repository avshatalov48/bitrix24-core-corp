/* eslint-disable flowtype/require-return-type */
/* eslint-disable no-await-in-loop */
/* eslint-disable promise/no-nesting */

/**
 * @module im/messenger/controller/dialog/lib/message-renderer
 */
jn.define('im/messenger/controller/dialog/lib/message-renderer', (require, exports, module) => {
	const { Type } = require('type');
	const { clone, isEqual } = require('utils/object');
	const { Uuid } = require('utils/uuid');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessageIdType, MessageType, DialogType, MessageParams } = require('im/messenger/const');
	const { DialogConverter } = require('im/messenger/lib/converter');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Analytics } = require('im/messenger/const');
	const { AnalyticsEvent } = require('analytics');

	const {
		Message,
		DateSeparatorMessage,
		UnreadSeparatorMessage,
	} = require('im/messenger/lib/element');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('dialog--message-renderer');

	/**
	 * @class MessageRenderer
	 */
	class MessageRenderer
	{
		constructor({ view, chatId, dialogId })
		{
			/** @type {DialogView} */
			this.view = view;
			this.chatId = chatId;
			this.dialogId = dialogId;

			this.store = serviceLocator.get('core').getStore();
			this.resetState();
		}

		resetState()
		{
			/** @type {Array<MessagesModelState>} */
			this.messageList = [];
			/** @type {Record<number || string, Message>} */
			this.viewMessageCollection = {};
			this.messageIdCollection = new Set();
			/** @type {Array<string>} */
			this.messageIdsStack = [];
			this.unreadSeparatorAdded = false;
			this.idAfterUnreadSeparatorMessage = '0';
			this.nextTickCallbackList = [];
			/** @type {Set<string>} */
			this.uploadingMessageCollection = new Set();
			this.renderQueuePromise = Promise.resolve();
		}

		/**
		 * @description Adds a messageList to the rendering queue.
		 * Returns a promise that will resolve after rendering of messageList completes
		 *
		 * @param {Array<MessagesModelState>} messageList
		 * @param {string || null} sectionCodeToUpdate
		 */
		async render(messageList, sectionCodeToUpdate = null)
		{
			logger.log('MessageRenderer.render:', messageList);

			let resolveRender;
			let rejectRender;
			const renderPromise = new Promise((resolve, reject) => {
				resolveRender = resolve;
				rejectRender = reject;
			});

			this.renderQueuePromise = this.renderQueuePromise
				.then(() => {
					const renderInternalPromise = this.renderInternal(messageList, sectionCodeToUpdate);

					renderInternalPromise
						.then(() => {
							logger.log('MessageRenderer.renderInternal: complete', messageList);

							resolveRender();
						})
						.catch((error) => {
							logger.error('MessageRenderer.renderInternal: error', error);

							rejectRender();
						})
					;

					return renderInternalPromise;
				})
				.catch((error) => {
					logger.error('MessageRenderer.renderQueue error:', error);

					// eslint-disable-next-line promise/no-return-wrap
					return Promise.resolve();
				});

			await renderPromise;
		}

		/**
		 * @private
		 * @param {Array<MessagesModelState>} messageList
		 * @param {string || null} sectionCodeToUpdate
		 */
		async renderInternal(messageList, sectionCodeToUpdate = null)
		{
			logger.log('MessageRenderer.renderInternal:', messageList);

			if (sectionCodeToUpdate !== null)
			{
				const updateMessageList = messageList
					.filter((message) => this.isMessageRendered(message.id))
				;
				logger.log('MessageRenderer.renderInternal updateMessageList with sectionCode ', updateMessageList, sectionCodeToUpdate);

				if (updateMessageList.length === 0)
				{
					return;
				}

				await this.updateMessageList(updateMessageList, sectionCodeToUpdate);

				this.doTick();

				return;
			}

			if (this.messageList.length === 0)
			{
				await this.setMessageList(messageList);

				this.doTick();

				return;
			}

			await this.killDoubleMessage(messageList);
			await this.killUploadingMessages(messageList);

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
				await this.addMessageList(newMessageList);
			}

			if (updateMessageList.length > 0)
			{
				await this.updateMessageList(updateMessageList);
			}

			this.doTick();
		}

		isMessageRendered(messageId)
		{
			return this.messageIdCollection.has(messageId);
		}

		async delete(messageIdList)
		{
			logger.info('MessageRenderer.delete:', messageIdList);

			for (const id of messageIdList)
			{
				if (this.uploadingMessageCollection.has(id))
				{
					this.uploadingMessageCollection.delete(id);
				}

				const messageModel = this.getMessageModelByAnyId(id);
				if (!messageModel)
				{
					return;
				}

				const deletingMessages = [id];

				// process delete unread separator
				if (this.idAfterUnreadSeparatorMessage === String(id))
				{
					if (this.getBottomMessage()?.id === String(id))
					{
						delete this.viewMessageCollection[UnreadSeparatorMessage.getDefaultId()];
						this.unreadSeparatorAdded = false;
						this.idAfterUnreadSeparatorMessage = '0';

						deletingMessages.push(UnreadSeparatorMessage.getDefaultId());
					}
					else
					{
						this.idAfterUnreadSeparatorMessage = this.getRealNextMessage(id).id;
					}
				}

				let previousMessage = this.getPreviousMessage(id);
				let nextMessage = this.getNextMessage(id);

				delete this.viewMessageCollection[id];
				this.messageIdCollection.delete(id);
				this.deleteIdFromStack(messageModel);
				this.messageList = this.messageList.filter((message) => message.id !== id);

				// If the message you want to delete is between two separators, delete the previous separator.
				if (
					previousMessage instanceof DateSeparatorMessage
					&& nextMessage instanceof DateSeparatorMessage
				)
				{
					const previousMessageId = previousMessage.id;
					delete this.viewMessageCollection[previousMessageId];
					this.messageIdCollection.delete(previousMessageId);
					this.deleteIdFromStackById(previousMessageId);
					this.messageList = this.messageList.filter((message) => message.id !== previousMessageId);
					previousMessage = null;

					deletingMessages.push(previousMessageId);
				}

				await this.view.removeMessagesByIds(deletingMessages);

				const updatedMessages = [];
				if (previousMessage)
				{
					const previousMessageData = this.getMessageModelByAnyId(previousMessage.id);
					if (previousMessageData)
					{
						updatedMessages.push(previousMessageData);
					}
				}

				if (nextMessage)
				{
					const nextMessageData = this.getMessageModelByAnyId(nextMessage.id);
					if (nextMessageData)
					{
						updatedMessages.push(nextMessageData);
					}
				}

				await this.updateMessageList(updatedMessages, null, true);

				if (this.view.isWelcomeScreenShown) // check when delete last message offline
				{
					this.view.hideMessageListLoader();
				}
			}

			const bottomMessage = this.getBottomMessage();

			if (bottomMessage && bottomMessage.type === MessageType.systemText)
			{
				const isSystemDateMessage = bottomMessage.id.includes(MessageIdType.templateSeparatorDate);
				if (isSystemDateMessage)
				{
					delete this.viewMessageCollection[bottomMessage.id];
					this.messageIdCollection.delete(bottomMessage.id);
					this.deleteIdFromStack(bottomMessage);

					await this.view.removeMessagesByIds([bottomMessage.id]);
				}
			}
		}

		/**
		 * @private
		 */
		async setMessageList(messageList)
		{
			logger.info('MessageRenderer.setMessageList:', messageList);
			this.messageList = [...messageList];
			this.markUploadingMessages(messageList);

			this.updateMessageIndex(this.messageList);

			const viewMessageList = DialogConverter.createMessageList(clone(messageList.reverse()), this.dialogId);
			const viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList);

			const messageForStack = [...viewMessageListWithTemplate];
			this.putMessageIdToStack(messageForStack.reverse());

			const viewMessageListToSet = this.processNearbyMessagesList(viewMessageListWithTemplate);
			this.view.unreadSeparatorAdded = this.unreadSeparatorAdded;

			if (this.store.getters['messagesModel/isUploadingMessage'](viewMessageListToSet[0]?.id))
			{
				this.view.setMessageIdToScrollAfterSet(viewMessageListToSet[0]?.id);
			}

			await this.view.setMessages(viewMessageListToSet);
			viewMessageListToSet.forEach((message) => {
				this.viewMessageCollection[message.id] = message;
			});

			if (this.isHistoryLimitExceeded())
			{
				logger.log(`${this.constructor.name}.setMessageList.pushPlanLimitMessage`);
				this.sendAnalyticsIsHistoryLimitExceeded(this.dialogId, Analytics.Section.chatStart);
				await this.view.pushMessages([this.getPlanLimitMessage()]);
			}
		}

		/**
		 * @private
		 * @param {Array<MessagesModelState>} messageList
		 */
		async addMessageList(messageList)
		{
			const messageListAbove = [];
			const messageListBelow = [];
			/** @type {Map<MessageMapBetweenKey, Array<MessagesModelState>>} */
			const messageMapBetween = new Map();
			this.markUploadingMessages(messageList);
			messageList.forEach((message) => {
				if (this.checkIsMessageAbove(message))
				{
					// logger.warn('MessageRenderer insert message above', message);
					messageListAbove.push(message);
				}
				else if (this.checkIsMessageBelow(message))
				{
					// logger.warn('MessageRenderer insert message below', message);
					messageListBelow.push(message);
				}
				else
				{
					const messageId = this.checkIsMessageBetween(message);
					if (!messageId)
					{
						logger.error('MessageRenderer: could not find the position where to place the message', message);

						return;
					}

					if (!messageMapBetween.has(messageId))
					{
						messageMapBetween.set(messageId, []);
					}
					messageMapBetween.get(messageId).push(message);
				}
			});

			if (messageListAbove.length > 0)
			{
				await this.addMessageListAbove(messageListAbove);
			}

			if (messageListBelow.length > 0)
			{
				await this.addMessageListBelow(messageListBelow);
			}

			if (messageMapBetween.size > 0)
			{
				await this.addMessagesBetween(messageMapBetween);
			}
		}

		/**
		 * @param {MessagesModelState} message
		 * @returns {boolean}
		 */
		checkIsMessageAbove(message)
		{
			const isTemplateMessage = Uuid.isV4(message.id);
			if (isTemplateMessage)
			{
				return false;
			}

			return (
				Type.isNumber(this.messageList[0].id)
				&& Type.isNumber(message.id)
				&& message.id < this.messageList[0].id
			);
		}

		/**
		 * @param {MessagesModelState} message
		 * @returns {boolean}
		 */
		checkIsMessageBelow(message)
		{
			const isTemplateMessage = Uuid.isV4(message.id);
			if (isTemplateMessage)
			{
				return true;
			}

			const size = this.messageList.length - 1;
			const bottomMessage = this.messageList[size];
			if (
				this.uploadingMessageCollection.has(bottomMessage.id)
				&& bottomMessage.previousId < message.id
			)
			{
				return true;
			}

			return (
				Type.isNumber(this.messageList[size].id)
				&& Type.isNumber(message.id)
				&& message.id > this.messageList[size].id
			);
		}

		/**
		 * @param {MessagesModelState} message
		 * @return {number| string | null}
		 */
		checkIsMessageBetween(message)
		{
			for (let i = 0; i < this.messageList.length - 1; i++)
			{
				const previousMessage = this.messageList[i];
				const nextMessage = this.messageList[i + 1];

				if (!Uuid.isV4(previousMessage.id) && !Uuid.isV4(nextMessage.id))
				{
					if (previousMessage.id < message.id && nextMessage.id > message.id)
					{
						return previousMessage.id;
					}
				}
				else if (
					previousMessage.date.getTime() < message.date.getTime()
					&& nextMessage.date.getTime() > message.date.getTime()
				)
				{
					return previousMessage.id;
				}
				else
				{
					logger.warn('MessageRenderer: could not find the position where to place the message', previousMessage, nextMessage, message);

					return previousMessage.id;
				}
			}

			return null;
		}

		/**
		 * @private
		 */
		async addMessageListAbove(messageList)
		{
			logger.info('MessageRenderer.addMessageListAbove:', messageList);
			// eslint-disable-next-line no-param-reassign
			this.messageList = [...messageList, ...this.messageList];
			messageList = messageList.reverse();

			this.updateMessageIndex(messageList);
			const viewMessageList = DialogConverter.createMessageList(clone(messageList), this.dialogId);
			const viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList);

			this.putMessageIdToStackStart(viewMessageListWithTemplate);
			const packNearbyMessages = [
				this.getNextMessage(viewMessageListWithTemplate[0].id),
				...viewMessageListWithTemplate,
			];
			const viewMessageListToPush = this.processTopNearbyMessages(packNearbyMessages);

			const updateMessage = viewMessageListToPush.shift();
			await this.updateViewMessages([updateMessage]);
			await this.view.pushMessages(viewMessageListToPush);

			viewMessageListWithTemplate.forEach((message) => {
				this.viewMessageCollection[message.id] = message;
			});

			if (this.isHistoryLimitExceeded())
			{
				await this.pushPlanLimitMessage();
			}
		}

		/**
		 * @desc Add a message to the end of the view message collection
		 * @param {Array<MessagesModelState>} messageList
		 * @private
		 */
		async addMessageListBelow(messageList)
		{
			logger.info('MessageRenderer.addMessageListBelow:', messageList);
			this.updateMessageIndex(messageList);

			const viewMessageList = DialogConverter.createMessageList(clone(messageList), this.dialogId);
			const endedMessage = this.getBottomMessage();

			viewMessageList.unshift(endedMessage);
			let viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList.reverse());
			viewMessageListWithTemplate = viewMessageListWithTemplate.filter((mes) => mes.id !== endedMessage.id);
			this.putMessageIdToStack([...viewMessageListWithTemplate].reverse());

			const packAuthorPreviousMessages = this.getPackAuthorPreviousMessages(messageList[0]);
			this.messageList.push(...messageList);

			const packAuthorMessages = [...viewMessageListWithTemplate, ...packAuthorPreviousMessages];
			const viewMessageListToAdd = this.processBottomNearbyMessages(packAuthorMessages);
			if (packAuthorPreviousMessages.length > 0)
			{
				const updateMessageList = viewMessageListToAdd.slice(viewMessageListWithTemplate.length);
				await this.updateViewMessages(updateMessageList);
				const addMessageList = viewMessageListToAdd.slice(0, viewMessageListWithTemplate.length);
				await this.view.addMessages(addMessageList);
				viewMessageListWithTemplate.forEach((message) => {
					this.viewMessageCollection[message.id] = message;
				});
			}
			else
			{
				await this.view.addMessages(viewMessageListToAdd);
				viewMessageListWithTemplate.forEach((message) => {
					this.viewMessageCollection[message.id] = message;
				});
			}
		}

		/**
		 * @param {Map<number, Array<MessagesModelState>>} messagesMap
		 */
		async addMessagesBetween(messagesMap)
		{
			for (let [pointedMessageId, messageList] of messagesMap.entries())
			{
				logger.info('MessageRenderer.addMessageListBetween:', pointedMessageId, messageList);

				const viewMessageList = DialogConverter.createMessageList(clone(messageList), this.dialogId);
				const referenceMessage = this.viewMessageCollection[pointedMessageId];
				const nextMessage = this.getRealNextMessage(pointedMessageId);
				const maybeNextMessage = this.getNextMessage(pointedMessageId); // maybe date or unread separator

				viewMessageList.unshift(referenceMessage);
				viewMessageList.push(nextMessage);

				let viewMessageListWithTemplate = this.addTemplateMessagesToList(viewMessageList.reverse()).reverse();

				viewMessageListWithTemplate = viewMessageListWithTemplate.filter((message) => {
					return message.id !== referenceMessage.id && message.id !== nextMessage.id;
				});

				if (nextMessage.id !== maybeNextMessage.id) // To be separator
				{
					const { after, before } = this.sliceBetweenPackMessagesBySeparator(maybeNextMessage.id, viewMessageListWithTemplate, messageList);

					if (before.length > 0 && after.length === 0)
					{
						viewMessageListWithTemplate = before;
					}
					else if (before.length === 0 && after.length > 0)
					{
						viewMessageListWithTemplate = after;
						pointedMessageId = maybeNextMessage.id;
					}
					else
					{
						return this.addMessagesBetween(new Map([
							[pointedMessageId, before
								.map((message) => messageList
									.find((modelMessage) => String(message.id) === String(modelMessage.id))),
							],
							[before[before.length - 1].id, after
								.map((message) => messageList
									.find((modelMessage) => String(message.id) === String(modelMessage.id))),
							],
						]));
					}
				}

				if (!this.putMessagesToMessageListById(pointedMessageId, messageList))
				{
					logger.error('We could not find the reference message either in the MessageList or in the stack. Messages will not be inserted', pointedMessageId, messageList);

					continue;
				}

				this.putMessagesIdToStackById(pointedMessageId, [...viewMessageListWithTemplate]);

				this.updateMessageIndex(messageList);

				const packAuthorPreviousMessages = this.getPackAuthorPreviousMessages(messageList[0]);

				if (packAuthorPreviousMessages.length > 0)
				{
					viewMessageListWithTemplate.reverse();
					const packAuthorMessages = [
						this.getNextMessage(viewMessageListWithTemplate[0].id),
						...viewMessageListWithTemplate,
						...packAuthorPreviousMessages,
					];

					const viewMessageListToAdd = this.processBottomNearbyMessages(packAuthorMessages);
					void viewMessageListToAdd.shift();
					if (packAuthorPreviousMessages.length > 0)
					{
						const updateMessageList = viewMessageListToAdd.slice(viewMessageListWithTemplate.length);
						await this.updateViewMessages(updateMessageList);
						const addMessageList = viewMessageListToAdd.slice(0, viewMessageListWithTemplate.length);
						await this.view.insertMessages(pointedMessageId, addMessageList, 'below');
						viewMessageListWithTemplate.forEach((message) => {
							this.viewMessageCollection[message.id] = message;
						});
					}
					else
					{
						await this.view.insertMessages(pointedMessageId, viewMessageListToAdd, 'below');
						viewMessageListWithTemplate.forEach((message) => {
							this.viewMessageCollection[message.id] = message;
						});
					}

					continue;
				}

				const packNearbyMessages = [
					this.getNextMessage(viewMessageListWithTemplate[viewMessageListWithTemplate.length - 1].id),
					...viewMessageListWithTemplate,
				];

				const viewMessageListToPush = this.processTopNearbyMessages([...packNearbyMessages]);

				const updateMessage = viewMessageListToPush.shift();
				await this.updateViewMessages([updateMessage]);
				await this.view.insertMessages(pointedMessageId, [...viewMessageListToPush].reverse(), 'below');

				viewMessageListWithTemplate.forEach((message) => {
					this.viewMessageCollection[message.id] = message;
				});
			}
		}

		/**
		 *
		 * @param {string} separatorId
		 * @param {Array<Message>} messageList
		 * @return {{before: Array<Message>, after: Array<Message>}}
		 */
		sliceBetweenPackMessagesBySeparator(separatorId, messageList)
		{
			const separatorIndex = messageList.findIndex((message) => message.id === separatorId);

			return {
				before: messageList.slice(0, separatorIndex),
				after: messageList.slice(separatorIndex + 1),
			};
		}

		/**
		 * @desc force update view message by list
		 * @param {Array<Message>} viewMessageList
		 * @private
		 */
		async updateViewMessages(viewMessageList)
		{
			const updateMessagesObj = {};
			for (const message of viewMessageList)
			{
				let messageObj = message;
				if (message.type === MessageType.audio)
				{
					messageObj = this.updatePlayingTimeByMessage(message);
				}

				updateMessagesObj[messageObj.id] = messageObj;
				this.viewMessageCollection[messageObj.id] = messageObj;
				await this.view.updateMessageListById(messageObj.id, messageObj);
			}

			await this.view.updateMessagesByIds(updateMessagesObj);
		}

		/**
		 * @desc update view message by id
		 * @private
		 * @param {number} id
		 * @param {Message} message
		 * @param {string | null} section
		 * @return {Promise}
		 */
		async #updateMessageById(id, message, section)
		{
			let messageObj = message;
			if (message.type === MessageType.audio)
			{
				messageObj = this.updatePlayingTimeByMessage(message);
			}

			await this.view.updateMessageById(id, messageObj, section);
		}

		/**
		 * @param {AudioMessage} message
		 * @return {AudioMessage}
		 */
		updatePlayingTimeByMessage(message)
		{
			if (!message.getIsPlaying())
			{
				return message;
			}

			const currentPlayingTime = this.view.getPlayingTime(message.id);
			message.setPlayingTime(currentPlayingTime);

			return message;
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
				(id) => id === currentModelMessage.id
					|| id === String(currentModelMessage.id) || id === currentModelMessage.templateId,
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
			const modelMessageIdStr = String(modelMessageId);
			const indexMessage = this.messageIdsStack.findIndex(
				(id) => id === modelMessageId || id === modelMessageIdStr || id === modelMessageTemplateId,
			);

			if (indexMessage === -1)
			{
				return [];
			}

			const centerMessage = this.viewMessageCollection[modelMessageId]
				|| this.viewMessageCollection[modelMessageIdStr]
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

			// if current message id === idAfterUnreadSeparatorMessage, then don`t find the oldest message
			if (String(modelMessageId) === this.idAfterUnreadSeparatorMessage
				|| modelMessageTemplateId === this.idAfterUnreadSeparatorMessage)
			{
				return packMessage;
			}

			for (let i = indexMessage - 1; i >= 0; i--) // get oldest messages
			{
				if (modelMessageId === this.messageIdsStack[i] || modelMessageIdStr === this.messageIdsStack[i])
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
				return this.viewMessageCollection[MessageIdType.templateSeparatorUnread];
			}

			const messageModel = this.getMessageModelByAnyId(currentMessageId);
			if (!messageModel)
			{
				return null;
			}

			const indexId = this.messageIdsStack.findIndex(
				(id) => id === currentMessageId
					|| id === String(currentMessageId)
					|| id === messageModel.id
					|| id === messageModel.templateId,
			);

			return indexId === -1 ? null : this.viewMessageCollection[this.messageIdsStack[indexId - 1]];
		}

		getRealPreviousMessage(currentMessageId)
		{
			const currentMessageIndex = this.messageIdsStack.indexOf(String(currentMessageId));

			if (currentMessageIndex === -1)
			{
				return null;
			}

			/** @type{Message || null} */
			let result = null;

			for (const messageId of this.messageIdsStack.slice(0, currentMessageIndex).reverse())
			{
				const message = this.viewMessageCollection[messageId];
				if (
					!message
					|| message instanceof DateSeparatorMessage
					|| message instanceof UnreadSeparatorMessage
				)
				{
					continue;
				}

				result = message;
				break;
			}

			return result;
		}

		/**
		 * @desc Returns next message by id
		 * @param {number} currentMessageId
		 * @return {Message|null|undefined}
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
				(id) => id === currentMessageId
					|| id === String(currentMessageId)
					|| id === messageModel.id
					|| id === messageModel.templateId,
			);

			if (indexMessage === -1)
			{
				return null;
			}

			const nextMessage = this.viewMessageCollection[this.messageIdsStack[indexMessage + 1]];
			if (nextMessage && String(nextMessage.id) === this.idAfterUnreadSeparatorMessage)
			{
				return this.viewMessageCollection[MessageIdType.templateSeparatorUnread];
			}

			return nextMessage;
		}

		/**
		 * @param currentMessageId
		 * @return {Message|null}
		 */
		getRealNextMessage(currentMessageId)
		{
			const currentMessageIndex = this.messageIdsStack.indexOf(String(currentMessageId));

			if (currentMessageIndex === -1)
			{
				return null;
			}

			/** @type{Message || null} */
			let result = null;

			for (const messageId of this.messageIdsStack.slice(currentMessageIndex + 1))
			{
				const message = this.viewMessageCollection[messageId];
				if (
					!message
					|| message instanceof DateSeparatorMessage
					|| message instanceof UnreadSeparatorMessage
				)
				{
					continue;
				}

				result = message;
				break;
			}

			return result;
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
		 * @param {Array<MessagesModelState>} messageList
		 * @param {string} section
		 * @param {boolean} skipCheckEquality
		 */
		async updateMessageList(messageList, section = null, skipCheckEquality = false)
		{
			logger.info('MessageRenderer.updateMessageList:', messageList);

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
					const messageIdsStackIndex = this.messageIdsStack.indexOf(message.templateId);// TODO add if
					this.messageIdsStack.splice(messageIdsStackIndex, 1, String(message.id));
				}
			});

			const dialog = this.store.getters['dialoguesModel/getById'](this.dialogId);
			const options = DialogConverter.prepareSharedOptionsForMessages(dialog);

			for (const messageListItem of messageList)
			{
				const viewMessageItem = DialogConverter.createMessage(messageListItem, options);
				const packAuthorMessages = this.getPackAuthorNeighborMessages(messageListItem);
				const indexPackMessage = packAuthorMessages.findIndex(
					(message) => message.id === viewMessageItem.id || message.id === messageListItem.templateId,
				);

				const formattedMessage = this.processNearbyMessages(
					packAuthorMessages[indexPackMessage - 1] || this.getPreviousMessage(messageListItem.id),
					viewMessageItem,
					packAuthorMessages[indexPackMessage + 1] || this.getNextMessage(messageListItem.id),
				);

				if (
					this.viewMessageCollection[formattedMessage.id] instanceof Message
					&& (skipCheckEquality || !isEqual(formattedMessage, this.viewMessageCollection[formattedMessage.id]))
				)
				{
					logger.log(`${this.constructor.name}.updateMessageList update message by id`, formattedMessage.id);

					await this.#updateMessageById(formattedMessage.id, formattedMessage, section);
					this.viewMessageCollection[formattedMessage.id] = formattedMessage;
				}

				if (
					messageListItem.templateId.length > 0
					&& this.viewMessageCollection[messageListItem.templateId] instanceof Message
					&& !isEqual(formattedMessage, this.viewMessageCollection[messageListItem.templateId])
				)
				{
					logger.log(`${this.constructor.name}.updateMessageList update message by template id`, messageListItem.templateId);

					await this.#updateMessageById(messageListItem.templateId, formattedMessage, section);

					if (Type.isNumber(messageListItem.id))
					{
						logger.log(
							`${this.constructor.name}.updateMessageList replace templateId with id in viewMessageCollection`,
							messageListItem.templateId,
							messageListItem.id,
						);

						this.viewMessageCollection[messageListItem.id] = formattedMessage;

						delete this.viewMessageCollection[messageListItem.templateId];
					}
					else
					{
						this.viewMessageCollection[messageListItem.templateId] = formattedMessage;
					}
				}

				if (this.isHistoryLimitExceeded() && !this.isHasPrevMorePage())
				{
					await this.pushPlanLimitMessage();
				}
			}
		}

		/**
		 * @desc Start check for double message with templateId and delete it
		 * @param {Array} messageList
		 * @private
		 */
		async killDoubleMessage(messageList)
		{
			for (const message of messageList)
			{
				if (message.templateId && message.id)
				{
					const doubleInMessageIdsStack = this.messageIdsStack.filter(
						(messId) => messId === message.templateId || messId === message.id || messId === String(message.id),
					);

					if (doubleInMessageIdsStack.length > 1 && this.viewMessageCollection[message.templateId])
					{
						await this.view.removeMessagesByIds([message.templateId]);
						logger.warn('MessageRenderer.killDoubleMessage: founded', doubleInMessageIdsStack);
						await this.delete([message.templateId]);
					}
				}
			}
		}

		/**
		 *
		 * @param {Array<MessagesModelState>} messageList
		 * @returns {Promise<void>}
		 */
		async killUploadingMessages(messageList)
		{
			const messageListToDelete = [];
			for (const message of messageList)
			{
				if (!Type.isNumber(message.id))
				{
					continue;
				}

				if (!this.uploadingMessageCollection.has(message.templateId))
				{
					continue;
				}

				const messageIndex = this.messageIdsStack.indexOf(message.templateId);
				if (messageIndex === -1)
				{
					continue;
				}

				const messageListFromCurrentToBottom = this.messageIdsStack.slice(messageIndex, this.messageIdsStack.length);
				if (
					Type.isArrayFilled(messageListFromCurrentToBottom)
					&& !messageListFromCurrentToBottom.every((messageId) => this.uploadingMessageCollection.has(messageId))
				)
				{
					messageListToDelete.push(message.templateId);
				}

				this.uploadingMessageCollection.delete(message.templateId);
			}

			if (Type.isArrayFilled(messageListToDelete))
			{
				await this.delete(messageListToDelete);
			}
		}

		/**
		 * @param {Array<MessagesModelState>} messageList
		 */
		markUploadingMessages(messageList)
		{
			for (const message of messageList)
			{
				if (!Uuid.isV4(message.id))
				{
					continue;
				}

				if (this.store.getters['messagesModel/isUploadingMessage'](message.id))
				{
					this.uploadingMessageCollection.add(message.id);
				}
			}
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
		 * @param {Array<Message>} messageList
		 * @private
		 */
		putMessageIdToStack(messageList)
		{
			messageList.forEach((message) => {
				if (!message.id.includes(MessageIdType.templateSeparatorUnread))
				{
					this.messageIdsStack.push(message.id);
				}
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
		 * @param {string || number} pointedId
		 * @param {Array<Message>} messageList
		 */
		putMessagesIdToStackById(pointedId, messageList)
		{
			const indexId = this.messageIdsStack.findIndex((id) => id === pointedId || Number(id) === pointedId);
			if (indexId === -1)
			{
				return false;
			}

			const messagesId = messageList
				.map((message) => message.id)
				.filter((messageId) => !messageId.includes(MessageIdType.templateSeparatorUnread))
			;

			this.messageIdsStack.splice(indexId + 1, 0, ...messagesId);

			return true;
		}

		/**
		 *
		 * @param {string || number} pointedId
		 * @param {Array<MessagesModelState>} messageList
		 * @return {boolean}
		 */
		putMessagesToMessageListById(pointedId, messageList)
		{
			let indexId = this.messageList.findIndex((message) => message.id === pointedId || Number(message.id) === pointedId);
			if (indexId === -1)
			{
				const stackMessageIndex = this.messageIdsStack.indexOf(pointedId); // if id is separator
				if (stackMessageIndex === -1)
				{
					return false;
				}

				pointedId = Number(this.messageIdsStack[stackMessageIndex - 1]);

				indexId = this.messageList.findIndex((message) => message.id === pointedId || Number(message.id) === pointedId);
			}

			this.messageList.splice(indexId + 1, 0, ...messageList);

			return true;
		}

		/**
		 * @private
		 * @return {Array<Message>}
		 */
		addTemplateMessagesToList(messageList)
		{
			const messageListWithTemplate = [];
			const dialogModelState = this.getDialog();
			messageList.reverse().forEach((message, index, messageList) => {
				if (Type.isNil(message))
				{
					return;
				}
				const isOldestMessage = index === 0;
				if (isOldestMessage)
				{
					const oldestMessage = this.getMessage(message.id);
					if (!oldestMessage)
					{
						return;
					}

					const isUnread = !Uuid.isV4(message.id) && dialogModelState.lastReadId < oldestMessage.id;
					if (
						isUnread
						&& oldestMessage.unread
						&& dialogModelState.lastReadId !== 0
						&& this.unreadSeparatorAdded === false
					)
					{
						logger.warn(`${this.constructor.name}: Unread separator displayed by isOldestMessage branch`);
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
						const dateSeparatorSystemMessage = this.getSeparator(newestMessage.date);
						messageListWithTemplate.push(dateSeparatorSystemMessage);
					}

					const isUnread = !Uuid.isV4(newestMessage.id) && dialogModelState.lastReadId < newestMessage.id && newestMessage.unread;
					if (
						isUnread
						&& dialogModelState.lastReadId !== 0
						&& this.unreadSeparatorAdded === false
					)
					{
						logger.warn(`${this.constructor.name}: Unread separator displayed by isNewestMessage branch`);
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

				const isReadPrevious = (!Uuid.isV4(previousMessage.id)
					&& dialogModelState.lastReadId >= previousMessage.id)
					|| this.uploadingMessageCollection.has(previousMessage.id)
				;
				const isUnreadCurrent = !Uuid.isV4(currentMessage.id)
					&& dialogModelState.lastReadId < currentMessage.id
					&& currentMessage.unread;

				if (
					isReadPrevious
					&& isUnreadCurrent
					&& dialogModelState.lastReadId !== 0
					&& this.unreadSeparatorAdded === false
				)
				{
					logger.warn(`${this.constructor.name}: Unread separator displayed by isReadPrevious and isUnreadCurrent branch`);
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
				}

				messageListWithTemplate.push(message);
			});

			messageListWithTemplate.forEach((messageTemplate) => {
				if (messageTemplate.type === MessageType.systemText)
				{
					this.prepareSystemMessage(messageTemplate);
				}

				if (messageTemplate.type === MessageType.unsupported)
				{
					this.prepareUnsupportedMessage(messageTemplate);
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
					const nextMessage = list[index + 1] || this.getNextMessage(message?.id);

					return this.processNearbyMessages(previousMessage, message, nextMessage);
				})
				.reverse()
			;
		}

		/**
		 * @private
		 * @return {Array<Message>}
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
			const dialogModelState = this.getDialog();
			const dialogType = dialogModelState?.type;
			const isPrivateDialog = dialogType === DialogType.user || dialogType === DialogType.private;
			if (!previousMessage)
			{
				message.setAuthorTopMessage(false);
				message.setAuthorBottomMessage(false);

				const previousModelMessage = this.getMessage(previousMessage?.id);
				const modelMessage = this.getMessage(message?.id);
				const nextModelMessage = this.getMessage(nextMessage?.id);

				/** margins block */
				this.setMargins(previousModelMessage, modelMessage, nextModelMessage, message);

				/** avatar block */
				if (nextModelMessage?.authorId === modelMessage?.authorId)
				{
					message.setShowAvatar(modelMessage, false);
					message.setAuthorBottomMessage(false);
					message.setAuthorTopMessage(false);
				}

				if (
					!Type.isNil(nextModelMessage?.authorId)
					&& nextModelMessage?.authorId !== modelMessage?.authorId
					&& (dialogModelState.parentMessageId !== 0 && modelMessage.id !== dialogModelState.parentMessageId)
					// scenario for comments chat when initial message is your
				)
				{
					message.setShowAvatar(modelMessage, true);
				}

				if (!nextMessage)
				{
					message.setAuthorTopMessage(true);
					message.setAuthorBottomMessage(true);
				}

				if (nextModelMessage?.authorId !== modelMessage?.authorId)
				{
					message.setAuthorBottomMessage(true);
				}

				if (message.type === MessageType.systemText)
				{
					this.prepareSystemMessage(message);
				}

				if (isPrivateDialog)
				{
					this.preparePrivateMessage(message, modelMessage);
				}

				if (message.type === MessageType.unsupported)
				{
					this.prepareUnsupportedMessage(message);
				}

				return message;
			}

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

				if (message.type === MessageType.systemText)
				{
					this.prepareSystemMessage(message);
				}

				if (message.type === MessageType.unsupported)
				{
					this.prepareUnsupportedMessage(message);
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

			const isYourMessage = modelMessage.authorId === serviceLocator.get('core').getUserId();
			if (isYourMessage)
			{
				message.setShowAvatar(modelMessage, false);
			}

			if (isPrivateDialog)
			{
				this.preparePrivateMessage(message, modelMessage);
			}

			if (message.type === MessageType.systemText)
			{
				this.prepareSystemMessage(message);
			}

			if (message.type === MessageType.unsupported)
			{
				this.prepareUnsupportedMessage(message);
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
		 * @desc Set property for unsupported message with type 'unsupported'
		 * @param {UnsupportedMessage} message
		 * @private
		 */
		prepareUnsupportedMessage(message)
		{
			message.setAvatarUnsupportedMessage();
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
				previousModelMessage?.authorId !== modelMessage?.authorId
				&& nextModelMessage?.authorId !== modelMessage?.authorId
			) // for alone message
			{
				message.setMarginTop(4);
				message.setMarginBottom(4);
			}

			if (
				previousModelMessage?.authorId !== modelMessage?.authorId
				&& nextModelMessage?.authorId === modelMessage?.authorId
			) // for first message in group
			{
				message.setMarginTop(4);
				message.setMarginBottom(0);
			}

			if (
				previousModelMessage?.authorId === modelMessage?.authorId
				&& nextModelMessage?.authorId === modelMessage?.authorId
			) // for message of the middle
			{
				message.setMarginTop(4);
				message.setMarginBottom(0);
			}

			if (
				previousModelMessage?.authorId === modelMessage?.authorId
				&& nextModelMessage?.authorId !== modelMessage?.authorId
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
			if (Uuid.isV4(messageId))
			{
				return this.store.getters['messagesModel/getByTemplateId'](messageId) ?? {};
			}

			return this.store.getters['messagesModel/getById'](messageId);
		}

		/**
		 * @desc Find model by id or templateId from store or current context
		 * @param {String|Number} messageId
		 * @return {MessagesModelState|null}
		 * @private
		 */
		getMessageModelByAnyId(messageId)
		{
			let modelMessage = this.store.getters['messagesModel/getById'](messageId);
			if (Type.isUndefined(modelMessage) || !('id' in modelMessage)) // getMessageById may returns {}
			{
				modelMessage = this.store.getters['messagesModel/getByTemplateId'](messageId);
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
				(idFromStack) => idFromStack === messageModel.id
					|| idFromStack === String(messageModel.id)
					|| idFromStack === messageModel.templateId,
			);
			this.messageIdsStack = this.messageIdsStack.filter((el, index) => index !== indexDeletedMessage);
		}

		deleteIdFromStackById(messageId)
		{
			this.messageIdsStack = this.messageIdsStack.filter((id) => id !== messageId);
		}

		/**
		 * @private
		 * @return {DialoguesModelState}
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
			const id = `${MessageIdType.templateSeparatorDate}-${this.toDateCode(date)}`;

			return new DateSeparatorMessage(id, date);
		}

		nextTick(callback)
		{
			if (!Type.isFunction(callback))
			{
				throw new TypeError('RecentRenderer.nextTick: callback must be a function');
			}

			this.nextTickCallbackList.push(callback);
		}

		doTick()
		{
			this.nextTickCallbackList.forEach((callback) => callback());
			this.nextTickCallbackList = [];
		}

		/**
		 * @return {boolean}
		 */
		isHistoryLimitExceeded()
		{
			return DialogHelper.createByModel(this.getDialog())?.isHistoryLimitExceeded;
		}

		/**
		 * @return {PlanLimitsBanner}
		 */
		getPlanLimitMessage()
		{
			const messageBanner = {
				text: MessageParams.ComponentId.PlanLimitsMessage,
				id: MessageIdType.planLimitBanner,
				authorId: 0,
				message: [],
				files: [],
				attach: [],
				reactions: null,
				params: {
					componentId: MessageParams.ComponentId.PlanLimitsMessage,
				},
			};

			return DialogConverter.createMessage(messageBanner);
		}

		/**
		 * @return {boolean}
		 */
		isHasPlanLimitMessage()
		{
			return this.view.isHasPlanLimitMessage();
		}

		/**
		 * @desc remove the limit banner.
		 * This is necessary due to asynchronous data retrieval and
		 * asynchronous rendering of the native (the native does not have time).
		 * @deprecated
		 * @return {Promise<boolean>}
		 */
		async removePlanLimitMessage()
		{
			logger.log(`${this.constructor.name}.removePlanLimitMessage`);

			return this.view.removeMessagesByIds([MessageIdType.planLimitBanner]);
		}

		/**
		 * @return {boolean}
		 */
		isPlanMessageOnTop()
		{
			const topMessage = this.view.getTopMessage();

			return Boolean(topMessage && topMessage.id === MessageIdType.planLimitBanner);
		}

		/**
		 * @return {boolean}
		 */
		isHasPrevMorePage()
		{
			const dialog = this.getDialog();

			return dialog && dialog.hasPrevPage;
		}

		/**
		 * @return {Promise<boolean>}
		 */
		async pushPlanLimitMessage()
		{
			if (this.isPlanMessageOnTop()) // banner on top stack, do nothing
			{
				return;
			}

			if (this.isHasPlanLimitMessage()) // banner into stack, but on top, remove
			{
				await this.removePlanLimitMessage();
			}

			logger.log(`${this.constructor.name}.pushPlanLimitMessage`);
			await this.view.pushMessages([this.getPlanLimitMessage()]);
			this.sendAnalyticsIsHistoryLimitExceeded(this.dialogId, Analytics.Section.chatHistory);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {string} section
		 */
		sendAnalyticsIsHistoryLimitExceeded(dialogId, section)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			const dialogType = dialog.type;
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.limitBanner)
				.setEvent(Analytics.Event.view)
				.setType(Analytics.Type.limitOfficeChatingHistory)
				.setSection(section)
				.setP1(Analytics.P1[dialogType]);

			analytics.send();
		}
	}

	module.exports = {
		MessageRenderer,
	};
});
