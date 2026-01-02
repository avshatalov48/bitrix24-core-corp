/**
 * @module im/messenger/provider/pull/lib/new-message-manager/channel/message-manager
 */
jn.define('im/messenger/provider/pull/lib/new-message-manager/channel/message-manager', (require, exports, module) => {
	const { RecentTab } = require('im/messenger/const');
	const { NewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/base');

	/**
	 * @class ChannelNewMessageManager
	 */
	class ChannelNewMessageManager extends NewMessageManager
	{
		needToProcessMessage()
		{
			return this.isMessageSuitableForTab(RecentTab.openChannel) || this.isCommentChat();
		}

		shouldUpdateRecent()
		{
			return this.isMessageSuitableForTab(RecentTab.openChannel);
		}
	}

	module.exports = { ChannelNewMessageManager };
});
