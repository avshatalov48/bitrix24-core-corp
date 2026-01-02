/**
 * @module im/messenger/provider/services/chat/read
 */
jn.define('im/messenger/provider/services/chat/read', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');
	const {
		ComponentCode,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');

	const logger = getLogger('read-service--chat');
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

			/**
			 * @private
			 * @type {Record<number, Set<number>>}
			 */
			this.messagesToRead = {};
		}

		/**
		 * @param {number} chatId
		 * @param {string} messageId
		 */
		readMessage(chatId, messageId)
		{
			if (!this.messagesToRead[chatId])
			{
				this.messagesToRead[chatId] = new Set();
			}
			this.messagesToRead[chatId].add(Number(messageId));

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
					const lastReadId = this.#getLastReadId(queueChatId);
					const unreadMessageList = this.#getUnreadMessageIdList(copiedMessageIds);

					this.#readMessagesOnClient(queueChatId, copiedMessageIds);
					this.sendMessagesToReadService({
						messageIdList: copiedMessageIds,
						chatId: queueChatId,
						lastReadId,
						unreadMessageList,
					});
				});
			}, READ_TIMEOUT);
		}

		/**
		 * @param {number} chatId
		 * @param {Array<number>} messageIds
		 * @returns {Promise<any>}
		 */
		#readMessagesOnClient(chatId, messageIds)
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
		 * @param {number} chatId
		 * @returns {number}
		 */
		#getLastReadId(chatId)
		{
			const dialog = this.store.getters['dialoguesModel/getByChatId'](chatId);

			return dialog.lastReadId;
		}

		/**
		 * @param {Array<number>} messageIdList
		 * @return {Array<number>}
		 */
		#getUnreadMessageIdList(messageIdList)
		{
			return this.store.getters['messagesModel/getListByIds'](messageIdList)
				.filter((message) => message.unread === true)
				.map((message) => message.id)
			;
		}

		sendMessagesToReadService({
			messageIdList,
			chatId,
			lastReadId,
			unreadMessageList,
		})
		{
			if (!Feature.isMessengerV2Enabled)
			{
				MessengerEmitter.emit(
					EventType.dialog.internal.readMessages,
					{
						messageIdList,
						chatId,
						lastReadId,
						unreadMessageList,
					},
					ComponentCode.imNavigation,
				);

				return;
			}

			serviceLocator.get('read-service').readMessages({
				messageIdList,
				chatId,
				lastReadId,
				unreadMessageList,
			}).catch((error) => {
				logger.error('ReadService.sendMessagesToReadService error', error);
			});
		}
	}

	module.exports = {
		ReadService,
	};
});
