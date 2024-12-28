/**
 * @module im/messenger/provider/service/classes/message/action
 */
jn.define('im/messenger/provider/service/classes/message/action', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Loc } = require('loc');
	const { Logger } = require('im/messenger/lib/logger');
	const { clone } = require('utils/object');
	const { Counters } = require('im/messenger/lib/counters');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { runAction } = require('im/messenger/lib/rest');
	const { EventType, RestMethod, DialogType } = require('im/messenger/const');
	const { QueueService } = require('im/messenger/provider/service/queue');

	class ActionService
	{
		constructor()
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
			/** @type {QueueService} */
			this.queueServiceInstanse = null;
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
				this.fullDeleteMessage(modelMessage, dialogId)
					.catch((error) => Logger.error(error));

				return;
			}

			await this.saveMessage(modelMessage);

			this.#localDelete(modelMessage, dialogId)
				.then(() => this.deleteRequest(modelMessage.id))
				.catch((errors) => {
					Logger.error('ActionService.delete.deleteRequest.catch: ', errors);

					this.restoreMessage(modelMessage.id, dialogId);
				})
				.finally(() => this.deleteTemporaryMessage(modelMessage.id))
				.catch((errors) => Logger.error('ActionService.delete.deleteTemporaryMessage.catch: ', errors))
			;
		}

		async #localDelete(modelMessage, dialogId)
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](dialogId);

			switch (dialogModel.type)
			{
				case DialogType.comment:
				{
					return this.updateMessage(
						modelMessage,
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
					return this.fullDeleteMessage(modelMessage, dialogId)
						.catch((error) => Logger.error(error))
					;
				}

				default:
				{
					if (this.isViewedByOtherUsers(modelMessage))
					{
						return this.updateMessage(
							modelMessage,
							Loc.getMessage('IMMOBILE_PULL_HANDLER_MESSAGE_DELETED'),
							dialogId,
							true,
						)
							.catch((error) => Logger.error(error))
						;
					}

					return this.fullDeleteMessage(modelMessage, dialogId).catch((r) => Logger.error(r));
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
			await this.saveMessage(getMessageStateModel);

			const clientDonePromise = this.updateMessage(getMessageStateModel, text, dialogId).catch((r) => Logger.error(r));

			clientDonePromise
				.then(() => this.updateRequest(getMessageStateModel.id, text))
				.catch((errors) => {
					Logger.error('ActionService.updateText.updateRequest.catch: ', errors);

					this.restoreMessage(getMessageStateModel.id, dialogId);
				})
				.finally(() => this.deleteTemporaryMessage(getMessageStateModel.id))
				.catch((errors) => Logger.error('ActionService.updateText.deleteTemporaryMessage.catch: ', errors))
			;
		}

		/**
		 * @desc call a rest request delete message
		 * @param {number|string} messageId
		 * @return {Promise}
		 */
		deleteRequest(messageId)
		{
			const deleteData = {
				id: messageId,
			};
			if (this.isAvailableInternet())
			{
				return runAction(RestMethod.imV2ChatMessageDelete, { data: deleteData });
			}

			return this.queueService.putRequest(
				RestMethod.imV2ChatMessageDelete,
				deleteData,
				1,
				messageId,
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
				messageId,
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
		 * @param {number|string} messageId
		 */
		deleteTemporaryMessage(messageId)
		{
			this.store.dispatch('messagesModel/deleteTemporaryMessage', { id: messageId })
				.catch((errors) => {
					Logger.error('ActionService.deleteTemporaryMessage from store error: ', errors);
				});
		}

		/**
		 * @desc save a message in local or vuex store
		 * @param {MessagesModelState} modelMessage
		 * @return {Promise}
		 */
		saveMessage(modelMessage)
		{
			return this.saveMessageToVuexStore(modelMessage).catch(
				(err) => Logger.error('ActionService.saveMessage messagesModel catch', err),
			);
		}

		/**
		 * @desc save a message in vuex store
		 * @param {MessagesModelState} modelMessage
		 */
		async saveMessageToVuexStore(modelMessage)
		{
			await this.store.dispatch('messagesModel/setTemporaryMessages', modelMessage);
		}

		/**
		 * @desc update a message and recent
		 * @param {MessagesModelState} modelMessage
		 * @param {string} dialogId
		 * @param {string} text
		 * @param {boolean} [isDeleted=false]
		 */
		async updateMessage(modelMessage, text, dialogId, isDeleted = false)
		{
			if (!modelMessage.id)
			{
				return;
			}

			const params = isDeleted
				? { ...modelMessage.params, IS_DELETED: 'Y', ATTACH: [] } : modelMessage.params;
			await this.store.dispatch('messagesModel/update', {
				id: modelMessage.id,
				fields: {
					text,
					params,
					files: modelMessage.files,
				},
			});

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			const messageRecentData = clone(modelMessage);
			if (recentItem.message.id === modelMessage.id)
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
		 * @desc call full delete message and recent for store
		 * @param {MessagesModelState} modelMessage
		 * @param {string} dialogId
		 */
		async fullDeleteMessage(modelMessage, dialogId)
		{
			const messages = this.store.getters['messagesModel/getByChatId'](modelMessage.chatId);

			if (!Type.isArrayFilled(messages))
			{
				return;
			}

			const lastMessage = messages.length > 1 ? messages[messages.length - 1] : null;
			const newLastMessage = messages.length > 2 ? messages[messages.length - 2] : null;

			this.store.dispatch('messagesModel/delete', { id: modelMessage.id })
				.catch((err) => Logger.error('ActionService.fullDeleteMessage messagesModel catch', err))
			;

			if (lastMessage?.id !== modelMessage.id)
			{
				return;
			}

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			let newRecentItem = recentItem;
			if (recentItem.uploadingState?.message?.id === modelMessage.id)
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
				return;
			}

			const dialogItem = this.store.getters['dialoguesModel/getById'](dialogId);
			await this.updateDialog(
				dialogItem,
				{
					message: {
						senderId: newLastMessage.authorId,
						id: newLastMessage.id,
					},
				},
			);

			try
			{
				await this.store.dispatch('recentModel/set', [newRecentItem])
					.then(() => {
						this.saveShareDialogCache();
					})
				;
			}
			catch (error)
			{
				Logger.error(`${this.constructor.name}.fullDeleteMessage.recentModel/set.catch:`, error);
			}
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
			return this.store.dispatch('recentModel/set', [recentItem]);
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
			const firstPage = this.store.getters['recentModel/getRecentPage'](1, 50);
			ShareDialogCache.saveRecentItemList(firstPage)
				.then((cache) => {
					Logger.log('ActionService: Saving recent items for the share dialog is successful.', cache);
				})
				.catch((cache) => {
					Logger.log('ActionService: Saving recent items for share dialog failed.', firstPage, cache);
				})
			;
		}

		/**
		 * @param {string|numberOriginal} messageId
		 * @param {string|numberOriginal} dialogId
		 */
		restoreMessage(messageId, dialogId)
		{
			const tempMessage = this.store.getters['messagesModel/getTemporaryMessageById'](messageId);
			if (!tempMessage)
			{
				return false;
			}

			if (this.isViewedByOtherUsers(tempMessage))
			{
				return this.store.dispatch('messagesModel/update', {
					id: messageId,
					fields: {
						...tempMessage,
					},
				});
			}

			return this.store.dispatch('messagesModel/add', tempMessage)
				.then(() => {
					const scrollToBottomEventData = {
						dialogId,
						withAnimation: true,
						force: true,
					};

					BX.postComponentEvent(EventType.dialog.external.scrollToBottom, [scrollToBottomEventData]);
				})
				.catch((ex) => Logger.error('ActionService.restoreMessage.add error', ex));
		}
	}

	module.exports = { ActionService };
});
