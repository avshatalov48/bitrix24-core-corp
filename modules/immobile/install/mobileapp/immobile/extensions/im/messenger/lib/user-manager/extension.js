/**
 * @module im/messenger/lib/user-manager
 */
jn.define('im/messenger/lib/user-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { DialogType, UserExternalType } = require('im/messenger/const');

	const AVAILABLE_EXTERNAL_AUTH_IDS = new Set([
		UserExternalType.default,
		UserExternalType.bot,
		UserExternalType.call,
	]);

	/**
	 * @class UserManager
	 */
	class UserManager
	{
		constructor(store)
		{
			this.store = store;
		}

		static getDialogForUser(user)
		{
			return {
				dialogId: user.id,
				avatar: user.avatar,
				color: user.color,
				name: user.name,
				type: DialogType.user,
			};
		}

		setUsersToModel(users)
		{
			if (Type.isPlainObject(users))
			{
				users = [users];
			}

			const filteredUsers = this.filterUsers(users);
			const dialogues = [];
			filteredUsers.forEach((user) => {
				dialogues.push(UserManager.getDialogForUser(user));
			});

			const usersPromise = this.store.dispatch('usersModel/set', filteredUsers);
			const dialoguesPromise = this.store.dispatch('dialoguesModel/set', dialogues);

			return Promise.all([usersPromise, dialoguesPromise]);
		}

		addShortUsersToModel(usersShort)
		{
			if (Type.isPlainObject(usersShort))
			{
				usersShort = [usersShort];
			}

			return this.store.dispatch('usersModel/addShort', usersShort);
		}

		filterUsers(users)
		{
			return users.filter((user) => {
				const userExternalAuthId = user.externalAuthId || user.external_auth_id;

				return AVAILABLE_EXTERNAL_AUTH_IDS.has(userExternalAuthId);
			});
		}
	}

	module.exports = {
		UserManager,
	};
});
