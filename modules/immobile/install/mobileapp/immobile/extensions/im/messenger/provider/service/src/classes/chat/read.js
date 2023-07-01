/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/provider/service/classes/chat/read
 */
jn.define('im/messenger/provider/service/classes/chat/read', (require, exports, module) => {

	const { Logger } = require('im/messenger/lib/logger');
	const { RestManager } = require('im/messenger/lib/rest-manager');
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
			this._messagesToRead = {};
		}

		readMessage(chatId, messageId)
		{
			if (!this._messagesToRead[chatId])
			{
				this._messagesToRead[chatId] = new Set();
			}
			this._messagesToRead[chatId].add(messageId);

			clearTimeout(this.readTimeout);
			this.readTimeout = setTimeout(() => {
				Object.entries(this._messagesToRead).forEach(([queueChatId, messageIds]) => {
					queueChatId = +queueChatId;
					Logger.warn('ReadService: readMessages', messageIds);
					if (messageIds.size === 0)
					{
						return;
					}

					const copiedMessageIds = [...messageIds];
					delete this._messagesToRead[queueChatId];

					this._readMessagesOnClient(queueChatId, copiedMessageIds).then((readMessagesCount) => {
						Logger.warn('ReadService: readMessage, need to reduce counter by', readMessagesCount);
						return this._decreaseChatCounter(queueChatId, readMessagesCount);
					}).then(() => {
						return this._readMessageOnServer(queueChatId, copiedMessageIds);
					}).catch(error => {
						console.error('ReadService: error reading message', error);
					});
				});
			}, READ_TIMEOUT);
		}

		_readMessagesOnClient(chatId, messageIds)
		{
			return this.store.dispatch('messagesModel/readMessages', {
				chatId,
				messageIds
			});
		}

		_decreaseChatCounter(chatId, readMessagesCount)
		{
			return this.store.dispatch('dialoguesModel/decreaseCounter', {
				dialogId: this._getDialogIdByChatId(chatId),
				count: readMessagesCount
			});
		}

		_readMessageOnServer(chatId, messageIds)
		{
			Logger.warn('ReadService: readMessages on server', messageIds);
			return BX.rest.callMethod(RestMethod.imV2ChatMessageRead, {
				chatId,
				ids: messageIds
			});
		}

		_getDialogIdByChatId(chatId)
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
