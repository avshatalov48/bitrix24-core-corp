import { DeleteAction } from './delete-action';
import { FireAction } from './fire-action';
import { ConfirmAction } from './confirm-action';
import { DeclineAction } from './decline-action';
import { ReinviteAction } from './reinvite-action';
import { CreateChatAction } from './create-chat-action';
import { ChangeDepartmentAction } from './change-department-action';
import { BaseAction, BaseActionType } from './base-action';

const ACTIONS = [
	DeleteAction,
	FireAction,
	ConfirmAction,
	DeclineAction,
	ReinviteAction,
	CreateChatAction,
	ChangeDepartmentAction,
];

export class ActionFactory
{
	static createAction(actionId: string, params: BaseActionType): BaseAction
	{
		const ActionClass = ACTIONS.find((action) => action.getActionId() === actionId);

		if (!ActionClass)
		{
			throw new Error(`Unknown actionId: ${actionId}`);
		}

		return new ActionClass(params);
	}
}
