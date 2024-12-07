/**
 * @module im/messenger/provider/pull/channel
 */
jn.define('im/messenger/provider/pull/channel', (require, exports, module) => {
	const { ChannelMessagePullHandler } = require('im/messenger/provider/pull/channel/message');
	const { ChannelDialogPullHandler } = require('im/messenger/provider/pull/channel/dialog');
	const { ChannelFilePullHandler } = require('im/messenger/provider/pull/channel/file');

	module.exports = {
		ChannelMessagePullHandler,
		ChannelDialogPullHandler,
		ChannelFilePullHandler,
	};
});

