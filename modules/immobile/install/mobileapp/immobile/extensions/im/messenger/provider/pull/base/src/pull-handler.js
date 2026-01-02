/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/provider/pull/base/pull-handler
 */
jn.define('im/messenger/provider/pull/base/pull-handler', (require, exports, module) => {
	const { Feature } = require('im/messenger/lib/feature');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SyncService } = require('im/messenger/provider/services/sync/service');
	const { SyncService: SyncServiceV2 } = require('im/messenger-v2/provider/services/sync');
	const { Logger } = require('im/messenger/lib/logger');

	/**
	 * @class BasePullHandler
	 * @extends PullCommandHandler
	 */
	class BasePullHandler
	{
		constructor(options = {})
		{
			/** @type {Logger} */
			this.logger = options.logger || Logger;
		}

		getModuleId()
		{
			return 'im';
		}

		/**
		 * @protected
		 * @param {PullExtraParams} extra
		 * @param {object} options
		 * @param {boolean|undefined} options.ignoreServerTimeAgoCheck
		 * @return boolean
		 */
		interceptEvent(extra, options = {})
		{
			if (!options.ignoreServerTimeAgoCheck && extra.server_time_ago > 30)
			{
				return true;
			}

			const syncService = this.getSyncService();

			return syncService.checkPullEventNeedsIntercept();
		}

		/**
		 * @return {SyncServiceV2|SyncService}
		 */
		getSyncService()
		{
			if (Feature.isMessengerV2Enabled)
			{
				return SyncServiceV2.getInstance();
			}

			return SyncService.getInstance();
		}

		/**
		 * @desc get class name for logger
		 * @return {string}
		 */
		getClassName()
		{
			return this.constructor.name;
		}

		/**
		 * @return {MessengerCoreStore}
		 */
		get store()
		{
			return serviceLocator.get('core').getStore();
		}
	}

	module.exports = {
		BasePullHandler,
	};
});
