/**
 * @module im/messenger/controller/sidebar-v2/controller/channel
 */
jn.define('im/messenger/controller/sidebar-v2/controller/channel', (require, exports, module) => {
	const { SidebarBaseController } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { ChannelSidebarView } = require('im/messenger/controller/sidebar-v2/controller/channel/src/view');
	const {
		SidebarContextMenuActionId,
		SidebarContextMenuActionPosition,
	} = require('im/messenger/controller/sidebar-v2/const');
	const { SidebarLinksTab } = require('im/messenger/controller/sidebar-v2/tabs/links');
	const { SidebarFilesTab } = require('im/messenger/controller/sidebar-v2/tabs/files');
	const { SidebarAudioTab } = require('im/messenger/controller/sidebar-v2/tabs/audio');
	const { SidebarMediaTab } = require('im/messenger/controller/sidebar-v2/tabs/media');
	const { SidebarParticipantsTab } = require('im/messenger/controller/sidebar-v2/tabs/participants');
	const { onAddParticipants } = require('im/messenger/controller/sidebar-v2/user-actions/participants');
	const { onLeaveChat } = require('im/messenger/controller/sidebar-v2/user-actions/user');
	const { onDeleteChat } = require('im/messenger/controller/sidebar-v2/user-actions/chat');
	const {
		createSearchButton,
		createMuteButton,
	} = require('im/messenger/controller/sidebar-v2/ui/primary-button/factory');
	const { Icon } = require('assets/icons');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');

	class ChannelSidebarController extends SidebarBaseController
	{
		createView(defaultProps)
		{
			return new ChannelSidebarView(defaultProps);
		}

		getWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CHANNEL_TITLE');
		}

		// region context menu

		getHeaderContextMenuItems()
		{
			return [
				{
					id: SidebarContextMenuActionId.ADD_PARTICIPANTS,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_ADD_SUBSCRIBERS'),
					icon: Icon.ADD_PERSON,
					testId: 'sidebar-context-menu-add-participants',
					sort: SidebarContextMenuActionPosition.MIDDLE,
					onItemSelected: () => {
						onAddParticipants({
							dialogId: this.dialogId,
							store: this.store,
							widgetTitle: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_ADD_SUBSCRIBERS'),
						}).catch((error) => {
							this.logger.error('onAddParticipants', error);
						});
					},
				},
				...super.getHeaderContextMenuItems(),
			];
		}

		getHeaderContextMenuItemLeave()
		{
			return {
				...super.getHeaderContextMenuItemLeave(),
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_UNSUBSCRIBE_FROM_CHANNEL'),
			};
		}

		handleDeleteDialogAction()
		{
			onDeleteChat(this.dialogId);
		}

		async handleEditDialogAction()
		{
			this.logger.info('handleEditDialogAction');
			const { UpdateChannel } = await requireLazy('im:messenger/controller/chat-composer');

			try
			{
				new UpdateChannel({ dialogId: this.dialogId, parentWidget: this.widget }).openChannelView();
				this.analyticsService.sendDialogEditHeaderMenuClick(this.dialogId);
			}
			catch (error)
			{
				this.logger.error('handleEditDialogAction', error);
			}
		}

		handleLeaveDialogAction()
		{
			onLeaveChat(this.dialogId);
		}

		// endregion

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
				createMuteButton({
					onClick: () => this.handleToggleMuteAction(),
					muted,
				}),
			];
		}

		// endregion

		// region tabs

		/**
		 * @return {SidebarBaseTab[]}
		 */
		createTabs()
		{
			const props = this.getTabsProps();

			return [
				new SidebarParticipantsTab(props),
				new SidebarMediaTab(props),
				new SidebarFilesTab(props),
				new SidebarLinksTab(props),
				new SidebarAudioTab(props),
			];
		}

		// endregion

		// region permissions

		canLeave()
		{
			return true;
		}

		// endregion
	}

	module.exports = {
		ChannelSidebarController,
		ControllerClass: ChannelSidebarController,
	};
});
