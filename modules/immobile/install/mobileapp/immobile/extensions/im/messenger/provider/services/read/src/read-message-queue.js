/**
 * @module im/messenger/provider/services/read/read-message-queue
 */
jn.define('im/messenger/provider/services/read/read-message-queue', (require, exports, module) => {
	const { Type } = require('type');
	const { isEmpty } = require('utils/object');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const MESSAGE_CHUNK_SIZE = 100;

	/**
	 * @class ReadMessageQueue
	 */
	class ReadMessageQueue
	{
		/** @type {{[chatId: number]: Set<number>}} */
		#localQueue;
		#readMessageRepository = serviceLocator.get('core').getRepository().readMessageQueue;
		#inited = false;

		async #init()
		{
			this.#localQueue = await this.#readMessageRepository.getQueue();

			this.#inited = true;
		}

		async isEmpty()
		{
			if (!this.#inited)
			{
				await this.#init();
			}

			return isEmpty(this.#localQueue);
		}

		/**
		 * @returns {Promise<{
		 * chatId: number,
		 * messageList: Array<number>,
		 * hasMoreForChat: boolean,
		 * }>}
		 */
		async getMessageChunk()
		{
			if (!this.#inited)
			{
				await this.#init();
			}

			const chatIdList = Object.keys(this.#localQueue);

			const chatId = chatIdList[0];

			const messageList = [...this.#localQueue[chatId]]
				.sort((a, b) => a - b)
				.slice(0, MESSAGE_CHUNK_SIZE)
			;

			const hasMoreForChat = messageList.length !== this.#localQueue[chatId].size;

			return {
				chatId: Number(chatId),
				messageList,
				hasMoreForChat,
			};
		}

		async getMaxMessageId(chatId)
		{
			if (!this.#inited)
			{
				await this.#init();
			}

			if (!this.#localQueue[chatId])
			{
				return 0;
			}

			return Math.max(...this.#localQueue[chatId]);
		}

		/**
		 * @param {Array<number>} messageList
		 * @param {number} chatId
		 * @returns {Promise<void>}
		 */
		async addMessagesToQueue({ messageList, chatId })
		{
			if (!this.#inited)
			{
				await this.#init();
			}

			if (!Type.isNumber(chatId))
			{
				return;
			}

			if (!this.#localQueue[chatId])
			{
				this.#localQueue[chatId] = new Set();
			}

			messageList.forEach((messageId) => {
				this.#localQueue[chatId].add(messageId);
			});

			await this.#readMessageRepository.addMessagesToQueue(messageList, chatId);
		}

		/**
		 * @param {Array<number>} messageList
		 * @param {number} chatId
		 * @returns {Promise<void>}
		 */
		async deleteMessages({ messageList, chatId })
		{
			if (!this.#inited)
			{
				await this.#init();
			}

			const chatMessageSet = this.#localQueue[chatId];
			messageList.forEach((messageId) => {
				chatMessageSet.delete(messageId);
			});

			if (chatMessageSet.size === 0)
			{
				delete this.#localQueue[chatId];
			}

			return this.#readMessageRepository.deleteMessagesFromQueue(messageList);
		}

		/**
		 * @param {Array<number>} chatIdList
		 * @returns {Promise<void>}
		 */
		async deleteChats(chatIdList)
		{
			if (!this.#inited)
			{
				await this.#init();
			}
			const chatIdListToDeleteFromDb = [];
			chatIdList.forEach((chatId) => {
				if (this.#localQueue[chatId])
				{
					delete this.#localQueue[chatId];
					chatIdListToDeleteFromDb.push(chatId);
				}
			});

			this.#readMessageRepository.deleteChatsFromQueue(chatIdListToDeleteFromDb);
		}
	}

	module.exports = { ReadMessageQueue };
});
