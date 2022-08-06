/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/service/chat
 */
jn.define('im/messenger/service/chat', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { DialogHelper } = jn.require('im/messenger/lib/helper');
	const { RestMethod } = jn.require('im/messenger/const');

	/**
	 * @class ChatService
	 */
	class ChatService
	{
		mute(options = {})
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

			if (!Type.isBoolean(options.shouldMute))
			{
				throw new Error('RecentService: options.shouldMute must be boolean value.');
			}

			methodParams.MUTE = options.shouldMute ? 'Y' : 'N';

			return BX.rest.callMethod(RestMethod.imChatMute, methodParams);
		}

		leave(options = {})
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

			return BX.rest.callMethod(RestMethod.imChatLeave, methodParams);
		}
	}

	module.exports = {
		ChatService,
	};
});
