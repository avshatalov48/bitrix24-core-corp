/**
 * @module im/messenger/lib/element/user-status
 */
jn.define('im/messenger/lib/element/user-status', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { userStatuses } = require('im/messenger/assets/common');
	const { Type } = require('type');

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
		 * @param {boolean} [isAllStatus=true]
		 * @return {string}
		 */
		static getStatusByUserId(userId, isAllStatus = true)
		{
			UserStatus.store = serviceLocator.get('core').getStore();
			const userData = UserStatus.store.getters['usersModel/getById'](userId);

			if (userData.birthday && Type.isStringFilled(userData.birthday))
			{
				const dateNow = new Date().toISOString();
				const normalUserDate = [userData.birthday.slice(3), '-', userData.birthday.slice(0, 2)].join('');
				const isBirthday = dateNow.includes(normalUserDate);

				if (isBirthday)
				{
					return userStatuses.birthday;
				}
			}

			if (userData.absent)
			{
				return userData ? userStatuses.absent : '';
			}

			if (isAllStatus)
			{
				return userData ? userStatuses[userData.status] : '';
			}

			return '';
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
		 * @desc Get svg manager chat status ( crown )
		 * @return {string}
		 */
		static getStatusGreenCrown()
		{
			return userStatuses.greenCrown;
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
