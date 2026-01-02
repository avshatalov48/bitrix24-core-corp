/**
 * @module im/messenger-v2/controller/navigation/controller
 */
jn.define('im/messenger-v2/controller/navigation/controller', (require, exports, module) => {
	/* global tabs */

	const { MessengerHeaderController } = require('im/messenger-v2/controller/messenger-header');

	const { TabSwitcher } = require('im/messenger-v2/controller/navigation/tab-switcher');
	const { NavigationHelper } = require('im/messenger-v2/controller/navigation/helper');
	const { RecentManager } = require('im/messenger-v2/controller/recent/manager');
	/**
	 * @class NavigationController
	 */
	class NavigationController
	{
		static #instance = null;

		#tabSwitcher = new TabSwitcher(tabs, this);

		/**
		 * @returns {NavigationController}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new NavigationController();
			}

			return this.#instance;
		}

		/**
		 * @return {Promise<string>}
		 */
		async getActiveTab()
		{
			return this.#tabSwitcher.getActiveTab();
		}

		/**
		 * @param {string} tabId
		 * @param {TabOptions} options
		 * @returns {Promise<SelectionTabResult>}
		 */
		async setActiveTab(tabId, options = {})
		{
			return this.#tabSwitcher.setActiveTab(tabId, options);
		}

		async closeAllWidgets()
		{
			return PageManager.getNavigator().popTo('im.tabs');
		}

		/**
		 * @return {Promise<void>}
		 */
		async makeMessengerTabActive()
		{
			return NavigationHelper.makeMessengerTabActive();
		}

		/**
		 * @return {Promise<boolean>}
		 */
		async isMessengerTabActive()
		{
			return NavigationHelper.isMessengerTabActive();
		}

		/**
		 * internal method
		 * @param currentTabId
		 * @param previousTabId
		 */
		handleTabChanged(currentTabId, previousTabId)
		{
			RecentManager.getInstance().setActiveRecent(currentTabId);
			MessengerHeaderController.getInstance().redrawRightButtonsIfNeeded(currentTabId);
		}
	}

	module.exports = { NavigationController };
});
