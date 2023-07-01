/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/provider/rest/chat
 */
jn.define('im/messenger/provider/rest/chat', (require, exports, module) => {

	const { Type } = require('type');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { RestMethod } = require('im/messenger/const');

	/**
	 * @class ChatRest
	 */
	class ChatRest
	{
		mute(options = {})
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

			if (!Type.isBoolean(options.shouldMute))
			{
				throw new Error('RecentRest: options.shouldMute must be boolean value.');
			}

			methodParams.MUTE = options.shouldMute ? 'Y' : 'N';

			return BX.rest.callMethod(RestMethod.imChatMute, methodParams);
		}

		leave(options = {})
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

			return BX.rest.callMethod(RestMethod.imChatLeave, methodParams);
		}
	}

	module.exports = {
		ChatRest,
	};
});
