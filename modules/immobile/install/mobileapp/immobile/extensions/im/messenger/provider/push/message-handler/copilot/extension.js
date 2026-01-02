/**
 * @module im/messenger/provider/push/message-handler/copilot
 */
jn.define('im/messenger/provider/push/message-handler/copilot', (require, exports, module) => {
	const { WaitingEntity } = require('im/messenger/const');
	const { BasePushMessageHandler } = require('im/messenger/provider/push/message-handler/base');

	/**
	 * @class CopilotPushMessageHandler
	 */
	class CopilotPushMessageHandler extends BasePushMessageHandler
	{
		getHandlerId()
		{
			return WaitingEntity.push.messageHandler.copilot;
		}

		/**
		 * @param {Array<MessengerPushEvent>} eventList
		 * @return {Array<MessengerPushEvent>}
		 */
		filterMessageEvents(eventList)
		{
			const verifiedEvents = [];

			for (const event of eventList)
			{
				if (event.command === 'message')
				{
					continue;
				}

				const helper = this.getHelper(event);
				if (!helper.isChatExist())
				{
					continue;
				}

				if (!helper.isCopilotChat())
				{
					continue;
				}

				verifiedEvents.push(event);
			}

			return verifiedEvents;
		}
	}

	module.exports = { CopilotPushMessageHandler };
});
