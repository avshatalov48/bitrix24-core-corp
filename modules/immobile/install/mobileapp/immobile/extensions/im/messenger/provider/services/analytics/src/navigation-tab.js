/**
 * @module im/messenger/provider/services/analytics/navigation-tab
 */
jn.define('im/messenger/provider/services/analytics/navigation-tab', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics, NavigationTab: NavigationTabNames } = require('im/messenger/const');

	/**
	 * @class NavigationTab
	 */
	class NavigationTab
	{
		sendChangeTab(currentTab, analyticsOptions = {})
		{
			try
			{
				if (currentTab === NavigationTabNames.imCopilotMessenger)
				{
					const analytics = new AnalyticsEvent()
						.setTool(Analytics.Tool.ai)
						.setCategory(Analytics.Category.chatOperations)
						.setEvent(Analytics.Event.openTab)
						.setSection(Analytics.Section.copilotTab);

					analytics.send();
				}

				if (currentTab !== NavigationTabNames.imMessenger)
				{
					const analytics = new AnalyticsEvent()
						.setTool(analyticsOptions.tool ?? Analytics.Tool.im)
						.setCategory(analyticsOptions.category ?? Analytics.Category.messenger)
						.setEvent(analyticsOptions.event ?? Analytics.Event.openTab)
						.setType(analyticsOptions.type ?? Analytics.Type[currentTab]);

					analytics.send();
				}
			}
			catch (e)
			{
				console.error(`${this.constructor.name}.sendStartCreation.catch:`, e);
			}
		}
	}

	module.exports = { NavigationTab };
});
