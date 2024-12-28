/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/chat/user
 */
jn.define('im/messenger/provider/pull/chat/user', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { Counters } = require('im/messenger/lib/counters');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-user');

	/**
	 * @class ChatUserPullHandler
	 */
	class ChatUserPullHandler extends BasePullHandler
	{
		handleUserInvite(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('ChatUserPullHandler.handleUserInvite', params);

			const recentModel = RecentConverter.fromPushUserInviteToModel(params);

			this.store.dispatch('usersModel/set', [params.user])
				.catch((err) => logger.error('ChatUserPullHandler.handleUserInvite.usersModel/set.catch:', err));
			this.store.dispatch('recentModel/set', [recentModel])
				.catch((err) => logger.error('ChatUserPullHandler.handleUserInvite.recentModel/set.catch:', err));
		}

		handleBotDelete(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('ChatUserPullHandler.handleBotDelete', params);

			this.store.dispatch('recentModel/delete', { id: params.botId })
				.then(() => Counters.update())
				.catch((err) => logger.error('ChatUserPullHandler.handleDeleteBot.recentModel/delete.catch:', err))
			;
		}

		handleUserUpdate(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('ChatUserPullHandler.handleUserUpdate', params);

			this.updateUser(params);
		}

		handleBotUpdate(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('ChatUserPullHandler.handleBotUpdate', params);

			this.updateUser(params);
		}

		updateUser(params)
		{
			const recentItem = RecentConverter.fromPushToModel({
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
