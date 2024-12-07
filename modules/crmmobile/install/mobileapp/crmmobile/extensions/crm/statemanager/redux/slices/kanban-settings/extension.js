/* eslint-disable no-param-reassign */
/**
 * @module crm/statemanager/redux/slices/kanban-settings
 */
jn.define('crm/statemanager/redux/slices/kanban-settings', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { StateCache } = require('statemanager/redux/state-cache');
	const {
		createAsyncThunk,
		createDraftSafeSelector,
		createSlice,
		createEntityAdapter,
	} = require('statemanager/redux/toolkit');
	const { CategoryAjax } = require('crm/ajax');
	const { getTunnelUniqueId } = require('crm/statemanager/redux/slices/tunnels');

	const ACTION_GET_CATEGORY = 'get';
	const SEMANTICS = {
		PROCESS: 'P',
		SUCCESS: 'S',
		FAILED: 'F',
	};
	const STATUS = {
		loading: 'loading',
		success: 'success',
		failed: 'failed',
	};

	const getCrmKanbanUniqId = (entityTypeId, categoryId) => {
		return `${entityTypeId}_${categoryId}`;
	};

	const getStageIds = (stages) => {
		return stages.map((stage) => stage.id);
	};

	const getTunnelsIdsFromStages = (stages) => {
		return stages.reduce((acc, stage) => {
			if (stage.tunnels.length > 0)
			{
				return [
					...acc,
					...stage.tunnels.map((tunnel) => {
						return getTunnelUniqueId(tunnel);
					}),
				];
			}

			return acc;
		}, []);
	};

	const getStageSemantics = (stageSemantics) => {
		if (stageSemantics === SEMANTICS.SUCCESS)
		{
			return 'successStages';
		}

		if (stageSemantics === SEMANTICS.FAILED)
		{
			return 'failedStages';
		}

		return 'processStages';
	};

	const checkEntityTypeId = (entityTypeId) => {
		if (!Type.existsById(entityTypeId))
		{
			throw new Error(`Wrong entity type id {${entityTypeId}}.`);
		}
	};

	const sortKanbanListByDefault = (kanbanList) => {
		return [...kanbanList].sort((a, b) => {
			if (a.isDefault && !b.isDefault)
			{
				return -1;
			}

			if (!a.isDefault && b.isDefault)
			{
				return 1;
			}

			return 0;
		});
	};

	const reducerName = 'crm:kanban';
	const adapter = createEntityAdapter({});
	const initialState = StateCache.getReducerState(
		reducerName,
		adapter.getInitialState({ isFetchedList: {} }),
	);

	const fetchCrmKanban = createAsyncThunk(
		`${reducerName}/fetchCrmKanban`,
		async (
			{ entityTypeId, kanbanSettingsId, categoryId },
			{ rejectWithValue },
		) => {
			try
			{
				checkEntityTypeId(entityTypeId);

				const result = await CategoryAjax.fetch(ACTION_GET_CATEGORY, {
					entityTypeId,
					categoryId,
				});

				if (result.status !== STATUS.success)
				{
					return rejectWithValue(result);
				}

				return result;
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		},
		{
			condition: ({ entityTypeId, categoryId, forceFetch = false }, { getState }) => {
				const state = getState();
				const status = state[reducerName].status;
				const currentEntityTypeId = state[reducerName].currentEntityTypeId;
				const currentKanbanSettingsId = state[reducerName].currentKanbanSettingsId;

				return forceFetch || !(
					(status === STATUS.loading || status === STATUS.success)
					&& currentEntityTypeId === entityTypeId
					&& currentKanbanSettingsId
					&& currentKanbanSettingsId === getCrmKanbanUniqId(entityTypeId, categoryId)
				);
			},
		},
	);

	const fetchCrmKanbanList = createAsyncThunk(
		`${reducerName}/fetchCrmKanbanList`,
		async ({ entityTypeId }) => {
			try
			{
				checkEntityTypeId(entityTypeId);

				const result = await CategoryAjax.fetch('getList', { entityTypeId });

				return {
					...result,
					entityTypeId,
				};
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		},
		{
			condition: ({ entityTypeId }, { getState }) => {
				const state = getState();
				const status = state[reducerName].status;
				const isFetchedList = state[reducerName].isFetchedList[entityTypeId];

				return !(
					status === STATUS.loading
					&& isFetchedList === entityTypeId
				);
			},
		},
	);

	const createCrmKanban = createAsyncThunk(
		`${reducerName}/createCrmKanban`,
		async (
			{ entityTypeId, fields },
			{ rejectWithValue },
		) => {
			try
			{
				checkEntityTypeId(entityTypeId);

				const result = await CategoryAjax.create(entityTypeId, fields);

				if (result.status !== STATUS.success)
				{
					return rejectWithValue({
						...result,
					});
				}

				return {
					...result,
					entityTypeId,
					fields,
				};
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		},
	);

	const updateCrmKanban = createAsyncThunk(
		`${reducerName}/updateCrmKanban`,
		async (
			{ entityTypeId, kanbanSettingsId, categoryId, fields },
			{ rejectWithValue },
		) => {
			try
			{
				checkEntityTypeId(entityTypeId);

				const {
					stageIdsBySemantics,
					...restFields
				} = fields;

				let preparedStages = null;

				if (stageIdsBySemantics && stageIdsBySemantics.processStages)
				{
					preparedStages = stageIdsBySemantics.processStages.map((stageId, index) => ({
						id: stageId,
						sort: index * 10 + 10,
					}));
				}
				const result = await CategoryAjax.update(entityTypeId, categoryId, {
					preparedStages,
					...restFields,
				});

				if (result.status !== STATUS.success)
				{
					return rejectWithValue({
						...result,
					});
				}

				return {
					...result,
					kanbanSettingsId,
					fields,
				};
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		},
	);

	const deleteCrmKanban = createAsyncThunk(
		`${reducerName}/deleteCrmKanban`,
		async (
			{ entityTypeId, kanbanSettingsId, categoryId },
			{ rejectWithValue },
		) => {
			try
			{
				checkEntityTypeId(entityTypeId);

				const result = await CategoryAjax.delete(entityTypeId, categoryId);

				if (result.status !== STATUS.success)
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

	const slice = createSlice({
		name: reducerName,
		initialState,
		reducers: {},
		extraReducers: (builder) => {
			builder
				.addCase(fetchCrmKanban.pending, (state, action) => {
					state.status = STATUS.loading;

					const { entityTypeId, categoryId } = action.meta.arg;
					state.currentEntityTypeId = entityTypeId;
					state.currentKanbanSettingsId = getCrmKanbanUniqId(entityTypeId, categoryId);

					const data = CategoryAjax.getCache(ACTION_GET_CATEGORY, {
						entityTypeId,
						categoryId,
					});
					if (data)
					{
						const {
							id,
							processStages,
							successStages,
							failedStages,
						} = data;

						const stages = [
							...processStages,
							...successStages,
							...failedStages,
						];

						const preparedData = {
							...data,
							id: getCrmKanbanUniqId(entityTypeId, id),
							entityTypeId,
							categoryId: id,
							processStages: getStageIds(processStages),
							successStages: getStageIds(successStages),
							failedStages: getStageIds(failedStages),
							tunnels: getTunnelsIdsFromStages(stages),
						};

						adapter.addOne(state, preparedData);
					}
				})
				.addCase(fetchCrmKanban.fulfilled, (state, action) => {
					const { entityTypeId } = action.meta.arg;
					const {
						data: {
							id,
							processStages,
							successStages,
							failedStages,
						},
						data,
						status,
					} = action.payload;

					state.status = status;

					const stages = [
						...processStages,
						...successStages,
						...failedStages,
					];

					const preparedData = {
						...data,
						id: getCrmKanbanUniqId(entityTypeId, id),
						entityTypeId,
						categoryId: id,
						processStages: getStageIds(processStages),
						successStages: getStageIds(successStages),
						failedStages: getStageIds(failedStages),
						tunnels: getTunnelsIdsFromStages(stages),
					};

					adapter.upsertOne(state, preparedData);
				})
				.addCase(fetchCrmKanban.rejected, (state, action) => {
					state.status = STATUS.failed;
				})
				.addCase(fetchCrmKanbanList.pending, (state, action) => {
					state.isFetchedList[action.meta.arg.entityTypeId] = true;
					state.status = STATUS.loading;
				})
				.addCase(fetchCrmKanbanList.fulfilled, (state, action) => {
					const {
						data: {
							categories: kanbanSettingsList = [],
							restrictions,
							canUserEditCategory,
						},
						status,
						entityTypeId,
					} = action.payload;

					state.status = status;
					state.restrictions = restrictions;
					state.canUserEditCategory = canUserEditCategory;

					const preparedData = kanbanSettingsList.map((kanbanSettings) => {
						const {
							id,
							processStages,
							successStages,
							failedStages,
							tunnels,
							...restFields
						} = kanbanSettings;

						const preparedId = getCrmKanbanUniqId(entityTypeId, id);
						const kanbanSettingsFromState = state.entities[preparedId];
						const preparedTunnels = tunnels.map((tunnel) => getTunnelUniqueId(tunnel));

						if (kanbanSettingsFromState)
						{
							return {
								id,
								restFields,
								tunnels: preparedTunnels,
							};
						}

						return {
							...kanbanSettings,
							id: preparedId,
							kanbanSettingsId: id,
							entityTypeId,
							categoryId: id,
							tunnels: preparedTunnels,
						};
					});

					adapter.upsertMany(state, preparedData);
				})
				.addCase(fetchCrmKanbanList.rejected, (state, action) => {
					state.status = action.payload.status;
				})
				.addCase(createCrmKanban.pending, (state) => {
					state.status = STATUS.loading;
				})
				.addCase(createCrmKanban.fulfilled, (state, action) => {
					const {
						data: id,
						entityTypeId,
						fields,
					} = action.payload;

					state.status = STATUS.success;

					const preparedData = {
						id: getCrmKanbanUniqId(entityTypeId, id),
						kanbanSettingsId: id,
						categoryId: id,
						entityTypeId,
						...fields,
					};

					adapter.upsertOne(state, preparedData);
				})
				.addCase(createCrmKanban.rejected, (state) => {
					state.status = STATUS.failed;
				})
				.addCase(updateCrmKanban.pending, (state) => {
					state.status = STATUS.loading;
				})
				.addCase(updateCrmKanban.fulfilled, (state, action) => {
					const {
						status,
						kanbanSettingsId,
						fields,
					} = action.payload;

					state.status = status;

					const {
						id,
						stageIdsBySemantics,
						...restFields
					} = fields;

					const kanbanSettings = state.entities[kanbanSettingsId];

					if (kanbanSettings)
					{
						adapter.upsertOne(state, {
							...kanbanSettings,
							...restFields,
							...stageIdsBySemantics,
						});
					}
				})
				.addCase(updateCrmKanban.rejected, (state) => {
					state.status = STATUS.failed;
				})
				.addCase(deleteCrmKanban.pending, (state) => {
					state.status = STATUS.loading;
				})
				.addCase(deleteCrmKanban.fulfilled, (state, action) => {
					state.status = action.payload.status;
					const {
						kanbanSettingsId,
					} = action.payload;

					adapter.removeOne(state, kanbanSettingsId);
				})
				.addCase(deleteCrmKanban.rejected, (state, action) => {
					state.status = action.payload.status;
				})
				.addCase('crm:stage/createCrmStage/fulfilled', (state, action) => {
					const {
						data: {
							id,
							semantics,
						},
						kanbanSettingsId,
					} = action.payload;
					const stageSemantics = getStageSemantics(semantics);
					const kanbanSettings = state.entities[kanbanSettingsId];

					if (kanbanSettings)
					{
						adapter.upsertOne(state, {
							...kanbanSettings,
							[stageSemantics]: [
								...kanbanSettings[stageSemantics],
								id,
							],
						});
					}
				})
				.addCase('crm:stage/deleteCrmStage/fulfilled', (state, action) => {
					const {
						id,
						kanbanSettingsId,
						semantics,
						status,
					} = action.payload;

					state.status = status;

					const kanbanSettings = state.entities[kanbanSettingsId];
					if (kanbanSettings)
					{
						const stageSemantics = getStageSemantics(semantics);
						const stageIds = kanbanSettings[stageSemantics];
						const filteredStageIds = stageIds.filter((stageId) => stageId !== id);

						adapter.upsertOne(state, {
							id: kanbanSettingsId,
							[stageSemantics]: filteredStageIds,
						});
					}
				})
				.addCase('crm:stage/updateCrmStage/fulfilled', (state, action) => {
					const {
						tunnels,
						kanbanSettingsId,
					} = action.payload;

					let tunnelsIds = [];
					if (Array.isArray(tunnels))
					{
						tunnelsIds = tunnels.map(({ id }) => id);
					}

					const kanbanSettings = state.entities[kanbanSettingsId];
					if (kanbanSettings)
					{
						adapter.upsertOne(state, {
							id: kanbanSettingsId,
							tunnels: tunnelsIds || kanbanSettings.tunnels,
						});
					}
				})
			;
		},
	});

	const {
		selectById,
		selectAll,
	} = adapter.getSelectors((state) => state[reducerName]);

	const selectStagesIdsBySemantics = createDraftSafeSelector(
		(state, id) => selectById(state, id),
		(kanbanSettings) => ({
			processStages: kanbanSettings?.processStages || [],
			successStages: kanbanSettings?.successStages || [],
			failedStages: kanbanSettings?.failedStages || [],
		}),
	);

	const selectStatus = createDraftSafeSelector(
		(state) => state[reducerName],
		(kanbanSettings) => kanbanSettings?.status || 'idle',
	);

	const selectRestrictions = createDraftSafeSelector(
		(state) => state[reducerName],
		(kanbanSettings) => kanbanSettings?.restrictions || [],
	);

	const selectByEntityTypeId = createDraftSafeSelector(
		(state, entityTypeId) => {
			const kanbanList = selectAll(state).filter((item) => item.entityTypeId === entityTypeId);

			return sortKanbanListByDefault(kanbanList);
		},
		(kanbanSettings) => kanbanSettings,
	);

	const selectCanUserEditCategory = createDraftSafeSelector(
		(state) => state[reducerName],
		(kanbanSettings) => kanbanSettings?.canUserEditCategory || false,
	);

	const selectIsFetchedList = createDraftSafeSelector(
		(state, entityTypeId) => {
			return ({
				kanbanSettings: state[reducerName],
				entityTypeId,
			});
		},
		({ kanbanSettings, entityTypeId }) => kanbanSettings?.isFetchedList[entityTypeId] || false,
	);

	const { reducer } = slice;

	ReducerRegistry.register(reducerName, reducer);

	module.exports = {
		getCrmKanbanUniqId,

		fetchCrmKanban,
		fetchCrmKanbanList,
		createCrmKanban,
		updateCrmKanban,
		deleteCrmKanban,

		selectById,
		selectStagesIdsBySemantics,
		selectStatus,
		selectRestrictions,
		selectByEntityTypeId,
		selectCanUserEditCategory,
		selectIsFetchedList,
		STATUS,
	};
});
