/**
 * @module im/messenger/db/repository/user
 */
jn.define('im/messenger/db/repository/user', (require, exports, module) => {
	/* global ChatUtils */
	const { Type } = require('type');

	const {
		UserTable,
	} = require('im/messenger/db/table/user');
	const { DateHelper, Url } = require('im/messenger/lib/helper');

	/**
	 * @class UserRepository
	 */
	class UserRepository
	{
		constructor()
		{
			this.userTable = new UserTable();
		}

		async saveFromModel(userList)
		{
			const userListToAdd = [];

			userList.forEach((user) => {
				const userToAdd = this.userTable.validate(user);

				userListToAdd.push(userToAdd);
			});

			return this.userTable.add(userListToAdd, true);
		}

		async saveShortFromModel(userList)
		{
			const userCollectionToAdd = {};
			const idList = new Set();
			userList.forEach((user) => {
				userCollectionToAdd[user.id] = this.userTable.validate(user);
				idList.add(user.id);
			});

			const existingUsers = await this.userTable.getListByIds([...idList], false);
			existingUsers.items.forEach((user) => {
				const name = userCollectionToAdd[user.id].name;
				if (Type.isStringFilled(name))
				{
					// eslint-disable-next-line no-param-reassign
					user.name = name;
				}

				const avatar = userCollectionToAdd[user.id].avatar;
				if (Type.isStringFilled(avatar))
				{
					// eslint-disable-next-line no-param-reassign
					user.avatar = avatar;
				}

				userCollectionToAdd[user.id] = user;
			});

			return this.userTable.add(Object.values(userCollectionToAdd), true);
		}

		async saveFromRest(userList)
		{
			const userListToAdd = [];
			userList.forEach((user) => {
				const userToAdd = this.userTable.validate(this.validateRestUser(user));

				userListToAdd.push(userToAdd);
			});

			return this.userTable.add(userListToAdd, true);
		}

		async saveFromPush(userList)
		{
			const userListMergePromiseList = [];
			userList.forEach((user) => {
				const userToAdd = this.validatePushUser(user);
				const mergePromise = this.userTable.merge(userToAdd.id, (existingUser) => {
					return {
						...existingUser,
						...userToAdd,
					};
				});

				userListMergePromiseList.push(mergePromise);
			});

			return Promise.all(userListMergePromiseList);
		}

		validateRestUser(user)
		{
			const result = {};

			if (Type.isNumber(user.id) || Type.isString(user.id))
			{
				result.id = Number.parseInt(user.id, 10);
			}

			if (Type.isStringFilled(user.name))
			{
				result.name = jnComponent.convertHtmlEntities(user.name);
			}

			if (Type.isStringFilled(user.first_name))
			{
				result.firstName = jnComponent.convertHtmlEntities(user.first_name);
			}

			if (Type.isStringFilled(user.firstName))
			{
				result.firstName = jnComponent.convertHtmlEntities(user.firstName);
			}

			if (Type.isStringFilled(user.last_name))
			{
				result.lastName = jnComponent.convertHtmlEntities(user.last_name);
			}

			if (Type.isStringFilled(user.lastName))
			{
				result.lastName = jnComponent.convertHtmlEntities(user.lastName);
			}

			if (Type.isStringFilled(user.gender))
			{
				result.gender = user.gender === 'F' ? 'F' : 'M';
			}

			if (Type.isStringFilled(user.avatar))
			{
				result.avatar = this.prepareAvatar(user.avatar);
			}

			if (Type.isStringFilled(user.color))
			{
				result.color = user.color;
			}

			if (Type.isStringFilled(user.type))
			{
				result.type = user.type;
			}

			if (Array.isArray(user.departments))
			{
				result.departments = [];
				user.departments.forEach((id) => {
					const departmentId = Number.parseInt(id, 10);
					if (departmentId > 0)
					{
						result.departments.push(departmentId);
					}
				});
			}

			if (Type.isStringFilled(user.work_position))
			{
				result.workPosition = jnComponent.convertHtmlEntities(user.work_position);
			}

			if (Type.isStringFilled(user.workPosition))
			{
				result.workPosition = ChatUtils.htmlspecialcharsback(user.workPosition);
			}

			if (Type.isPlainObject(user.phones))
			{
				result.phones = user.phones;
			}
			else
			{
				result.phones = {
					workPhone: '',
					personalMobile: '',
					personalPhone: '',
					innerPhone: '',
				};
			}

			if (Type.isStringFilled(user.external_auth_id))
			{
				result.externalAuthId = user.external_auth_id;
			}

			if (Type.isStringFilled(user.externalAuthId))
			{
				result.externalAuthId = user.externalAuthId;
			}

			if (Type.isBoolean(user.extranet))
			{
				result.extranet = user.extranet;
			}

			if (Type.isBoolean(user.network))
			{
				result.network = user.network;
			}

			if (Type.isBoolean(user.bot))
			{
				result.bot = user.bot;
			}

			if (Type.isStringFilled(user.birthday))
			{
				result.birthday = user.birthday;
			}

			if (Type.isStringFilled(user.absent))
			{
				result.absent = user.absent;
			}

			if (Type.isObject(user.bot_data))
			{
				result.botData = {
					appId: user.bot_data.app_id,
					code: user.bot_data.code,
					isHidden: user.bot_data.is_hidden,
					isSupportOpenline: user.bot_data.is_support_openline,
					type: user.bot_data.type,
					backgroundId: user.bot_data.background_id,
				};
			}
			else if (Type.isObject(user.botData))
			{
				result.botData = user.botData;
			}
			else
			{
				result.botData = {};
			}

			if (Type.isBoolean(user.connector))
			{
				result.connector = user.connector;
			}

			if (Type.isStringFilled(user.last_activity_date))
			{
				result.lastActivityDate = DateHelper.cast(user.last_activity_date);
			}
			else if (Type.isDate(user.last_activity_date))
			{
				result.lastActivityDate = user.last_activity_date;
			}
			else if (Type.isStringFilled(user.lastActivityDate))
			{
				result.lastActivityDate = DateHelper.cast(user.lastActivityDate);
			}

			if (Type.isStringFilled(user.mobile_last_date))
			{
				result.mobileLastDate = DateHelper.cast(user.mobile_last_date);
			}
			else if (Type.isStringFilled(user.mobileLastDate))
			{
				result.mobileLastDate = DateHelper.cast(user.mobileLastDate);
			}
			else if (Type.isDate(user.mobile_last_date))
			{
				result.mobileLastDate = user.mobile_last_date;
			}
			else if (Type.isDate(user.mobileLastDate))
			{
				result.mobileLastDate = user.mobileLastDate;
			}

			result.isCompleteInfo = true; // for data from the server, trust the full complete info

			return result;
		}

		prepareAvatar(avatar)
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
				const urlHelper = Url.createFromPath(result);
				if (!urlHelper.isEncoded)
				{
					result = encodeURI(result);
				}
			}

			return result;
		}

		validatePushUser(user)
		{
			return this.validateRestUser(user);
		}
	}

	module.exports = {
		UserRepository,
	};
});
