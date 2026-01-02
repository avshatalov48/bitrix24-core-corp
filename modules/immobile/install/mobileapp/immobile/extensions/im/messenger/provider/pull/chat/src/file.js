/**
 * @module im/messenger/provider/pull/chat/file
 */
jn.define('im/messenger/provider/pull/chat/file', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { FileUtils } = require('im/messenger/provider/pull/lib/file');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('pull-handler--chat-file');

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
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleFileAdd:`, params);
			await FileUtils.setFiles(params);
		}

		/**
		 * @param {object} params
		 * @param {object} extra
		 * @param {object} command
		 */
		async handleFileTranscription(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleFileTranscription:`, params);
			await FileUtils.setTranscript(params);
		}
	}

	module.exports = {
		ChatFilePullHandler,
	};
});
