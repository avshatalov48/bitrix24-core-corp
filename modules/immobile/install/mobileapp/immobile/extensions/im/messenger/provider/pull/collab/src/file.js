/**
 * @module im/messenger/provider/pull/collab/file
 */
jn.define('im/messenger/provider/pull/collab/file', (require, exports, module) => {
	const { ChatFilePullHandler } = require('im/messenger/provider/pull/chat');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--collab-file');

	/**
	 * @class CollabFilePullHandler
	 */
	class CollabFilePullHandler extends ChatFilePullHandler
	{
		constructor()
		{
			super({ logger });
		}
	}

	module.exports = {
		CollabFilePullHandler,
	};
});
