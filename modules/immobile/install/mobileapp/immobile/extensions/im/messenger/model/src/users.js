/**
 * @module im/messenger/model/users
 */
jn.define('im/messenger/model/users', (require, exports, module) => {

	const { UsersCache } = jn.require('im/messenger/cache');

	const usersModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			getUserById: (state) => (userId) => {
				return state.collection[userId];
			},
			getUserList: (state) => {
				const userList = [];

				Object.keys(state.collection).forEach((userId) => {
					userList.push(state.collection[userId]);
				});

				return userList;
			},
		},
		actions: {
			setState: (store, payload) =>
			{
				store.commit('setState', payload);
			},
			set: (store, payload) =>
			{
				store.commit('set', payload);
			},
			delete: (store, payload) =>
			{
				const existingItem = store.state.collection[payload.id];
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', { id: payload.id });
			},
		},
		mutations: {
			setState: (state, payload) => {
				state.collection = payload.collection;
			},
			set: (state, payload) => {
				payload.forEach((user) => {
					state.collection[user.id] = user;
				});

				UsersCache.save(state);
			},
			delete: (state, payload) => {
				delete state.collection[payload.id];

				UsersCache.save(state);
			},
		}
	};

	module.exports = { usersModel };
});
