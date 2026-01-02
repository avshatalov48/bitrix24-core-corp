/**
 * @module im/messenger/provider/services/chat/create
 */
jn.define('im/messenger/provider/services/chat/create', (require, exports, module) => {
	const { Type } = require('type');
	const { RestMethod } = require('im/messenger/const');
	const { runAction } = require('im/messenger/lib/rest');

	/**
	 * @class CreateService
	 */
	class CreateService
	{
		/**
		 * @param {CreateChatParams} params
		 * @returns {Promise<{chatId: number}>}
		 */
		async createChat(params)
		{
			const config = {
				type: params.type,
				ownerId: params.ownerId,
				searchable: params.searchable,
				memberEntities: params.memberEntities,
			};

			if (Type.isNumber(params.messagesAutoDeleteDelay))
			{
				config.messagesAutoDeleteDelay = params.messagesAutoDeleteDelay;
			}

			if (Type.isStringFilled(params.title))
			{
				config.title = params.title;
			}

			if (Type.isStringFilled(params.description))
			{
				config.description = params.description;
			}

			if (Type.isStringFilled(params.avatar))
			{
				config.avatar = params.avatar;
			}

			return runAction(RestMethod.imV2ChatAdd, {
				data: {
					fields: config,
				},
			});
		}

		/**
		 * @param {CreateCopilotParams} params
		 * @returns {Promise<{chatId: number}>}
		 */
		async createCopilot(params)
		{
			const config = {
				type: params.type,
				copilotMainRole: params.copilotMainRole,
			};

			return runAction(RestMethod.imV2ChatAdd, {
				data: {
					fields: config,
				},
			});
		}
	}

	module.exports = { CreateService };
});
