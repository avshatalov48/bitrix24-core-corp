/**
 * @module im/messenger/model/users
 */
jn.define('im/messenger/model/users', (require, exports, module) => {
	const { usersModel } = require('im/messenger/model/users/model');
	const { userDefaultElement } = require('im/messenger/model/users/default-element');

	module.exports = {
		usersModel,
		userDefaultElement,
	};
});
