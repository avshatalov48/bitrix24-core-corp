/**
 * @module im/messenger/provider/pull/lib/new-message-manager/collab/message-manager
 */
jn.define('im/messenger/provider/pull/lib/new-message-manager/collab/message-manager', (require, exports, module) => {
	const { RecentTab } = require('im/messenger/const');
	const { NewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/base');
	/**
	 * @class CollabNewMessageManager
	 */
	class CollabNewMessageManager extends NewMessageManager
	{
		needToProcessMessage()
		{
			return this.isMessageSuitableForTab(RecentTab.collab);
		}

		shouldUpdateRecent()
		{
			return this.isMessageSuitableForTab(RecentTab.collab);
		}
	}

	module.exports = { CollabNewMessageManager };
});
