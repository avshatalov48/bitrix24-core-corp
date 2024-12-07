/**
 * @module im/messenger/provider/pull/chat/file
 */
jn.define('im/messenger/provider/pull/chat/file', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { FileUtils } = require('im/messenger/provider/pull/lib/file');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-file');

	/**
	 * @class ChatFilePullHandler
	 */
	class ChatFilePullHandler extends BasePullHandler
	{
		/**
		 * @param {object} params
		 * @param {object} extra
		 * @param {object} command
		 */
		async handleFileAdd(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('ChatFilePullHandler.handleFileAdd:', params);
			await FileUtils.setFiles(params);
		}
	}

	module.exports = {
		ChatFilePullHandler,
	};
});
