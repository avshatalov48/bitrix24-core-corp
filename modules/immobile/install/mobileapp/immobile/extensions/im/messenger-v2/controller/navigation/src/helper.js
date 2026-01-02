/**
 * @module im/messenger-v2/controller/navigation/helper
 */
jn.define('im/messenger-v2/controller/navigation/helper', (require, exports, module) => {
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');

	/**
	 * @class NavigationHelper
	 */
	class NavigationHelper
	{
		static isTabAvailable(tabId)
		{
			const availableTabs = MessengerParams.get('AVAILABLE_TABS', {});

			return Boolean(availableTabs[tabId]);
		}

		static isTabIdCorrect(tabId)
		{
			const availableTabs = MessengerParams.get('AVAILABLE_TABS', {});

			return tabId in availableTabs;
		}

		/**
		 * @return {Promise<boolean>}
		 */
		static async isMessengerTabActive()
		{
			const navigationContext = await PageManager.getNavigator().getNavigationContext();

			if (Type.isBoolean(navigationContext.isTabActive))
			{
				return navigationContext.isTabActive;
			}

			return PageManager.getNavigator().isActiveTab();
		}

		static async makeMessengerTabActive()
		{
			const isTabActive = await NavigationHelper.isMessengerTabActive();
			if (!isTabActive)
			{
				await PageManager.getNavigator().makeTabActive();
			}
		}
	}

	module.exports = { NavigationHelper };
});
