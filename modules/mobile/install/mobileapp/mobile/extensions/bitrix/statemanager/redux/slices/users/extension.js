/**
 * @module statemanager/redux/slices/users
 */
jn.define('statemanager/redux/slices/users', (require, exports, module) => {
	const { ReducerRegistry } = require('statemanager/redux/reducer-registry');
	const { createEntityAdapter, createSlice } = require('statemanager/redux/toolkit');

	const usersInitialState = [];
	const usersAdapter = createEntityAdapter({});
	const emptyInitialState = usersAdapter.getInitialState();
	const filledState = usersAdapter.upsertMany(emptyInitialState, usersInitialState);

	const prepareUser = ({
		id,
		login,
		name,
		lastName,
		secondName,
		fullName,
		workPosition,
		link,
		avatarSizeOriginal,
		avatarSize100,
	}) => ({
		id: Number(id),
		login,
		name,
		lastName,
		secondName,
		fullName,
		workPosition,
		link,
		avatarSizeOriginal,
		avatarSize100,
	});

	const reducerName = 'mobile:users';
	const usersSlice = createSlice({
		name: reducerName,
		initialState: filledState,
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
		},
	});

	const {
		usersUpserted,
		usersAdded,
	} = usersSlice.actions;

	const { reducer } = usersSlice;
	const usersSelector = usersAdapter.getSelectors((state) => state[reducerName]);

	ReducerRegistry.register(reducerName, reducer);

	module.exports = {
		usersReducer: reducer,
		usersSelector,
		usersUpserted,
		usersAdded,
	};
});
