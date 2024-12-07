/**
 * @module im/messenger/lib/ui/base/avatar
 */
jn.define('im/messenger/lib/ui/base/avatar', (require, exports, module) => {
	const { Avatar } = require('im/messenger/lib/ui/base/avatar/avatar-base');
	const { AvatarSafe } = require('im/messenger/lib/ui/base/avatar/avatar-safe');

	module.exports = { Avatar, AvatarSafe };
});
