/**
 * @module layout/ui/user/avatar
 */
jn.define('layout/ui/user/avatar', (require, exports, module) => {
	const { Avatar } = require('layout/ui/user/avatar/src/base-avatar');
	const { ReduxAvatar } = require('layout/ui/user/avatar/src/redux-avatar');

	module.exports = {
		Avatar,
		ReduxAvatar,
	};
});
