import { BaseAction } from './base-action';
import { Loc } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { ConfirmAction } from './confirm-action';

export class ReinviteAction extends BaseAction
{
	static getActionId(): string
	{
		return 'reInvite';
	}

	getAjaxMethod(): string
	{
		return 'intranet.controller.user.userlist.groupReInvite';
	}

	handleSuccess(result)
	{
		this.grid.tableUnfade();
		const { skippedActiveUsers, skippedFiredUsers, skippedWaitingUsers } = result.data;

		if (skippedActiveUsers && Object.keys(skippedActiveUsers).length > 0)
		{
			this.showActiveUsersPopup(skippedActiveUsers);
		}
		else if (skippedWaitingUsers && Object.keys(skippedWaitingUsers).length > 0)
		{
			this.showWaitingUsersPopup(skippedWaitingUsers);
		}
		else if (skippedFiredUsers && Object.keys(skippedFiredUsers).length > 0)
		{
			this.showFiredUsersPopup(skippedFiredUsers);
		}
		else
		{
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_REINVITE_SUCCESS'),
				autoHide: true,
				position: 'bottom-right',
				category: 'menu-self-item-popup',
				autoHideDelay: 3000,
			});
			BX.Bitrix24?.EmailConfirmation?.showPopupDispatched();
		}

		this.unselectRows(this.grid);
	}

	showWaitingUsersPopup(waitingUsers: Array): void
	{
		MessageBox.show({
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_ALREADY_ACCEPT_INVITE_TITLE'),
			message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_ALREADY_ACCEPT_INVITE_MESSAGE', waitingUsers),
			buttons: MessageBoxButtons.YES_CANCEL,
			yesCaption: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_BUTTON'),
			onYes: (messageBox) => {
				messageBox.close();
				(new ConfirmAction({
					grid: this.grid,
					filter: this.userFilter,
					selectedUsers: Object.keys(waitingUsers),
					showPopups: false,
				})).execute();
			},
		});
	}

	showFiredUsersPopup(firedUsers: Array): void
	{
		MessageBox.show({
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_TITLE'),
			message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_ACCEPT_FIRED_MESSAGE', firedUsers),
			buttons: MessageBoxButtons.YES,
			yesCaption: Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
			onYes: (messageBox) => {
				messageBox.close();
			},
		});
	}

	getSkippedUsersMessageCode(): string
	{
		return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_MESSAGE';
	}

	getSkippedUsersTitleCode(): string
	{
		return 'INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_SKIPPED_TITLE';
	}
}
