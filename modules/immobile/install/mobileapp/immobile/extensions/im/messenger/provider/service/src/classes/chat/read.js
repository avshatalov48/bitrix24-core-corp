/**
 * @module im/messenger/provider/service/classes/chat/read
 */
jn.define('im/messenger/provider/service/classes/chat/read', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { RestMethod } = require('im/messenger/const/rest');
	const { Counters } = require('im/messenger/lib/counters');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');

	const READ_TIMEOUT = 300;

	/**
	 * @class ReadService
	 */
	class ReadService
	{
		constructor(store)
		{
			/**
			 * @private
			 * @type{MessengerCoreStore}
			 */
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
					queueChatId = Number(queueChatId);
					Logger.warn('ReadService: readMessages', messageIds);
					if (messageIds.size === 0)
					{
						return;
					}

					const copiedMessageIds = [...messageIds];
					delete this.messagesToRead[queueChatId];

					this.readMessagesOnClient(queueChatId, copiedMessageIds)
						.then((readMessagesCount) => {
							Logger.warn('ReadService: readMessage, need to reduce counter by', readMessagesCount);

							return this.decreaseChatCounter(queueChatId, readMessagesCount);
						})
						.then(() => {
							return this.updateMessengerCounters();
						})
						.catch((error) => {
							Logger.error('ReadService: error reading message', error);
						})
					;

					this.readMessageOnServer(queueChatId, copiedMessageIds);
				});
			}, READ_TIMEOUT);
		}

		/**
		 * @private
		 */
		readMessagesOnClient(chatId, messageIds)
		{
			const maxMessageId = Math.max(...messageIds);
			const dialog = this.store.getters['dialoguesModel/getByChatId'](chatId);
			if (maxMessageId > dialog.lastReadId)
			{
				this.store.dispatch('dialoguesModel/update', {
					dialogId: dialog.dialogId,
					fields: {
						lastId: maxMessageId,
					},
				});
			}

			return this.store.dispatch('messagesModel/readMessages', {
				chatId,
				messageIds,
			});
		}

		/**
		 * @private
		 */
		async decreaseChatCounter(chatId, readMessagesCount, lastId = null)
		{
			const isDecreaseComplete = await this.store.dispatch('dialoguesModel/decreaseCounter', {
				dialogId: this.getDialogIdByChatId(chatId),
				count: readMessagesCount,
				lastId,
			});

			if (isDecreaseComplete)
			{
				const recentItem = this.store.getters['recentModel/getById'](this.getDialogIdByChatId(chatId));

				await this.store.dispatch('recentModel/set', [{
					...recentItem,
					counter: recentItem.counter - readMessagesCount,
				}]);

				MessengerEmitter.emit(EventType.messenger.renderRecent);
			}

			return isDecreaseComplete;
		}

		async updateMessengerCounters()
		{
			Counters.update();
		}

		/**
		 * @private
		 */
		readMessageOnServer(chatId, messageIds)
		{
			Logger.warn('ReadService: readMessages on server', messageIds);

			BX.rest.callMethod(
				RestMethod.imV2ChatMessageRead,
				{
					chatId,
					ids: messageIds,
				},
				(data) => {
					const { counter, lastId } = data.data();

					this.decreaseChatCounter(chatId, counter, lastId)
						.then((needUpdateCounters) => {
							if (needUpdateCounters)
							{
								this.updateMessengerCounters();
							}
						})
					;
				},
			);
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
