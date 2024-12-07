/**
 * @module im/messenger/provider/pull/copilot/file
 */
jn.define('im/messenger/provider/pull/copilot/file', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--copilot-file');

	/**
	 * @class CopilotFilePullHandler
	 */
	class CopilotFilePullHandler extends BasePullHandler
	{
		/**
		 * @param {object} params
		 * @param {object} extra
		 * @param {object} command
		 */
		handleFileAdd(params, extra, command)
		{
			logger.info('CopilotFilePullHandler.handleFileAdd and nothing happened', params);
			// TODO file add is not available now for copilot chat
		}
	}

	module.exports = {
		CopilotFilePullHandler,
	};
});
