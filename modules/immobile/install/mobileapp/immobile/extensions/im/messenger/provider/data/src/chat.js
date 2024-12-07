/* eslint-disable consistent-return */
/**
 * @module im/messenger/provider/data/chat
 */
jn.define('im/messenger/provider/data/chat', (require, exports, module) => {
	const { Type } = require('type');
	const { ChatGetter } = require('im/messenger/provider/data/chat/getter');
	const { ChatDeleter } = require('im/messenger/provider/data/chat/deleter');
	const { BaseDataProvider } = require('im/messenger/provider/data/base');
	const { DataProviderResult } = require('im/messenger/provider/data/result');

	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('data-provider--chat');

	/**
	 * @class ChatDataProvider
	 */
	class ChatDataProvider extends BaseDataProvider
	{
		#getter = new ChatGetter();
		#deleter = new ChatDeleter();

		get className()
		{
			return this.constructor.name;
		}

		/**
		 * @desc get chat data from a model or database
		 * @param {?DialogId} dialogId
		 * @param {?number} chatId
		 * @return {Promise<DataProviderResult<DialoguesModelState>>}
		 */
		async get({ dialogId = null, chatId = null })
		{
			if (!Type.isNil(dialogId))
			{
				return this.#getter.getByDialogId(dialogId);
			}

			if (!Type.isNil(chatId))
			{
				return this.#getter.getByChatId(chatId);
			}

			logger.error(`${this.className}.get error: too few arguments`);

			return new DataProviderResult();
		}

		/**
		 * @desc performs a complete cleaning of chat data from the database and a partial one from the model
		 * @param {?DialogId} dialogId
		 * @param {?number} chatId
		 */
		async delete({ dialogId = null, chatId = null })
		{
			logger.log(`${this.constructor.name}.delete`, { dialogId, chatId });

			if (!Type.isNil(dialogId))
			{
				return this.#deleter.deleteByDialogId(dialogId);
			}

			if (!Type.isNil(chatId))
			{
				return this.#deleter.deleteByChatId(chatId);
			}

			logger.error(`${this.className}.delete error: too few arguments`);
		}

		/**
		 * @param {'model' | 'database'} source
		 * @param {?DialogId} dialogId
		 * @param {?number} chatId
		 */
		async deleteFromSource(source, { dialogId = null, chatId = null })
		{
			logger.log(`${this.constructor.name}.deleteFromSource`, source, { dialogId, chatId });

			if (source === BaseDataProvider.source.database)
			{
				return this.#deleteFromDatabase({ dialogId, chatId });
			}

			if (source === BaseDataProvider.source.model)
			{
				return this.#deleteFromModel({ dialogId, chatId });
			}

			logger.error(`${this.className}.deleteEntityFromSource error: unknown source: ${source}`);
		}

		/**
		 * @param {?DialogId} dialogId
		 * @param {?number} chatId
		 */
		async #deleteFromModel({ dialogId = null, chatId = null })
		{
			if (!Type.isNil(dialogId))
			{
				return this.#deleter.deleteFromModelByDialogId(dialogId);
			}

			if (!Type.isNil(chatId))
			{
				return this.#deleter.deleteFromModelByChatId(chatId);
			}

			logger.error(`${this.className}.deleteFromModel error: too few arguments`);
		}

		/**
		 * @param {?DialogId} dialogId
		 * @param {?number} chatId
		 */
		async #deleteFromDatabase({ dialogId = null, chatId = null })
		{
			if (!Type.isNil(dialogId))
			{
				return this.#deleter.deleteFromDatabaseByDialogId(dialogId);
			}

			if (!Type.isNil(chatId))
			{
				return this.#deleter.deleteFromDatabaseByChatId(chatId);
			}

			logger.error(`${this.className}.deleteFromDatabase error: too few arguments`);
		}
	}

	module.exports = { ChatDataProvider };
});
