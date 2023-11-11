import { Base } from './base';
import { Type } from 'main.core';
import ConfigurableItem from '../configurable-item';
import ContactList from "../components/content-blocks/mail/contact-list";

export class Email extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Email::OpenMessage' && actionData)
		{
			this.#openMessage(actionData);
		}

		if (action === 'Email::Schedule' && actionData)
		{
			this.#schedule(actionData.activityId, actionData.scheduleDate);
		}
	}

	#viewActivity(id): void
	{
		const editor = this.#getActivityEditor();
		if (editor && id)
		{
			const emailActivity = BX.CrmActivityEmail.create(
				{
					ID: id,
				},
				editor,
				{}
			);

			emailActivity.openDialog(BX.CrmDialogMode.view);
		}
	}

	#getActivityEditor(): BX.CrmActivityEditor
	{
		return BX.CrmActivityEditor.getDefault();
	}

	#schedule(activityId: Number, scheduleDate: String): void
	{
		const menuBar = BX.Crm?.Timeline?.MenuBar?.getDefault();
		if (menuBar)
		{
			menuBar.setActiveItemById('todo');

			const todoEditor = menuBar.getItemById('todo');
			todoEditor.focus();
			todoEditor.setParentActivityId(activityId);
			todoEditor.setDeadLine(scheduleDate);
		}
	}

	#openMessage(actionData): void
	{
		if (!Type.isNumber(actionData.threadId))
		{
			return;
		}
		this.#viewActivity(actionData.threadId);
	}

	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			ContactList,
		};
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'ContactList' || item.getType() === 'Activity:Email' || item.getType() === 'EmailActivitySuccessfullyDelivered');
	}
}
