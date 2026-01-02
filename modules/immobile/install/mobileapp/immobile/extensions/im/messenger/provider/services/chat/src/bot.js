/**
 * @module im/messenger/provider/services/chat/bot
 */
jn.define('im/messenger/provider/services/chat/bot', (require, exports, module) => {
	const { Type } = require('type');

	const { RestMethod } = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { runAction } = require('im/messenger/lib/rest');

	const logger = LoggerManager.getInstance().getLogger('dialog--chat-service');

	/**
	 * @class BotService
	 */
	class BotService
	{
		/**
		 * @param {DialogId} dialogId
		 * @param {object} context
		 * @return {Promise<any>}
		 */
		async sendContext(dialogId, context)
		{
			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error(`${this.constructor.name}.sendContext: dialogId is not provided`));
			}

			return runAction(
				RestMethod.imV2ChatBotSendContext,
				{
					data: {
						dialogId,
						context,
					},
				},
			)
				.then(
					(response) => {
						if (response?.errors?.length > 0)
						{
							logger.error(`${this.constructor.name}.sendContext.error:`, response.errors);

							return response.errors;
						}

						logger.log(`${this.constructor.name}.sendContext.response:`, response);

						return response;
					},
				)
				.catch((error) => logger.error(`${this.constructor.name}.sendContext.catch:`, error));
		}
	}

	module.exports = {
		BotService,
	};
});
