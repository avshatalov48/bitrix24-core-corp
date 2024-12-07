/**
 * @module im/messenger/provider/data/chat/getter
 */
jn.define('im/messenger/provider/data/chat/getter', (require, exports, module) => {
	const { Type } = require('type');
	const { mergeImmutable } = require('utils/object');
	const { DataProviderResult } = require('im/messenger/provider/data/result');
	const { BaseDataProvider } = require('im/messenger/provider/data/base');
	const { dialogDefaultElement } = require('im/messenger/model');

	/**
	 * @class ChatGetter
	 */
	class ChatGetter extends BaseDataProvider
	{
		/**
		 * @param {DialogId} dialogId
		 * @return {Promise<DataProviderResult<DialoguesModelState>>}
		 */
		async getByDialogId(dialogId)
		{
			const chatData = this.store.getters['dialoguesModel/getById'](dialogId);

			if (Type.isPlainObject(chatData))
			{
				return new DataProviderResult(chatData, BaseDataProvider.source.model);
			}

			const databaseChatData = await this.repository.dialog.getByDialogId(dialogId);

			if (Type.isNull(databaseChatData))
			{
				return new DataProviderResult();
			}

			const preparedChatData = this.#prepareDatabaseData(chatData);

			return new DataProviderResult(preparedChatData, BaseDataProvider.source.database);
		}

		/**
		 * @param {number} chatId
		 * @return {Promise<DataProviderResult<DialoguesModelState>>}
		 */
		async getByChatId(chatId)
		{
			const chatData = this.store.getters['dialoguesModel/getByChatId'](chatId);

			if (Type.isPlainObject(chatData))
			{
				return new DataProviderResult(chatData, BaseDataProvider.source.model);
			}

			const databaseChatData = await this.repository.dialog.getByChatId(chatId);

			if (Type.isNull(databaseChatData))
			{
				return new DataProviderResult();
			}

			const preparedChatData = this.#prepareDatabaseData(chatData);

			return new DataProviderResult(preparedChatData, BaseDataProvider.source.database);
		}

		/**
		 * @param {DialogStoredData} chatData
		 * @return {DialoguesModelState}
		 */
		#prepareDatabaseData(chatData)
		{
			return mergeImmutable(dialogDefaultElement, chatData);
		}
	}

	module.exports = { ChatGetter };
});
