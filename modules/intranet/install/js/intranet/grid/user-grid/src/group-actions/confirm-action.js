import { BaseAction } from './base-action';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { Loc } from 'main.core';

export class ConfirmAction extends BaseAction
{
	static getActionId(): string
	{
		return 'confirm';
	}

	getConfirmationPopup(): ?MessageBox
	{
		return new MessageBox({
			message: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE'),
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_TITLE'),
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_MESSAGE_BUTTON'),
		});
	}

	handleSuccess(result: Result): void
	{
		this.grid.reload();

		if (this.showPopups)
		{
			const { skippedFiredUsers } = result.data;

			if (skippedFiredUsers && Object.keys(skippedFiredUsers).length > 0)
			{
				this.showFiredUsersPopup(skippedFiredUsers);
			}
		}
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

	getAjaxMethod(): string
	{
		return 'intranet.controller.user.userlist.groupConfirm';
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
