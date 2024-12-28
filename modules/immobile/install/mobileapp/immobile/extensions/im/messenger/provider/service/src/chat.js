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
	const { UpdateService } = require('im/messenger/provider/service/classes/chat/update');
	const { CreateService } = require('im/messenger/provider/service/classes/chat/create');

	/**
	 * @class ChatService
	 */
	class ChatService
	{
		/** @type {LoadService} */
		#loadService;
		/** @type {ReadService} */
		#readService;
		/** @type {MuteService} */
		#muteService;
		/** @type {UserService} */
		#userService;
		/** @type {CommentsService} */
		#commentService;
		/** @type {UpdateService} */
		#updateService;
		/** @type {CreateService} */
		#createService;

		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		get loadService()
		{
			this.#loadService = this.#loadService ?? new LoadService();

			return this.#loadService;
		}

		get readService()
		{
			this.#readService = this.#readService ?? new ReadService();

			return this.#readService;
		}

		get muteService()
		{
			this.#muteService = this.#muteService ?? new MuteService();

			return this.#muteService;
		}

		get userService()
		{
			this.#userService = this.#userService ?? new UserService();

			return this.#userService;
		}

		get commentsService()
		{
			this.#commentService = this.#commentService ?? new CommentsService();

			return this.#commentService;
		}

		get updateService()
		{
			this.#updateService = this.#updateService ?? new UpdateService();

			return this.#updateService;
		}

		get createService()
		{
			this.#createService = this.#createService ?? new CreateService();

			return this.#createService;
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

		/**
		 * @param {number} chatId
		 * @param {Array<number>} members
		 * @param {boolean} showHistory
		 *
		 * @return {Promise<*|T>}
		 */
		addToChat(chatId, members, showHistory)
		{
			return this.userService.addToChat(chatId, members, showHistory);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} userId
		 *
		 * @return {Promise<*|T>}
		 */
		kickUserFromChat(dialogId, userId)
		{
			return this.userService.kickUserFromChat(dialogId, userId);
		}

		/**
		 * @param {DialogId} dialogId
		 *
		 * @return {Promise<*|T>}
		 */
		leaveFromChat(dialogId)
		{
			return this.userService.leaveFromChat(dialogId);
		}

		/**
		 * @param {DialogId} dialogId
		 *
		 * @return {Promise<*|T>}
		 */
		deleteChat(dialogId)
		{
			return this.userService.deleteChat(dialogId);
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
		 *
		 * @param {CreateChatParams} params
		 * @return {Promise<{chatId: number}>}
		 */
		createChat(params)
		{
			return this.createService.createChat(params);
		}
	}

	module.exports = {
		ChatService,
	};
});
