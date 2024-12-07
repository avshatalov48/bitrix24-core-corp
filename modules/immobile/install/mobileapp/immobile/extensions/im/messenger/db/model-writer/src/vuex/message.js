/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/message
 */
jn.define('im/messenger/db/model-writer/vuex/message', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');

	const { Logger } = require('im/messenger/lib/logger');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class MessageWriter extends Writer
	{
		initRouters()
		{
			super.initRouters();
			this.storeRouter = this.storeRouter.bind(this);
		}

		subscribeEvents()
		{
			this.storeManager
				.on('messagesModel/setChatCollection', this.addRouter)
				.on('messagesModel/store', this.storeRouter)
				.on('messagesModel/update', this.updateRouter)
				.on('messagesModel/updateWithId', this.updateWithIdRouter)
				.on('messagesModel/delete', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('messagesModel/setChatCollection', this.addRouter)
				.off('messagesModel/store', this.storeRouter)
				.off('messagesModel/update', this.updateRouter)
				.off('messagesModel/updateWithId', this.updateWithIdRouter)
				.off('messagesModel/delete', this.deleteRouter)
			;
		}

		/**
		 * @param {MutationPayload<MessagesSetChatCollectionData, MessagesSetChatCollectionActions>} mutation.payload
		 */
		async addRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'setChatCollection',
				'add',
			];

			if (!saveActions.includes(actionName))
			{
				return;
			}

			await this.#saveMessageData(data);
		}

		/**
		 * @param {MutationPayload<MessagesStoreData, MessagesStoreActions>} mutation.payload
		 */
		async storeRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const saveActions = [
				'storeToLocalDatabase',
			];

			if (!saveActions.includes(actionName))
			{
				return;
			}

			await this.#saveMessageData(data);
		}

		/**
		 * @param {MutationPayload<MessagesUpdateData, MessagesUpdateActions>} mutation.payload
		 */
		async updateRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const updateActions = [
				'update',
				'readMessages',
				'setViewedByOthers',
				'deleteAttach',
			];
			if (!updateActions.includes(actionName))
			{
				return;
			}

			const messageId = data.id;
			const message = this.store.getters['messagesModel/getById'](messageId);
			if (!message || !message.id)
			{
				Logger.warn(`MessageWriter.updateRouter: there is no message with id "${messageId}" in model`);

				return;
			}

			if (!Type.isNumber(message.id))
			{
				return;
			}

			const chatId = message.chatId;
			const dialog = this.store.getters['dialoguesModel/getByChatId'](chatId);
			if (DialogType.comment === dialog?.type)
			{
				return;
			}

			await this.repository.message.saveFromModel([message])
				.catch((error) => Logger.error('MessageWriter.updateRouter.saveFromModel.catch:', error))
			;
		}

		/**
		 * @param {MutationPayload<MessagesUpdateWithIdData, MessagesUpdateWithIdActions>} mutation.payload
		 */
		async updateWithIdRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const updateActions = [
				'updateWithId',
			];
			if (!updateActions.includes(actionName))
			{
				return;
			}

			const messageId = data.fields?.id;
			const message = this.store.getters['messagesModel/getById'](messageId);
			if (!message || !message.id)
			{
				Logger.warn(`MessageWriter.updateWithIdRouter: there is no message with id "${messageId}" in model`);

				return;
			}

			if (!Type.isNumber(message.id))
			{
				return;
			}

			const chatId = message.chatId;
			const dialogHelper = DialogHelper.createByChatId(chatId);
			if (!dialogHelper?.isLocalStorageSupported)
			{
				return;
			}

			await this.repository.message.saveFromModel([message])
				.catch((error) => Logger.error('MessageWriter.updateWithIdRouter.saveFromModel.catch:', error));
		}

		/**
		 * @param {MutationPayload<MessagesDeleteData, MessagesDeleteActions>} mutation.payload
		 */
		async deleteRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const deleteActions = [
				'delete',
				'deleteByIdList',
			];
			if (!deleteActions.includes(actionName))
			{
				return;
			}

			const messageId = data.id;
			if (messageId)
			{
				await this.repository.message.deleteByIdList([data.id]);
			}
		}

		async #saveMessageData(data)
		{
			if (!Type.isArrayFilled(data.messageList))
			{
				return;
			}

			const messageList = [];
			data.messageList.forEach((message) => {
				const modelMessage = this.store.getters['messagesModel/getById'](message.id);

				const chatId = modelMessage.chatId;

				const dialogHelper = DialogHelper.createByChatId(chatId);
				if (!dialogHelper?.isLocalStorageSupported)
				{
					return;
				}

				if (modelMessage && modelMessage.id && Type.isNumber(modelMessage.id))
				{
					messageList.push(modelMessage);
				}
			});

			if (!Type.isArrayFilled(messageList))
			{
				return;
			}

			await this.repository.message.saveFromModel(messageList)
				.catch((error) => Logger.error('MessageWriter.addRouter.saveFromModel.catch:', error));
		}
	}

	module.exports = {
		MessageWriter,
	};
});
