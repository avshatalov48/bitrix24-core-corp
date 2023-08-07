/**
 * @module im/messenger/provider/pull
 */
jn.define('im/messenger/provider/pull', (require, exports, module) => {
	const { MessagePullHandler } = require('im/messenger/provider/pull/message');
	const { FilePullHandler } = require('im/messenger/provider/pull/file');
	const { DialogPullHandler } = require('im/messenger/provider/pull/dialog');
	const { UserPullHandler } = require('im/messenger/provider/pull/user');
	const { DesktopPullHandler } = require('im/messenger/provider/pull/desktop');
	const { NotificationPullHandler } = require('im/messenger/provider/pull/notification');
	const { OnlinePullHandler } = require('im/messenger/provider/pull/online');

	module.exports = {
		MessagePullHandler,
		FilePullHandler,
		DialogPullHandler,
		UserPullHandler,
		DesktopPullHandler,
		NotificationPullHandler,
		OnlinePullHandler,
	};
});
