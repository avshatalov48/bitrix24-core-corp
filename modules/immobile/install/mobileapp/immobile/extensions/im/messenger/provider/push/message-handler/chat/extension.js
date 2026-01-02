/**
 * @module im/messenger/provider/push/message-handler/chat
 */
jn.define('im/messenger/provider/push/message-handler/chat', (require, exports, module) => {
	const { WaitingEntity } = require('im/messenger/const');
	const { Feature } = require('im/messenger/lib/feature');
	const { BasePushMessageHandler } = require('im/messenger/provider/push/message-handler/base');

	/**
	 * @class ChatPushMessageHandler
	 */
	class ChatPushMessageHandler extends BasePushMessageHandler
	{
		getHandlerId()
		{
			return WaitingEntity.push.messageHandler.chat;
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
					verifiedEvents.push(event);

					continue;
				}

				const helper = this.getHelper(event);

				if (helper.isLines())
				{
					continue;
				}

				if (!helper.isChatExist())
				{
					continue;
				}

				if (helper.isOpenChannelChat && !helper.isUserInChat())
				{
					continue;
				}

				verifiedEvents.push(event);
			}

			return verifiedEvents;
		}
	}

	module.exports = { ChatPushMessageHandler };
});
