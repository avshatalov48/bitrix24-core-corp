/**
 * @module statemanager/redux/slices/users/meta
 */
jn.define('statemanager/redux/slices/users/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'mobile:users';
	const usersAdapter = createEntityAdapter();

	module.exports = {
		sliceName,
		usersAdapter,
	};
});
