/* eslint-disable no-param-reassign */
/**
 * @module crm/statemanager/redux/slices/stage-counters
 */
jn.define('crm/statemanager/redux/slices/stage-counters', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
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
	const initialState = adapter.getInitialState();
	const filledState = adapter.upsertMany(initialState, []);

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
			condition: ({ entityTypeId, categoryId }, { getState }) => {
				const state = getState()[reducerName];
				const status = state.status;
				const stateEntityTypeId = state.entityTypeId;
				const stateCategoryId = state.categoryId;

				return !(
					status === statusTypes.pending
					&& stateEntityTypeId === entityTypeId
					&& stateCategoryId === categoryId
				);
			},
		},
	);

	const slice = createSlice({
		name: reducerName,
		initialState: filledState,
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
					state.categoryId = action.meta.arg.categoryId;
				})
				.addCase(fetchStageCounters.fulfilled, (state, action) => {
					state.status = statusTypes.success;

					const {
						entityTypeId,
						kanbanSettingsId,
						data,
					} = action.payload;
					const totalCounter = data.reduce((acc, item) => {
						return {
							id: `${entityTypeId}_${kanbanSettingsId}`,
							currency: item.currency,
							dropzone: false,
							count: acc.count + item.count,
							total: acc.total + item.total,
						};
					}, { id: '', currency: '', dropzone: '', count: 0, total: 0 });

					adapter.upsertMany(state, {
						...data,
						totalCounter,
					});
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
	} = adapter.getSelectors((state) => state[reducerName]);

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

		selectById,
		selectEntities,
		selectStatus,
	};
});
