/**
 * @module im/messenger/provider/service/classes/chat/read
 */
jn.define('im/messenger/provider/service/classes/chat/read', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { RestMethod, DialogType } = require('im/messenger/const');
	const { Counters } = require('im/messenger/lib/counters');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');
	const { UuidManager } = require('im/messenger/lib/uuid-manager');

	const logger = LoggerManager.getInstance().getLogger('read-service--chat');
	const READ_TIMEOUT = 300;

	/**
	 * @class ReadService
	 */
	class ReadService
	{
		constructor()
		{
			/**
			 * @private
			 * @type{MessengerCoreStore}
			 */
			this.store = serviceLocator.get('core').getStore();

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
					logger.warn('ReadService: readMessages', messageIds);
					if (messageIds.size === 0)
					{
						return;
					}

					const copiedMessageIds = [...messageIds];
					delete this.messagesToRead[queueChatId];

					this.readMessagesOnClient(queueChatId, copiedMessageIds)
						// TODO local counters
						// .then((readMessagesCount) => {
						// 	logger.warn('ReadService: readMessage, need to reduce counter by', readMessagesCount);
						//
						// 	return this.decreaseChatCounter(queueChatId, readMessagesCount);
						// })
						// .then(() => {
						// 	return this.updateMessengerCounters();
						// })
						.catch((error) => {
							logger.error('ReadService: error reading message', error);
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

		/**
		 * @private
		 */
		readMessageOnServer(chatId, messageIds)
		{
			logger.warn('ReadService.readMessageOnServer: ', messageIds);

			const messageReadData = {
				chatId,
				ids: messageIds,
				actionUuid: UuidManager.getInstance().getActionUuid(),
			};

			return runAction(RestMethod.imV2ChatMessageRead, { data: messageReadData })
				.then((/** @type {{counter: number, chatId: number, lastId: number, viewedMessages: Array<number>}} */ data) => {
					const dialogId = this.getDialogIdByChatId(data.chatId);
					this.updateDialogCounters(dialogId, data.counter)
						.then(() => this.updateRecentCounters(dialogId, data.counter))
						.then(() => this.updateMessengerCounters())
						.catch((error) => logger.error('ReadService.readMessageOnServer: error when updating models', error))
					;
				})
				.catch((errors) => {
					logger.error('ReadService.readMessageOnServer error:', errors);
				})
			;
		}

		async updateDialogCounters(dialogId, counter)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);
			if (dialog.type === DialogType.comment)
			{
				await this.store.dispatch('commentModel/setCounters', {
					[dialog.parentChatId]: {
						[dialog.chatId]: counter,
					},
				});
			}

			return this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					counter,
				},
			});
		}

		async updateRecentCounters(dialogId, counter)
		{
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			if (dialog.type === DialogType.comment)
			{
				dialogId = `chat${dialog.parentChatId}`;
			}

			const recentItem = this.store.getters['recentModel/getById'](dialogId);

			if (!recentItem)
			{
				return;
			}

			await this.store.dispatch('recentModel/set', [{
				...recentItem,
				counter,
			}]);

			MessengerEmitter.emit(EventType.messenger.renderRecent);
		}

		async updateMessengerCounters()
		{
			Counters.update();
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
