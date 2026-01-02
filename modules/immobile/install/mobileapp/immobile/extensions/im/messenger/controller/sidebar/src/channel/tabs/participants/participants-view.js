/**
 * @module im/messenger/controller/sidebar/channel/tabs/participants/participants-view
 */
jn.define('im/messenger/controller/sidebar/channel/tabs/participants/participants-view', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Icon } = require('assets/icons');
	const { SidebarActionType } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { SidebarParticipantsView } = require('im/messenger/controller/sidebar/chat/tabs/participants/participants-view');
	const { ChannelParticipantsService } = require('im/messenger/controller/sidebar/channel/tabs/participants/participants-service');
	const { showLeaveChannelAlert } = require('im/messenger/lib/ui/alert');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--participants-view');

	/**
	 * @class ChannelParticipantsView
	 * @typedef {LayoutComponent<SidebarParticipantsViewProps, SidebarParticipantsViewState>} ChannelParticipantsView
	 */
	class ChannelParticipantsView extends SidebarParticipantsView
	{
		/**
		 * @return {ChannelParticipantsService}
		 */
		getParticipantsService()
		{
			return new ChannelParticipantsService(this.props);
		}

		/**
		 * @param {object} item
		 * @param {number} index
		 * @return object
		 */
		setStyleItem(item, index)
		{
			const chatStyleItem = super.setStyleItem(item, index);

			delete chatStyleItem.isCopilot;

			return chatStyleItem;
		}

		/**
		 * @param {object} item
		 * @return object
		 */
		setItemEntity(item)
		{
			return { isYou: item.isYou, isManager: item.isManager };
		}

		/**
		 * @desc Handler leave chat
		 * @void
		 * @protected
		 */
		onClickLeaveChannel()
		{
			showLeaveChannelAlert({
				leaveCallback: () => {
					this.participantsService.onClickLeaveChat()
						.catch((error) => logger.error(`${this.constructor.name}.onClickLeaveChannel`, error));
				},
			});
		}

		/**
		 * @desc Handler long click item
		 * @param {string} key
		 * @param {number} userId
		 * @param {object} isEntity
		 * @param {boolean} isEntity.isYou
		 * @param {boolean?} isEntity.isManager
		 * @param {LayoutComponent} ref
		 * @protected
		 */
		onLongClickItem(key, userId, isEntity, ref)
		{
			const actionsItems = [];

			if (isEntity.isYou)
			{
				actionsItems.push({
					id: SidebarActionType.notes,
					title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_NOTES'),
					callback: this.participantsService.onClickGetNotes,
					icon: Icon.FLAG,
					testId: 'SIDEBAR_USER_CONTEXT_MENU_NOTES',
				});
				if (this.state.permissions.canLeave)
				{
					actionsItems.push({
						id: SidebarActionType.leaveFromChannel,
						title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_CHANNEL_LEAVE'),
						callback: this.onClickLeaveChannel.bind(this),
						icon: Icon.DAY_OFF,
						testId: 'SIDEBAR_USER_CONTEXT_MENU_CHANNEL_LEAVE',
					});
				}
			}
			else
			{
				if (ChatPermission.canMention(this.props.dialogId))
				{
					actionsItems.push({
						id: SidebarActionType.mention,
						title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_MENTION'),
						callback: this.participantsService.onClickPingUser.bind(this, userId),
						icon: Icon.MENTION,
						testId: 'SIDEBAR_USER_CONTEXT_MENU_MENTION',
					});
				}
				actionsItems.push({
					id: SidebarActionType.send,
					title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_SEND'),
					callback: this.participantsService.onClickSendMessage.bind(this, userId),
					icon: Icon.MESSAGE,
					testId: 'SIDEBAR_USER_CONTEXT_MENU_SEND',
				});

				if (DialogHelper.createByDialogId(this.props.dialogId)?.isCurrentUserOwner)
				{
					if (isEntity.isManager)
					{
						actionsItems.push({
							id: SidebarActionType.channelRemoveManager,
							title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_CHANNEL_REMOVE_MANAGER'),
							callback: this.participantsService.onClickRemoveManager.bind(this, userId),
							icon: Icon.CIRCLE_CROSS,
							testId: 'SIDEBAR_USER_CONTEXT_MENU_CHANNEL_REMOVE_MANAGER',
						});
					}
					else
					{
						actionsItems.push({
							id: SidebarActionType.channelAddManager,
							title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_CHANNEL_ADD_MANAGER'),
							callback: this.participantsService.onClickAddManager.bind(this, userId),
							icon: Icon.CROWN,
							testId: 'SIDEBAR_USER_CONTEXT_MENU_CHANNEL_ADD_MANAGER',
						});
					}
				}

				const canDelete = this.state.permissions.canRemoveParticipants;
				if (canDelete && ChatPermission.canRemoveUserById(userId, this.props.dialogId))
				{
					actionsItems.push({
						id: SidebarActionType.removeFromChannel,
						title: Loc.getMessage('IMMOBILE_PARTICIPANTS_MANAGER_ITEM_LIST_CHANNEL_REMOVE'),
						callback: this.onClickRemoveParticipant.bind(this, {
							key,
							userId,
						}),
						icon: Icon.BAN,
						testId: 'SIDEBAR_USER_CONTEXT_MENU_CHANNEL_REMOVE',
					});
				}
			}

			return this.openParticipantManager(ref, actionsItems);
		}

		getUserAddWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_TITLE_CHANNEL');
		}

		/**
		 * @desc Handler remove participant
		 * @param {object} event
		 * @param {string} event.key  - string key item
		 * @void
		 * @protected
		 */
		onClickRemoveParticipant(event)
		{
			setTimeout(() => {
				navigator.notification.confirm(
					'',
					(buttonId) => {
						if (buttonId === 2)
						{
							const {
								key,
								userId,
							} = event;
							const itemPos = this.listViewRef.getElementPosition(key);
							this.removeParticipant(itemPos.index, itemPos.section, userId);
						}
					},
					Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_REMOVE_SUBSCRIBER_CONFIRM_TITLE'),
					[
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_REMOVE_PARTICIPANT_CONFIRM_NO'),
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_REMOVE_PARTICIPANT_CONFIRM_YES'),
					],
				);
			}, 10);
		}
	}

	module.exports = { ChannelParticipantsView };
});
