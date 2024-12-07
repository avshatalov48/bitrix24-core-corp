/**
 * @module im/messenger/provider/pull/channel/file
 */
jn.define('im/messenger/provider/pull/channel/file', (require, exports, module) => {
	const { ChatFilePullHandler } = require('im/messenger/provider/pull/chat');
	/**
	 * @class ChannelFilePullHandler
	 */
	class ChannelFilePullHandler extends ChatFilePullHandler
	{
		constructor()
		{
			super();
			this.supportSharedEvents = true;
		}
	}

	module.exports = { ChannelFilePullHandler };
});
