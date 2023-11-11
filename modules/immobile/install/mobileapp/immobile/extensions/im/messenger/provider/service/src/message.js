/**
 * @module im/messenger/provider/service/message
 */
jn.define('im/messenger/provider/service/message', (require, exports, module) => {
	const { LoadService } = require('im/messenger/provider/service/classes/message/load');
	const { ReactionService } = require('im/messenger/provider/service/classes/message/reaction');
	const { StatusService } = require('im/messenger/provider/service/classes/message/status');
	const { RestMethod } = require('im/messenger/const/rest');

	/**
	 * @class MessageService
	 */
	class MessageService
	{
		constructor({ store, chatId })
		{
			/** @type {MessengerCoreStore} */
			this.store = store;
			this.chatId = chatId;
			/** @type {LoadService} */
			this.loadService = null;
			/** @type {ReactionService} */
			this.reactionService = null;

			this.initServices();
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
				MESSAGE_ID: messageId,
				MESSAGE: text,
			});
		}

		delete(messageId)
		{
			return BX.rest.callMethod(RestMethod.imV2ChatMessageDelete, {
				id: messageId,
			});
		}

		openUsersReadMessageList(messageId)
		{
			this.statusService.openUsersReadMessageList(messageId);
		}

		createUsersReadCache()
		{
			this.statusService.createCache();
		}

		/**
		 * @private
		 */
		initServices()
		{
			this.loadService = new LoadService({
				store: this.store,
				chatId: this.chatId,
			});

			this.reactionService = new ReactionService({
				store: this.store,
				chatId: this.chatId,
			});

			this.statusService = new StatusService({
				store: this.store,
				chatId: this.chatId,
			});
		}
	}

	module.exports = {
		MessageService,
	};
});
