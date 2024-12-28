/**
 * @module im/messenger/provider/service/classes/chat/create
 */
jn.define('im/messenger/provider/service/classes/chat/create', (require, exports, module) => {
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
	}

	module.exports = { CreateService };
});
