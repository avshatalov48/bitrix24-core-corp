/**
 * @module im/messenger/db/repository/user
 */
jn.define('im/messenger/db/repository/user', (require, exports, module) => {
	const { Type } = require('type');

	const {
		UserTable,
	} = require('im/messenger/db/table/user');
	const { DateHelper } = require('im/messenger/lib/helper');

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
				const userToAdd = this.validateRestUser(user);

				userListToAdd.push(userToAdd);
			});

			return this.userTable.add(userListToAdd, true);
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
				result.name = ChatUtils.htmlspecialcharsback(user.name);
			}

			if (Type.isStringFilled(user.first_name))
			{
				result.firstName = ChatUtils.htmlspecialcharsback(user.first_name);
			}

			if (Type.isStringFilled(user.last_name))
			{
				result.lastName = ChatUtils.htmlspecialcharsback(user.last_name);
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

				result.departments = JSON.stringify(result.departments);
			}

			if (Type.isStringFilled(user.work_position))
			{
				result.workPosition = ChatUtils.htmlspecialcharsback(user.work_position);
			}

			if (Type.isPlainObject(user.phones))
			{
				result.phones = JSON.stringify(user.phones);
			}
			else
			{
				result.phones = JSON.stringify({
					workPhone: '',
					personalMobile: '',
					personalPhone: '',
					innerPhone: '',
				});
			}

			if (Type.isStringFilled(user.external_auth_id))
			{
				result.externalAuthId = user.external_auth_id;
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

			if (Type.isObject(user.bot_data))
			{
				result.botData = {
					appId: user.bot_data.app_id,
					code: user.bot_data.code,
					isHidden: user.bot_data.is_hidden,
					isSupportOpenline: user.bot_data.is_support_openline,
					type: user.bot_data.type,
				};
			}
			else
			{
				result.botData = {};
			}

			if (result.botData)
			{
				result.botData = JSON.stringify(result.botData);
			}

			if (Type.isBoolean(user.connector))
			{
				result.connector = user.connector;
			}

			if (Type.isStringFilled(user.last_activity_date))
			{
				result.lastActivityDate = DateHelper.cast(user.last_activity_date).toISOString();
			}
			else if (Type.isDate(user.last_activity_date))
			{
				result.lastActivityDate = user.last_activity_date.toISOString();
			}

			if (Type.isStringFilled(user.mobile_last_date))
			{
				result.mobileLastDate = DateHelper.cast(user.mobile_last_date).toISOString();
			}
			else if (Type.isDate(user.mobile_last_date))
			{
				result.mobileLastDate = user.mobile_last_date.toISOString();
			}

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
				result = encodeURI(result);
			}

			return result;
		}
	}

	module.exports = {
		UserRepository,
	};
});
