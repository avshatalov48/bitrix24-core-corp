/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/provider/pull/base/pull-handler
 */
jn.define('im/messenger/provider/pull/base/pull-handler', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SyncService } = require('im/messenger/provider/service/sync');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class BasePullHandler
	 * @extends PullCommandHandler
	 */
	class BasePullHandler
	{
		constructor(options = {})
		{
			/** @type {MessengerCoreStore} */
			this.store = serviceLocator.get('core').getStore();
			/** @type {Logger} */
			this.logger = options.logger || Logger;
		}

		getModuleId()
		{
			return 'im';
		}

		/**
		 * @protected
		 * @param {object} params
		 * @param {object} extra
		 * @param {object} command
		 * @param {object} options
		 * @param {boolean|undefined} options.ignoreServerTimeAgoCheck
		 * @return boolean
		 */
		interceptEvent(params, extra, command, options = {})
		{
			if (!options.ignoreServerTimeAgoCheck && extra.server_time_ago > 30)
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

		/**
		 * @desc get class name for logger
		 * @return {string}1
		 */
		getClassName()
		{
			return this.constructor.name;
		}
	}

	module.exports = {
		BasePullHandler,
	};
});
