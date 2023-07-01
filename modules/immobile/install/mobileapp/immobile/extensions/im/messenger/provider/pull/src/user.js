/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/provider/pull/user
 */
jn.define('im/messenger/provider/pull/user', (require, exports, module) => {

	const { PullHandler } = require('im/messenger/provider/pull/base');
	const { Logger } = require('im/messenger/lib/logger');
	const { RecentConverter } = require('im/messenger/lib/converter');
	const { Counters } = require('im/messenger/lib/counters');

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

			this.store.dispatch('usersModel/set', user);
			this.store.dispatch('recentModel/set', [user]);
		}

		handleDeleteBot(params, extra, command)
		{
			Logger.info('UserPullHandler.handleUserUpdate', params);

			this.store.dispatch('recentModel/delete', { id: params.botId })
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

			this.store.dispatch('recentModel/set', [recentItem]);

			this.store.dispatch('usersModel/set', [params.user]);
		}
	}

	module.exports = {
		UserPullHandler,
	};
});
