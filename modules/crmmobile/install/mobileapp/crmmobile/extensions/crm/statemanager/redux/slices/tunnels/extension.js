/**
 * @module crm/statemanager/redux/slices/tunnels
 */
jn.define('crm/statemanager/redux/slices/tunnels', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const {
		createEntityAdapter,
		createSlice,
	} = require('statemanager/redux/toolkit');

	const reducerName = 'crm:tunnels';
	const adapter = createEntityAdapter({});
	const initialState = adapter.getInitialState();
	const filledState = adapter.upsertMany(initialState, []);

	const prepareTunnelsToSave = (tunnels) => {
		return tunnels.map((tunnel) => {
			return {
				id: getTunnelUniqueId(tunnel),
				...tunnel,
			};
		});
	};

	const prepareTunnelsBeforeSave = (tunnels) => {
		if (!Array.isArray(tunnels))
		{
			return [];
		}

		return tunnels.map((tunnel) => {
			if (tunnel.isNewTunnel)
			{
				return {
					srcCategory: tunnel.srcCategoryId,
					srcStage: tunnel.srcStageStatusId,
					dstCategory: tunnel.dstCategoryId,
					dstStage: tunnel.dstStageStatusId,
				};
			}

			return {
				srcCategory: tunnel.srcCategoryId,
				srcStage: tunnel.srcStageStatusId,
				dstCategory: tunnel.dstCategoryId,
				dstStage: tunnel.dstStageStatusId,
				robot: {
					Name: tunnel.robot.name,
				},
			};
		});
	};

	const findDeletedTunnelIds = (tunnelsBeforeUpdate, tunnelsAfterUpdate) => {
		return tunnelsBeforeUpdate.reduce((acc, tunnel) => {
			const isTunnelExists = tunnelsAfterUpdate.some((tunnelAfterUpdate) => {
				return tunnelAfterUpdate.id === tunnel.id;
			});

			if (!isTunnelExists)
			{
				return [
					...acc,
					tunnel.id,
				];
			}

			return acc;
		}, []);
	};

	const getTunnelUniqueId = (tunnel) => {
		return `${tunnel.srcCategoryId}_${tunnel.srcStageId}_${tunnel.dstCategoryId}_${tunnel.dstStageId}`;
	};

	const slice = createSlice({
		name: reducerName,
		initialState: filledState,
		reducers: {},
		extraReducers: (builder) => {
			builder
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

					const tunnels = stages.reduce((acc, stage) => {
						if (stage.tunnels)
						{
							return [
								...acc,
								...prepareTunnelsToSave(stage.tunnels),
							];
						}

						return acc;
					}, []);

					adapter.upsertMany(state, tunnels);
				})
				.addCase('crm:kanban/fetchCrmKanbanList/fulfilled', (state, action) => {
					const {
						data: {
							categories: kanbanSettingsList,
						},
					} = action.payload;

					const preparedTunnels = kanbanSettingsList.reduce((acc, kanbanSettings) => {
						const {
							tunnels,
						} = kanbanSettings;

						if (Array.isArray(tunnels))
						{
							return [
								...acc,
								...prepareTunnelsToSave(tunnels),
							];
						}

						return acc;
					}, []);
					adapter.upsertMany(state, preparedTunnels);
				})
				.addCase('crm:stage/updateCrmStage/fulfilled', (state, action) => {
					const {
						tunnels,
						tunnelsBeforeUpdate,
						fields,
					} = action.payload;

					if (!fields.tunnels)
					{
						return;
					}

					const deletedTunnels = findDeletedTunnelIds(tunnelsBeforeUpdate, tunnels);

					const preparedData = tunnels.map((tunnel) => {
						const {
							isNewTunnel,
							robot,
							...rest
						} = tunnel;

						return rest;
					});

					adapter.removeMany(state, deletedTunnels);
					adapter.upsertMany(state, preparedData);
				})
			;
		},
	});

	const { reducer } = slice;

	const {
		selectById,
		selectEntities,
	} = adapter.getSelectors((state) => state[reducerName]);

	ReducerRegistry.register(reducerName, reducer);

	module.exports = {
		selectById,
		selectEntities,
		getTunnelUniqueId,
		prepareTunnelsBeforeSave,
	};
});
