/**
 * @module im/messenger/const/user
 */

jn.define('im/messenger/const/user', (require, exports, module) => {
	const UserExternalType = Object.freeze({
		default: 'default',
		bot: 'bot',
		call: 'call',
	});

	const UserRole = Object.freeze({
		guest: 'guest',
		member: 'member',
		manager: 'manager',
		owner: 'owner',
		none: 'none',
	});

	module.exports = {
		UserExternalType,
		UserRole,
	};
});
