/**
 * @module im/messenger/controller/sidebar/collab/tabs/participants/participants-view
 */
jn.define('im/messenger/controller/sidebar/collab/tabs/participants/participants-view', (require, exports, module) => {
	const { SidebarParticipantsView } = require('im/messenger/controller/sidebar/chat/tabs/participants/participants-view');
	const { showLeaveCollabAlert, showRemoveParticipantCollabAlert } = require('im/messenger/lib/ui/alert');
	const { ChatPermission, UserPermission } = require('im/messenger/lib/permission-manager');
	const { SidebarActionType } = require('im/messenger/const');

	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--participants-view');

	const { Icon } = require('assets/icons');
	const { Type } = require('type');
	const { Loc } = require('loc');
	/**
	 * @class CollabParticipantsView
	 * @typedef {LayoutComponent<SidebarParticipantsViewProps, SidebarParticipantsViewState>} CollabParticipantsView
	 */
	class CollabParticipantsView extends SidebarParticipantsView
	{
		constructor(props)
		{
			super(props);

			this.state = {
				participants: this.participantsService.getParticipantsFromStore(),
				permissions: {
					isCanAddParticipants: ChatPermission.isCanAddParticipants(props.dialogId),
				},
			};
		}

		/**
		 * @desc Handler long click item
		 * @param {string} key
		 * @param {number} userId
		 * @param {object} isEntity
		 * @param {boolean} isEntity.isYou
		 * @param {LayoutComponent} ref
		 * @protected
		 */
		onLongClickItem(key, userId, isEntity, ref)
		{
			const { isYou } = isEntity;
			const actionsItems = [];

			const canOpenNotes = isYou;
			const canLeave = UserPermission.canLeaveFromCollab(userId)
				&& ChatPermission.isCanLeaveFromChat(this.props.dialogId)
				&& isYou;
			const canMentionAndSend = !isYou;
			const canRemoveUser = !isYou && ChatPermission.isOwner();

			if (canOpenNotes)
			{
				actionsItems.push({
					id: SidebarActionType.notes,
					title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_NOTES'),
					callback: this.participantsService.onClickGetNotes,
					icon: Icon.FLAG,
					testId: 'SIDEBAR_USER_CONTEXT_MENU_NOTES',
				});
			}

			if (canLeave)
			{
				actionsItems.push({
					id: SidebarActionType.leaveFromCollab,
					title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_COLLAB_LEAVE'),
					callback: this.onLeaveChatHandle.bind(this),
					icon: Icon.DAY_OFF,
					testId: 'SIDEBAR_USER_CONTEXT_MENU_COLLAB_LEAVE',
				});
			}

			if (canMentionAndSend)
			{
				actionsItems.push({
					id: SidebarActionType.mention,
					title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_MENTION'),
					callback: this.participantsService.onClickPingUser.bind(this, userId),
					icon: Icon.MENTION,
					testId: 'SIDEBAR_USER_CONTEXT_MENU_MENTION',
				}, {
					id: SidebarActionType.send,
					title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_SEND'),
					callback: this.participantsService.onClickSendMessage.bind(this, userId),
					icon: Icon.MESSAGE,
					testId: 'SIDEBAR_USER_CONTEXT_MENU_SEND',
				});
			}

			if (canRemoveUser)
			{
				actionsItems.push({
					id: SidebarActionType.removeFromCollab,
					title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_REMOVE_FROM_COLLAB'),
					callback: this.onRemoveParticipantHandle.bind(this, userId),
					icon: Icon.BAN,
					testId: 'SIDEBAR_USER_CONTEXT_MENU_REMOVE_FROM_COLLAB',
				});
			}

			if (actionsItems.length === 0)
			{
				return false;
			}

			return this.openParticipantManager(ref, actionsItems);
		}

		onLeaveChatHandle()
		{
			showLeaveCollabAlert({
				leaveCallback: () => {
					this.participantsService.onClickLeaveChat()
						.catch((err) => logger.error(`${this.constructor.name}.onLeaveChatHandle`, err));
				},
			});
		}

		/**
		 * @desc Handler remove participant
		 * @param {number} userId
		 */
		onRemoveParticipantHandle(userId)
		{
			showRemoveParticipantCollabAlert({
				removeCallback: () => {
					void this.participantsService.sidebarRestService.deleteParticipant(userId)
						.catch((error) => {
							logger.error(`${this.constructor.name}.onRemoveParticipantHandle: `, error);
						});
				},
			});
		}

		/**
		 * @desc Call user add widget
		 * @void
		 * @protected
		 */
		async openParticipantsAddWidget()
		{
			const dialog = this.store.getters['dialoguesModel/getById'](this.props.dialogId);
			if (!dialog)
			{
				return Promise.reject(new Error('openParticipantsAddWidget: unknown dialog'));
			}

			this.sendAnalyticsAboutOpeningParticipantAddWidget();

			const collabId = this.store.getters['dialoguesModel/collabModel/getCollabIdByDialogId'](this.props.dialogId);
			if (Type.isNumber(collabId))
			{
				const { openCollabInvite, CollabInviteAnalytics } = await requireLazy('collab/invite');
				openCollabInvite({
					collabId,
					analytics: new CollabInviteAnalytics()
						.setSection(CollabInviteAnalytics.Section.CHAT_SIDEBAR)
						.setChatId(dialog.chatId),
				});
			}

			return Promise.resolve();
		}
	}

	module.exports = { CollabParticipantsView };
});
