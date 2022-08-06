/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/service/dialog
 */
jn.define('im/messenger/service/dialog', (require, exports, module) => {

	const { DialogHelper } = jn.require('im/messenger/lib/helper');
	const { RestMethod } = jn.require('im/messenger/const');

	/**
	 * @class DialogService
	 */
	class DialogService
	{
		getMessageList(options = {})
		{
			const methodParams = {};

			if (!options.dialogId)
			{
				throw new Error('DialogService: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(options.dialogId) && !DialogHelper.isChatId(options.dialogId))
			{
				throw new Error('DialogService: options.dialogId is invalid.');
			}

			methodParams.DIALOG_ID = options.dialogId;

			if (options.fromMessageId)
			{
				methodParams.FIRST_ID = options.fromMessageId;
			}

			if (options.toMessageId)
			{
				methodParams.LAST_ID = options.toMessageId;
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
				throw new Error('RecentService: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(options.dialogId) && !DialogHelper.isChatId(options.dialogId))
			{
				throw new Error('RecentService: options.dialogId is invalid.');
			}

			methodParams.DIALOG_ID = options.dialogId;

			return BX.rest.callMethod(RestMethod.imDialogGet, methodParams);
		}

		readAllMessages()
		{
			return BX.rest.callMethod('im.dialog.read.all');
		}
	}

	module.exports = {
		DialogService,
	};
});
