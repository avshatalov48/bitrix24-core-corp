/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/application
 */
jn.define('im/messenger/provider/pull/chat/application', (require, exports, module) => {
	const { BaseApplicationPullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-application');

	/**
	 * @class ChatApplicationPullHandler
	 */
	class ChatApplicationPullHandler extends BaseApplicationPullHandler
	{
		constructor()
		{
			super({ logger });
		}
	}

	module.exports = {
		ChatApplicationPullHandler,
	};
});
