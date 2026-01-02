/**
 * @module im/messenger/controller/sidebar-v2/controller/notes
 */
jn.define('im/messenger/controller/sidebar-v2/controller/notes', (require, exports, module) => {
	const { SidebarBaseController } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { NotesSidebarView } = require('im/messenger/controller/sidebar-v2/controller/notes/src/view');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarLinksTab } = require('im/messenger/controller/sidebar-v2/tabs/links');
	const { SidebarFilesTab } = require('im/messenger/controller/sidebar-v2/tabs/files');
	const { SidebarAudioTab } = require('im/messenger/controller/sidebar-v2/tabs/audio');
	const { SidebarMediaTab } = require('im/messenger/controller/sidebar-v2/tabs/media');
	const { createSearchButton } = require('im/messenger/controller/sidebar-v2/ui/primary-button/factory');

	class NotesSidebarController extends SidebarBaseController
	{
		createView(defaultProps)
		{
			return new NotesSidebarView(defaultProps);
		}

		getWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CHAT_TITLE');
		}

		// region primary actions

		/**
		 * @return {SidebarPrimaryActionButton[]}
		 */
		getPrimaryActionButtons()
		{
			return [
				createSearchButton({
					onClick: () => this.handleSearchAction(),
				}),
			];
		}

		// endregion

		// region tabs

		createTabs()
		{
			const props = this.getTabsProps();

			return [
				new SidebarMediaTab(props),
				new SidebarFilesTab(props),
				new SidebarLinksTab(props),
				new SidebarAudioTab(props),
			];
		}

		// endregion
	}

	module.exports = {
		NotesSidebarController,
		ControllerClass: NotesSidebarController,
	};
});
