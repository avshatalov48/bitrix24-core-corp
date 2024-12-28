/**
 * @module calendar/data-managers/user-manager
 */
jn.define('calendar/data-managers/user-manager', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const {
		usersSelector,
		usersUpserted,
	} = require('statemanager/redux/slices/users');

	/**
	 * @class UserManager
	 */
	class UserManager
	{
		getNotRequestedUserIds(userIds)
		{
			return userIds.filter((userId) => !this.getUser(userId));
		}

		getUser(userId)
		{
			return usersSelector.selectById(store.getState(), userId);
		}

		addUsersToRedux(users)
		{
			store.dispatch(usersUpserted(users));
		}

		getUsers(userIds)
		{
			return usersSelector.selectAll(store.getState()).filter((user) => userIds.includes(user.id));
		}

		getById(userId)
		{
			return usersSelector.selectById(store.getState(), userId);
		}
	}

	module.exports = { UserManager: new UserManager() };
});
