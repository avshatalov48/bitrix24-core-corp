/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/provider/service/chat
 */
jn.define('im/messenger/provider/service/chat', (require, exports, module) => {

	const { LoadService } = require('im/messenger/provider/service/classes/chat/load');
	const { ReadService } = require('im/messenger/provider/service/classes/chat/read');

	/**
	 * @class ChatService
	 */
	class ChatService
	{
		constructor(store)
		{
			this.store = store;

			this._initServices();
		}

		loadChatWithMessages(dialogId)
		{
			return this.loadService.loadChatWithMessages(dialogId);
		}

		readMessage(chatId, messageId)
		{
			this.readService.readMessage(chatId, messageId);
		}

		_initServices()
		{
			this.loadService = new LoadService(this.store);
			this.readService = new ReadService(this.store);
		}
	}

	module.exports = {
		ChatService,
	};
});
