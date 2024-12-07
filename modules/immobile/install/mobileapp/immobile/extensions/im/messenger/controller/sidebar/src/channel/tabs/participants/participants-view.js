/**
 * @module im/messenger/controller/sidebar/channel/tabs/participants/participants-view
 */
jn.define('im/messenger/controller/sidebar/channel/tabs/participants/participants-view', (require, exports, module) => {
	const { SidebarParticipantsView } = require(
		'im/messenger/controller/sidebar/chat/tabs/participants/participants-view',
	);
	const { ChatPermission } = require('im/messenger/lib/permission-manager');
	const { Loc } = require('loc');
	const { Icon } = require('assets/icons');
	const { ChannelParticipantsService } = require('im/messenger/controller/sidebar/channel/tabs/participants/participants-service');

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
			chatStyleItem.isManager = item.isManager;

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
			setTimeout(() => {
				navigator.notification.confirm(
					'',
					(buttonId) => {
						if (buttonId === 2)
						{
							this.participantsService.onClickLeaveChat();
						}
					},
					Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHANNEL_CONFIRM_TITLE'),
					[
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHAT_CONFIRM_NO'),
						Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_LEAVE_CHANNEL_CONFIRM_YES'),
					],
				);
			}, 10);
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
					actionName: 'notes',
					callback: this.participantsService.onClickGetNotes,
					icon: Icon.FLAG,
				});
				if (this.state.permissions.isCanLeave)
				{
					actionsItems.push({
						actionName: 'channel_leave',
						callback: this.onClickLeaveChannel.bind(this),
						icon: Icon.DAY_OFF,
					});
				}
			}
			else
			{
				if (ChatPermission.isCanMention(this.props.dialogId))
				{
					actionsItems.push({
						actionName: 'mention',
						callback: this.participantsService.onClickPingUser.bind(this, userId),
						icon: Icon.MENTION,
					});
				}
				actionsItems.push({
					actionName: 'send',
					callback: this.participantsService.onClickSendMessage.bind(this, userId),
					icon: Icon.MESSAGE,
				});

				if (ChatPermission.isOwner())
				{
					if (isEntity.isManager)
					{
						actionsItems.push({
							actionName: 'channel_remove_manager',
							callback: this.participantsService.onClickRemoveManager.bind(this, userId),
							icon: Icon.CIRCLE_CROSS,
						});
					}
					else
					{
						actionsItems.push({
							actionName: 'channel_add_manager',
							callback: this.participantsService.onClickAddManager.bind(this, userId),
							icon: Icon.CROWN,
						});
					}
				}

				const isCanDelete = this.state.permissions.isCanRemoveParticipants;
				if (isCanDelete && ChatPermission.isCanRemoveUserById(userId, this.props.dialogId))
				{
					actionsItems.push({
						actionName: 'channel_remove',
						callback: this.onClickRemoveParticipant.bind(this, {
							key,
							userId,
						}),
						icon: Icon.BAN,
					});
				}
			}

			return this.openParticipantManager(ref, actionsItems);
		}

		getUserAddWidgetTitle()
		{
			return Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PARTICIPANTS_ADD_TITLE_CHANNEL');
		}
	}

	module.exports = { ChannelParticipantsView };
});
