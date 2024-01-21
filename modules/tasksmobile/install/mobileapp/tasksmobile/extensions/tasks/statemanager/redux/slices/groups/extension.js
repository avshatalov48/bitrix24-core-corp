/**
 * @module tasks/statemanager/redux/slices/groups
 */
jn.define('tasks/statemanager/redux/slices/groups', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createEntityAdapter, createSlice } = require('statemanager/redux/toolkit');

	const groupsAdapter = createEntityAdapter();
	const {
		selectById: selectGroupById,
		selectAll: selectAllGroups,
	} = groupsAdapter.getSelectors((state) => state['tasks:groups']);

	const prepareGroup = ({ id, name, image, additionalData }) => ({
		id: Number(id),
		name,
		image,
		additionalData,
	});

	const groupsSlice = createSlice({
		name: 'tasks:groups',
		initialState: groupsAdapter.getInitialState(),
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
		},
	});

	const { groupsUpserted, groupsAdded } = groupsSlice.actions;

	ReducerRegistry.register('tasks:groups', groupsSlice.reducer);

	module.exports = {
		groupsUpserted,
		groupsAdded,
		selectAllGroups,
		selectGroupById,
	};
});
