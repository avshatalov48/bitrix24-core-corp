/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/menu/group
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/menu/group', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { UserHelper } = require('im/messenger/lib/helper');
	const { confirmDefaultAction } = require('alert');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { onRemoveParticipant } = require('im/messenger/controller/sidebar-v2/user-actions/participants');
	const { onRemoveManager, onAddManager } = require('im/messenger/controller/sidebar-v2/user-actions/manager');
	const { SidebarActionType, ErrorType } = require('im/messenger/const');
	const { ParticipantsBaseMenu } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/menu/base');
	const { SIDEBAR_DEFAULT_TOAST_OFFSET } = require('im/messenger/controller/sidebar-v2/const');

	/**
	 * @class ParticipantsGroupMenu
	 */
	class ParticipantsGroupMenu extends ParticipantsBaseMenu
	{
		getActionItems()
		{
			return this.isYou()
				? this.selfActions()
				: [
					this.canMention() && this.mentionAction(),
					this.sendAction(),
					this.ownerAction(),
					this.canDelete() && this.removeAction(),
				];
		}

		selfActions()
		{
			return [
				this.notesAction(),
				this.canLeave() && this.leaveAction(),
			];
		}

		ownerAction()
		{
			if (!this.canChangeOwner())
			{
				return null;
			}

			if (this.isManager())
			{
				return {
					id: SidebarActionType.commonRemoveManager,
					title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_MENU_ITEM_LIST_REMOVE_MANAGER'),
					onItemSelected: this.onRemoveManager,
					icon: Icon.CIRCLE_CROSS,
					testId: 'SIDEBAR_USER_MENU_REMOVE_MANAGER',
				};
			}

			return {
				id: SidebarActionType.commonAddManager,
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_MENU_ITEM_LIST_ADD_MANAGER'),
				onItemSelected: this.onAddManager,
				icon: Icon.CROWN,
				testId: 'SIDEBAR_USER_MENU_ADD_MANAGER',
			};
		}

		removeAction()
		{
			return {
				id: SidebarActionType.remove,
				title: this.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_MENU_ITEM_LIST_REMOVE'),
				onItemSelected: this.onRemoveParticipant,
				icon: Icon.BAN,
				testId: 'SIDEBAR_USER_MENU_REMOVE',
			};
		}

		canLeave()
		{
			return this.permissionManager.canLeave();
		}

		canMention()
		{
			return this.permissionManager.canMention();
		}

		canDelete()
		{
			return this.permissionManager.canRemoveUserById(this.getUserId(), this.getDialogId())
				&& this.permissionManager.canRemoveParticipants();
		}

		isOwner()
		{
			return this.dialogHelper.isCurrentUserOwner;
		}

		isManager()
		{
			return this.dialogHelper.dialogModel.managerList.includes(this.getUserId());
		}

		canChangeOwner()
		{
			return this.permissionManager.canChangeOwner() && this.isOwner();
		}

		onAddManager = () => onAddManager(this.getDialogId(), this.getUserId());

		onRemoveManager = () => onRemoveManager(this.getDialogId(), this.getUserId());

		onRemoveParticipant = async () => {
			await this.showRemoveConfirm();

			const showNotificationError = () => {
				Notification.showToastWithParams({
					message: Loc.getMessage(
						'IMMOBILE_SIDEBAR_V2_PARTICIPANTS_USER_INVITED_FROM_STRUCTURE_DELETE_ERROR',
					),
					backgroundColor: Color.accentMainAlert.toHex(),
					offset: SIDEBAR_DEFAULT_TOAST_OFFSET,
				});
			};

			onRemoveParticipant(this.getDialogId(), this.getUserId())
				.catch((errors) => {
					if (errors[0]?.code === ErrorType.dialog.delete.userInvitedFromStructure)
					{
						showNotificationError();
					}
				});
		};

		showRemoveConfirm()
		{
			return new Promise((resolve) => {
				confirmDefaultAction({
					title: this.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_CONFIRM_REMOVE_TITLE'),
					actionButtonText: this.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_CONFIRM_REMOVE_YES'),
					onAction: resolve,
				});
			});
		}

		shouldShowMenu()
		{
			const userHelper = UserHelper.createByUserId(this.getUserId());

			return this.hasActionItems() && !userHelper.isCopilotBot;
		}
	}

	module.exports = {
		ParticipantsGroupMenu,
	};
});
