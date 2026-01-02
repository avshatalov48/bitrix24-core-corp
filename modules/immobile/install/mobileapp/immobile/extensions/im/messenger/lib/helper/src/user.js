/**
 * @module im/messenger/lib/helper/user
 */

jn.define('im/messenger/lib/helper/user', (require, exports, module) => {
	const { Type } = require('type');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { UserType, BotCode } = require('im/messenger/const');

	const logger = LoggerManager.getInstance().getLogger('helpers--user');

	/**
	 * @class UserHelper
	 */
	class UserHelper
	{
		/** @type {UsersModelState} */
		userModel = null;

		/**
		 * @param {UsersModelState} userModel
		 * @return {UserHelper|null}
		 */
		static createByModel(userModel)
		{
			if (!Type.isPlainObject(userModel))
			{
				logger.error('UserHelper.getByModel error: userModel is not an object', userModel);

				return null;
			}

			return new UserHelper(userModel);
		}

		/**
		 * @param {number} userId
		 * @return {UserHelper|null}
		 */
		static createByUserId(userId)
		{
			if (!Type.isNumber(userId))
			{
				logger.error('UserHelper.getByUserId error: userId is not a number', userId);

				return null;
			}

			const userModel = serviceLocator.get('core').getStore().getters['usersModel/getById'](userId);
			if (!userModel)
			{
				logger.warn('UserHelper.getByUserId: user not found', userId);

				return null;
			}

			return UserHelper.createByModel(userModel);
		}

		/**
		 * @param {number} userId
		 * @return {boolean}
		 */
		static isCurrentUser(userId)
		{
			const currentUserId = serviceLocator.get('core').getUserId();

			return currentUserId === Number(userId);
		}

		/**
		 * @param {UsersModelState} userModel
		 */
		constructor(userModel)
		{
			this.userModel = userModel;
		}

		get id()
		{
			return this.userModel.id;
		}

		get isCollaber()
		{
			return this.userModel.type === UserType.collaber;
		}

		get isExtranet()
		{
			return this.userModel.type === UserType.extranet;
		}

		get isExtranetOrCollaber()
		{
			return [UserType.collaber, UserType.extranet].includes(this.userModel.type);
		}

		get isBot()
		{
			return this.userModel.bot;
		}

		get isCopilotBot()
		{
			return this.userModel.bot && this.userModel.botData && this.userModel.botData.code === BotCode.copilot;
		}

		get isAiAssistant()
		{
			return this.userModel.bot && this.userModel.botData && this.userModel.botData.code === BotCode.aiAssistant;
		}
	}

	module.exports = {
		UserHelper,
	};
});
