/**
 * @module im/messenger/controller/search
 */
jn.define('im/messenger/controller/search', (require, exports, module) => {
	const { UserSearchController } = require('im/messenger/controller/search/user');
	const { CopilotSearchController } = require('im/messenger/controller/search/copilot');

	module.exports = {
		UserSearchController,
		CopilotSearchController,
	};
});