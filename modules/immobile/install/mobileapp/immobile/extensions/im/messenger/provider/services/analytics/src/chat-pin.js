/**
 * @module im/messenger/provider/services/analytics/chat-pin
 */
jn.define('im/messenger/provider/services/analytics/chat-pin', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');

	/**
	 * @class ChatPin
	 */
	class ChatPin
	{
		sendPinChatNotes()
		{
			try
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(Analytics.Category.chat)
					.setEvent(Analytics.Event.pinChat)
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

	module.exports = { ChatPin };
});
