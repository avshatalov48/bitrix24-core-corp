/**
 * @module im/messenger/controller/sidebar-v2/tabs/favorites
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/favorites', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseTab, SidebarBaseTabDummyContent } = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { SidebarTabDummyDataProvider } = require('im/messenger/controller/sidebar-v2/services/dummy-data-provider');

	class SidebarFavoritesTab extends SidebarBaseTab
	{
		getId()
		{
			return 'favorites';
		}

		getTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TABS_FAVORITES_TITLE');
		}

		createDataProvider()
		{
			return new SidebarTabDummyDataProvider();
		}

		getView()
		{
			return (props = {}) => new SidebarBaseTabDummyContent(props);
		}
	}

	module.exports = { SidebarFavoritesTab };
});
