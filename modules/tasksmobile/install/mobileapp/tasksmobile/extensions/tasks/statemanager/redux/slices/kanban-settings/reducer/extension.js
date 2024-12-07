/**
 * @module tasks/statemanager/redux/slices/kanban-settings/reducer
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/reducer', (require, exports, module) => {
	const { adapter } = require('tasks/statemanager/redux/slices/kanban-settings/meta');
	const { getStageIds, getUniqId } = require('tasks/statemanager/redux/slices/kanban-settings/tools');

	const setKanbanSettings = (state, action) => {
		const {
			view,
			projectId,
			userId,
			stages,
			canEdit,
			canMoveStage,
		} = action.payload;

		if (Array.isArray(stages))
		{
			adapter.upsertOne(state, {
				id: getUniqId(view, projectId, userId),
				kanbanSettingId: view,
				projectId,
				canEdit,
				canMoveStage,
				stages: getStageIds(stages),
			});
		}
	};

	module.exports = {
		setKanbanSettings,
	};
});
