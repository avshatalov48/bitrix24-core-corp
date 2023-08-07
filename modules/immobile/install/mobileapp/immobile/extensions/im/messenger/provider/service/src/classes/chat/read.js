/**
 * @module im/messenger/provider/service/classes/chat/read
 */
jn.define('im/messenger/provider/service/classes/chat/read', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { RestMethod } = require('im/messenger/const/rest');

	const READ_TIMEOUT = 300;

	/**
	 * @class ReadService
	 */
	class ReadService
	{
		constructor(store)
		{
			this.store = store;
			/** @private */
			this.messagesToRead = {};
		}

		readMessage(chatId, messageId)
		{
			if (!this.messagesToRead[chatId])
			{
				this.messagesToRead[chatId] = new Set();
			}
			this.messagesToRead[chatId].add(messageId);

			clearTimeout(this.readTimeout);
			this.readTimeout = setTimeout(() => {
				Object.entries(this.messagesToRead).forEach(([queueChatId, messageIds]) => {
					// eslint-disable-next-line no-param-reassign
					queueChatId = +queueChatId;
					Logger.warn('ReadService: readMessages', messageIds);
					if (messageIds.size === 0)
					{
						return;
					}

					const copiedMessageIds = [...messageIds];
					delete this.messagesToRead[queueChatId];

					this.readMessagesOnClient(queueChatId, copiedMessageIds).then((readMessagesCount) => {
						Logger.warn('ReadService: readMessage, need to reduce counter by', readMessagesCount);

						return this.decreaseChatCounter(queueChatId, readMessagesCount);
					}).then(() => {
						return this.readMessageOnServer(queueChatId, copiedMessageIds);
					}).catch((error) => {
						Logger.error('ReadService: error reading message', error);
					});
				});
			}, READ_TIMEOUT);
		}

		/**
		 * @private
		 */
		readMessagesOnClient(chatId, messageIds)
		{
			return this.store.dispatch('messagesModel/readMessages', {
				chatId,
				messageIds,
			});
		}

		/**
		 * @private
		 */
		decreaseChatCounter(chatId, readMessagesCount)
		{
			return this.store.dispatch('dialoguesModel/decreaseCounter', {
				dialogId: this.getDialogIdByChatId(chatId),
				count: readMessagesCount,
			});
		}

		/**
		 * @private
		 */
		readMessageOnServer(chatId, messageIds)
		{
			Logger.warn('ReadService: readMessages on server', messageIds);

			return BX.rest.callMethod(RestMethod.imV2ChatMessageRead, {
				chatId,
				ids: messageIds,
			});
		}

		/**
		 * @private
		 */
		getDialogIdByChatId(chatId)
		{
			const dialog = this.store.getters['dialoguesModel/getByChatId'](chatId);
			if (!dialog)
			{
				return 0;
			}

			return dialog.dialogId;
		}
	}

	module.exports = {
		ReadService,
	};
});
