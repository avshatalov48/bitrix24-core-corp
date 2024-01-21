/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/provider/pull/base
 */
jn.define('im/messenger/provider/pull/base', (require, exports, module) => {
	const { core } = require('im/messenger/core');
	const { SyncService } = require('im/messenger/provider/service/sync');

	/**
	 * @class PullHandler
	 */
	class PullHandler
	{
		constructor(options = {})
		{
			this.store = core.getStore();
		}

		getModuleId()
		{
			return 'im';
		}

		/**
		 * @protected
		 * @return boolean
		 */
		interceptEvent(params, extra, command)
		{
			if (extra.server_time_ago > 30)
			{
				return true;
			}

			const syncService = SyncService.getInstance();
			if (syncService.checkPullEventNeedsIntercept(params, extra, command))
			{
				syncService.storePullEvent(params, extra, command);

				return true;
			}

			return false;
		}
	}

	module.exports = {
		PullHandler,
	};
});
