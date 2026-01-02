/**
 * @module im/messenger-v2/controller/recent/recent-ui-getter
 */
jn.define('im/messenger-v2/controller/recent/recent-ui-getter', (require, exports, module) => {
	/* global tabs */
	const { NavigationTabId } = require('im/messenger/const');

	/**
	 * @class RecentUiGetter
	 */
	class RecentUiGetter
	{
		#ui = tabs;

		get #nestedWidgets()
		{
			return this.#ui.nestedWidgets();
		}

		getCurrentRecent()
		{
			return this.getRecentList(this.currentTab.id);
		}

		/**
		 * @param tabId
		 * @return {BaseList}
		 */
		getRecentListByTabId(tabId)
		{
			return this.getRecentList(tabId);
		}

		getRecentList(recentName)
		{
			return this.#nestedWidgets[recentName] ?? null;
		}

		get chatRecentList()
		{
			return this.getRecentList(NavigationTabId.chats);
		}

		get copilotRecentList()
		{
			return this.getRecentList(NavigationTabId.copilot);
		}

		get channelRecentList()
		{
			return this.getRecentList(NavigationTabId.channel);
		}

		get collabRecentList()
		{
			return this.getRecentList(NavigationTabId.collab);
		}

		get openlinesRecentList()
		{
			return this.getRecentList(NavigationTabId.openlines);
		}

		/**
		 * @return {{id: string, spotlightId: string, style: object, title: string}}
		 */
		get currentTab()
		{
			return tabs.getCurrentItem();
		}
	}

	module.exports = { RecentUiGetter };
});
