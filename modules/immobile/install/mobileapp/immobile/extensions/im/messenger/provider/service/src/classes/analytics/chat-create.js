/**
 * @module im/messenger/provider/service/classes/analytics/chat-create
 */
jn.define('im/messenger/provider/service/classes/analytics/chat-create', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');

	const { AnalyticsHelper } = require('im/messenger/provider/service/classes/analytics/helper');

	/**
	 * @class ChatCreate
	 */
	class ChatCreate
	{
		sendStartCreation({ category, type, section = Analytics.Section.chatTab })
		{
			try
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setSection(section)
					.setCategory(category)
					.setEvent(Analytics.Event.clickCreateNew)
					.setType(type)
					.setP2(AnalyticsHelper.getP2ByUserType())
				;

				analytics.send();
			}
			catch (e)
			{
				console.error(`${this.constructor.name}.sendStartCreation.catch:`, e);
			}
		}
	}

	module.exports = { ChatCreate };
});
