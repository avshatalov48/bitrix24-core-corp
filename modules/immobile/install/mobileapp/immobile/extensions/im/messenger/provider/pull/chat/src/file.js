/**
 * @module im/messenger/provider/pull/chat/file
 */
jn.define('im/messenger/provider/pull/chat/file', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/lib');
	const { LoggerManager } = require('im/messenger/lib/logger');
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
		handleFileAdd(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('ChatFilePullHandler.handleFileAdd:', params);

			this.store.dispatch('filesModel/set', params.files)
				.catch((err) => logger.error('ChatFilePullHandler.handleChatPin.filesModel/set.catch:', err));
		}
	}

	module.exports = {
		ChatFilePullHandler,
	};
});
