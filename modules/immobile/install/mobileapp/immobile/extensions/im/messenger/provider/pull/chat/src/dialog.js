/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/dialog
 */
jn.define('im/messenger/provider/pull/chat/dialog', (require, exports, module) => {
	const { BaseDialogPullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { DialogHelper } = require('im/messenger/lib/helper');

	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-dialog');

	/**
	 * @class ChatDialogPullHandler
	 */
	class ChatDialogPullHandler extends BaseDialogPullHandler
	{
		constructor()
		{
			super({ logger });
		}

		/**
		 * @param {DialoguesModelState} chatData
		 * @return {boolean}
		 */
		shouldDeleteChat(chatData)
		{
			const helper = DialogHelper.createByModel(chatData);

			if (helper?.isCopilot)
			{
				return false;
			}

			if (helper.isOpenChannel && !helper?.isCurrentUserParticipant)
			{
				return false;
			}

			return true;
		}
	}

	module.exports = {
		ChatDialogPullHandler,
	};
});
