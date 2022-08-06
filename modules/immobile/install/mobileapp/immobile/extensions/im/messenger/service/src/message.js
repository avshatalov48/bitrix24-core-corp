/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/service/message
 */
jn.define('im/messenger/service/message', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { RestMethod } = jn.require('im/messenger/const');

	/**
	 * @class MessageService
	 */
	class MessageService
	{
		send(options = {})
		{
			const messageAddParams = {
				DIALOG_ID: options.dialogId,
				MESSAGE: options.text,
			};

			if (Type.isString(options.messageType))
			{
				messageAddParams.MESSAGE_TYPE = options.messageType;
			}

			if (Type.isString(options.templateId))
			{
				messageAddParams.TEMPLATE_ID = options.templateId;
			}

			return BX.rest.callMethod(RestMethod.imMessageAdd, messageAddParams);
		}

		like(options = {})
		{
			if (!options.messageId)
			{
				throw new Error('DialogService: options.messageId is required.');
			}

			if (!Type.isNumber(options.messageId))
			{
				throw new Error('DialogService: options.messageId is invalid.');
			}

			const messageLikeParams = {
				MESSAGE_ID: options.messageId,
				ACTION: ['auto', 'plus', 'minus'].includes(options.action) ? options.action : 'auto',
			};

			return BX.rest.callMethod(RestMethod.imMessageLike, messageLikeParams);
		}
	}

	module.exports = {
		MessageService,
	};
});
