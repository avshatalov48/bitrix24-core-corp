/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/collab/user
 */
jn.define('im/messenger/provider/pull/collab/user', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-user');

	/**
	 * @class CollabUserPullHandler
	 */
	class CollabUserPullHandler extends BasePullHandler
	{
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
			this.store.dispatch('usersModel/set', [params.user]);
		}
	}

	module.exports = {
		CollabUserPullHandler,
	};
});
