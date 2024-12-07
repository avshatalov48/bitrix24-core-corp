/* eslint-disable no-param-reassign */
/**
 * @module crm/statemanager/redux/slices/stage-settings
 */
jn.define('crm/statemanager/redux/slices/stage-settings', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { StateCache } = require('statemanager/redux/state-cache');
	const {
		createEntityAdapter,
		createSlice,
		createDraftSafeSelector,
		createAsyncThunk,
	} = require('statemanager/redux/toolkit');

	const { StageAjax, CategoryAjax } = require('crm/ajax');
	const {
		getTunnelUniqueId,
		prepareTunnelsBeforeSave,
		selectItemsByIds: selectTunnelsEntities,
	} = require('crm/statemanager/redux/slices/tunnels');

	const reducerName = 'crm:stage';
	const adapter = createEntityAdapter({});
	const initialState = StateCache.getReducerState(reducerName, adapter.getInitialState());

	const {
		selectById,
		selectEntities,
		selectAll,
	} = adapter.getSelectors((state) => state[reducerName]);

	const selectStatusIdById = createDraftSafeSelector(
		selectById,
		(stage) => stage?.statusId,
	);

	const createCrmStage = createAsyncThunk(
		`${reducerName}/createCrmStage`,
		async (
			{ entityTypeId, fields, stageId, categoryId, kanbanSettingsId },
			{ getState, rejectWithValue },
		) => {
			try
			{
				const { sort } = selectById(getState(), stageId) || {};
				const result = await StageAjax.create(entityTypeId, categoryId, {
					...fields,
					sort: sort + 1,
				});

				if (result.status !== 'success')
				{
					return rejectWithValue({
						...result,
					});
				}

				return {
					...result,
					kanbanSettingsId,
				};
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		},
	);

	const updateCrmStage = createAsyncThunk(
		`${reducerName}/updateCrmStage`,
		async (
			{ entityTypeId, fields, kanbanSettingsId },
			{ rejectWithValue, getState },
		) => {
			try
			{
				const {
					tunnels: tunnelsIds,
				} = selectById(getState(), fields.id) || [];
				const tunnelsBeforeUpdate = Object.values(selectTunnelsEntities(getState(), tunnelsIds));

				let tunnels = [];
				let preparedTunnels = [];
				if (fields.tunnels)
				{
					tunnels = fields.tunnels;
					preparedTunnels = prepareTunnelsBeforeSave(fields.tunnels);
				}
				else
				{
					tunnels = tunnelsBeforeUpdate;
					preparedTunnels = prepareTunnelsBeforeSave(tunnelsBeforeUpdate);
				}

				const response = await StageAjax.update(entityTypeId, {
					...fields,
					tunnels: preparedTunnels,
				});

				if (response.status !== 'success')
				{
					return rejectWithValue({
						...response,
					});
				}

				return {
					...response,
					fields,
					kanbanSettingsId,
					tunnels,
					tunnelsBeforeUpdate,
				};
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		},
	);

	const deleteCrmStage = createAsyncThunk(
		`${reducerName}/deleteCrmStage`,
		async (
			{ entityTypeId, statusId, id, kanbanSettingsId, semantics },
			{ rejectWithValue },
		) => {
			try
			{
				const response = await StageAjax.delete(entityTypeId, statusId);

				if (response.status !== 'success')
				{
					return rejectWithValue({
						...response,
					});
				}

				return {
					...response,
					id,
					kanbanSettingsId,
					semantics,
				};
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		},
	);

	const slice = createSlice({
		name: reducerName,
		initialState,
		reducers: {},
		extraReducers: (builder) => {
			builder
				.addCase('crm:kanban/fetchCrmKanban/pending', (state, action) => {
					const { entityTypeId, categoryId } = action.meta.arg;
					const data = CategoryAjax.getCache('get', { entityTypeId, categoryId });
					if (data)
					{
						const {
							processStages,
							successStages,
							failedStages,
						} = data;

						const stages = [
							...processStages,
							...successStages,
							...failedStages,
						];

						const preparedStages = stages.map((stage) => ({
							...stage,
							tunnels: stage.tunnels.map((tunnel) => getTunnelUniqueId(tunnel)),
						}));

						adapter.addMany(state, preparedStages);
					}
				})
				.addCase('crm:kanban/fetchCrmKanban/fulfilled', (state, action) => {
					const {
						data: {
							processStages,
							successStages,
							failedStages,
						},
					} = action.payload;

					const stages = [
						...processStages,
						...successStages,
						...failedStages,
					];

					const preparedStages = stages.map((stage) => ({
						...stage,
						tunnels: stage.tunnels.map((tunnel) => getTunnelUniqueId(tunnel)),
					}));

					adapter.upsertMany(state, preparedStages);
				})
				.addCase(createCrmStage.pending, (state) => {
					state.status = 'loading';
				})
				.addCase(createCrmStage.fulfilled, (state, action) => {
					state.status = action.payload.status;
					const { data } = action.payload;

					adapter.addOne(state, data);
				})
				.addCase(createCrmStage.rejected, (state, action) => {
					state.status = action.payload.status;
				})
				.addCase(updateCrmStage.pending, (state) => {
					state.status = 'loading';
				})
				.addCase(updateCrmStage.fulfilled, (state, action) => {
					const {
						fields,
						tunnels,
						status,
					} = action.payload;
					state.status = status;

					let tunnelsIds = [];
					if (Array.isArray(tunnels))
					{
						tunnelsIds = tunnels.map(({ id }) => id);
					}

					const stage = state.entities[fields.id];

					if (stage)
					{
						adapter.upsertOne(state, {
							...stage,
							...fields,
							tunnels: tunnelsIds || stage.tunnels,
						});
					}
				})
				.addCase(updateCrmStage.rejected, (state, action) => {
					state.status = action.payload.status;
				})
				.addCase(deleteCrmStage.pending, (state) => {
					state.status = 'loading';
				})
				.addCase(deleteCrmStage.fulfilled, (state, action) => {
					const {
						status,
						id,
					} = action.payload;
					state.status = status;

					adapter.removeOne(state, id);
				})
				.addCase(deleteCrmStage.rejected, (state, action) => {
					state.status = action.payload.status;
				})
			;
		},
	});

	const selectIdAndStatusByIds = createDraftSafeSelector(
		selectAll,
		(store, ids) => ids,
		(items, ids) => ids.map((id) => {
			const item = items.find(({ id: itemId }) => itemId === id);

			return item ? { id: item.id, statusId: item.statusId } : null;
		}),
	);

	const { reducer } = slice;

	ReducerRegistry.register(reducerName, reducer);

	module.exports = {
		selectById,
		selectEntities,
		selectStatusIdById,
		selectIdAndStatusByIds,

		createCrmStage,
		updateCrmStage,
		deleteCrmStage,
	};
});
