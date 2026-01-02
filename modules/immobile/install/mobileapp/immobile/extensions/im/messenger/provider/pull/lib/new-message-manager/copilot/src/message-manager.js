/**
 * @module im/messenger/provider/pull/lib/new-message-manager/copilot/message-manager
 */
jn.define('im/messenger/provider/pull/lib/new-message-manager/copilot/message-manager', (require, exports, module) => {
	const { RecentTab } = require('im/messenger/const');
	const { NewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/base');
	/**
	 * @class CopilotNewMessageManager
	 */
	class CopilotNewMessageManager extends NewMessageManager
	{
		needToProcessMessage()
		{
			return this.isMessageSuitableForTab(RecentTab.copilot);
		}

		shouldUpdateRecent()
		{
			return this.isMessageSuitableForTab(RecentTab.copilot);
		}
	}

	module.exports = { CopilotNewMessageManager };
});
