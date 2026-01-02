/**
 * @module im/messenger/provider/services/analytics/message-type
 */
jn.define('im/messenger/provider/services/analytics/message-type', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');

	class MessageType
	{
		sendTypeMessageChatNotes()
		{
			try
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(Analytics.Category.chat)
					.setEvent(Analytics.Event.typeMessage)
					.setP1(Analytics.P1.notes)
				;

				analytics.send();
			}
			catch (error)
			{
				console.error(`${this.constructor.name}.sendPinChatNotes.catch:`, error);
			}
		}
	}

	module.exports = { MessageType };
});
