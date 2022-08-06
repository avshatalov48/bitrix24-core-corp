/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/pull-handler
 */
jn.define('im/messenger/pull-handler', (require, exports, module) => {

	const { MessagePullHandler } = jn.require('im/messenger/pull-handler/message');
	const { DialogPullHandler } = jn.require('im/messenger/pull-handler/dialog');
	const { UserPullHandler } = jn.require('im/messenger/pull-handler/user');
	const { DesktopPullHandler } = jn.require('im/messenger/pull-handler/desktop');
	const { NotificationPullHandler } = jn.require('im/messenger/pull-handler/notification');
	const { OnlinePullHandler } = jn.require('im/messenger/pull-handler/online');

	module.exports = {
		MessagePullHandler,
		DialogPullHandler,
		UserPullHandler,
		DesktopPullHandler,
		NotificationPullHandler,
		OnlinePullHandler,
	};
});
