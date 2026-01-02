/**
 * @module im/messenger/controller/sidebar-v2/controller/copilot
 */
jn.define('im/messenger/controller/sidebar-v2/controller/copilot', (require, exports, module) => {
	const { SidebarBaseController } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { CopilotSidebarView } = require('im/messenger/controller/sidebar-v2/controller/copilot/src/view');
	const { CopilotSidebarPermissionManager } = require('im/messenger/controller/sidebar-v2/controller/copilot/src/permission-manager');
	const { SidebarParticipantsTab } = require('im/messenger/controller/sidebar-v2/tabs/participants');
	const { onAddParticipants } = require('im/messenger/controller/sidebar-v2/user-actions/participants');
	const { onDeleteChat } = require('im/messenger/controller/sidebar-v2/user-actions/chat');
	const {
		SidebarContextMenuActionId,
		SidebarContextMenuActionPosition,
	} = require('im/messenger/controller/sidebar-v2/const');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const {
		createSearchButton,
		createMuteButton,
	} = require('im/messenger/controller/sidebar-v2/ui/primary-button/factory');
	const { Icon } = require('assets/icons');

	class CopilotSidebarController extends SidebarBaseController
	{
		createView(defaultProps)
		{
			return new CopilotSidebarView(defaultProps);
		}

		createPermissionManager(defaultProps)
		{
			return new CopilotSidebarPermissionManager(defaultProps);
		}

		getWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CHAT_TITLE');
		}

		// region context menu

		getHeaderContextMenuItems()
		{
			return [
				{
					id: SidebarContextMenuActionId.ADD_PARTICIPANTS,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_ADD_PARTICIPANTS'),
					icon: Icon.ADD_PERSON,
					testId: 'sidebar-context-menu-add-participants',
					sort: SidebarContextMenuActionPosition.MIDDLE,
					onItemSelected: () => {
						onAddParticipants({
							dialogId: this.dialogId,
							store: this.store,
						}).catch((error) => {
							this.logger.error('onAddParticipants', error);
						});
					},
				},
				...super.getHeaderContextMenuItems(),
			];
		}

		handleDeleteDialogAction()
		{
			onDeleteChat(this.dialogId);
		}

		async handleEditDialogAction()
		{
			this.logger.info('handleEditDialogAction');
			const { UpdateGroupChat } = await requireLazy('im:messenger/controller/chat-composer');

			try
			{
				new UpdateGroupChat({ dialogId: this.dialogId, parentWidget: this.widget }).openGroupChatView();
				this.analyticsService.sendDialogEditHeaderMenuClick(this.dialogId);
			}
			catch (error)
			{
				this.logger.error('handleEditDialogAction', error);
			}
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
				{
					id: 'copilot-role',
					icon: Icon.COPILOT,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_BUTTON_COPILOT_ROLE'),
					onClick: () => this.handleSelectCopilotRoleAction(),
				},
				createSearchButton({
					onClick: () => this.handleSearchAction(),
				}),
				createMuteButton({
					onClick: () => this.handleToggleMuteAction(),
					muted,
				}),
			];
		}

		async handleSelectCopilotRoleAction()
		{
			this.logger.info('handleSelectCopilotRoleAction');

			const { CopilotRoleSelector } = await requireLazy('layout/ui/copilot-role-selector');
			const { CopilotRest } = await requireLazy('im:messenger/provider/rest');

			try
			{
				const result = await CopilotRoleSelector.open({
					showOpenFeedbackItem: true,
					openWidgetConfig: {
						backdrop: {
							mediumPositionPercent: 75,
							horizontalSwipeAllowed: false,
							onlyMediumPosition: false,
						},
					},
					skipButtonText: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_CLOSE'),
				});

				if (result?.role?.code)
				{
					CopilotRest.changeRole({ dialogId: this.dialogId, roleCode: result.role.code });
				}
			}
			catch (error)
			{
				this.logger.error('handleSelectCopilotRoleAction', error);
			}
		}

		// endregion

		// region tabs

		createTabs()
		{
			const props = this.getTabsProps();

			return [
				new SidebarParticipantsTab(props),
			];
		}

		// endregion
	}

	module.exports = {
		CopilotSidebarController,
		ControllerClass: CopilotSidebarController,
	};
});
