/**
 * @module im/messenger/lib/counters/tab-counters/channel
 */
jn.define('im/messenger/lib/counters/tab-counters/channel', (require, exports, module) => {
	const { Type } = require('type');
	const { BaseTabCounters } = require('im/messenger/lib/counters/tab-counters/base');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('counters--chat');

	/**
	 * @deprecated
	 * @see MessengerCounters
	 * @class ChannelCounters
	 */
	class ChannelCounters extends BaseTabCounters
	{
		constructor()
		{
			super({ logger });
		}

		/**
		 * @param {immobileTabChannelLoadResult} data
		 */
		handleCountersGet(data)
		{
			const counters = data?.imCounters;
			if (!Type.isPlainObject(counters))
			{
				logger.error(`${this.getClassName()}.handleCountersGet`, counters);

				return;
			}

			const { channelComment } = counters;

			if (channelComment)
			{
				this.store.dispatch('commentModel/setCounters', channelComment);
			}

			this.sendCountersToCounterService(counters);
		}

		clearAll()
		{
		}

		deleteCounterByChatId(chatId)
		{
		}
	}

	module.exports = { ChannelCounters };
});
