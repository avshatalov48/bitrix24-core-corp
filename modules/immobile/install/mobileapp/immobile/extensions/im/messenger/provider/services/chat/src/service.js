/**
 * @module im/messenger/provider/services/chat/service
 */
jn.define('im/messenger/provider/services/chat/service', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoadService } = require('im/messenger/provider/services/chat/load');
	const { ReadService } = require('im/messenger/provider/services/chat/read');
	const { MuteService } = require('im/messenger/provider/services/chat/mute');
	const { UserService } = require('im/messenger/provider/services/chat/user');
	const { CommentsService } = require('im/messenger/provider/services/chat/comments');
	const { UpdateService } = require('im/messenger/provider/services/chat/update');
	const { CreateService } = require('im/messenger/provider/services/chat/create');
	const { InputActionNotifyService } = require('im/messenger/provider/services/chat/input-action-notify');
	const { BotService } = require('im/messenger/provider/services/chat/bot');
	const { HealthCheckService } = require('im/messenger/provider/services/chat/health-check');

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
		/** @type {InputActionNotifyService} */
		#inputActionNotifyService;
		/** @type {BotService} */
		#botService;
		/** @type {HealthCheckService} */
		#healthCheckService;

		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		get inputActionNotifyService()
		{
			this.#inputActionNotifyService = this.#inputActionNotifyService ?? new InputActionNotifyService();

			return this.#inputActionNotifyService;
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

		get botService()
		{
			this.#botService = this.#botService ?? new BotService();

			return this.#botService;
		}

		get healthCheckService()
		{
			this.#healthCheckService = this.#healthCheckService ?? new HealthCheckService();

			return this.#healthCheckService;
		}

		async writingMessageNotify(dialogId)
		{
			return this.inputActionNotifyService.writingMessageNotify(dialogId);
		}

		async recordVoiceMessageNotify(dialogId)
		{
			return this.inputActionNotifyService.recordVoiceMessageNotify(dialogId);
		}

		async uploadFileMessageNotify(dialogId)
		{
			return this.inputActionNotifyService.uploadFileMessageNotify(dialogId);
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

		/**
		 *
		 * @param {CreateCopilotParams} params
		 * @return {Promise<{chatId: number}>}
		 */
		createCopilot(params)
		{
			return this.createService.createCopilot(params);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {object} context
		 * @return {Promise<any>}
		 */
		sendContext(dialogId, context)
		{
			return this.botService.sendContext(dialogId, context);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} delay
		 * @return {Promise<any>}
		 */
		updateMessagesAutoDeleteDelay({ delay, dialogId })
		{
			return this.updateService.updateMessagesAutoDeleteDelay({ delay, dialogId });
		}
	}

	module.exports = {
		ChatService,
	};
});
