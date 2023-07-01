/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/provider/pull
 */
jn.define('im/messenger/provider/pull', (require, exports, module) => {

	const { MessagePullHandler } = require('im/messenger/provider/pull/message');
	const { DialogPullHandler } = require('im/messenger/provider/pull/dialog');
	const { UserPullHandler } = require('im/messenger/provider/pull/user');
	const { DesktopPullHandler } = require('im/messenger/provider/pull/desktop');
	const { NotificationPullHandler } = require('im/messenger/provider/pull/notification');
	const { OnlinePullHandler } = require('im/messenger/provider/pull/online');

	module.exports = {
		MessagePullHandler,
		DialogPullHandler,
		UserPullHandler,
		DesktopPullHandler,
		NotificationPullHandler,
		OnlinePullHandler,
	};
});
