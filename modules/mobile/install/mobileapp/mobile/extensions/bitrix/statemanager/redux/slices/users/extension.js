/**
 * @module statemanager/redux/slices/users
 */
jn.define('statemanager/redux/slices/users', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { sliceName, usersAdapter } = require('statemanager/redux/slices/users/meta');
	const { usersSelector } = require('statemanager/redux/slices/users/selector');
	const { updateUserThunk } = require('statemanager/redux/slices/users/thunk');

	const initialState = StateCache.getReducerState(sliceName, usersAdapter.getInitialState());

	const prepareUser = ({
		id,
		login,
		isAdmin,
		isCollaber,
		isExtranet,
		name,
		lastName,
		secondName,
		fullName,
		link,
		avatarSizeOriginal,
		avatarSize100,
		workPosition,
		personalMobile,
		personalPhone,
	}) => ({
		id: Number(id),
		login,
		isAdmin,
		isCollaber,
		isExtranet,
		name,
		lastName,
		secondName,
		fullName,
		workPosition,
		link,
		avatarSizeOriginal,
		avatarSize100,
		personalMobile,
		personalPhone,
	});

	const prepareUserFromEntitySelector = (user) => ({
		id: Number(user.id),
		login: user.customData?.login,
		name: user.customData?.name,
		lastName: user.customData?.lastName,
		secondName: user.customData?.secondName,
		fullName: user.title,
		workPosition: user.customData?.position,
		link: `/company/personal/user/${user.id}/`,
		avatarSizeOriginal: user.imageUrl,
		avatarSize100: user.imageUrl,
		isExtranet: user.entityType === 'extranet',
		isCollaber: user.entityType === 'collaber',
	});

	const usersSlice = createSlice({
		name: sliceName,
		initialState,
		reducers: {
			usersUpserted: {
				reducer: usersAdapter.upsertMany,
				prepare: (users) => ({
					payload: users.map((user) => prepareUser(user)),
				}),
			},
			usersAdded: {
				reducer: usersAdapter.addMany,
				prepare: (users) => ({
					payload: users.map((user) => prepareUser(user)),
				}),
			},
			usersAddedFromEntitySelector: {
				reducer: usersAdapter.addMany,
				prepare: (users) => ({
					payload: users.map((user) => prepareUserFromEntitySelector(user)),
				}),
			},
			usersUpsertedFromEntitySelector: {
				reducer: usersAdapter.upsertMany,
				prepare: (users) => ({
					payload: users.map((user) => prepareUserFromEntitySelector(user)),
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
						const { users = [] } = updatedNewRelatedTasks;

						if (Array.isArray(users) && users.length > 0)
						{
							usersAdapter.upsertMany(state, users.map((user) => prepareUser(user)));
						}
					}
				})
				.addCase('tasks:tasks/updateSubTasks/fulfilled', (state, action) => {
					const { data } = action.payload;
					if (data)
					{
						const { updatedNewRelatedTasks = [] } = data;
						const { users } = updatedNewRelatedTasks;

						if (Array.isArray(users) && users.length > 0)
						{
							usersAdapter.upsertMany(state, users.map((user) => prepareUser(user)));
						}
					}
				})
				.addCase(updateUserThunk.fulfilled, (state, action) => {
					const { data, isSuccess } = action.payload;
					if (isSuccess && data)
					{
						const preparedData = Object.keys(data).reduce((acc, key) => {
							// eslint-disable-next-line no-param-reassign
							acc[key.toLowerCase()] = data[key];

							return acc;
						}, {});

						usersAdapter.upsertOne(state, preparedData);
					}
				});
		},
	});

	const {
		usersUpserted,
		usersAdded,
		usersAddedFromEntitySelector,
		usersUpsertedFromEntitySelector,
	} = usersSlice.actions;

	const { reducer } = usersSlice;

	ReducerRegistry.register(sliceName, reducer);

	module.exports = {
		usersReducer: reducer,
		usersSelector,
		usersUpserted,
		usersAdded,
		usersAddedFromEntitySelector,
		usersUpsertedFromEntitySelector,
	};
});
