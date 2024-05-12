/**
 * @module im/messenger/provider/service/classes/chat/participant
 */
jn.define('im/messenger/provider/service/classes/chat/participant', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { RestMethod } = require('im/messenger/const/rest');
	const { UserRole } = require('im/messenger/const');

	/**
	 * @class ParticipantService
	 */
	class ParticipantService
	{
		/**
		 * @constructor
		 */
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @desc Return promise from call rest method im.v2.Chat.join
		 * @param {string} dialogId
		 * @return {Promise}
		 */
		joinChat(dialogId)
		{
			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('ChatService: ParticipantService.joinChat: dialogId is not provided'));
			}

			Logger.info(`ParticipantService: join chat ${dialogId}`);
			this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					role: UserRole.member,
				},
			})
				.catch((err) => Logger.error('ParticipantService.joinChat.dialoguesModel/update.error', err));

			return BX.rest.callMethod(RestMethod.imV2ChatJoin, { dialogId });
		}
	}

	module.exports = {
		ParticipantService,
	};
});
