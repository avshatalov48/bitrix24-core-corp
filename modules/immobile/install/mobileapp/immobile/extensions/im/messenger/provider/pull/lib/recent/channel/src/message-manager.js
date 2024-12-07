/**
 * @module im/messenger/provider/pull/lib/recent/channel/message-manager
 */
jn.define('im/messenger/provider/pull/lib/recent/channel/message-manager', (require, exports, module) => {
	const { BaseRecentMessageManager } = require('im/messenger/provider/pull/lib/recent/base');
	/**
	 * @class ChannelRecentMessageManager
	 */
	class ChannelRecentMessageManager extends BaseRecentMessageManager
	{
		needToSkipMessageEvent()
		{
			return !this.isChannelListEvent();
		}
	}

	module.exports = { ChannelRecentMessageManager };
});
