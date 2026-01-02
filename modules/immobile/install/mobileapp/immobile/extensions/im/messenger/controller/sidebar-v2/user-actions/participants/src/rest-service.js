/**
 * @module im/messenger/controller/sidebar-v2/user-actions/participants/src/rest-service
 */
jn.define(
	'im/messenger/controller/sidebar-v2/user-actions/participants/src/rest-service',
	(require, exports, module) => {
		const { runAction } = require('im/messenger/lib/rest');
		const { LoggerManager } = require('im/messenger/lib/logger');
		const { MessengerEmitter } = require('im/messenger/lib/emitter');
		const { ChatService } = require('im/messenger/provider/services/chat');
		const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-rest-service');
		const { RestMethod, EventType, ComponentCode } = require('im/messenger/const');

		/**
		 * @desc Rest call add participant
		 * @param {DialogId} dialogId
		 * @param {Array<numbers>} userIds
		 * @return {Promise}
		 */
		async function addParticipants(dialogId, userIds)
		{
			const chatSettings = Application.storage.getObject('settings.chat', {
				historyShow: true,
			});

			const addUserData = {
				id: dialogId.replace('chat', ''),
				userIds,
				hideHistory: chatSettings.historyShow ? 'N' : 'Y',
			};

			const response = await runAction(RestMethod.imV2ChatAddUsers, { data: addUserData }).catch((error) => {
				logger.error('ParticipantsRestService.addParticipants.catch:', error);
			});

			if (response)
			{
				logger.log('ParticipantsRestService.addParticipants response: ', response);
			}

			return response;
		}

		/**
		 * @desc Rest call add chat from private dialog
		 * @param {Array<numbers>} userIds
		 * @return {Promise}
		 */
		async function addChat(userIds)
		{
			const result = await BX.rest.callMethod(RestMethod.imChatAdd, { USERS: userIds })
				.catch((error) => logger.error('ParticipantsRestService.addChat.catch:', error));

			const chatId = parseInt(result.data(), 10);
			if (result?.answer?.error)
			{
				logger.error('ParticipantsRestService.addChat.error', result.answer.error_description);
			}

			if (!chatId)
			{
				return null;
			}

			setTimeout(() => {
				MessengerEmitter.emit(
					EventType.messenger.openDialog,
					{ dialogId: `chat${chatId}` },
					ComponentCode.imMessenger,
				);
			}, 500);

			return result;
		}

		/**
		 * @desc Rest call delete participant by id
		 * @param {DialogId} dialogId
		 * @param {number} userId
		 * @return {Promise<boolean>}
		 */
		function deleteParticipant(dialogId, userId)
		{
			return (new ChatService()).kickUserFromChat(dialogId, userId);
		}

		module.exports = {
			addChat,
			addParticipants,
			deleteParticipant,
		};
	},
);
