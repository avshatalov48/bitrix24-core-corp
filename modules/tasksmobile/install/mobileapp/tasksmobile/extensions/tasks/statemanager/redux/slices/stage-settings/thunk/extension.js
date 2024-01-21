/**
 * @module tasks/statemanager/redux/slices/stage-settings/thunk
 */
jn.define('tasks/statemanager/redux/slices/stage-settings/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { sliceName } = require('tasks/statemanager/redux/slices/stage-settings/meta');
	const {
		addStageOnServer,
		deleteStageOnServer,
		updateStageOnServer,
	} = require('tasks/statemanager/redux/slices/stage-settings/thunk/src/data-provider');

	const addStage = createAsyncThunk(
		`${sliceName}/addStage`,
		async ({ filterParams, name, color, afterId }, { rejectWithValue }) => {
			try
			{
				const result = await addStageOnServer({
					view: filterParams.view,
					projectId: filterParams.projectId,
					name,
					color,
					afterId,
				});
				if (result.status === 'success')
				{
					return {
						status: result.status,
						filterParams,
						...result.data.stage,
					};
				}

				return rejectWithValue({
					filterParams,
					...result,
				});
			}
			catch (error)
			{
				console.error(error);
			}
		},
	);

	const deleteStage = createAsyncThunk(
		`${sliceName}/deleteStage`,
		async ({ stageId, view, projectId, ownerId }, { rejectWithValue }) => {
			try
			{
				const response = await deleteStageOnServer({ stageId, view, projectId });
				const preparedData = {
					...response,
					stageId,
					view,
					projectId,
					ownerId,
				};
				if (preparedData.status === 'success')
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
	);

	const updateStage = createAsyncThunk(
		`${sliceName}/updateStage`,
		async ({ view, projectId, stageId, name, color }, { rejectWithValue }) => {
			try
			{
				const preparedColor = color.replace('#', '');
				const response = await updateStageOnServer({
					view,
					projectId,
					stageId,
					name,
					color: preparedColor,
				});
				const preparedData = {
					...response,
					view,
					projectId,
				};

				if (preparedData.status === 'success')
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
	);

	module.exports = {
		addStage,
		deleteStage,
		updateStage,
	};
});
