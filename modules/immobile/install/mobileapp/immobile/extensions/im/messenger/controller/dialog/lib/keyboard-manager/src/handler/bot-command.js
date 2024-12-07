/**
 * @module im/messenger/controller/dialog/lib/keyboard-manager/handler/bot-command
 */
jn.define('im/messenger/controller/dialog/lib/keyboard-manager/handler/bot-command', (require, exports, module) => {
	const {
		RestMethod,
	} = require('im/messenger/const');

	/**
	 * @class BotCommandHandler
	 */
	class BotCommandHandler
	{
		#dialogId;
		#messageId;

		/**
		 * @param {DialogId} dialogId
		 * @param {string} messageId
		 */
		constructor(dialogId, messageId)
		{
			this.#dialogId = dialogId;
			this.#messageId = messageId;
		}

		/**
		 * @param {KeyboardButtonConfig['botId']} botId
		 * @param {KeyboardButtonConfig['command']} command
		 * @param {KeyboardButtonConfig['commandParams']} commandParams
		 * @return {Promise}
		 */
		handle(botId, command, commandParams)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(RestMethod.imMessageCommand, {
					MESSAGE_ID: this.#messageId,
					DIALOG_ID: this.#dialogId,
					BOT_ID: botId,
					COMMAND: command,
					COMMAND_PARAMS: commandParams,
				})
					.then((result) => resolve(result))
					.catch((error) => reject(error))
				;
			});
		}
	}

	module.exports = { BotCommandHandler };
});
