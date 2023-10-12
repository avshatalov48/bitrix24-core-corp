import { Text } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { Base } from './base';
import ConfigurableItem from '../configurable-item';

export class Activity extends Base
{
	getDeleteActionMethod(): string
	{
		return 'crm.timeline.activity.delete';
	}

	getDeleteActionCfg(recordId: Number, ownerTypeId: Number, ownerId: Number): Object
	{
		return {
			data: {
				activityId: recordId,
				ownerTypeId: ownerTypeId,
				ownerId: ownerId,
			}
		};
	}

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
					message: Text.encode(confirmationText),
					modal: true,
					buttons: MessageBoxButtons.YES_NO,
					onYes: () => {
						return this.runDeleteAction(actionData.activityId, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
					},
					onNo: (messageBox) => {
						messageBox.close();
					},
				});
			}
			else
			{
				this.runDeleteAction(actionData.activityId, actionData.ownerTypeId, actionData.ownerId);
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
