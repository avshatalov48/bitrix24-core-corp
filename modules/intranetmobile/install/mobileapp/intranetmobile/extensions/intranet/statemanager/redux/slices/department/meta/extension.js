/**
 * @module intranet/statemanager/redux/slices/department/meta
 */
jn.define('intranet/statemanager/redux/slices/department/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'intranet:department';
	const departmentListAdapter = createEntityAdapter();

	module.exports = {
		sliceName,
		departmentListAdapter,
	};
});
