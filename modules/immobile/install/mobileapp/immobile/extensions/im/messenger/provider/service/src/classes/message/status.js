/**
 * @module im/messenger/provider/service/classes/message/status
 */
jn.define('im/messenger/provider/service/classes/message/status', (require, exports, module) => {
	const { UsersReadMessageList } = require('im/messenger/controller/users-read-message-list');
	const { MapCache } = require('im/messenger/cache');

	/**
	 * @class StatusService
	 * @desc This class service calling backdrop widget with users of list who read message
	 */
	class StatusService
	{
		constructor({ store, chatId })
		{
			this.store = store;
			this.chatId = chatId;
			this.cacheFreshnesTime = 30000; // this time for hold new rest request
			this.readMessageUsersCache = null;
			this.messageId = 0;

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

		/**
		 * @desc Call widget with rest result cache
		 * @param {number} messageId
		 * @return void
		 */
		openUsersReadMessageList(messageId)
		{
			this.messageId = messageId;
			UsersReadMessageList.open(messageId, this.readMessageUsersCache, (data) => this.setCache(data));
		}

		/**
		 * @desc Set data in cache with current message id key
		 * @param {object} data
		 * @return void
		 */
		setCache(data)
		{
			this.readMessageUsersCache.set(this.messageId, data);
		}
	}

	module.exports = {
		StatusService,
	};
});
