/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/chat-recent
 */
jn.define('im/messenger/cache/chat-recent', (require, exports, module) => {
	const {
		CacheName,
	} = require('im/messenger/const');
	const { BaseRecentCache } = require('im/messenger/cache/base-recent');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('cache--chat-recent');

	/**
	 * @class ChatRecentCache
	 */
	class ChatRecentCache extends BaseRecentCache
	{
		/**
		 * @param {object} options
		 */
		constructor(options)
		{
			super({
				...options,
				name: CacheName.chatRecent,
				logger,
			});
		}
	}

	module.exports = {
		ChatRecentCache,
	};
});
