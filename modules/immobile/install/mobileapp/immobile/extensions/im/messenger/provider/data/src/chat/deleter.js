/**
 * @module im/messenger/provider/data/chat/deleter
 */
jn.define('im/messenger/provider/data/chat/deleter', (require, exports, module) => {
	const { BaseDataProvider } = require('im/messenger/provider/data/base');
	const { ChatGetter } = require('im/messenger/provider/data/chat/getter');

	/**
	 * @class ChatDeleter
	 */
	class ChatDeleter extends BaseDataProvider
	{
		constructor()
		{
			super();
			this.chatGetter = new ChatGetter();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		async deleteByDialogId(dialogId)
		{
			const chatDataResult = await this.chatGetter.getByDialogId(dialogId);
			if (!chatDataResult.hasData())
			{
				return;
			}

			await this.#deleteFromModel(chatDataResult.getData());

			await this.#deleteFromDatabase(chatDataResult.getData());
		}

		/**
		 * @param {number} chatId
		 */
		async deleteByChatId(chatId)
		{
			const chatDataResult = await this.chatGetter.getByChatId(chatId);
			if (!chatDataResult.hasData())
			{
				return;
			}

			await this.#deleteFromModel(chatDataResult.getData());

			await this.#deleteFromDatabase(chatDataResult.getData());
		}

		/**
		 * @param {DialogId} dialogId
		 */
		async deleteFromModelByDialogId(dialogId)
		{
			const chatDataResult = await this.chatGetter.getByDialogId(dialogId);
			if (!chatDataResult.hasData())
			{
				return;
			}

			await this.#deleteFromModel(chatDataResult.getData());
		}

		/**
		 * @param {number} chatId
		 */
		async deleteFromModelByChatId(chatId)
		{
			const chatDataResult = await this.chatGetter.getByChatId(chatId);
			if (!chatDataResult.hasData())
			{
				return;
			}

			await this.#deleteFromModel(chatDataResult.getData());
		}

		/**
		 * @param {DialogId} dialogId
		 */
		async deleteFromDatabaseByDialogId(dialogId)
		{
			const chatDataResult = await this.chatGetter.getByDialogId(dialogId);
			if (!chatDataResult.hasData())
			{
				return;
			}

			await this.#deleteFromDatabase(chatDataResult.getData());
		}

		/**
		 * @param {number} chatId
		 */
		async deleteFromDatabaseByChatId(chatId)
		{
			const chatDataResult = await this.chatGetter.getByChatId(chatId);
			if (!chatDataResult.hasData())
			{
				return;
			}

			await this.#deleteFromDatabase(chatDataResult.getData());
		}

		/**
		 * @param {DialoguesModelState} chatData
		 */
		async #deleteFromModel(chatData)
		{
			const { dialogId, chatId } = chatData;

			// by dialogId
			await this.store.dispatch('dialoguesModel/deleteFromModel', { dialogId });
			// await this.store.dispatch('recentModel/deleteFromModel', { id: dialogId });

			// by chatId
			await this.store.dispatch('filesModel/deleteByChatId', { chatId });
			await this.store.dispatch('messagesModel/reactionsModel/deleteByChatId', { chatId });
			await this.store.dispatch('messagesModel/pinModel/deleteMessagesByChatId', { chatId });

			await this.store.dispatch('messagesModel/deleteByChatId', { chatId });
		}

		/**
		 *
		 * @param {DialoguesModelState} chatData
		 */
		async #deleteFromDatabase(chatData)
		{
			const { dialogId, chatId } = chatData;

			// by dialogId
			await this.repository.dialog.deleteById(dialogId);
			// await this.repository.recent.deleteById(dialogId);
			await this.repository.copilot.deleteByIdList([dialogId]);

			// by chatId
			await this.repository.reaction.deleteByChatId(chatId);
			await this.repository.file.deleteByChatId(chatId);
			await this.repository.pinMessage.deleteByChatId(chatId);
			await this.repository.tempMessage.deleteByChatId(chatId);

			await this.repository.message.deleteByChatId(chatId);
		}
	}

	module.exports = { ChatDeleter };
});
