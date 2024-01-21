/**
 * @module tasks/statemanager/redux/slices/kanban-settings/thunk/src/data-provider
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/thunk/src/data-provider', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Views } = require('tasks/statemanager/redux/types');

	function getLoadStagesFromServerEndpoint(view)
	{
		switch (view)
		{
			case Views.PLANNER:
				return 'tasksmobile.Stage.getPlannerStages';
			case Views.DEADLINE:
				return 'tasksmobile.Stage.getDeadlineStages';
			case Views.KANBAN:
				return 'tasksmobile.Stage.getKanbanStages';
			default:
				return null;
		}
	}

	function getRunActionExecutor(stagesEndpoint, loadStagesParams)
	{
		return (
			new RunActionExecutor(stagesEndpoint, loadStagesParams)
				.setCacheTtl(7 * 24 * 3600) // 7 days
		);
	}

	function getStagesFromCache(loadStagesParams)
	{
		const stagesEndpoint = getLoadStagesFromServerEndpoint(loadStagesParams.view);
		if (stagesEndpoint === null)
		{
			return [];
		}

		const executor = getRunActionExecutor(stagesEndpoint, loadStagesParams);
		const cache = executor.getCache();

		return cache.getData()?.data ?? [];
	}

	function loadStagesFromServer(loadStagesParams)
	{
		return new Promise((resolve) => {
			const stagesEndpoint = getLoadStagesFromServerEndpoint(loadStagesParams.view);
			if (stagesEndpoint === null)
			{
				resolve([]);
			}
			else
			{
				getRunActionExecutor(stagesEndpoint, loadStagesParams)
					.setHandler((result) => resolve(result))
					.call(false);
			}
		});
	}

	function getUpdateStagesOrderEndpoint(view)
	{
		switch (view)
		{
			case Views.PLANNER:
				return 'tasksmobile.Stage.updatePlannerStagesOrder';
			case Views.KANBAN:
				return 'tasksmobile.Stage.updateKanbanStagesOrder';
			default:
				return null;
		}
	}

	function getUpdateStagesOrderPreparedParams(updateStagesOrderParams)
	{
		const preparedParams = {
			stagesOrder: updateStagesOrderParams.stagesOrder,
		};
		if (updateStagesOrderParams.view === Views.KANBAN)
		{
			preparedParams.projectId = updateStagesOrderParams.projectId;
		}

		return preparedParams;
	}

	function updateStagesOrderOnServer(updateStagesParams)
	{
		return new Promise((resolve) => {
			const endpoint = getUpdateStagesOrderEndpoint(updateStagesParams.view);
			if (endpoint === null)
			{
				resolve();
			}
			else
			{
				new RunActionExecutor(
					getUpdateStagesOrderEndpoint(updateStagesParams.view),
					getUpdateStagesOrderPreparedParams(updateStagesParams),
				)
					.setHandler((result) => resolve(result))
					.call(false);
			}
		});
	}

	module.exports = {
		getStagesFromCache,
		loadStagesFromServer,
		updateStagesOrderOnServer,
	};
});
