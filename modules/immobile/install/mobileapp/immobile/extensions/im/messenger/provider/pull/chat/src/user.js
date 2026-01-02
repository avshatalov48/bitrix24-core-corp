/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/user
 */
jn.define('im/messenger/provider/pull/chat/user', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { RecentDataConverter } = require('im/messenger/lib/converter/data/recent');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('pull-handler--chat-user');

	/**
	 * @class ChatUserPullHandler
	 */
	class ChatUserPullHandler extends BasePullHandler
	{
		handleUserInvite(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.info('ChatUserPullHandler.handleUserInvite', params);

			const recentModel = RecentDataConverter.fromPushUserInviteToModel(params);

			this.store.dispatch('usersModel/set', [params.user])
				.catch((err) => logger.error('ChatUserPullHandler.handleUserInvite.usersModel/set.catch:', err));
			this.store.dispatch('recentModel/set', [recentModel])
				.catch((err) => logger.error('ChatUserPullHandler.handleUserInvite.recentModel/set.catch:', err));
		}

		handleBotDelete(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.info('ChatUserPullHandler.handleBotDelete', params);

			this.store.dispatch('recentModel/delete', { id: params.botId })
				.then(() => serviceLocator.get('tab-counters').update())
				.catch((err) => logger.error('ChatUserPullHandler.handleDeleteBot.recentModel/delete.catch:', err))
			;
		}

		/**
		 * @param {UserUpdateParams} params
		 * @param {object} extra
		 * @param {object} command
		 * @void
		 */
		handleUserUpdate(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.info('ChatUserPullHandler.handleUserUpdate', params);

			this.updateUser(params);
		}

		/**
		 * @desc this handler works for the scenario of adding a new bot to the portal (new registration)
		 * @param {BotUpdateParams} params
		 * @param {object} extra
		 * @param {object} command
		 * @void
		 */
		handleBotAdd(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.logger.info('ChatUserPullHandler.handleBotAdd', params);

			this.updateUser(params);
		}

		/**
		 * @param {BotUpdateParams} params
		 * @param {object} extra
		 * @param {object} command
		 * @void
		 */
		handleBotUpdate(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			logger.info('ChatUserPullHandler.handleBotUpdate', params);

			this.updateUser(params);
		}

		/**
		 * @param {BotUpdateParams|UserUpdateParams} params
		 * @void
		 */
		updateUser(params)
		{
			const recentItem = RecentDataConverter.fromPushToModel({
				id: params.user.id,
				user: params.user,
			});

			this.store.dispatch('recentModel/set', [recentItem]);

			this.store.dispatch('usersModel/set', [params.user]);
		}
	}

	module.exports = {
		ChatUserPullHandler,
	};
});
