/**
 * @module im/messenger/cache
 */
jn.define('im/messenger/cache', (require, exports, module) => {
	const { ShareDialogCache } = require('im/messenger/cache/share-dialog');

	// temporary replacement for the local state manager
	const { ChatRecentCache } = require('im/messenger/cache/chat-recent');
	const { CopilotRecentCache } = require('im/messenger/cache/copilot-recent');
	const { DraftCache } = require('im/messenger/cache/draft');

	const { MapCache } = require('im/messenger/cache/simple-wrapper/map-cache');

	module.exports = {
		ChatRecentCache,
		CopilotRecentCache,
		ShareDialogCache,
		DraftCache,
		MapCache,
	};
});
