/**
 * @module im/messenger/provider/pull/lib/recent/chat
 */
jn.define('im/messenger/provider/pull/lib/recent/chat', (require, exports, module) => {
	const { ChatRecentUpdateManager } = require('im/messenger/provider/pull/lib/recent/chat/update-manager');
	const { ChatRecentMessageManager } = require('im/messenger/provider/pull/lib/recent/chat/message-manager');

	module.exports = {
		ChatRecentUpdateManager,
		ChatRecentMessageManager,
	};
});

