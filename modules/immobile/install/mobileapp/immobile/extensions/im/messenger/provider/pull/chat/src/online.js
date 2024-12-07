/**
 * @module im/messenger/provider/pull/chat/online
 */
jn.define('im/messenger/provider/pull/chat/online', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--online');

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
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.updateOnline(params, extra, command);
		}

		handleUserStatus(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
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
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			if (extra.server_time_ago > 30)
			{
				return;
			}

			logger.log('OnlinePullHandler.updateOnline', params);

			const userCollection = params.users;

			Object.keys(userCollection).forEach((userId) => {
				let recentItem = clone(this.store.getters['recentModel/getById'](userId));
				if (!recentItem)
				{
					return;
				}

				recentItem = ChatUtils.objectMerge(recentItem, {
					user: this.getUserDataFormat(userCollection[userId]),
				});

				this.store.dispatch('recentModel/set', [recentItem]);
				this.store.dispatch('usersModel/update', userCollection[userId]);
			});

			this.store.dispatch('usersModel/update', Object.values(userCollection));
		}

		/**
		 * @param {OnlineUpdateParamsState} user
		 * @return {*}
		 */
		getUserDataFormat(user)
		{
			const userData = ChatDataConverter.getUserDataFormat(user);

			if (userData.id === 0)
			{
				return userData;
			}

			if (!Type.isUndefined(userData.name))
			{
				userData.name = ChatUtils.htmlspecialcharsback(userData.name);
			}

			if (!Type.isUndefined(userData.last_name))
			{
				userData.last_name = ChatUtils.htmlspecialcharsback(userData.last_name);
			}

			if (!Type.isUndefined(userData.first_name))
			{
				userData.first_name = ChatUtils.htmlspecialcharsback(userData.first_name);
			}

			if (!Type.isUndefined(userData.work_position))
			{
				userData.work_position = ChatUtils.htmlspecialcharsback(userData.work_position);
			}

			return userData;
		}
	}

	module.exports = {
		OnlinePullHandler,
	};
});
