/**
 * @module im/messenger/lib/permission-manager
 */
jn.define('im/messenger/lib/permission-manager', (require, exports, module) => {
	const { UserPermission } = require('im/messenger/lib/permission-manager/user-permission');
	const { ChatPermission } = require('im/messenger/lib/permission-manager/chat-permission');

	module.exports = {
		UserPermission, ChatPermission,
	};
});
