/**
 * @module im/messenger/controller/dialog/lib/assets
 */
jn.define('im/messenger/controller/dialog/lib/assets', (require, exports, module) => {
	const { ChatAssets } = require('im/messenger/controller/dialog/lib/assets/chat-assets');
	const { CopilotAssets } = require('im/messenger/controller/dialog/lib/assets/copilot-assets');

	module.exports = { ChatAssets, CopilotAssets };
});
