/**
 * @module tasks/statemanager/redux/slices/kanban-settings/tools
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/tools', (require, exports, module) => {
	const { Views } = require('tasks/statemanager/redux/types');

	const getStageIds = (stages) => {
		return stages.map((stage) => stage.id);
	};

	const getUniqId = (view, projectId, ownerId = env.userId) => {
		if (view === Views.PLANNER)
		{
			return `${Views.PLANNER}_${ownerId}`;
		}

		if (view === Views.DEADLINE)
		{
			return Views.DEADLINE;
		}

		return `${Views.KANBAN}_${projectId}`;
	};

	module.exports = {
		getStageIds,
		getUniqId,
	};
});
