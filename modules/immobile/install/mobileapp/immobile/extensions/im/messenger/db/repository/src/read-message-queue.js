/**
 * @module im/messenger/db/repository/read-message-queue
 */
jn.define('im/messenger/db/repository/read-message-queue', (require, exports, module) => {
	const { Type } = require('type');
	const { getLogger } = require('im/messenger/lib/logger');

	const {
		ReadMessageQueueTable,
	} = require('im/messenger/db/table');

	const logger = getLogger('repository--read-message-queue');

	/**
	 * @class ReadMessageQueueRepository
	 */
	class ReadMessageQueueRepository
	{
		constructor()
		{
			this.queueTable = new ReadMessageQueueTable();
		}

		/**
		 * @return {Promise<{[p: number]: Set<number>}>}
		 */
		async getQueue()
		{
			const { items } = await this.queueTable.getList({});

			/** @type {{[chatId: number]: Set<number>}} */
			const result = {};
			items.forEach((item) => {
				if (!result[item.chatId])
				{
					result[item.chatId] = new Set();
				}

				result[item.chatId].add(item.messageId);
			});

			return result;
		}

		/**
		 * @param {Array<number>} messageList
		 * @param {number} chatId
		 * @return {Promise<void>}
		 */
		async addMessagesToQueue(messageList, chatId)
		{
			const dataToSave = messageList.map((messageId) => {
				return {
					messageId,
					chatId,
				};
			});

			if (!Type.isArrayFilled(dataToSave))
			{
				return;
			}

			try
			{
				await this.queueTable.add(dataToSave);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}: queueTable.add error:`, error);
			}
		}

		/**
		 * @param {Array<number>} messageList
		 * @returns {Promise<void>}
		 */
		async deleteMessagesFromQueue(messageList)
		{
			return this.queueTable.deleteByIdList(messageList);
		}

		/**
		 * @param {Array<number>} chatIdList
		 * @returns {Promise<void>}
		 */
		async deleteChatsFromQueue(chatIdList)
		{
			try
			{
				await this.queueTable.deleteByChatIdList(chatIdList);
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}: queueTable.deleteChatsFromQueue error:`, error);
			}
		}

		/**
		 * @param {number} chatId
		 * @returns {Promise<void>}
		 */
		async deleteByChatId(chatId)
		{
			try
			{
				await this.queueTable.delete({ chatId });
			}
			catch (error)
			{
				logger.error(`${this.constructor.name}: queueTable.deleteByChatId error:`, error);
			}
		}

		/**
		 * @returns {Promise<*>}
		 */
		async clearQueue()
		{
			return this.queueTable.truncate();
		}
	}

	module.exports = { ReadMessageQueueRepository };
});
