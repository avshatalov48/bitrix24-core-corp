/**
 * @module im/messenger/provider/services/analytics/messenger
 */
jn.define('im/messenger/provider/services/analytics/messenger', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');

	/**
	 * @class Messenger
	 */
	class Messenger
	{
		sendOpenDialogCreator()
		{
			try
			{
				const analytics = new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(Analytics.Category.messenger)
					.setEvent(Analytics.Event.openCreateMenu)
					.setSection(Analytics.Section.chatTab)
				;

				analytics.send();
			}
			catch (e)
			{
				console.error(`${this.constructor.name}.sendOpenDialogCreator.catch:`, e);
			}
		}
	}

	module.exports = { Messenger };
});
