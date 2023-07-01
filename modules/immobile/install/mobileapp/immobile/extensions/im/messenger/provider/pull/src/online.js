/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/provider/pull/online
 */
jn.define('im/messenger/provider/pull/online', (require, exports, module) => {

	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { PullHandler } = require('im/messenger/provider/pull/base');
	const { Logger } = require('im/messenger/lib/logger');

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
				let recentItem = clone(this.store.getters['recentModel/getById'](userId));
				if (!recentItem)
				{
					return;
				}

				recentItem = ChatUtils.objectMerge(recentItem, {
					user: this.getUserDataFormat(userCollection[userId]),
				});

				this.store.dispatch('recentModel/set', [recentItem]);
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
