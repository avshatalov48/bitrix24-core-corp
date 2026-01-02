/**
 * @module im/messenger/provider/services/message/action
 */
jn.define('im/messenger/provider/services/message/action', (require, exports, module) => {
	/* global ChatMessengerCommon */
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Loc } = require('im/messenger/loc');
	const { Logger } = require('im/messenger/lib/logger');
	const { clone } = require('utils/object');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { runAction } = require('im/messenger/lib/rest');
	const { RestMethod, DialogType } = require('im/messenger/const');
	const { QueueService } = require('im/messenger/provider/services/queue');

	class ActionService
	{
		constructor()
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
			/** @type {QueueService} */
			this.queueServiceInstanse = null;

			this.shareDialogCache = new ShareDialogCache();
		}

		get queueService()
		{
			if (!this.queueServiceInstanse)
			{
				this.queueServiceInstanse = new QueueService();
			}

			return this.queueServiceInstanse;
		}

		/**
		 * @desc delete message
		 * @param {MessagesModelState} modelMessage
		 * @param {string} dialogId
		 */
		async delete(modelMessage, dialogId)
		{
			if (modelMessage.error)
			{
				this.fullDeleteMessage([modelMessage], dialogId)
					.catch((error) => Logger.error(error));

				return;
			}

			await this.saveMessages([modelMessage]);

			this.#localDelete([modelMessage], dialogId)
				.then(() => this.deleteRequest([modelMessage.id]))
				.catch((errors) => {
					Logger.error('ActionService.delete.deleteRequest.catch: ', errors);

					return this.restoreMessage([modelMessage.id], dialogId);
				})
				.finally(() => this.deleteTemporaryMessages([modelMessage.id]))
				.catch((errors) => Logger.error('ActionService.delete.deleteTemporaryMessages.catch: ', errors))
			;
		}

		/**
		 * @param {Array<string|number>} listId
		 * @param {string} dialogId
		 */
		async deleteByIdList(listId, dialogId)
		{
			const messageModels = this.store.getters['messagesModel/getListByIds'](listId);
			await this.saveMessages(messageModels);

			this.#localDelete(messageModels, dialogId)
				.then(() => this.deleteRequest(listId))
				.catch((error) => {
					Logger.error(`${this.constructor.name}.deleteByIdList.deleteRequest.catch: `, error);

					return this.restoreMessage(listId, dialogId);
				})
				.finally(() => this.deleteTemporaryMessages(listId))
				.catch((error) => Logger.error(`${this.constructor.name}.deleteByIdList.deleteTemporaryMessages.catch: `, error))
			;
		}

		async #localDelete(messageModels, dialogId)
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](dialogId);

			switch (dialogModel.type)
			{
				case DialogType.comment:
				{
					return this.updateMessage(
						messageModels,
						Loc.getMessage('IMMOBILE_PULL_HANDLER_MESSAGE_DELETED'),
						dialogId,
						true,
					)
						.catch((error) => Logger.error(error))
					;
				}

				case DialogType.channel:
				case DialogType.generalChannel:
				case DialogType.openChannel:
				{
					return this.fullDeleteMessage(messageModels, dialogId)
						.catch((error) => Logger.error(error))
					;
				}

				default:
				{
					const viewedMessages = [];
					const newMessages = [];
					messageModels.forEach((message) => {
						if (this.isViewedByOtherUsers(message))
						{
							return viewedMessages.push(message);
						}

						return newMessages.push(message);
					});

					if (newMessages)
					{
						await this.fullDeleteMessage(newMessages, dialogId).catch((error) => Logger.error(error));
					}

					return this.updateMessage(
						viewedMessages,
						Loc.getMessage('IMMOBILE_PULL_HANDLER_MESSAGE_DELETED'),
						dialogId,
						true,
					)
						.catch((error) => Logger.error(error))
					;
				}
			}
		}

		/**
		 * @desc update text message
		 * @param {string|number} messageId
		 * @param {string} text
		 * @param {string} dialogId
		 */
		async updateText(messageId, text, dialogId)
		{
			const getMessageStateModel = this.store.getters['messagesModel/getById'](messageId);
			await this.saveMessages([getMessageStateModel]);

			const clientDonePromise = this.updateMessage([getMessageStateModel], text, dialogId)
				.catch((r) => Logger.error(r));

			clientDonePromise
				.then(() => this.updateRequest(getMessageStateModel.id, text))
				.catch((errors) => {
					Logger.error('ActionService.updateText.updateRequest.catch: ', errors);

					return this.restoreMessage([getMessageStateModel.id], dialogId);
				})
				.finally(() => this.deleteTemporaryMessages([getMessageStateModel.id]))
				.catch((errors) => Logger.error('ActionService.updateText.deleteTemporaryMessages.catch: ', errors))
			;
		}

		/**
		 * @desc call a rest request delete message
		 * @param {Array<MessageId>} messageIdList
		 * @return {Promise}
		 */
		deleteRequest(messageIdList)
		{
			const deleteData = {
				messageIds: messageIdList,
			};
			if (this.isAvailableInternet())
			{
				return runAction(RestMethod.imV2ChatMessageDelete, { data: deleteData });
			}

			return this.queueService.putRequest(
				RestMethod.imV2ChatMessageDelete,
				deleteData,
				1,
				messageIdList,
			);
		}

		/**
		 * @desc call rest request update message
		 * @param {number|string} messageId
		 * @param {string} text
		 * @return {Promise}
		 */
		updateRequest(messageId, text)
		{
			const updateData = {
				id: messageId,
				fields: {
					message: text,
				},
			};

			if (this.isAvailableInternet())
			{
				return runAction(RestMethod.imV2ChatMessageUpdate, { data: updateData });
			}

			return this.queueService.putRequest(
				RestMethod.imV2ChatMessageUpdate,
				updateData,
				1,
				[messageId],
			);
		}

		/**
		 * @desc check is connection
		 * @return {boolean}
		 */
		isAvailableInternet()
		{
			return this.store.getters['applicationModel/getNetworkStatus']();
		}

		/**
		 * @desc delete temporary message from vuex store
		 * @param {Array<MessageId>} messageIdList
		 */
		deleteTemporaryMessages(messageIdList)
		{
			this.store.dispatch('messagesModel/deleteTemporaryMessages', { ids: messageIdList })
				.catch((errors) => {
					Logger.error('ActionService.deleteTemporaryMessages from store error: ', errors);
				});
		}

		/**
		 * @desc save a message in local or vuex store
		 * @param {Array<MessagesModelState>} modelMessages
		 * @return {Promise}
		 */
		saveMessages(modelMessages)
		{
			return this.saveMessagesToVuexStore(modelMessages).catch(
				(err) => Logger.error('ActionService.saveMessages messagesModel catch', err),
			);
		}

		/**
		 * @desc save a message in vuex store
		 * @param {Array<MessagesModelState>} modelMessages
		 */
		async saveMessagesToVuexStore(modelMessages)
		{
			await this.store.dispatch('messagesModel/setTemporaryMessages', modelMessages);
		}

		/**
		 * @desc update a message and recent
		 * @param {Array<MessagesModelState>} modelMessages
		 * @param {string} dialogId
		 * @param {string} text
		 * @param {boolean} [isDeleted=false]
		 */
		async updateMessage(modelMessages, text, dialogId, isDeleted = false)
		{
			const preparedMessageList = modelMessages.map((message) => {
				const preparedMessage = { ...message, text };
				if (isDeleted)
				{
					preparedMessage.params = { ...message.params, IS_DELETED: 'Y', ATTACH: [] };
				}

				return preparedMessage;
			});

			await this.store.dispatch('messagesModel/updateList', {
				messageList: preparedMessageList,
			});

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			const lastMessage = preparedMessageList.sort((a, b) => b.date.getTime() - a.date.getTime())[0];
			const messageRecentData = clone(lastMessage);
			if (recentItem.message.id === modelMessages[0]?.id)
			{
				messageRecentData.text = ChatMessengerCommon.purifyText(text, messageRecentData.params);
				messageRecentData.file = Array.isArray(messageRecentData.files) && messageRecentData.files.length > 0;

				recentItem.message = {
					...recentItem.message,
					...messageRecentData,
				};

				recentItem.date_update = new Date();
			}

			recentItem.writing = false;
			await this.store.dispatch('recentModel/set', [recentItem]);
		}

		/**
		 * @desc full delete messages
		 * @param {Array<MessagesModelState>} modelMessages
		 * @param {string} dialogId
		 */
		async fullDeleteMessage(modelMessages, dialogId)
		{
			const idList = modelMessages.map((message) => message.id);

			await this.store.dispatch('messagesModel/deleteByIdList', { idList })
				.catch((error) => Logger.error(`${this.constructor.name}.fullDeleteMessage catch:`, error))
			;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return false;
			}

			const currentRecentMessageId = recentItem.message?.id;
			const deletedRecentMessage = modelMessages.find((modelMessage) => {
				if (modelMessage.id === currentRecentMessageId)
				{
					return modelMessage;
				}

				return false;
			});

			if (Type.isNil(deletedRecentMessage))
			{
				return false;
			}

			const messages = this.store.getters['messagesModel/getByChatId'](modelMessages[0].chatId);
			const newLastMessage = messages.length > 1 ? messages[messages.length - 1] : null;
			let newRecentItem = recentItem;
			if (recentItem.uploadingState?.message?.id === deletedRecentMessage.id)
			{
				newRecentItem = {
					...recentItem,
					uploadingState: null,
				};
			}
			else if (newLastMessage)
			{
				newRecentItem.message = {
					text: newLastMessage.text,
					date: newLastMessage.date,
					author_id: newLastMessage.authorId,
					id: newLastMessage.id,
					file: newLastMessage.files ? (newLastMessage.files.length > 0) : false,
				};

				newRecentItem.lastActivityDate = newLastMessage.date;
			}
			else
			{
				return false;
			}

			const dialogItem = this.store.getters['dialoguesModel/getById'](dialogId);
			await this.updateDialog(
				dialogItem,
				{
					message: {
						senderId: newLastMessage?.authorId,
						id: newLastMessage?.id,
					},
				},
			);

			return this.updateRecentItem(newRecentItem);
		}

		/**
		 * @desc update dialog store and clear views
		 * @param {DialoguesModelState} dialogItem
		 * @param {Object} params
		 * @param {Object} params.message
		 * @param {Number} params.counter
		 * @return {Promise<any>}
		 */
		async updateDialog(dialogItem, params)
		{
			const dialog = dialogItem;

			if (!dialog)
			{
				return Promise.resolve(false);
			}

			const dialogFieldsToUpdate = {};
			if (params.message.id < dialog.lastMessageId)
			{
				dialogFieldsToUpdate.lastMessageId = params.message.id;
			}

			if (params.message.id < dialog.lastReadId)
			{
				dialogFieldsToUpdate.lastId = params.message.id;
			}

			if (Object.keys(dialogFieldsToUpdate).length > 0)
			{
				return this.store.dispatch('dialoguesModel/update', {
					dialogId: dialogItem.dialogId,
					fields: dialogFieldsToUpdate,
				}).then(() => this.store.dispatch('dialoguesModel/clearLastMessageViews', {
					dialogId: dialogItem.dialogId,
				}));
			}

			return Promise.resolve(false);
		}

		/**
		 * @param {RecentModelState} recentItem
		 * @returns {Promise<any>}
		 */
		async updateRecentItem(recentItem)
		{
			try
			{
				await this.store.dispatch('recentModel/set', [recentItem])
					.then(() => {
						this.saveShareDialogCache();
					})
				;
			}
			catch (error)
			{
				Logger.error(`${this.constructor.name}.updateRecentItem.recentModel/set.catch:`, error);
			}
		}

		/**
		 * @desc is have views on a message
		 * @param {MessagesModelState} modelMessage
		 */
		isViewedByOtherUsers(modelMessage)
		{
			return modelMessage.viewedByOthers;
		}

		saveShareDialogCache()
		{
			this.shareDialogCache.saveRecentItemListThrottled();
		}

		/**
		 * @param {Array<MessageId>} messageIdList
		 * @param {DialogId} dialogId
		 */
		async restoreMessage(messageIdList, dialogId)
		{
			const tempMessages = this.store.getters['messagesModel/getTemporaryMessagesByIdList'](messageIdList);
			if (!Type.isArrayFilled(tempMessages))
			{
				return false;
			}

			const updateMessages = [];
			const addMessages = [];
			tempMessages.forEach((message) => {
				// eslint-disable-next-line no-param-reassign
				message.params = { ...message.params, IS_DELETED: 'N' };
				if (this.isViewedByOtherUsers(message))
				{
					return updateMessages.push(message);
				}

				return addMessages.push(message);
			});

			if (updateMessages.length > 0)
			{
				await this.store.dispatch('messagesModel/updateList', {
					messageList: updateMessages,
				});
			}

			if (addMessages.length > 0)
			{
				await this.store.dispatch('messagesModel/addList', { messageList: addMessages });
			}

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return false;
			}

			const messages = this.store.getters['messagesModel/getByChatId'](tempMessages[0].chatId);
			const newLastMessage = messages.length > 1 ? messages[messages.length - 1] : null;
			const newRecentItem = recentItem;
			if (newLastMessage)
			{
				newRecentItem.message = {
					text: newLastMessage.text,
					date: newLastMessage.date,
					author_id: newLastMessage.authorId,
					id: newLastMessage.id,
					file: newLastMessage.files ? (newLastMessage.files.length > 0) : false,
				};

				newRecentItem.lastActivityDate = newLastMessage.date;
			}
			else
			{
				return false;
			}

			return this.updateRecentItem(newRecentItem);
		}
	}

	module.exports = { ActionService };
});
