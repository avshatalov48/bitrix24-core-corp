/**
 * @module tasks/statemanager/redux/slices/kanban-settings/action
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/action', (require, exports, module) => {
	const { createAction } = require('statemanager/redux/toolkit');
	const { sliceName } = require('tasks/statemanager/redux/slices/kanban-settings/meta');

	const setKanbanSettingsActionName = `${sliceName}/setKanbanSettings`;

	const setKanbanSettings = createAction(setKanbanSettingsActionName, (
		{
			view,
			projectId,
			userId,
			stages,
			canEdit,
			canMoveStage,
		},
	) => ({
		type: setKanbanSettingsActionName,
		payload: {
			view,
			projectId,
			userId,
			stages,
			canEdit,
			canMoveStage,
		},
	}));

	module.exports = {
		setKanbanSettingsActionName,
		setKanbanSettings,
	};
});
