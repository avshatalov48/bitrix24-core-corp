/* eslint-disable consistent-return */
/* eslint-disable no-param-reassign */
/**
 * @module im/messenger/provider/data/recent
 */
jn.define('im/messenger/provider/data/recent', (require, exports, module) => {
	const { Type } = require('type');
	const { RecentDeleter } = require('im/messenger/provider/data/recent/deleter');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const { BaseDataProvider } = require('im/messenger/provider/data/base');
	const { ChatDataProvider } = require('im/messenger/provider/data/chat');

	const logger = LoggerManager.getInstance().getLogger('data-provider--recent');

	/**
	 * @class RecentDataProvider
	 */
	class RecentDataProvider extends BaseDataProvider
	{
		#deleter = new RecentDeleter();

		get className()
		{
			return this.constructor.name;
		}

		/**
		 * @param {?DialogId} dialogId
		 * @param {?number} chatId
		 */
		async delete({ dialogId = null, chatId = null })
		{
			logger.log(`${this.constructor.name}.delete`, { dialogId, chatId });

			if (!Type.isNil(dialogId))
			{
				return this.#deleter.delete(dialogId);
			}

			if (!Type.isNil(chatId))
			{
				dialogId = await this.#getDialogIdByChatId(chatId);
				if (Type.isNull(dialogId))
				{
					return;
				}

				return this.#deleter.deleteFromDatabase(dialogId);
			}

			logger.error(`${this.className}.delete error: too few arguments`);
		}

		/**
		 * @param {'model' | 'database'} source
		 * @param {?DialogId} dialogId
		 * @param {?number } chatId
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
				return this.#deleter.deleteFromModel(dialogId);
			}

			if (!Type.isNil(chatId))
			{
				dialogId = await this.#getDialogIdByChatId(chatId);
				if (Type.isNull(dialogId))
				{
					return;
				}

				return this.#deleter.deleteFromModel(dialogId);
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
				return this.#deleter.deleteFromDatabase(dialogId);
			}

			if (!Type.isNil(chatId))
			{
				dialogId = await this.#getDialogIdByChatId(chatId);
				if (Type.isNull(dialogId))
				{
					return;
				}

				return this.#deleter.deleteFromDatabase(dialogId);
			}

			logger.error(`${this.className}.deleteFromDatabase error: too few arguments`);
		}

		/**
		 * @param {number} chatId
		 * @returns {Promise<DialogId|null>}
		 */
		async #getDialogIdByChatId(chatId)
		{
			const chatProvider = new ChatDataProvider();
			const chatDataResult = await chatProvider.get({ chatId });

			if (!chatDataResult.hasData())
			{
				return null;
			}

			return chatDataResult.getData().dialogId;
		}
	}

	module.exports = { RecentDataProvider };
});
