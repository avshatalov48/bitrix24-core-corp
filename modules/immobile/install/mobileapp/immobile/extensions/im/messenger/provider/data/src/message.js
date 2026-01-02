/* eslint-disable consistent-return */
/**
 * @module im/messenger/provider/data/message
 */
jn.define('im/messenger/provider/data/message', (require, exports, module) => {
	const { BaseDataProvider } = require('im/messenger/provider/data/base');
	const { MessageDeleter } = require('im/messenger/provider/data/message/deleter');
	const { ChatDataProvider } = require('im/messenger/provider/data/chat');

	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('data-provider--message');

	/**
	 * @class MessageDataProvider
	 */
	class MessageDataProvider extends BaseDataProvider
	{
		#deleter = new MessageDeleter();

		/**
		 * @desc performs full data cleaning of messages from the database and partial data cleaning from the model
		 * @param {?DialogId} dialogId
		 * @param {?number} chatId
		 */
		async deleteByChat({ dialogId = null, chatId = null })
		{
			logger.log(`${this.constructor.name}.clearHistory`, { dialogId, chatId });

			const chatData = await this.#getDialogData({ chatId, dialogId });
			if (chatData.hasData())
			{
				await this.#deleter.deleteByChat(chatData.getData());
			}
		}

		/**
		 * @param {?number} chatId
		 * @param {?DialogId} dialogId
		 * @returns {Promise<DataProviderResult<DialoguesModelState>>}
		 */
		async #getDialogData({ chatId, dialogId })
		{
			return new ChatDataProvider().get({ chatId, dialogId });
		}
	}

	module.exports = { MessageDataProvider };
});
