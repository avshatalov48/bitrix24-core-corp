import { BaseAction } from './base-action';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { Loc } from 'main.core';
import { FireAction } from './fire-action';

export class DeleteAction extends BaseAction
{
	static getActionId(): string
	{
		return 'delete';
	}

	getAjaxMethod(): string
	{
		return 'intranet.controller.user.userlist.groupDelete';
	}

	getConfirmationPopup(): ?MessageBox
	{
		return new MessageBox({
			message: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE'),
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE_TITLE'),
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_MESSAGE_BUTTON'),
		});
	}

	showActiveUsersPopup(activeUsers: Array)
	{
		MessageBox.show({
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_TITLE'),
			message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_MESSAGE', activeUsers),
			buttons: MessageBoxButtons.YES_CANCEL,
			yesCaption: Loc.getMessage('INTRANET_USER_LIST_ACTION_DEACTIVATE_INVITED_BUTTON'),
			onYes: (messageBox) => {
				messageBox.close();
				(new FireAction({
					selectedUsers: Object.keys(activeUsers),
					grid: this.grid,
					filter: this.userFilter,
					showPopups: false,
				})).execute();
			},
			onNo: () => {
				this.grid.reload();
			},
		});
	}

	showFiredUsersPopup(firedUsers: Array): void
	{
		MessageBox.show({
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DELETE_FIRED_TITLE'),
			message: this.getMessageWithProfileNames('INTRANET_USER_LIST_GROUP_ACTION_DELETE_FIRED_MESSAGE', firedUsers),
			buttons: MessageBoxButtons.YES,
			yesCaption: Loc.getMessage('INTRANET_USER_LIST_ACTION_UNDERSTOOD_BUTTON'),
			onYes: (messageBox) => {
				messageBox.close();
			},
		});
	}

	getSkippedUsersTitleCode(): string
	{
		return 'INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_TITLE';
	}

	getSkippedUsersMessageCode(): string
	{
		return 'INTRANET_USER_LIST_GROUP_ACTION_DELETE_SKIPPED_MESSAGE';
	}
}
