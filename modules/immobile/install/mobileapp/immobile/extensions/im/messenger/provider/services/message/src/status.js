/**
 * @module im/messenger/provider/services/message/status
 */
jn.define('im/messenger/provider/services/message/status', (require, exports, module) => {
	const { MapCache } = require('im/messenger/cache');

	/**
	 * @class StatusService
	 * @desc This class service calling backdrop widget with users of list who read message
	 */
	class StatusService
	{
		constructor({ store, chatId })
		{
			this.cacheFreshnesTime = 30000; // this time for hold new rest request
			this.readMessageUsersCache = null;

			this.createCache();
		}

		/**
		 * @desc Create map cache
		 * @return void
		 */
		createCache()
		{
			this.readMessageUsersCache = new MapCache(this.cacheFreshnesTime);
		}
	}

	module.exports = {
		StatusService,
	};
});
