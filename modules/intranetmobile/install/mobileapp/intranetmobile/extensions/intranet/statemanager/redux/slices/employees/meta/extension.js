/**
 * @module intranet/statemanager/redux/slices/employees/meta
 */
jn.define('intranet/statemanager/redux/slices/employees/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'intranet:employees';
	const userListAdapter = createEntityAdapter();

	module.exports = {
		sliceName,
		userListAdapter,
	};
});
