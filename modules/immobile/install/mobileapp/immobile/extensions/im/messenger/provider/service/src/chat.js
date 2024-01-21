/**
 * @module im/messenger/provider/service/chat
 */
jn.define('im/messenger/provider/service/chat', (require, exports, module) => {
	const { core } = require('im/messenger/core');
	const { LoadService } = require('im/messenger/provider/service/classes/chat/load');
	const { ReadService } = require('im/messenger/provider/service/classes/chat/read');
	const { MuteService } = require('im/messenger/provider/service/classes/chat/mute');
	const { ParticipantService } = require('im/messenger/provider/service/classes/chat/participant');

	/**
	 * @class ChatService
	 */
	class ChatService
	{
		constructor()
		{
			this.store = core.getStore();
			this.initServices();
		}

		loadChatWithMessages(dialogId)
		{
			return this.loadService.loadChatWithMessages(dialogId);
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
		joinChat(dialogId)
		{
			return this.participantService.joinChat(dialogId);
		}

		/**
		 * @private
		 */
		initServices()
		{
			this.loadService = new LoadService();
			this.readService = new ReadService();
			this.muteService = new MuteService();
			this.participantService = new ParticipantService()
		}
	}

	module.exports = {
		ChatService,
	};
});
