/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/dialog
 */
jn.define('im/messenger/provider/pull/chat/dialog', (require, exports, module) => {
	const { DialogBasePullHandler } = require('im/messenger/provider/pull/lib');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-dialog');

	/**
	 * @class ChatDialogPullHandler
	 */
	class ChatDialogPullHandler extends DialogBasePullHandler
	{
		constructor()
		{
			super({ logger });
		}
	}

	module.exports = {
		ChatDialogPullHandler,
	};
});
