/**
 * @module tasks/statemanager/redux/slices/tasks-stages/thunk
 */
jn.define('tasks/statemanager/redux/slices/tasks-stages/thunk', (require, exports, module) => {
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { isOnline } = require('device/connection');

	const { ViewMode } = require('tasks/enum');
	const { sliceName } = require('tasks/statemanager/redux/slices/tasks-stages/meta');

	const Actions = {
		userPlannerAction: 'tasksmobile.Task.updateUserPlannerTaskStage',
		projectPlannerAction: 'tasksmobile.Task.updateProjectPlannerTaskStage',
		userDeadlineAction: 'tasksmobile.Task.updateUserDeadlineTaskStage',
		projectDeadlineAction: 'tasksmobile.Task.updateProjectDeadlineTaskStage',
		kanbanAction: 'tasksmobile.Task.updateProjectKanbanTaskStage',
	};

	const { selectById } = require('tasks/statemanager/redux/slices/stage-settings/selector');

	const condition = () => isOnline();

	const getUpdateTaskStageEndpoint = ({ viewMode, projectId }) => {
		if (viewMode === ViewMode.KANBAN)
		{
			return Actions.kanbanAction;
		}

		if (viewMode === ViewMode.PLANNER)
		{
			if (projectId)
			{
				return Actions.projectPlannerAction;
			}

			return Actions.userPlannerAction;
		}

		if (viewMode === ViewMode.DEADLINE)
		{
			if (projectId)
			{
				return Actions.projectDeadlineAction;
			}

			return Actions.userDeadlineAction;
		}

		return null;
	};

	const updateTaskStage = createAsyncThunk(
		`${sliceName}/updateTaskStage`,
		async ({ taskId, stageId, projectId, view }, { rejectWithValue }) => {
			try
			{
				const response = await updateTaskStageOnServer({
					id: taskId,
					stageId,
					projectId,
					viewMode: view,
				});

				const preparedData = {
					...response,
					taskId,
					stageId,
					viewMode: view,
					projectId,
				};

				if (response.status === 'success')
				{
					return preparedData;
				}

				return rejectWithValue(preparedData);
			}
			catch (error)
			{
				console.error(error);
			}
		},
		{
			condition,
			getPendingMeta: (action, store) => {
				// this is needed to prepare stage to update task deadline on pending action
				// prepare stage for pending reducers
				const { stageId } = action.arg;
				const stage = selectById(store.getState(), stageId);

				return {
					stage,
				};
			},
		},
	);

	const updateTaskStageOnServer = ({
		id,
		stageId,
		projectId,
		viewMode,
	}) => {
		return new Promise((resolve) => {
			const endpoint = getUpdateTaskStageEndpoint({ viewMode, projectId });
			if (endpoint === null)
			{
				resolve();
			}
			else
			{
				new RunActionExecutor(
					endpoint,
					{
						id,
						stageId,
						projectId,
					},
				)
					.setHandler((result) => resolve(result))
					.call(false);
			}
		});
	};

	module.exports = {
		updateTaskStage,
	};
});
