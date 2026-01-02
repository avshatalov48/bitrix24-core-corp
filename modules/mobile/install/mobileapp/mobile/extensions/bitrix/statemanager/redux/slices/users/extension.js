/**
 * @module statemanager/redux/slices/users
 */
jn.define('statemanager/redux/slices/users', (require, exports, module) => {
	const { StateCache } = require('statemanager/redux/state-cache');
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createSlice } = require('statemanager/redux/toolkit');
	const { sliceName, usersAdapter } = require('statemanager/redux/slices/users/meta');
	const { usersSelector, selectNonExistentUsersByIds } = require('statemanager/redux/slices/users/selector');
	const { updateUserThunk, fetchUsersIfNotLoaded, updateProfilePhoto } = require('statemanager/redux/slices/users/thunk');
	const { Type } = require('type');

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

	const prepareUserFromRest = (user) => ({
		id: Number(user.ID),
		login: user.LOGIN,
		name: user.NAME,
		lastName: user.LAST_NAME,
		secondName: user.SECOND_NAME,
		fullName: user.NAME_FORMATTED,
		email: user.EMAIL,
		workPhone: user.WORK_PHONE,
		workPosition: user.WORK_POSITION,
		link: `/company/personal/user/${user.ID}/`,
		avatarSizeOriginal: user.PERSONAL_PHOTO_ORIGINAL,
		avatarSize100: user.PERSONAL_PHOTO_ORIGINAL,
		isAdmin: user.STATUS === 'admin',
		isCollaber: user.STATUS === 'collaber',
		isExtranet: user.STATUS === 'extranet',
		personalMobile: user.PERSONAL_MOBILE,
		personalPhone: user.PERSONAL_PHONE,
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
				.addCase('intranet:department/fetchParentDepartments/fulfilled', (state, action) => {
					const { users } = action.payload;
					if (Type.isArrayFilled(users))
					{
						usersAdapter.upsertMany(state, users.map((user) => prepareUser(user)));
					}
				})
				.addCase('tasks:tasks/updateRelatedTasks/fulfilled', (state, action) => {
					const { data } = action.payload;
					if (data)
					{
						const { updatedNewRelatedTasks = [] } = data;
						const { users = [] } = updatedNewRelatedTasks;

						if (Type.isArrayFilled(users))
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

						if (Type.isArrayFilled(users))
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
				})
				.addCase(fetchUsersIfNotLoaded.fulfilled, (state, action) => {
					const { data, isSuccess } = action.payload;
					if (isSuccess && data)
					{
						usersAdapter.upsertMany(state, data.map((user) => prepareUserFromRest(user)));
					}
				})
				.addCase(updateProfilePhoto.fulfilled, (state, { payload, meta }) => {
					const { image } = payload;
					const userId = meta.arg.userId;

					if (image)
					{
						const { previewUrl } = image;
						usersAdapter.updateOne(state, {
							id: Number(userId),
							changes: {
								avatarSizeOriginal: previewUrl,
								avatarSize100: previewUrl,
							},
						});
					}
				})
				.addCase(updateProfilePhoto.rejected, (state, { payload }) => {
					const { error, errorDescription } = payload || {};

					if (error)
					{
						console.error(error, errorDescription);
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
		selectNonExistentUsersByIds,
		usersUpserted,
		usersAdded,
		usersAddedFromEntitySelector,
		usersUpsertedFromEntitySelector,
		updateProfilePhoto,
	};
});
