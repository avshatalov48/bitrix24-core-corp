/**
 * @module im/messenger/const/user
 */

jn.define('im/messenger/const/user', (require, exports, module) => {
	const UserType = Object.freeze({
		user: 'user',
		bot: 'bot',
		extranet: 'extranet',
		collaber: 'collaber',
	});

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

	const UserColor = Object.freeze({
		default: '#048bd0',
	});

	module.exports = {
		UserType,
		UserExternalType,
		UserRole,
		UserColor,
	};
});
