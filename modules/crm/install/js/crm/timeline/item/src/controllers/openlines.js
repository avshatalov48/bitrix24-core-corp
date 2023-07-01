import { Base } from './base';
import ConfigurableItem from '../configurable-item';
import ChatMessage from '../components/content-blocks/chat-message';
import { MessageBox } from "ui.dialogs.messagebox";
import { MessageBoxButtons } from "ui.dialogs.messagebox";
import { Loc } from "main.core";
import { ajax as Ajax } from "main.core";
import { UI } from "ui.notification";

export class OpenLines extends Base
{
	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			ChatMessage,
		};
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData, animationCallbacks} = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Openline:OpenChat' && actionData && actionData.dialogId)
		{
			this.#openChat(actionData.dialogId);
		}

		if (action === 'Openline:Complete' && actionData && actionData.activityId)
		{
			this.#onComplete(item, actionData, animationCallbacks);
		}
	}

	#openChat(dialogId): void
	{
		if (window.top['BXIM'])
		{
			window.top['BXIM'].openMessengerSlider(dialogId, {RECENT: 'N', MENU: 'N'});
		}
	}

	#onComplete(item: ConfigurableItem, actionData: Object, animationCallbacks: ?Object): void
	{
		MessageBox.show({
			title: Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_OPENLINE_COMPLETE_CONF_TITLE'),
			message: Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_OPENLINE_COMPLETE_CONF'),
			modal: true,
			okCaption: BX.Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_OPENLINE_COMPLETE_CONF_OK_TEXT'),
			buttons: MessageBoxButtons.OK_CANCEL,
			onOk: () => {
				return this.#runCompleteAction(actionData.activityId, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
			},
			onCancel: (messageBox) => {
				const changeStreamButton = item.getLayoutHeaderChangeStreamButton();
				if (changeStreamButton)
				{
					changeStreamButton.markCheckboxUnchecked();
				}

				messageBox.close();
			},
		});
	}

	#runCompleteAction(activityId: Number, ownerTypeId: Number, ownerId: Number, animationCallbacks: ?Object): Promise
	{
		if (animationCallbacks.onStart)
		{
			animationCallbacks.onStart();
		}

		return Ajax.runAction(
			'crm.timeline.activity.complete',
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
		},
		(response) => {
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



	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:OpenLine');
	}
}
