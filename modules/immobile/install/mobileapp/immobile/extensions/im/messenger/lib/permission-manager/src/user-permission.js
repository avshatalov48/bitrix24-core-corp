/**
 * @module im/messenger/lib/permission-manager/user-permission
 */
jn.define('im/messenger/lib/permission-manager/user-permission', (require, exports, module) => {
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { ActionByUserType } = require('im/messenger/const');

	class UserPermission
	{
		constructor() {
			this.userData = Object.create(null);
		}

		/**
		 * @desc Set data dialog
		 * @param {UsersModelState|string} userData
		 * @return {boolean}
		 */
		setUserData(userData)
		{
			if (Type.isNumber(userData))
			{
				this.store = serviceLocator.get('core').getStore();
				const userState = this.store.getters['usersModel/getById'](userData);
				if (Type.isUndefined(userState))
				{
					return false;
				}

				this.userData = userState;
			}

			if (Type.isObject(userData))
			{
				this.userData = userData;
			}

			return true;
		}

		/**
		 * @desc check is can call by user data ( use id user or user state object )
		 * @param {UsersModelState||number} userData
		 * @param {boolean} [verbose=false] - prop for verbose response, returns object with key
		 * @return {boolean|object}
		 */
		isCanCall(userData, verbose = false)
		{
			if (!this.setUserData(userData))
			{
				return false;
			}

			const isYou = this.isYou();
			const isBot = this.isBot();
			const isNetwork = this.isNetwork();
			const isLive = this.isLive();
			const isCanCall = !isYou && !isBot && !isNetwork && isLive;

			if (verbose)
			{
				return {
					isCanCall,
					isYou,
					isBot,
					isNetwork,
					isLive,
				};
			}

			return isCanCall;
		}

		/**
		 * @desc check user is bot by property user data
		 * @param {UserState} [userData]
		 * @return {boolean}
		 */
		isBot(userData = this.userData)
		{
			return userData.bot;
		}

		/**
		 * @desc check user is network by property user data
		 * @param {UsersModelState} [userData]
		 * @return {boolean}
		 */
		isNetwork(userData = this.userData)
		{
			return userData.network;
		}

		/**
		 * @desc check user is live by property lastActivityDate
		 * @param {UsersModelState} [userData]
		 * @return {boolean}
		 */
		isLive(userData = this.userData)
		{
			if (Type.isString(userData.lastActivityDate))
			{
				return true;
			}

			if (Type.isUndefined(userData.lastActivityDate))
			{
				return false;
			}

			return userData.lastActivityDate;
		}

		/**
		 * @desc check the user is you
		 * @param {UsersModelState} [userData]
		 * @return {boolean}
		 */
		isYou(userData = this.userData)
		{
			const currentUserId = MessengerParams.getUserId();

			return userData.id === currentUserId;
		}

		/**
		 * @param actionType
		 * @param {UsersModelState||number} userData
		 * @return {*|boolean|boolean}
		 */
		canPerformActionByUserType(actionType, userData)
		{
			let userInfo = MessengerParams.getUserInfo();
			if (userData && this.setUserData(userData))
			{
				userInfo = this.userData;
			}

			return this.getPermissionByUserType(userInfo)?.[actionType] ?? true;
		}

		/**
		 * @param {UsersModelState||number} userData
		 * @return {*|boolean|boolean}
		 */
		canLeaveFromCollab(userData)
		{
			if (!this.setUserData(userData))
			{
				return false;
			}

			const permissionsByUserType = this.getPermissionByUserType(this.userData);

			return permissionsByUserType?.[ActionByUserType.leaveCollab] ?? false;
		}

		/**
		 * @return {Permissions}
		 */
		getChatPermissions()
		{
			return MessengerParams.getPermissions();
		}

		/**
		 * @return {UsersModelState} userData
		 */
		getPermissionByUserType(userData)
		{
			const userType = userData.type;
			const chatPermissions = this.getChatPermissions();

			return chatPermissions?.byUserType?.[userType] ?? {};
		}
	}

	module.exports = {
		UserPermission: new UserPermission(),
	};
});
