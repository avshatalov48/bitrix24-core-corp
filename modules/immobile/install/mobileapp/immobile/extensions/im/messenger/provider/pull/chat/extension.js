/**
 * @module im/messenger/provider/pull/chat
 */
jn.define('im/messenger/provider/pull/chat', (require, exports, module) => {
	const { ChatMessagePullHandler } = require('im/messenger/provider/pull/chat/message');
	const { ChatFilePullHandler } = require('im/messenger/provider/pull/chat/file');
	const { ChatDialogPullHandler } = require('im/messenger/provider/pull/chat/dialog');
	const { ChatUserPullHandler } = require('im/messenger/provider/pull/chat/user');
	const { ChatRecentPullHandler } = require('im/messenger/provider/pull/chat/recent');
	const { DesktopPullHandler } = require('im/messenger/provider/pull/chat/desktop');
	const { NotificationPullHandler } = require('im/messenger/provider/pull/chat/notification');
	const { OnlinePullHandler } = require('im/messenger/provider/pull/chat/online');

	module.exports = {
		ChatMessagePullHandler,
		ChatFilePullHandler,
		ChatDialogPullHandler,
		ChatUserPullHandler,
		ChatRecentPullHandler,
		DesktopPullHandler,
		NotificationPullHandler,
		OnlinePullHandler,
	};
});
