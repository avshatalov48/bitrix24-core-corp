/**
 * @module disk/statemanager/redux/slices/files/selector
 */
jn.define('disk/statemanager/redux/slices/files/selector', (require, exports, module) => {
	const { createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const { sliceName, fileListAdapter } = require('disk/statemanager/redux/slices/files/meta');
	const { FileType } = require('disk/enum');

	const {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,
	} = fileListAdapter.getSelectors((state) => state[sliceName]);

	const selectRightsById = createDraftSafeSelector(
		(state, id) => selectById(state, id),
		(diskObject) => diskObject?.rights ?? null,
	);

	module.exports = {
		selectAll,
		selectById,
		selectEntities,
		selectIds,
		selectTotal,

		selectRightsById,
	};
});
