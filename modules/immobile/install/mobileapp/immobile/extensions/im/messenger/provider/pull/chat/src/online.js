/**
 * @module im/messenger/provider/pull/chat/online
 */
jn.define('im/messenger/provider/pull/chat/online', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('pull-handler--online');

	/**
	 * @class OnlinePullHandler
	 */
	class OnlinePullHandler extends BasePullHandler
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
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.updateOnline(params, extra, command);
		}

		handleUserStatus(params, extra, command)
		{
			if (this.interceptEvent(extra))
			{
				return;
			}

			this.updateOnline(params, extra, command);
		}

		/**
		 *
		 * @param {OnlineUpdateParams} params
		 * @param extra
		 * @param command
		 */
		updateOnline(params, extra, command)
		{
			if (extra.server_time_ago > 30)
			{
				return;
			}

			logger.log(`${this.constructor.name}.updateOnline params:`, params);

			const userCollection = [];
			Object.values(params.users).forEach((userData) => {
				userCollection.push({
					id: userData.id,
					fields: {
						lastActivityDate: userData.last_activity_date,
					},
				});
			});

			this.store.dispatch('usersModel/update', userCollection);
		}
	}

	module.exports = {
		OnlinePullHandler,
	};
});
