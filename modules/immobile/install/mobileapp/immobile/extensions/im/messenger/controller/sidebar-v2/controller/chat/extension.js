/**
 * @module im/messenger/controller/sidebar-v2/controller/chat
 */
jn.define('im/messenger/controller/sidebar-v2/controller/chat', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { SidebarBaseController } = require('im/messenger/controller/sidebar-v2/controller/base');
	const { ChatSidebarView } = require('im/messenger/controller/sidebar-v2/controller/chat/src/view');
	const {
		ChatSidebarPermissionManager,
	} = require('im/messenger/controller/sidebar-v2/controller/chat/src/permission-manager');
	const { SidebarLinksTab } = require('im/messenger/controller/sidebar-v2/tabs/links');
	const { SidebarFilesTab } = require('im/messenger/controller/sidebar-v2/tabs/files');
	const { SidebarAudioTab } = require('im/messenger/controller/sidebar-v2/tabs/audio');
	const { SidebarCommonChatsTab } = require('im/messenger/controller/sidebar-v2/tabs/common-chats');
	const { SidebarMediaTab } = require('im/messenger/controller/sidebar-v2/tabs/media');
	const { SidebarParticipantsTab } = require('im/messenger/controller/sidebar-v2/tabs/participants');
	const { onClearHistoryChat } = require('im/messenger/controller/sidebar-v2/user-actions/chat');
	const {
		SidebarContextMenuActionId,
		SidebarContextMenuActionPosition,
	} = require('im/messenger/controller/sidebar-v2/const');
	const {
		SidebarUserDepartmentService,
	} = require('im/messenger/controller/sidebar-v2/services/user-department-service');
	const { UserProfile } = require('im/messenger/controller/user-profile');
	const { Icon } = require('assets/icons');
	const { onAddParticipants } = require('im/messenger/controller/sidebar-v2/user-actions/participants');
	const {
		createSearchButton,
		createMuteButton,
		createVideoCallButton,
		createAudioCallButton,
		createAutoDeleteButton,
	} = require('im/messenger/controller/sidebar-v2/ui/primary-button/factory');

	class ChatSidebarController extends SidebarBaseController
	{
		/**
		 * @protected
		 * @return {Object[]}
		 */
		getInitialDataProviders()
		{
			return [new SidebarUserDepartmentService(this.dialogId)];
		}

		createPermissionManager(defaultProps)
		{
			return new ChatSidebarPermissionManager(defaultProps);
		}

		createView(defaultProps)
		{
			return new ChatSidebarView(defaultProps);
		}

		getWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_CHAT_TITLE');
		}

		/**
		 * @return {SidebarViewDefaultProps}
		 */
		prepareViewProps()
		{
			return {
				...super.prepareViewProps(),
				callbacks: {
					callUserProfile: this.callUserProfile,
				},
			};
		}

		/**
		 * @desc Method call user profile component
		 */
		callUserProfile()
		{
			UserProfile.show(this.dialogId, {
				backdrop: true,
				openingDialogId: this.dialogId,
			}).catch((err) => this.logger.error('callUserProfile.catch:', err));
		}

		// region context menu

		handleClearHistoryForMeDialogAction()
		{
			onClearHistoryChat({ dialogId: this.dialogId, forAll: false });
		}

		handleClearHistoryForAllDialogAction()
		{
			onClearHistoryChat({ dialogId: this.dialogId, forAll: true });
		}

		getHeaderContextMenuItems()
		{
			return [
				...super.getHeaderContextMenuItems(),
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
							this.logger.log(`${this.constructor.name}.onAddParticipants`, error);
						});
					},
				},
				{
					id: SidebarContextMenuActionId.OPEN_PROFILE,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_OPEN_PROFILE'),
					icon: Icon.PERSON,
					testId: 'sidebar-context-menu-open-profile',
					sort: SidebarContextMenuActionPosition.MIDDLE,
					onItemSelected: () => this.callUserProfile(),
				},
				{
					id: SidebarContextMenuActionId.OPEN_CALENDAR,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_OPEN_CALENDAR'),
					icon: Icon.CALENDAR,
					testId: 'sidebar-context-menu-open-calendar',
					sort: SidebarContextMenuActionPosition.MIDDLE,
					onItemSelected: async () => {
						try
						{
							const { Entry } = await requireLazy('calendar:entry');
							Entry.openUserCalendarView({
								userId: this.dialogId,
								title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_USER_CALENDAR_TITLE'),
							});
						}
						catch (err)
						{
							this.logger.error('openUserCalendarView', err);
						}
					},
				},
			];
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
				createVideoCallButton({
					onClick: () => this.handleVideoCallAction(),
					disabled: !this.permissionManager.canCall(),
				}),
				createAudioCallButton({
					onClick: () => this.handleAudioCallAction(),
					disabled: !this.permissionManager.canCall(),
				}),
				createSearchButton({
					onClick: () => this.handleSearchAction(),
				}),
				this.permissionManager.canMute() && createMuteButton({
					onClick: () => this.handleToggleMuteAction(),
					muted,
				}),
				createAutoDeleteButton({
					onClick: (ref) => this.handleToggleAutoDeleteAction(ref),
					selected: this.dialogHelper.isMessagesAutoDeleteDelayEnabled,
				}),
			];
		}

		// endregion

		// region tabs

		createTabs()
		{
			const props = this.getTabsProps();

			return [
				new SidebarParticipantsTab(props),
				new SidebarCommonChatsTab(props),
				new SidebarMediaTab(props),
				new SidebarFilesTab(props),
				new SidebarLinksTab(props),
				new SidebarAudioTab(props),
			];
		}

		// endregion
	}

	module.exports = {
		ChatSidebarController,
		ControllerClass: ChatSidebarController,
	};
});
