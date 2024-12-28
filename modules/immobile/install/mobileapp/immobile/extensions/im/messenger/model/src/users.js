/* eslint-disable no-param-reassign */

/**
 * @module im/messenger/model/users
 */
jn.define('im/messenger/model/users', (require, exports, module) => {
	const { Type } = require('type');
	const { DateHelper } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { UserType, UserColor } = require('im/messenger/const');
	const logger = LoggerManager.getInstance().getLogger('model--users');

	const userDefaultElement = Object.freeze({
		id: 0,
		name: '',
		firstName: '',
		lastName: '',
		avatar: '',
		color: UserColor.default,
		type: UserType.user,
		workPosition: '',
		gender: 'M',
		extranet: false,
		network: false,
		bot: false,
		botData: {},
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
		isCompleteInfo: true,
	});

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
			 * @return {UsersModelState[]}
			 */
			getList: (state) => () => {
				const userList = [];

				Object.keys(state.collection).forEach((userId) => {
					const user = state.collection[userId];
					if (user.isCompleteInfo === true)
					{
						userList.push(user);
					}
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
			 * @return {UsersModelState[]}
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
						return {
							...userDefaultElement,
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

			/** @function usersModel/set */
			set: (store, payload) => {
				let result = [];
				if (Type.isArray(payload))
				{
					result = payload.map((user) => {
						return {
							...userDefaultElement,
							...validate(user),
							isCompleteInfo: true,
						};
					});
				}

				if (result.length === 0)
				{
					return false;
				}

				store.commit('set', {
					actionName: 'set',
					data: {
						userList: result,
					},
				});

				return true;
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
						modelUser.isCompleteInfo = false;
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
								isCompleteInfo: true,
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

	function validate(fields, options = {})
	{
		const result = {};
		const {
			fromLocalDatabase,
		} = options;

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

		if (Type.isStringFilled(fields.type))
		{
			result.type = fields.type;
		}
		else if (fromLocalDatabase && Type.isNil(fields.type))
		{
			if (Type.isBoolean(fields.bot))
			{
				result.type = UserType.bot;
			}
			else if (Type.isBoolean(fields.extranet))
			{
				result.type = UserType.extranet;
			}
			else
			{
				result.type = UserType.user;
			}
		}

		if (Type.isStringFilled(fields.avatar))
		{
			if (fromLocalDatabase === true)
			{
				result.avatar = fields.avatar;
			}
			else
			{
				result.avatar = prepareAvatar(fields.avatar);
			}
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

		if (Type.isObject(fields.bot_data))
		{
			result.botData = {
				appId: fields.bot_data.app_id,
				code: fields.bot_data.code,
				isHidden: fields.bot_data.is_hidden,
				isSupportOpenline: fields.bot_data.is_support_openline,
				type: fields.bot_data.type,
			};
		}
		else
		{
			result.botData = {};
		}

		if (Type.isObject(fields.botData))
		{
			result.botData = fields.botData;
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

		if (Type.isBoolean(fields.isCompleteInfo))
		{
			result.isCompleteInfo = fields.isCompleteInfo;
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

	module.exports = { usersModel, userDefaultElement };
});
