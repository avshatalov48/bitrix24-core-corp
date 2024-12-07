/**
 * @module im/messenger/lib/counters/channel-counters
 */
jn.define('im/messenger/lib/counters/channel-counters', (require, exports, module) => {
	const { BaseCounters } = require('im/messenger/lib/counters/lib/base-counters');

	/**
	 * @class ChannelCounters
	 */
	class ChannelCounters extends BaseCounters
	{
		/**
		 * @param {immobileTabChannelLoadResult} data
		 */
		handleCountersGet(data)
		{
			const channelComment = data?.imCounters?.channelComment;

			if (channelComment)
			{
				this.store.dispatch('commentModel/setCounters', channelComment);
			}
		}
	}

	module.exports = { ChannelCounters };
});
