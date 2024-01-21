/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/message
 */
jn.define('im/messenger/db/model-writer/vuex/message', (require, exports, module) => {
	const { Type } = require('type');

	const { Logger } = require('im/messenger/lib/logger');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class MessageWriter extends Writer
	{
		initRouters()
		{
			super.initRouters();
			this.deleteByChatIdRouter = this.deleteByChatIdRouter.bind(this);
		}

		subscribeEvents()
		{
			this.storeManager
				.on('messagesModel/setChatCollection', this.addRouter)
				.on('messagesModel/update', this.updateRouter)
				.on('messagesModel/updateWithId', this.updateWithIdRouter)
				.on('messagesModel/deleteByChatId', this.deleteByChatIdRouter)
				.on('messagesModel/delete', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('messagesModel/setChatCollection', this.addRouter)
				.off('messagesModel/update', this.updateRouter)
				.off('messagesModel/updateWithId', this.updateWithIdRouter)
				.off('messagesModel/deleteByChatId', this.deleteByChatIdRouter)
				.off('messagesModel/delete', this.deleteRouter)
			;
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		addRouter(mutation)
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

			if (!Type.isArrayFilled(data.messageList))
			{
				return;
			}

			const messageList = [];
			data.messageList.forEach((message) => {
				const modelMessage = this.store.getters['messagesModel/getById'](message.id);
				if (modelMessage && modelMessage.id && Type.isNumber(modelMessage.id))
				{
					messageList.push(modelMessage);
				}
			});

			if (!Type.isArrayFilled(messageList))
			{
				return;
			}

			this.repository.message.saveFromModel(messageList);
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		updateRouter(mutation)
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

			this.repository.message.saveFromModel([message]);
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		updateWithIdRouter(mutation)
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

			this.repository.message.saveFromModel([message]);
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		deleteRouter(mutation)
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
				this.repository.message.deleteByIdList([data.id]);
			}
		}

		/**
		 * @param {MutationPayload} mutation.payload
		 */
		deleteByChatIdRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}

			const actionName = mutation?.payload?.actionName;
			const data = mutation?.payload?.data || {};
			const deleteActions = [
				'deleteByChatId',
			];
			if (!deleteActions.includes(actionName))
			{
				return;
			}

			const chatId = data.chatId;
			if (chatId)
			{
				this.repository.message.deleteByChatId(chatId);
			}
		}
	}

	module.exports = {
		MessageWriter,
	};
});
