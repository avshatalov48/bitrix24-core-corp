/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/users
 */
jn.define('im/messenger/model/users', (require, exports, module) => {
	const { UsersCache } = require('im/messenger/cache');
	const { Type } = require('type');
	const { Logger } = require('im/messenger/lib/logger');

	const elementState = {
		id: 0,
		name: '',
		firstName: '',
		lastName: '',
		avatar: '',
		color: '#048bd0',
		workPosition: '',
		gender: 'M',
		extranet: false,
		network: false,
		bot: false,
		connector: false,
		externalAuthId: 'default',
		status: '',
		idle: false,
		lastActivityDate: false,
		mobileLastDate: false,
		isOnline: false,
		isMobileOnline: false,
		birthday: false,
		isBirthday: false,
		absent: false,
		isAbsent: false,
		departments: [],
		departmentName: '',
		phones: {
			workPhone: '',
			personalMobile: '',
			personalPhone: '',
			innerPhone: '',
		},
	};

	const usersModel = {
		namespaced: true,
		state: () => ({
			collection: {},
		}),
		getters: {
			/**
			 * @function usersModel/getUserById
			 * @return {UsersModelState|undefined}
			 */
			getUserById: (state) => (userId) => {
				return state.collection[userId];
			},

			/**
			 * @function usersModel/getUserList
			 * @return {UsersModelState[]}
			 */
			getUserList: (state) => () => {
				const userList = [];

				Object.keys(state.collection).forEach((userId) => {
					userList.push(state.collection[userId]);
				});

				return userList;
			},
		},
		actions: {
			/** @function usersModel/setState */
			setState: (store, payload) => {
				store.commit('setState', payload);
			},

			/** @function usersModel/set */
			set: (store, payload) => {
				let result = [];
				if (Type.isArray(payload))
				{
					result = payload.map((user) => {
						return {
							...elementState,
							...validate(user),
						};
					});
				}

				if (result.length === 0)
				{
					return false;
				}

				store.commit('set', result);

				return true;
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

				store.commit('set', result);
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
									...elementState,
									...validate(user),
								});
							}
						}
					});
				}

				store.commit('set', result);
			},

			/** @function usersModel/delete */
			delete: (store, payload) => {
				const existingItem = store.state.collection[payload.id];
				if (!existingItem)
				{
					return false;
				}

				store.commit('delete', { id: payload.id });

				return true;
			},
		},
		mutations: {
			setState: (state, payload) => {
				Logger.warn('usersModel: setState mutation', payload);

				state.collection = payload.collection;
			},
			set: (state, payload) => {
				Logger.warn('usersModel: set mutation', payload);

				payload.forEach((user) => {
					state.collection[user.id] = user;
				});

				UsersCache.save(state);
			},
			delete: (state, payload) => {
				Logger.warn('usersModel: delete mutation', payload);

				delete state.collection[payload.id];

				UsersCache.save(state);
			},
		},
	};

	function validate(fields)
	{
		const result = {};

		if (Type.isNumber(fields.id) || Type.isString(fields.id))
		{
			result.id = Number.parseInt(fields.id, 10);
		}

		if (Type.isStringFilled(fields.first_name))
		{
			fields.firstName = fields.first_name;
		}

		if (Type.isStringFilled(fields.last_name))
		{
			fields.lastName = fields.last_name;
		}

		if (Type.isStringFilled(fields.firstName))
		{
			result.firstName = ChatUtils.htmlspecialcharsback(fields.firstName);
		}

		if (Type.isStringFilled(fields.lastName))
		{
			result.lastName = ChatUtils.htmlspecialcharsback(fields.lastName);
		}

		if (Type.isStringFilled(fields.name))
		{
			fields.name = ChatUtils.htmlspecialcharsback(fields.name);
			result.name = fields.name;
		}

		if (Type.isStringFilled(fields.color))
		{
			result.color = fields.color;
		}

		if (Type.isStringFilled(fields.avatar))
		{
			result.avatar = prepareAvatar(fields.avatar);
		}

		if (Type.isStringFilled(fields.work_position))
		{
			fields.workPosition = fields.work_position;
		}

		if (Type.isStringFilled(fields.workPosition))
		{
			result.workPosition = ChatUtils.htmlspecialcharsback(fields.workPosition);
		}

		if (Type.isStringFilled(fields.gender))
		{
			result.gender = fields.gender === 'F' ? 'F' : 'M';
		}

		if (Type.isStringFilled(fields.birthday))
		{
			result.birthday = fields.birthday;
		}

		if (Type.isBoolean(fields.extranet))
		{
			result.extranet = fields.extranet;
		}

		if (Type.isBoolean(fields.network))
		{
			result.network = fields.network;
		}

		if (Type.isBoolean(fields.bot))
		{
			result.bot = fields.bot;
		}

		if (Type.isBoolean(fields.connector))
		{
			result.connector = fields.connector;
		}

		if (Type.isStringFilled(fields.external_auth_id))
		{
			fields.externalAuthId = fields.external_auth_id;
		}

		if (Type.isStringFilled(fields.externalAuthId))
		{
			result.externalAuthId = fields.externalAuthId;
		}

		if (Type.isStringFilled(fields.status))
		{
			result.status = fields.status;
		}

		if (!Type.isUndefined(fields.idle))
		{
			result.idle = fields.idle;
		}

		if (!Type.isUndefined(fields.last_activity_date))
		{
			fields.lastActivityDate = fields.last_activity_date;
		}

		if (!Type.isUndefined(fields.lastActivityDate))
		{
			result.lastActivityDate = fields.lastActivityDate;
		}

		if (!Type.isUndefined(fields.mobile_last_date))
		{
			fields.mobileLastDate = fields.mobile_last_date;
		}

		if (!Type.isUndefined(fields.mobileLastDate))
		{
			result.mobileLastDate = fields.lastActivityDate;
		}

		if (!Type.isUndefined(fields.absent))
		{
			result.absent = fields.absent;
		}

		if (Array.isArray(fields.departments))
		{
			result.departments = [];
			fields.departments.forEach((departmentId) => {
				departmentId = Number.parseInt(departmentId, 10);
				if (departmentId > 0)
				{
					result.departments.push(departmentId);
				}
			});
		}

		if (Type.isString(fields.departmentName))
		{
			result.departmentName = fields.departmentName;
		}

		if (Type.isPlainObject(fields.phones))
		{
			result.phones = preparePhones(fields.phones);
		}

		return result;
	}

	function prepareAvatar(avatar)
	{
		let result = '';

		if (!avatar || avatar.endsWith('/js/im/images/blank.gif'))
		{
			result = '';
		}
		else if (avatar.startsWith('http'))
		{
			result = avatar;
		}
		else
		{
			result = currentDomain + avatar;
		}

		if (result)
		{
			result = encodeURI(result);
		}

		return result;
	}

	function preparePhones(phones)
	{
		const result = {};

		if (!Type.isUndefined(phones.work_phone))
		{
			phones.workPhone = phones.work_phone;
		}

		if (Type.isStringFilled(phones.workPhone) || Type.isNumber(phones.workPhone))
		{
			result.workPhone = phones.workPhone.toString();
		}

		if (!Type.isUndefined(phones.personal_mobile))
		{
			phones.personalMobile = phones.personal_mobile;
		}

		if (Type.isStringFilled(phones.personalMobile) || Type.isNumber(phones.personalMobile))
		{
			result.personalMobile = phones.personalMobile.toString();
		}

		if (!Type.isUndefined(phones.personal_phone))
		{
			phones.personalPhone = phones.personal_phone;
		}

		if (Type.isStringFilled(phones.personalPhone) || Type.isNumber(phones.personalPhone))
		{
			result.personalPhone = phones.personalPhone.toString();
		}

		if (!Type.isUndefined(phones.inner_phone))
		{
			phones.innerPhone = phones.inner_phone;
		}

		if (Type.isStringFilled(phones.innerPhone) || Type.isNumber(phones.innerPhone))
		{
			result.innerPhone = phones.innerPhone.toString();
		}

		return result;
	}

	module.exports = { usersModel };
});
