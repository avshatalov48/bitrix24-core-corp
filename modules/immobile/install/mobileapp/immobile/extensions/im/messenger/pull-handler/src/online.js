/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/pull-handler/online
 */
jn.define('im/messenger/pull-handler/online', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { PullHandler } = jn.require('im/messenger/pull-handler/base');
	const { Logger } = jn.require('im/messenger/lib/logger');

	/**
	 * @class OnlinePullHandler
	 */
	class OnlinePullHandler extends PullHandler
	{
		getModuleId()
		{
			return 'online';
		}

		getSubscriptionType()
		{
			return BX.PullClient.SubscriptionType.Online;
		}

		handleList(params, extra, command)
		{
			this.updateOnline(params, extra, command);
		}

		handleUserStatus(params, extra, command)
		{
			this.updateOnline(params, extra, command);
		}

		updateOnline(params, extra, command)
		{
			if (extra.server_time_ago > 30)
			{
				return;
			}

			Logger.log('OnlinePullHandler.updateOnline', params);

			const userCollection = params.users;

			Object.keys(userCollection).forEach(userId => {
				let recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](userId));
				if (!recentItem)
				{
					return;
				}

				recentItem = ChatUtils.objectMerge(recentItem, {
					user: this.getUserDataFormat(userCollection[userId]),
				});

				MessengerStore.dispatch('recentModel/set', [recentItem]);
			});
		}

		getUserDataFormat(user)
		{
			user = ChatDataConverter.getUserDataFormat(user);

			if (user.id === 0)
			{
				return user;
			}

			if (!Type.isUndefined(user.name))
			{
				user.name = ChatUtils.htmlspecialcharsback(user.name);
			}
			if (!Type.isUndefined(user.last_name))
			{
				user.last_name = ChatUtils.htmlspecialcharsback(user.last_name);
			}
			if (!Type.isUndefined(user.first_name))
			{
				user.first_name = ChatUtils.htmlspecialcharsback(user.first_name);
			}
			if (!Type.isUndefined(user.work_position))
			{
				user.work_position = ChatUtils.htmlspecialcharsback(user.work_position);
			}

			return user;
		}
	}

	module.exports = {
		OnlinePullHandler,
	};
});
