/**
 * @module im/messenger/const/user
 */

jn.define('im/messenger/const/user', (require, exports, module) => {

	const UserExternalType = Object.freeze({
		default: 'default',
		bot: 'bot',
		call: 'call'
	});

	module.exports = {
		UserExternalType,
	};
});
