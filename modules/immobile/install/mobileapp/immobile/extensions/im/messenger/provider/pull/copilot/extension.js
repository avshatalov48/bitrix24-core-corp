/**
 * @module im/messenger/provider/pull/copilot
 */
jn.define('im/messenger/provider/pull/copilot', (require, exports, module) => {
	const { CopilotDialogPullHandler } = require('im/messenger/provider/pull/copilot/dialog');
	const { CopilotMessagePullHandler } = require('im/messenger/provider/pull/copilot/message');
	const { CopilotFilePullHandler } = require('im/messenger/provider/pull/copilot/file');
	const { CopilotUserPullHandler } = require('im/messenger/provider/pull/copilot/user');

	module.exports = {
		CopilotDialogPullHandler,
		CopilotMessagePullHandler,
		CopilotFilePullHandler,
		CopilotUserPullHandler,
	};
});
