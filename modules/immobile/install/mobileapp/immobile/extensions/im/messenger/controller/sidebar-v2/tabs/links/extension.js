/**
 * @module im/messenger/controller/sidebar-v2/tabs/links
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/links', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseTab } = require('im/messenger/controller/sidebar-v2/tabs/base');
	const { SidebarLinksService } = require('im/messenger/controller/sidebar-v2/services/links-service');
	const { SidebarLinksTabContent } = require('im/messenger/controller/sidebar-v2/tabs/links/src/content');

	class SidebarLinksTab extends SidebarBaseTab
	{
		getId()
		{
			return 'links';
		}

		getTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TABS_LINKS_TITLE');
		}

		createDataProvider()
		{
			return new SidebarLinksService(this.dialogData?.chatId);
		}

		getView()
		{
			return (props = {}) => new SidebarLinksTabContent({
				chatId: this.dialogData?.chatId,
				dataProvider: this.getDataProvider(),
				...props,
			});
		}
	}

	module.exports = { SidebarLinksTab };
});
