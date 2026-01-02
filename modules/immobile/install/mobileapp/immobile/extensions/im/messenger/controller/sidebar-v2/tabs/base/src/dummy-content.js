/**
 * @module im/messenger/controller/sidebar-v2/tabs/base/src/dummy-content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/base/src/dummy-content', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseTabContent } = require('im/messenger/controller/sidebar-v2/tabs/base/src/content');

	class SidebarBaseTabDummyContent extends SidebarBaseTabContent
	{
		/**
		 * @public
		 * @return {void}
		 */
		scrollToBegin()
		{}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						alignItems: 'center',
						paddingTop: 48,
					},
				},
				Text({
					text: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_CONTENT_COMING_SOON'),
				}),
			);
		}
	}

	module.exports = { SidebarBaseTabDummyContent };
});
