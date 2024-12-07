/**
 * @module tasks/statemanager/redux/slices/groups
 */
jn.define('tasks/statemanager/redux/slices/groups', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { StateCache } = require('statemanager/redux/state-cache');
	const { createEntityAdapter, createSlice } = require('statemanager/redux/toolkit');

	const reducerName = 'tasks:groups';
	const groupsAdapter = createEntityAdapter();
	const initialState = StateCache.getReducerState(reducerName, groupsAdapter.getInitialState());

	const {
		selectById: selectGroupById,
		selectAll: selectAllGroups,
	} = groupsAdapter.getSelectors((state) => state[reducerName]);

	const prepareGroup = ({ id, name, image, resizedImage100, additionalData, dateStart, dateFinish }) => ({
		id: Number(id),
		name,
		image,
		resizedImage100,
		additionalData,
		dateStart,
		dateFinish,
	});

	const prepareGroupFromEntitySelector = (group) => {
		const datePlan = group?.customData?.datePlan;

		return ({
			id: Number(group.id),
			name: group.title,
			image: group.imageUrl,
			dateStart: datePlan.dateStart,
			dateFinish: datePlan.dateFinish,
			additionalData: {},
		});
	};

	const groupsSlice = createSlice({
		name: reducerName,
		initialState,
		reducers: {
			groupsUpserted: {
				reducer: groupsAdapter.upsertMany,
				prepare: (groups) => ({
					payload: groups.map((group) => prepareGroup(group)),
				}),
			},
			groupsAdded: {
				reducer: groupsAdapter.addMany,
				prepare: (groups) => ({
					payload: groups.map((group) => prepareGroup(group)),
				}),
			},
			groupsUpsertedFromEntitySelector: {
				reducer: groupsAdapter.upsertMany,
				prepare: (groups) => ({
					payload: groups.map((group) => prepareGroupFromEntitySelector(group)),
				}),
			},
			groupsAddedFromEntitySelector: {
				reducer: groupsAdapter.addMany,
				prepare: (groups) => ({
					payload: groups.map((group) => prepareGroupFromEntitySelector(group)),
				}),
			},
		},
		extraReducers: (builder) => {
			builder
				.addCase('tasks:tasks/updateRelatedTasks/fulfilled', (state, action) => {
					const { data } = action.payload;
					if (data)
					{
						const { updatedNewRelatedTasks = [] } = data;
						const { groups = [] } = updatedNewRelatedTasks;
						if (Array.isArray(groups) && groups.length > 0)
						{
							groupsAdapter.upsertMany(state, groups.map((group) => prepareGroup(group)));
						}
					}
				})
				.addCase('tasks:tasks/updateSubTasks/fulfilled', (state, action) => {
					const { data } = action.payload;
					if (data)
					{
						const { updatedNewRelatedTasks = [] } = data;
						const { groups } = updatedNewRelatedTasks;

						if (Array.isArray(groups) && groups.length > 0)
						{
							groupsAdapter.upsertMany(state, groups.map((group) => prepareGroup(group)));
						}
					}
				});
		},
	});

	const {
		groupsUpserted,
		groupsAdded,
		groupsUpsertedFromEntitySelector,
		groupsAddedFromEntitySelector,
	} = groupsSlice.actions;

	ReducerRegistry.register(reducerName, groupsSlice.reducer);

	module.exports = {
		groupsUpserted,
		groupsAdded,
		groupsUpsertedFromEntitySelector,
		groupsAddedFromEntitySelector,
		selectAllGroups,
		selectGroupById,
	};
});
