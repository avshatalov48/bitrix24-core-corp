/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/copilot-recent
 */
jn.define('im/messenger/cache/copilot-recent', (require, exports, module) => {
	const {
		CacheName,
	} = require('im/messenger/const');
	const { BaseRecentCache } = require('im/messenger/cache/base-recent');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('cache--copilot-recent');

	/**
	 * @class CopilotRecentCache
	 */
	class CopilotRecentCache extends BaseRecentCache
	{
		/**
		 * @param {object} options
		 */
		constructor(options)
		{
			super({
				...options,
				name: CacheName.copilotRecent,
				logger,
			});
		}
	}

	module.exports = {
		CopilotRecentCache,
	};
});
