/**
 * @module tasks/statemanager/redux/slices/stage-settings
 */
jn.define('tasks/statemanager/redux/slices/stage-settings', (require, exports, module) => {
	const {
		selectById,
		selectEntities,
		selectByViewAndProjectId,
	} = require('tasks/statemanager/redux/slices/stage-settings/src/selector');

	const {
		addStage,
		deleteStage,
		updateStage,
	} = require('tasks/statemanager/redux/slices/stage-settings/thunk');

	const { slice } = require('tasks/statemanager/redux/slices/stage-settings/src/slice');

	module.exports = {
		selectById,
		selectEntities,
		selectByViewAndProjectId,
		addStage,
		deleteStage,
		updateStage,
		slice,
	};
});
