/**
 * @module im/messenger/provider/pull/lib/recent/chat/message-manager
 */
jn.define('im/messenger/provider/pull/lib/recent/chat/message-manager', (require, exports, module) => {
	const { BaseRecentMessageManager } = require('im/messenger/provider/pull/lib/recent/base');

	/**
	 * @class ChatRecentMessageManager
	 */
	class ChatRecentMessageManager extends BaseRecentMessageManager
	{
		needToSkipMessageEvent()
		{
			return this.isLinesChat() || this.isCommentChat() || !this.isUserInChat();
		}
	}

	module.exports = { ChatRecentMessageManager };
});
