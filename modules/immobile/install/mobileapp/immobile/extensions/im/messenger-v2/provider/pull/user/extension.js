/**
 * @module im/messenger-v2/provider/pull/user
 */
jn.define('im/messenger-v2/provider/pull/user', (require, exports, module) => {
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { BasePullHandler } = require('im/messenger/provider/pull/base');

	/**
	 * @class UserPullHandler
	 */
	class UserPullHandler extends BasePullHandler
	{
		constructor()
		{
			super({ logger: getLoggerWithContext('pull-handler--user-v2', UserPullHandler) });
		}

		/**
		 * @param {UserUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleUserInvite(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleUserInvite:', params);

			await this.#updateUser(params);
		}

		/**
		 * @param {UserUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleUserUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleUserUpdate:', params);

			await this.#updateUser(params);
		}

		/**
		 * @desc this handler works for the scenario of adding a new bot to the portal (new registration)
		 * @param {BotUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleBotAdd(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleBotAdd:', params);

			await this.#updateUser(params);
		}

		/**
		 * @param {BotUpdateParams} params
		 * @param {PullExtraParams} extra
		 */
		async handleBotUpdate(params, extra)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('handleBotUpdate:', params);

			await this.#updateUser(params);
		}

		/**
		 * @param {UserUpdateParams|BotUpdateParams|UserUpdateParams} params
		 */
		async #updateUser(params)
		{
			await this.store.dispatch('usersModel/set', [params.user]);
		}
	}

	module.exports = {
		UserPullHandler,
	};
});
