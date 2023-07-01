/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/provider/rest/dialog
 */
jn.define('im/messenger/provider/rest/dialog', (require, exports, module) => {

	const { Type } = require('type');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { RestMethod } = require('im/messenger/const');

	/**
	 * @class DialogRest
	 */
	class DialogRest
	{
		getMessageList(options = {})
		{
			const methodParams = {};

			if (!options.dialogId)
			{
				throw new Error('DialogRest: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(options.dialogId) && !DialogHelper.isChatId(options.dialogId))
			{
				throw new Error('DialogRest: options.dialogId is invalid.');
			}

			methodParams.DIALOG_ID = options.dialogId;

			if (options.bottomMessageId)
			{
				methodParams.FIRST_ID = options.bottomMessageId;
			}

			if (options.topMessageId)
			{
				methodParams.LAST_ID = options.topMessageId;
			}

			if (options.limit)
			{
				methodParams.LIMIT = options.limit;
			}

			return BX.rest.callMethod(RestMethod.imDialogMessagesGet, methodParams);
		}

		getDialog(options = {})
		{
			const methodParams = {};

			if (!options.dialogId)
			{
				throw new Error('RecentRest: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(options.dialogId) && !DialogHelper.isChatId(options.dialogId))
			{
				throw new Error('RecentRest: options.dialogId is invalid.');
			}

			methodParams.DIALOG_ID = options.dialogId;

			return BX.rest.callMethod(RestMethod.imDialogGet, methodParams);
		}

		readAllMessages()
		{
			return BX.rest.callMethod('im.dialog.read.all');
		}

		readMessage(dialogId, messageId)
		{
			if (!dialogId)
			{
				throw new Error('DialogRest: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(dialogId) && !DialogHelper.isChatId(dialogId))
			{
				throw new Error('DialogRest: options.dialogId is invalid.');
			}

			if (!Type.isNumber(messageId))
			{
				throw new Error('DialogRest: options.dialogId is invalid.');
			}

			const messageReadParams = {
				DIALOG_ID: dialogId,
				MESSAGE_ID: messageId,
			};

			return BX.rest.callMethod(RestMethod.imDialogRead, messageReadParams);
		}

		unreadMessage(dialogId, messageId)
		{
			if (!dialogId)
			{
				throw new Error('DialogRest: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(dialogId) && !DialogHelper.isChatId(dialogId))
			{
				throw new Error('DialogRest: options.dialogId is invalid.');
			}

			if (!Type.isNumber(messageId))
			{
				throw new Error('DialogRest: options.dialogId is invalid.');
			}

			const messageReadParams = {
				DIALOG_ID: dialogId,
				MESSAGE_ID: messageId,
			};

			return BX.rest.callMethod(RestMethod.imDialogUnread, messageReadParams);
		}
	}

	module.exports = {
		DialogRest,
	};
});
