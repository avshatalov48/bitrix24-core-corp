/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/collab/message
 */
jn.define('im/messenger/provider/pull/collab/message', (require, exports, module) => {
	const { ChatMessagePullHandler } = require('im/messenger/provider/pull/chat');
	const { CollabNewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/collab');
	const { getLogger } = require('im/messenger/lib/logger');

	const logger = getLogger('pull-handler--collab-message');

	/**
	 * @class CollabMessagePullHandler
	 */
	class CollabMessagePullHandler extends ChatMessagePullHandler
	{
		constructor()
		{
			super({ logger });
		}

		handleMessage(params, extra, command)
		{}

		isNeedNotify()
		{
			return false;
		}

		getNewMessageManager(params, extra = {})
		{
			return new CollabNewMessageManager(params, extra);
		}
	}

	module.exports = {
		CollabMessagePullHandler,
	};
});
