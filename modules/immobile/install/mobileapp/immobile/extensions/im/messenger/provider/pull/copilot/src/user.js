/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/copilot/user
 */
jn.define('im/messenger/provider/pull/copilot/user', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--copilot-user');

	/**
	 * @class CopilotUserPullHandler
	 */
	class CopilotUserPullHandler extends BasePullHandler
	{
		handleUserInvite(params, extra, command)
		{
			logger.info('CopilotUserPullHandler.handleUserInvite and nothing happened', params);
		}

		handleBotDelete(params, extra, command)
		{
			logger.info('CopilotUserPullHandler.handleBotDelete and nothing happened', params);
		}

		handleUserUpdate(params, extra, command)
		{
			logger.info('CopilotUserPullHandler.handleUserUpdate and nothing happened', params);
		}

		handleBotUpdate(params, extra, command)
		{
			logger.info('CopilotUserPullHandler.handleBotUpdate and nothing happened', params);
		}
	}

	module.exports = {
		CopilotUserPullHandler,
	};
});
