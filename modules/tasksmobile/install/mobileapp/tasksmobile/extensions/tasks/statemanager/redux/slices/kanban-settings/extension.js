/**
 * @module tasks/statemanager/redux/slices/kanban-settings
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings', (require, exports, module) => {
	const { getUniqId } = require('tasks/statemanager/redux/slices/kanban-settings/tools');
	const { slice } = require('tasks/statemanager/redux/slices/kanban-settings/src/slice');

	const {
		fetchStages,
		updateStagesOrder,
	} = require('tasks/statemanager/redux/slices/kanban-settings/thunk');

	const {
		selectById,
		selectStatus,
		selectStages,
		selectNameById,
		selectCanEdit,
		selectCanMoveStage,
	} = require('tasks/statemanager/redux/slices/kanban-settings/selector');

	module.exports = {
		selectById,
		selectNameById,
		selectStatus,
		selectStages,
		selectCanEdit,
		selectCanMoveStage,

		getUniqId,

		fetchStages,
		updateStagesOrder,

		slice,
	};
});
