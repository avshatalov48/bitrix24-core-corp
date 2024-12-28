/**
 * @module im/messenger/provider/pull/chat/counter
 */
jn.define('im/messenger/provider/pull/chat/counter', (require, exports, module) => {
	const { CounterType } = require('im/messenger/const');
	const { Counters } = require('im/messenger/lib/counters');
	const { BaseCounterPullHandler } = require('im/messenger/provider/pull/base/counter');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--chat-counter');

	/**
	 * @class ChatCounterPullHandler
	 */
	class ChatCounterPullHandler extends BaseCounterPullHandler
	{
		constructor()
		{
			super({ logger });
		}

		/**
		 * @protected
		 * @param params
		 */
		updateCounter(params)
		{
			const {
				dialogId,
				counter,
				counterType,
			} = params;

			if (
				counterType === CounterType.chat
				|| counterType === CounterType.collab
			)
			{
				Counters.chatCounter.detail[dialogId] = counter;
				Counters.update();
			}
		}
	}

	module.exports = {
		ChatCounterPullHandler,
	};
});
