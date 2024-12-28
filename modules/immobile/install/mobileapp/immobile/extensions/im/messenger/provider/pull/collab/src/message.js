/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/collab/message
 */
jn.define('im/messenger/provider/pull/collab/message', (require, exports, module) => {
	const { ChatMessagePullHandler } = require('im/messenger/provider/pull/chat');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--collab-message');

	/**
	 * @class CollabMessagePullHandler
	 */
	class CollabMessagePullHandler extends ChatMessagePullHandler
	{
		constructor()
		{
			super({ logger });
		}

		handleMessageChat(params, extra, command)
		{
			const recentMessageManager = this.getRecentMessageManager(params, extra);
			if (!recentMessageManager.isCollabChat())
			{
				return;
			}

			super.handleMessageChat(params, extra, command);
		}

		handleMessage(params, extra, command)
		{}
	}

	module.exports = {
		CollabMessagePullHandler,
	};
});
