/* eslint-disable no-param-reassign */
/* global dateFormatter */

/**
 * @module im/messenger/model/users/model
 */
jn.define('im/messenger/model/users/model', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { validate } = require('im/messenger/model/users/validator');
	const { userDefaultElement } = require('im/messenger/model/users/default-element');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('model--users');

	/** @type {UsersModel} */
	const usersModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function usersModel/getById
			 * @return {?UsersModelState}
			 */
			getById: (state) => (userId) => {
				return state.collection[userId];
			},

			/**
			 * @function usersModel/getList
			 * @return {Array<UsersModelState>}
			 */
			getList: (state) => () => {
				const userList = [];

				Object.keys(state.collection).forEach((userId) => {
					userList.push(state.collection[userId]);
				});

				return userList;
			},

			/**
			 * @function usersModel/getByIdList
			 * @return {Array<UsersModelState>}
			 */
			getByIdList: (state, getters) => (idList) => {
				if (!Type.isArrayFilled(idList))
				{
					return [];
				}

				const userList = [];
				idList.forEach((id) => {
					const dialog = getters.getById(id);
					if (dialog)
					{
						userList.push(dialog);
					}
				});

				return userList;
			},

			/** @function usersModel/getCollectionByIdList */
			getCollectionByIdList: (state, getters) => (idList) => {
				if (!Type.isArrayFilled(idList))
				{
					return [];
				}

				const collection = {};
				idList.forEach((id) => {
					const dialog = getters.getById(id);
					if (dialog)
					{
						collection[id] = dialog;
					}
				});

				return collection;
			},

			/**
			 * @function usersModel/getListWithUncompleted
			 * @return {Array<UsersModelState>}
			 */
			getListWithUncompleted: (state) => () => {
				const userList = [];

				Object.keys(state.collection).forEach((userId) => {
					userList.push(state.collection[userId]);
				});

				return userList;
			},

			/**
			 * @function usersModel/hasBirthday
			 * @return boolean
			 */
			hasBirthday: (state) => (rawUserId) => {
				const userId = Number.parseInt(rawUserId, 10);

				const user = state.collection[userId];
				if (userId <= 0 || !user)
				{
					return false;
				}

				const timestampInSeconds = Math.round(Date.now() / 1000);

				return user.birthday === dateFormatter.get(timestampInSeconds, 'd-m');
			},

			/**
			 * @function usersModel/hasVacation
			 * @return boolean
			 */
			hasVacation: (state) => (rawUserId) => {
				const userId = Number.parseInt(rawUserId, 10);

				const user = state.collection[userId];
				if (userId <= 0 || !user)
				{
					return false;
				}

				const absentDate = DateHelper.cast(user.absent, false);
				if (absentDate === false)
				{
					return false;
				}

				return absentDate > new Date();
			},

			/**
			 * @function usersModel/getBotBackgroundId
			 * @return {DialogBackgroundId|null}
			 */
			getBotBackgroundId: (state, getters) => (userId) => {
				const userModel = getters.getById(userId);
				if (!userModel)
				{
					return null;
				}

				return userModel.botData.backgroundId ?? null;
			},
		},
		actions: {
			/** @function usersModel/setState */
			setState: (store, payload) => {
				store.commit('setState', {
					actionName: 'setState',
					data: {
						collection: payload.collection,
					},
				});
			},

			/** @function usersModel/setFromLocalDatabase */
			setFromLocalDatabase: (store, payload) => {
				let result = [];
				if (Type.isArray(payload))
				{
					result = payload.map((user) => {
						const existingItem = store.state.collection[user.id];
						const mergeItem = existingItem || userDefaultElement;

						return {
							...mergeItem,
							...validate(user, {
								fromLocalDatabase: true,
							}),
						};
					});
				}

				if (result.length === 0)
				{
					return false;
				}

				store.commit('set', {
					actionName: 'setFromLocalDatabase',
					data: {
						userList: result,
					},
				});

				return true;
			},

			/**
			 * @function usersModel/setFromSync
			 */
			setFromSync: async (store, userList) => {
				return store.dispatch('set', { userList, actionName: 'setFromSync' });
			},

			/** @function usersModel/set */
			set: (store, payload) => {
				let userList = [];
				let actionName = 'set';
				if (!Type.isArray(payload) && Type.isPlainObject(payload))
				{
					userList = payload.userList;
					actionName = payload.actionName;
				}

				if (Type.isArray(payload))
				{
					userList = payload;
				}

				const result = userList.map((user) => {
					const existingItem = store.state.collection[user.id];
					const mergeItem = existingItem || userDefaultElement;

					return {
						...mergeItem,
						...validate(user),
					};
				});

				if (result.length === 0)
				{
					return false;
				}

				store.commit('set', {
					actionName,
					data: {
						userList: result,
					},
				});

				return true;
			},

			/** @function usersModel/setFromPush */
			setFromPush: (store, payload) => {
				if (!Type.isArrayFilled(payload))
				{
					return;
				}

				const userList = payload.map((user) => validate(user));

				store.commit('setFromPush', {
					actionName: 'setFromPush',
					data: {
						userList,
					},
				});
			},

			/** @function usersModel/addShort */
			addShort: (store, payload) => {
				if (!Type.isArray(payload) && Type.isPlainObject(payload))
				{
					payload = [payload];
				}

				const userList = [];
				payload.forEach((user) => {
					const modelUser = validate(user);
					const existingUser = store.state.collection[modelUser.id];
					if (!existingUser)
					{
						userList.push({
							...userDefaultElement,
							...modelUser,
						});
					}
				});

				if (Type.isArrayFilled(userList))
				{
					store.commit('set', {
						actionName: 'addShort',
						data: {
							userList,
						},
					});
				}
			},

			/** @function usersModel/update */
			update: (store, payload) => {
				const result = [];
				if (Type.isArray(payload))
				{
					payload.forEach((user) => {
						const existingItem = store.state.collection[user.id];
						if (existingItem)
						{
							result.push({
								...store.state.collection[user.id],
								...validate(user),
							});
						}
					});
				}

				if (result.length > 0)
				{
					store.commit('set', {
						actionName: 'update',
						data: {
							userList: result,
						},
					});
				}
			},

			/** @function usersModel/merge */
			merge: (store, payload) => {
				const result = [];
				if (Type.isArray(payload))
				{
					payload.forEach((user) => {
						const existingItem = store.state.collection[user.id];
						if (existingItem)
						{
							result.push({
								...store.state.collection[user.id],
								...validate(user),
							});
						}
						else
						{
							const isHasBaseProperty = (
								user.id
								&& user.name
								&& (user.firstName || user.first_name)
							);

							if (isHasBaseProperty)
							{
								result.push({
									...userDefaultElement,
									...validate(user),
								});
							}
						}
					});
				}

				if (result.length > 0)
				{
					store.commit('set', {
						actionName: 'merge',
						data: {
							userList: result,
						},
					});
				}
			},

			/** @function usersModel/delete */
			delete: (store, payload) => {
				const existingItem = store.state.collection[payload.id];
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', {
					actionName: 'delete',
					data: {
						id: payload.id,
					},
				});

				return true;
			},
		},
		mutations: {
			/**
			 * @param state
			 * @param {MutationPayload<UsersSetStateData, UsersSetStateActions>} payload
			 */
			setState: (state, payload) => {
				logger.log('usersModel: setState mutation', payload);

				const {
					collection,
				} = payload.data;

				state.collection = collection;
			},

			/**
			 * @param state
			 * @param {MutationPayload<UsersSetData, UsersSetActions>} payload
			 */
			set: (state, payload) => {
				logger.log('usersModel: set mutation', payload);

				const {
					userList,
				} = payload.data;

				userList.forEach((user) => {
					state.collection[user.id] = user;
				});
			},

			/**
			 * @param state
			 * @param {MutationPayload<UsersSetFromPushData, UsersSetFromPushActions>} payload
			 */
			setFromPush: (state, payload) => {
				logger.log('usersModel: setFromPush mutation', payload);

				const { userList } = payload.data;

				for (const user of userList)
				{
					state.collection[user.id] = {
						...userDefaultElement,
						...state.collection[user.id],
						...user,
					};
				}
			},

			/**
			 * @param state
			 * @param {MutationPayload<UsersDeleteData, UsersDeleteActions>} payload
			 */
			delete: (state, payload) => {
				logger.log('usersModel: delete mutation', payload);

				const {
					id,
				} = payload.data;

				delete state.collection[id];
			},
		},
	};

	module.exports = { usersModel, userDefaultElement };
});
