import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import {ajax as Ajax} from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import {UI} from 'ui.notification';

export class Activity extends Base
{
	onItemAction(item: ConfigurableItem, action: String, actionData: ?Object): void
	{
		if (action === 'Activity:Edit' && actionData && actionData.activityId)
		{
			this.#editActivity(actionData.activityId);
		}
		if (action === 'Activity:View' && actionData && actionData.activityId)
		{
			this.#viewActivity(actionData.activityId);
		}
		if (action === 'Activity:Delete' && actionData && actionData.activityId)
		{
			const confirmationText = actionData.confirmationText ?? '';
			if (confirmationText)
			{
				MessageBox.show({
					message: confirmationText,
					modal: true,
					buttons: MessageBoxButtons.YES_NO,
					onYes: () => {
						return this.#deleteActivity(actionData.activityId, actionData.ownerTypeId, actionData.ownerId);
					},
					onNo: (messageBox) => {
						messageBox.close();
					},
				});
			}
			else
			{
				this.#deleteActivity(actionData.activityId, actionData.ownerTypeId, actionData.ownerId);
			}
		}
	}

	#viewActivity(id): void
	{
		const editor = this.#getActivityEditor();
		if (editor && id)
		{
			editor.viewActivity(id);
		}
	}

	#editActivity(id): void
	{
		const editor = this.#getActivityEditor();
		if (editor && id)
		{
			editor.editActivity(id);
		}
	}

	#deleteActivity(activityId: Number, ownerTypeId: Number, ownerId: Number)
	{
		return Ajax.runAction(
			'crm.timeline.activity.delete',
			{
				data: {
					activityId,
					ownerTypeId,
					ownerId,
				}
			}
		).then(() => {
			return true;
		}, (response) =>
		{
			UI.Notification.Center.notify({
				content: response.errors[0].message,
				autoHideDelay: 5000,
			});

			return true;
		});
	}

	#getActivityEditor(): BX.CrmActivityEditor
	{
		return BX.CrmActivityEditor.getDefault();
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		const itemType = item.getType();

		return (itemType.indexOf('Activity:') === 0); // for items with type started from `Activity:`
	}
}

