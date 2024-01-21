/* eslint-disable es/no-optional-chaining */

/**
 * @module im/messenger/db/model-writer/vuex/temp-message
 */
jn.define('im/messenger/db/model-writer/vuex/temp-message', (require, exports, module) => {
	const { Type } = require('type');
	const { Writer } = require('im/messenger/db/model-writer/vuex/writer');

	class TempMessageWriter extends Writer
	{
		subscribeEvents()
		{
			this.storeManager
				.on('messagesModel/setTemporaryMessages', this.addRouter)
				.on('messagesModel/deleteTemporaryMessage', this.deleteRouter)
				.on('messagesModel/deleteTemporaryMessages', this.deleteRouter)
			;
		}

		unsubscribeEvents()
		{
			this.storeManager
				.off('messagesModel/setTemporaryMessages', this.addRouter)
				.off('messagesModel/deleteTemporaryMessage', this.deleteRouter)
				.off('messagesModel/deleteTemporaryMessages', this.deleteRouter)
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

			const data = mutation?.payload?.data || {};
			if (!Type.isArrayFilled(data.messageList))
			{
				return;
			}

			const messageList = [];
			data.messageList.forEach((message) => {
				const modelMessage = this.store.getters['messagesModel/getById'](message.id);
				if (modelMessage && modelMessage.id)
				{
					messageList.push(modelMessage);
				}
			});

			if (!Type.isArrayFilled(messageList))
			{
				return;
			}

			this.repository.tempMessage.saveFromModel(messageList);
		}

		deleteRouter(mutation)
		{
			if (this.checkIsValidMutation(mutation) === false)
			{
				return;
			}
			const data = mutation?.payload.data || {};
			let ids;
			if (mutation?.payload?.actionName === 'deleteTemporaryMessages')
			{
				ids = data.ids;
			}
			else
			{
				ids = [data.id];
			}

			if (ids.length > 0)
			{
				this.repository.tempMessage.deleteByIdList(ids);
			}
		}
	}

	module.exports = {
		TempMessageWriter,
	};
});
