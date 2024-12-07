/**
 * @module tasks/statemanager/redux/slices/kanban-settings/thunk
 */
jn.define('tasks/statemanager/redux/slices/kanban-settings/thunk', (require, exports, module) => {
	const { createAsyncThunk } = require('statemanager/redux/toolkit');
	const { sliceName } = require('tasks/statemanager/redux/slices/kanban-settings/meta');
	const { getUniqId } = require('tasks/statemanager/redux/slices/kanban-settings/tools');
	const { statusTypes } = require('tasks/statemanager/redux/types');
	const { stringifyWithKeysSort } = require('tasks/statemanager/redux/utils');
	const { md5 } = require('utils/hash');

	const {
		getStagesFromCache,
		loadStagesFromServer,
		updateStagesOrderOnServer,
	} = require('tasks/statemanager/redux/slices/kanban-settings/thunk/src/data-provider');

	const fetchStages = createAsyncThunk(
		`${sliceName}/fetchStages`,
		async (loadStagesParams, { rejectWithValue }) => {
			try
			{
				const response = await loadStagesFromServer(loadStagesParams);
				const preparedData = {
					...response,
					...loadStagesParams,
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
			condition: (loadStagesParams, { getState, extra }) => {
				const state = getState()[sliceName];
				const status = state.status;
				const id = getUniqId(
					loadStagesParams.view,
					loadStagesParams.projectId,
					loadStagesParams.searchParams.ownerId,
				);
				const currentSearchRequestId = md5(stringifyWithKeysSort(loadStagesParams.searchParams));

				return !(
					status === statusTypes.pending
					&& state.id === id
					&& state.lastSearchRequestId === currentSearchRequestId
				);
			},
		},
	);

	const updateStagesOrder = createAsyncThunk(`${sliceName}/updateStagesOrder`, async ({
		view,
		projectId,
		fields,
	}, { rejectWithValue }) => {
		try
		{
			const response = await updateStagesOrderOnServer({
				view,
				projectId,
				stagesOrder: fields.stageIdsBySemantics.processStages,
			});
			const preparedData = {
				...response,
				view,
				projectId,
				fields,
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
	});

	module.exports = {
		getStagesFromCache,
		fetchStages,
		updateStagesOrder,
	};
});
