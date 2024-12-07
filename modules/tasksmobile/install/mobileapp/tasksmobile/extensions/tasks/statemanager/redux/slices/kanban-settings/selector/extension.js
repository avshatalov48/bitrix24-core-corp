/**
 * @module tasks/statemanager/redux/slices/kanban-settings/selector
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/selector', (require, exports, module) => {
	const {	createDraftSafeSelector } = require('statemanager/redux/toolkit');
	const {
		sliceName,
		adapter,
	} = require('tasks/statemanager/redux/slices/kanban-settings/meta');

	const { selectById } = adapter.getSelectors((state) => state[sliceName]);

	const selectStatus = createDraftSafeSelector(
		(state) => state[sliceName],
		(kanbanSettings) => kanbanSettings.status,
	);

	const selectStages = createDraftSafeSelector(
		(state, id) => selectById(state, id),
		(kanbanSettings) => kanbanSettings?.stages || [],
	);

	const selectCanEdit = createDraftSafeSelector(
		(state, id) => selectById(state, id),
		(kanbanSettings) => kanbanSettings?.canEdit || false,
	);

	const selectNameById = createDraftSafeSelector(
		(state, id) => selectById(state, id),
		(kanbanSettings) => kanbanSettings?.name || '',
	);

	const selectCanMoveStage = createDraftSafeSelector(
		(state, id) => selectById(state, id),
		(kanbanSettings) => kanbanSettings?.canMoveStage || false,
	);

	module.exports = {
		selectById,
		selectStatus,
		selectStages,
		selectNameById,
		selectCanEdit,
		selectCanMoveStage,
	};
});
