/**
 * @module tasks/statemanager/redux/slices/stage-settings/thunk/src/data-provider
 */
jn.define('tasks/statemanager/redux/slices/stage-settings/thunk/src/data-provider', (require, exports, module) => {
	const { Views } = require('tasks/statemanager/redux/types');

	function getAddStageEndpoint(view)
	{
		switch (view)
		{
			case Views.PLANNER:
				return 'tasksmobile.Stage.addPlannerStage';
			case Views.KANBAN:
				return 'tasksmobile.Stage.addKanbanStage';
			default:
				return null;
		}
	}

	function getDeleteStageEndpoint(view)
	{
		switch (view)
		{
			case Views.PLANNER:
				return 'tasksmobile.Stage.deletePlannerStage';
			case Views.KANBAN:
				return 'tasksmobile.Stage.deleteKanbanStage';
			default:
				return null;
		}
	}

	function getUpdateStageEndpoint(view)
	{
		switch (view)
		{
			case Views.PLANNER:
				return 'tasksmobile.Stage.updatePlannerStage';
			case Views.KANBAN:
				return 'tasksmobile.Stage.updateKanbanStage';
			default:
				return null;
		}
	}

	function getAddStagePreparedParams(addStageParams)
	{
		const preparedParams = {
			name: addStageParams.name,
			color: addStageParams.color,
			afterId: addStageParams.afterId,
		};
		if (addStageParams.view === Views.KANBAN)
		{
			preparedParams.projectId = addStageParams.projectId;
		}

		return preparedParams;
	}

	function getDeleteStagePreparedParams(deleteStageParams)
	{
		const preparedParams = {
			stageId: deleteStageParams.stageId,
		};

		if (deleteStageParams.view === Views.KANBAN)
		{
			preparedParams.projectId = deleteStageParams.projectId;
		}

		return preparedParams;
	}

	function getUpdateStagePreparedParams(updateStageParams)
	{
		const preparedParams = {
			stageId: updateStageParams.stageId,
			name: updateStageParams.name,
			color: updateStageParams.color,
		};

		if (updateStageParams.view === Views.KANBAN)
		{
			preparedParams.projectId = updateStageParams.projectId;
		}

		return preparedParams;
	}

	function addStageOnServer(addStageParams)
	{
		return new Promise((resolve, reject) => {
			const endpoint = getAddStageEndpoint(addStageParams.view);
			if (endpoint === null)
			{
				reject();
			}
			else
			{
				new RunActionExecutor(endpoint, getAddStagePreparedParams(addStageParams))
					.setHandler((result) => resolve(result))
					.call(false);
			}
		});
	}

	function deleteStageOnServer(deleteStageParams)
	{
		return new Promise((resolve, reject) => {
			const endpoint = getDeleteStageEndpoint(deleteStageParams.view);
			if (endpoint === null)
			{
				reject();
			}
			else
			{
				new RunActionExecutor(endpoint, getDeleteStagePreparedParams(deleteStageParams))
					.setHandler((result) => resolve(result))
					.call(false);
			}
		});
	}

	function updateStageOnServer(updateStageParams)
	{
		return new Promise((resolve, reject) => {
			const endpoint = getUpdateStageEndpoint(updateStageParams.view);
			if (endpoint === null)
			{
				reject();
			}
			else
			{
				new RunActionExecutor(endpoint, getUpdateStagePreparedParams(updateStageParams))
					.setHandler((result) => resolve(result))
					.call(false);
			}
		});
	}

	module.exports = {
		addStageOnServer,
		deleteStageOnServer,
		updateStageOnServer,
	};
});
