/**
 * @module im/messenger/lib/element/user-status
 */
jn.define('im/messenger/lib/element/user-status', (require, exports, module) => {
	const { core } = require('im/messenger/core');
	const { userStatuses } = require('im/messenger/assets/common');

	/**
	 * @class UserStatus
	 * @desc The class API provides getting the user's status. The returned data will be in string format (string svg)
	 * * May be two variant in use. 1 - instance with user data but without call store. 2 - use static method by user id
	 */
	class UserStatus
	{
		/**
		 * @param {object} userData
		 */
		constructor(userData)
		{
			this.userData = userData;
		}

		/**
		 * @desc Get svg user`s status by user id
		 * @param {string} userId
		 * @return {string}
		 */
		static getStatusByUserId(userId)
		{
			UserStatus.store = core.getStore();
			const userData = UserStatus.store.getters['usersModel/getUserById'](userId);

			return userData ? userStatuses[userData.status] : '';
		}

		/**
		 * @desc Get svg admin chat status ( crown )
		 * @return {string}
		 */
		static getStatusCrown()
		{
			return userStatuses.crown;
		}

		/**
		 * @desc Get svg user`s status
		 * @return {string}
		 */
		getStatus()
		{
			return this.userData.status ? userStatuses[this.userData.status] : '';
		}
	}

	module.exports = {
		UserStatus,
	};
});
