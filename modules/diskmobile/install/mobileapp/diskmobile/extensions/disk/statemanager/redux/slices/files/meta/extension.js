/**
 * @module disk/statemanager/redux/slices/files/meta
 */
jn.define('disk/statemanager/redux/slices/files/meta', (require, exports, module) => {
	const { createEntityAdapter } = require('statemanager/redux/toolkit');

	const sliceName = 'disk:files';
	const fileListAdapter = createEntityAdapter();

	module.exports = {
		sliceName,
		fileListAdapter,
	};
});
