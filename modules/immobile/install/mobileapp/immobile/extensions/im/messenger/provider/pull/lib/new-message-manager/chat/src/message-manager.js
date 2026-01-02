/**
 * @module im/messenger/provider/pull/lib/new-message-manager/chat/message-manager
 */
jn.define('im/messenger/provider/pull/lib/new-message-manager/chat/message-manager', (require, exports, module) => {
	const { RecentTab } = require('im/messenger/const');
	const { NewMessageManager } = require('im/messenger/provider/pull/lib/new-message-manager/base');

	/**
	 * @class ChatNewMessageManager
	 */
	class ChatNewMessageManager extends NewMessageManager
	{
		needToProcessMessage()
		{
			return this.isMessageSuitableForTab(RecentTab.chat)
				|| this.isCommentChat()
				|| this.isTaskComments()
			;
		}

		shouldUpdateRecent()
		{
			return this.isMessageSuitableForTab(RecentTab.chat);
		}
	}

	module.exports = { ChatNewMessageManager };
});
