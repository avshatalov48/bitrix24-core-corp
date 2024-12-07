/**
 * @module im/messenger/provider/service/chat
 */
jn.define('im/messenger/provider/service/chat', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoadService } = require('im/messenger/provider/service/classes/chat/load');
	const { ReadService } = require('im/messenger/provider/service/classes/chat/read');
	const { MuteService } = require('im/messenger/provider/service/classes/chat/mute');
	const { UserService } = require('im/messenger/provider/service/classes/chat/user');
	const { CommentsService } = require('im/messenger/provider/service/classes/chat/comments');

	/**
	 * @class ChatService
	 */
	class ChatService
	{
		/**
		 * @param {DialogLocator} locator
		 */
		constructor(locator)
		{
			this.store = serviceLocator.get('core').getStore();
			this.initServices(locator);
		}

		loadChatWithMessages(dialogId)
		{
			return this.loadService.loadChatWithMessages(dialogId);
		}

		loadChatWithContext(dialogId, messageId)
		{
			return this.loadService.loadChatWithContext(dialogId, messageId);
		}

		loadCommentChatWithMessages(dialogId)
		{
			return this.loadService.loadCommentChatWithMessages(dialogId);
		}

		loadCommentChatWithMessagesByPostId(postId)
		{
			return this.loadService.loadCommentChatWithMessagesByPostId(postId);
		}

		getByDialogId(dialogId)
		{
			return this.loadService.getByDialogId(dialogId);
		}

		readMessage(chatId, messageId)
		{
			this.readService.readMessage(chatId, messageId);
		}

		muteChat(dialogId)
		{
			this.muteService.muteChat(dialogId);
		}

		unmuteChat(dialogId)
		{
			this.muteService.unmuteChat(dialogId);
		}

		/**
		 * @return {Promise}
		 */
		async joinChat(dialogId)
		{
			return this.userService.joinChat(dialogId);
		}

		subscribeToComments(dialogId)
		{
			return this.commentsService.subscribe(dialogId);
		}

		subscribeToCommentsByPostId(postId)
		{
			return this.commentsService.subscribeByPostId(postId);
		}

		unsubscribeFromComments(dialogId)
		{
			return this.commentsService.unsubscribe(dialogId);
		}

		unsubscribeFromCommentsByPostId(postId)
		{
			return this.commentsService.unsubscribeByPostId(postId);
		}

		readChannelComments(dialogId)
		{
			return this.commentsService.readChannelComments(dialogId);
		}

		/**
		 * @private
		 */
		initServices(locator)
		{
			this.commentsService = new CommentsService(locator);
			this.loadService = new LoadService(locator);
			this.readService = new ReadService(locator);
			this.muteService = new MuteService(locator);
			this.userService = new UserService(locator);
		}
	}

	module.exports = {
		ChatService,
	};
});
