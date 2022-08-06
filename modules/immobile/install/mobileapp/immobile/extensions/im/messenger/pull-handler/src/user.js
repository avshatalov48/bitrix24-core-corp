/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/pull-handler/user
 */
jn.define('im/messenger/pull-handler/user', (require, exports, module) => {

	const { PullHandler } = jn.require('im/messenger/pull-handler/base');
	const { Logger } = jn.require('im/messenger/lib/logger');
	const { RecentConverter } = jn.require('im/messenger/lib/converter');
	const { Counters } = jn.require('im/messenger/lib/counters');

	/**
	 * @class UserPullHandler
	 */
	class UserPullHandler extends PullHandler
	{
		handleUserInvite(params, extra, command)
		{
			Logger.info('UserPullHandler.handleUserInvite', params);

			const user = ChatDataConverter.getElementByEntity('user', params.user);
			user.avatar = user.avatar.url;
			user.invited = params.invited;

			MessengerStore.dispatch('recentModel/set', [user]);
		}

		handleDeleteBot(params, extra, command)
		{
			Logger.info('UserPullHandler.handleUserUpdate', params);

			MessengerStore.dispatch('recentModel/delete', { id: params.botId })
				.then(() => Counters.update())
			;
		}

		handleUserUpdate(params, extra, command)
		{
			Logger.info('UserPullHandler.handleUserUpdate', params);

			this.updateUser(params);
		}

		handleUpdateUser(params, extra, command)
		{
			Logger.info('UserPullHandler.handleUpdateUser', params);

			this.updateUser(params);
		}

		handleBotUpdate(params, extra, command)
		{
			Logger.info('UserPullHandler.handleBotUpdate', params);

			this.updateUser(params);
		}

		handleUpdateBot(params, extra, command)
		{
			Logger.info('UserPullHandler.handleUpdateBot', params);

			this.updateUser(params);
		}

		updateUser(params)
		{
			const recentItem = RecentConverter.fromPushToModel({
				id: params.user.id,
				user: params.user,
			});

			MessengerStore.dispatch('recentModel/set', [recentItem]);

			MessengerStore.dispatch('usersModel/set', [params.user]);
		}
	}

	module.exports = {
		UserPullHandler,
	};
});
