/**
 * @module im/messenger/provider/data/message/deleter
 */
jn.define('im/messenger/provider/data/message/deleter', (require, exports, module) => {
	const { BaseDataProvider } = require('im/messenger/provider/data/base');

	/**
	 * @class MessageDeleter
	 */
	class MessageDeleter extends BaseDataProvider
	{
		/**
		 * @param {{ chatId: number, dialogId: DialogId }} chatData
		 */
		async deleteByChat(chatData)
		{
			await this.deleteFromModel(chatData);
			await this.deleteFromDatabase(chatData);
		}

		/**
		 * @param {{ chatId: number, dialogId: DialogId }} chatData
		 */
		async deleteFromModel(chatData)
		{
			const { dialogId, chatId } = chatData;

			await this.store.dispatch('filesModel/deleteByChatId', { chatId });
			await this.store.dispatch('messagesModel/reactionsModel/deleteByChatId', { chatId });
			await this.store.dispatch('messagesModel/pinModel/deleteMessagesByChatId', { chatId });
			await this.store.dispatch('messagesModel/voteModel/deleteByChatId', { chatId });

			await this.store.dispatch('messagesModel/deleteByChatId', { chatId });
			await this.store.dispatch('sidebarModel/deleteTabsData', { dialogId, chatId });
		}

		/**
		 * @param {{ chatId: number, dialogId: DialogId }} chatData
		 */
		async deleteFromDatabase(chatData)
		{
			const { chatId } = chatData;

			await this.repository.reaction.deleteByChatId(chatId);
			await this.repository.file.deleteByChatId(chatId);
			await this.repository.pinMessage.deleteByChatId(chatId);
			await this.repository.tempMessage.deleteByChatId(chatId);
			await this.repository.vote.deleteByChatId(chatId);

			await this.repository.message.deleteByChatId(chatId);

			await this.repository.comment.deleteByParentChatId(chatId);
		}
	}

	module.exports = { MessageDeleter };
});
