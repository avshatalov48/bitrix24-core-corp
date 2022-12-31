import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import {ajax as Ajax} from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import {UI} from 'ui.notification';

export class Activity extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData, animationCallbacks} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}
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
						return this.#deleteActivity(actionData.activityId, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
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

	#deleteActivity(activityId: Number, ownerTypeId: Number, ownerId: Number, animationCallbacks: ?Object)
	{
		if (animationCallbacks.onStart)
		{
			animationCallbacks.onStart();
		}
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
			if (animationCallbacks.onStop)
			{
				animationCallbacks.onStop();
			}
			return true;
		}, (response) =>
		{
			UI.Notification.Center.notify({
				content: response.errors[0].message,
				autoHideDelay: 5000,
			});
			if (animationCallbacks.onStop)
			{
				animationCallbacks.onStop();
			}

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

		return (
			itemType.indexOf('Activity:') === 0  // for items with type started from `Activity:`
			|| itemType === 'TodoCreated' // TodoCreated can contain link to activity
		);
	}
}

