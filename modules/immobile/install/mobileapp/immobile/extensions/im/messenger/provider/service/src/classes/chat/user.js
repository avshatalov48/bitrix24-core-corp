/**
 * @module im/messenger/provider/service/classes/chat/user
 */
jn.define('im/messenger/provider/service/classes/chat/user', (require, exports, module) => {
	const { Type } = require('type');
	const { RestMethod, UserRole} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { runAction } = require('im/messenger/lib/rest');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('dialog--chat-service');

	/**
	 * @class UserService
	 */
	class UserService
	{
		/** @type {MessengerCoreStore} */
		#store;

		constructor()
		{
			this.#store = serviceLocator.get('core').getStore();
		}

		async joinChat(dialogId)
		{
			logger.warn(`UserService: join chat ${dialogId}`);

			if (!Type.isStringFilled(dialogId))
			{
				return Promise.reject(new Error('UserService.joinChat: dialogId is not provided'));
			}

			this.#store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: {
					role: UserRole.member,
				},
			});

			return runAction(RestMethod.imV2ChatJoin, {
				data: {
					dialogId,
				},
			}).catch((error) => {
				// eslint-disable-next-line no-console
				logger.error('UserService.joinChat: error', error);

				this.#store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: {
						role: UserRole.guest,
					},
				});
			});
		}
	}

	module.exports = { UserService };
});
