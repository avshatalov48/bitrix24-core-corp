/**
 * @module im/messenger/controller/sidebar-v2/controller/bot
 */
jn.define('im/messenger/controller/sidebar-v2/controller/bot', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseController } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { BotSidebarView } = require('im/messenger/controller/sidebar-v2/controller/bot/src/view');
	const { SidebarLinksTab } = require('im/messenger/controller/sidebar-v2/tabs/links');
	const { SidebarFilesTab } = require('im/messenger/controller/sidebar-v2/tabs/files');
	const { SidebarAudioTab } = require('im/messenger/controller/sidebar-v2/tabs/audio');
	const { SidebarMediaTab } = require('im/messenger/controller/sidebar-v2/tabs/media');
	const { SidebarParticipantsTab } = require('im/messenger/controller/sidebar-v2/tabs/participants');
	const {
		createSearchButton,
		createMuteButton,
	} = require('im/messenger/controller/sidebar-v2/ui/primary-button/factory');

	class BotSidebarController extends SidebarBaseController
	{
		createView(defaultProps)
		{
			return new BotSidebarView(defaultProps);
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
			const muted = this.dialogHelper.isMuted;

			return [
				createSearchButton({
					onClick: () => this.handleSearchAction(),
				}),
				this.permissionManager.canMute() && createMuteButton({
					onClick: () => this.handleToggleMuteAction(),
					muted,
				}),
			];
		}

		// endregion

		// region tabs

		createTabs()
		{
			const props = this.getTabsProps();

			if (this.dialogHelper.isAiAssistant)
			{
				return [
					new SidebarParticipantsTab(props),
					new SidebarLinksTab(props),
				];
			}

			return [
				new SidebarParticipantsTab(props),
				new SidebarMediaTab(props),
				new SidebarFilesTab(props),
				new SidebarLinksTab(props),
				new SidebarAudioTab(props),
			];
		}

		// endregion
	}

	module.exports = {
		BotSidebarController,
		ControllerClass: BotSidebarController,
	};
});
