/* eslint-disable no-param-reassign */
/**
 * @module crm/statemanager/redux/slices/stage-counters
 */
jn.define('crm/statemanager/redux/slices/stage-counters', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { StateCache } = require('statemanager/redux/state-cache');
	const {
		createAsyncThunk,
		createEntityAdapter,
		createSlice,
		createDraftSafeSelector,
	} = require('statemanager/redux/toolkit');
	const { CategoryAjax } = require('crm/ajax');

	const statusTypes = {
		success: 'success',
		failure: 'failure',
		pending: 'pending',
	};

	const reducerName = 'crm:stageCounters';
	const adapter = createEntityAdapter({});
	const initialState = StateCache.getReducerState(reducerName, adapter.getInitialState());

	const fetchStageCounters = createAsyncThunk(
		`${reducerName}/fetchStageCounters`,
		async (params) => {
			const {
				entityTypeId,
				categoryId,
			} = params;
			const result = await CategoryAjax.fetch('getCounters', params);

			return {
				data: result.data.stages,
				entityTypeId,
				kanbanSettingsId: categoryId,
			};
		},
		{
			condition: ({ entityTypeId, categoryId, params = {}, forceFetch = false }, { getState }) => {
				const state = getState()[reducerName];
				const status = state.status;
				const stateEntityTypeId = state.entityTypeId;
				const stateKanbanSettingsId = state.kanbanSettingsId;

				const newFilter = JSON.stringify(params?.filter);

				return !(
					(status === statusTypes.pending || status === statusTypes.success)
					&& stateEntityTypeId === entityTypeId
					&& stateKanbanSettingsId === categoryId
					&& newFilter === state.filter
				) || forceFetch;
			},
		},
	);

	const slice = createSlice({
		name: reducerName,
		initialState,
		reducers: {
			counterIncremented: (state, action) => {
				const {
					id,
					amount,
					count,
				} = action.payload;

				const counter = state.entities[id];
				if (counter)
				{
					counter.total += amount;
					counter.count += count;
				}
			},
			counterDecremented: (state, action) => {
				const {
					id,
					amount,
					count,
				} = action.payload;

				const counter = state.entities[id];
				if (counter)
				{
					counter.total -= amount;
					counter.count -= count;
				}
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase(fetchStageCounters.pending, (state, action) => {
					state.status = statusTypes.pending;
					state.entityTypeId = action.meta.arg.entityTypeId;
					state.kanbanSettingsId = action.meta.arg.categoryId;
					state.filter = JSON.stringify(action.meta.arg?.params?.filter);
				})
				.addCase(fetchStageCounters.fulfilled, (state, action) => {
					state.status = statusTypes.success;

					const {
						entityTypeId,
						kanbanSettingsId,
						data = [],
					} = action.payload;
					const newData = data.map((item) => ({
						...item,
						entityTypeId,
						kanbanSettingsId,
					}));

					adapter.upsertMany(state, newData);
				})
				.addCase(fetchStageCounters.rejected, (state) => {
					state.status = statusTypes.failure;
				})
				.addCase('crm:stage/createCrmStage/fulfilled', (state, action) => {
					const data = action.payload.data;

					const preparedCounter = {
						id: data.id,
						count: 0,
						total: 0,
						currency: null,
						dropzone: false,
					};

					adapter.addOne(state, preparedCounter);
				})
			;
		},
	});

	const selectStatus = createDraftSafeSelector(
		(state) => state[reducerName],
		(stageCounters) => stageCounters.status,
	);

	const { reducer, actions } = slice;

	const {
		selectById,
		selectEntities,
		selectAll,
	} = adapter.getSelectors((state) => state[reducerName]);

	const selectByIdOrByEntityAndKanban = createDraftSafeSelector(
		(state, id) => {
			if (Number.isFinite(id))
			{
				return selectById(state, id);
			}

			if (typeof id === 'string')
			{
				const [entityTypeId, kanbanSettingsId] = id.split('_').map(Number);
				if (Number.isFinite(entityTypeId) && Number.isFinite(kanbanSettingsId))
				{
					const counters = selectAll(state).filter((counter) => {
						return counter.entityTypeId === entityTypeId && counter.kanbanSettingsId === kanbanSettingsId;
					});

					return {
						id,
						count: counters.reduce((acc, counter) => acc + counter.count, 0),
						total: counters.reduce((acc, counter) => acc + counter.total, 0),
						dropzone: false,
						currency: counters[0]?.currency,
					};
				}
			}

			return {};
		},
		(counter) => counter,
	);

	const {
		counterIncremented,
		counterDecremented,
	} = actions;

	ReducerRegistry.register(reducerName, reducer);

	module.exports = {
		statusTypes,

		fetchStageCounters,
		counterIncremented,
		counterDecremented,

		selectById: selectByIdOrByEntityAndKanban,
		selectEntities,
		selectStatus,
	};
});
