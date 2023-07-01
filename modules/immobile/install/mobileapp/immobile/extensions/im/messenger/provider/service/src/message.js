/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

/**
 * @module im/messenger/provider/service/message
 */
jn.define('im/messenger/provider/service/message', (require, exports, module) => {

	const { LoadService } = require('im/messenger/provider/service/classes/message/load');
	const { ReactionService } = require('im/messenger/provider/service/classes/message/reaction');
	const { RestMethod } = require('im/messenger/const/rest');

	/**
	 * @class MessageService
	 */
	class MessageService
	{
		constructor({ store, chatId })
		{
			this.store = store;
			this.chatId = chatId;

			this._initServices();
		}

		static getMessageRequestLimit()
		{
			return LoadService.getMessageRequestLimit();
		}

		loadUnread()
		{
			return this.loadService.loadUnread();
		}

		loadHistory()
		{
			return this.loadService.loadHistory();
		}

		hasPreparedUnreadMessages()
		{
			return this.loadService.hasPreparedUnreadMessages();
		}

		hasPreparedHistoryMessages()
		{
			return this.loadService.hasPreparedHistoryMessages();
		}

		drawPreparedHistoryMessages()
		{
			return this.loadService.drawPreparedHistoryMessages();
		}

		drawPreparedUnreadMessages()
		{
			return this.loadService.drawPreparedUnreadMessages();
		}

		addReaction(reactionId, messageId)
		{
			return this.reactionService.add(reactionId, messageId);
		}

		removeReaction(reactionId, messageId)
		{
			return this.reactionService.remove(reactionId, messageId);
		}

		updateText(messageId, text)
		{
			return BX.rest.callMethod(RestMethod.imMessageUpdate, {
				'MESSAGE_ID': messageId,
				'MESSAGE': text
			});
		}

		delete(messageId)
		{
			return BX.rest.callMethod(RestMethod.imMessageDelete, {
				'MESSAGE_ID': messageId,
			});
		}

		_initServices()
		{
			this.loadService = new LoadService({
				store: this.store,
				chatId: this.chatId,
			});

			this.reactionService = new ReactionService({
				store: this.store,
				chatId: this.chatId,
			});
		}
	}

	module.exports = {
		MessageService,
	};
});
