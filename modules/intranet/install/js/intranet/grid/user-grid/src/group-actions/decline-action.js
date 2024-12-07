import { BaseAction } from './base-action';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { Loc } from 'main.core';
import {FireAction} from "./fire-action";

export class DeclineAction extends BaseAction
{
	static getActionId(): string
	{
		return 'decline';
	}

	getConfirmationPopup(): ?MessageBox
	{
		return new MessageBox({
			message: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE'),
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE_TITLE'),
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_MESSAGE_BUTTON'),
		});
	}

	showActiveUsersPopup(activeUsers: Array): void
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
		});
	}

	getAjaxMethod(): string
	{
		return 'intranet.controller.user.userlist.groupDecline';
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
