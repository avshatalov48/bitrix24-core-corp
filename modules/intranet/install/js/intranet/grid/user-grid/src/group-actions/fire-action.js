import { BaseAction } from './base-action';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { Loc } from 'main.core';

export class FireAction extends BaseAction
{
	static getActionId(): string
	{
		return 'fire';
	}

	getConfirmationPopup(): ?MessageBox
	{
		return new MessageBox({
			message: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE'),
			title: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE_TITLE'),
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('INTRANET_USER_LIST_GROUP_ACTION_FIRE_MESSAGE_BUTTON'),
		});
	}

	getAjaxMethod(): string
	{
		return 'intranet.controller.user.userlist.groupFire';
	}

	getSkippedUsersMessageCode(): string
	{
		return 'INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_MESSAGE';
	}

	getSkippedUsersTitleCode(): string
	{
		return 'INTRANET_USER_LIST_GROUP_ACTION_FIRE_SKIPPED_TITLE';
	}
}
