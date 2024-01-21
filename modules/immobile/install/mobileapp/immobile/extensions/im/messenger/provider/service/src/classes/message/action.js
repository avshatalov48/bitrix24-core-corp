/**
 * @module im/messenger/provider/service/classes/message/action
 */
jn.define('im/messenger/provider/service/classes/message/action', (require, exports, module) => {
	const { core } = require('im/messenger/core');
	const { Loc } = require('loc');
	const { Logger } = require('im/messenger/lib/logger');
	const { clone } = require('utils/object');
	const { Counters } = require('im/messenger/lib/counters');
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');
	const { runAction } = require('im/messenger/lib/rest');
	const { RestMethod } = require('im/messenger/const/rest');
	const { EventType } = require('im/messenger/const');
	const { QueueService } = require('im/messenger/provider/service/queue');

	class ActionService
	{
		constructor()
		{
			this.store = core.getStore();
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
			await this.saveMessage(modelMessage);

			let clientDonePromise;
			if (this.isViewedByOthersUsers(modelMessage))
			{
				// eslint-disable-next-line no-param-reassign
				modelMessage.text = Loc.getMessage('IMMOBILE_PULL_HANDLER_MESSAGE_DELETED');

				clientDonePromise = this.updateMessage(modelMessage, modelMessage.text, dialogId, true)
					.catch((r) => Logger.error(r));
			}
			else
			{
				clientDonePromise = this.fullDeleteMessage(modelMessage, dialogId).catch((r) => Logger.error(r));
			}

			clientDonePromise
				.then(() => this.deleteRequest(modelMessage.id))
				.catch((errors) => {
					Logger.error('ActionService.delete error: ', errors);

					this.restoreMessage(modelMessage.id, dialogId);
				})
				.finally(() => this.deleteTemporaryMessage(modelMessage.id))
			;
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
					Logger.error('ActionService.update error: ', errors);

					this.restoreMessage(getMessageStateModel.id, dialogId);
				})
				.finally(() => this.deleteTemporaryMessage(getMessageStateModel.id))
			;
		}

		/**
		 * @desc call rest request delete message
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
		 * @desc save message in local or vuex store
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
		 * @desc save message in vuex store
		 * @param {MessagesModelState} modelMessage
		 */
		async saveMessageToVuexStore(modelMessage)
		{
			await this.store.dispatch('messagesModel/setTemporaryMessages', modelMessage);
		}

		/**
		 * @desc update message and recent
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
			this.store.dispatch('messagesModel/delete', { id: modelMessage.id })
				.catch((err) => Logger.error('ActionService.fullDeleteMessage messagesModel catch', err));

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			const dialogItem = this.store.getters['dialoguesModel/getById'](dialogId);
			const messages = this.store.getters['messagesModel/getByChatId'](modelMessage.chatId);
			const newLastMessage = messages.length > 1 ? messages[messages.length - 1] : null;
			if (newLastMessage)
			{
				recentItem.message = {
					text: newLastMessage.text,
					date: newLastMessage.date,
					author_id: newLastMessage.authorId,
					id: newLastMessage.id,
					file: newLastMessage.files ? (newLastMessage.files.length > 0) : false,
				};

				recentItem.date_update = new Date();
			}
			else
			{
				return;
			}

			await this.updateDialog(
				dialogItem,
				{
					message: {
						senderId: newLastMessage.authorId,
						id: newLastMessage.id,
					},
					counter: dialogItem.counter === 0 ? 0 : dialogItem.counter - 1,
				},
			);

			this.store.dispatch('recentModel/set', [recentItem])
				.then(() => {
					Counters.update();

					this.saveShareDialogCache();
				})
				.catch((err) => Logger.error('ActionService.fullDeleteMessage recentModel store catch', err))
			;
		}

		/**
		 * @desc update dialog store and clear views
		 * @param {DialoguesModelState} dialogItem
		 * @param {MessagePullHandlerUpdateDialogParams} params
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

			dialogFieldsToUpdate.counter = params.counter;

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
		 * @desc is have views on message
		 * @param {MessagesModelState} modelMessage
		 */
		isViewedByOthersUsers(modelMessage)
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

			if (this.isViewedByOthersUsers(tempMessage))
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
