import { Base } from './base';
import { Type } from 'main.core';
import ConfigurableItem from '../configurable-item';

export class Call extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Call:MakeCall' && actionData)
		{
			this.#makeCall(actionData);
		}

		if (action === 'Call:Schedule' && actionData)
		{
			this.#scheduleCall(actionData.activityId, actionData.scheduleDate);
		}

		if (action === 'Call:OpenTranscript' && actionData && actionData.callId)
		{
			this.#openTranscript(actionData.callId);
		}

		if (action === 'Call:ChangePlayerState' && actionData && actionData.recordId)
		{
			this.#changePlayerState(item, actionData.recordId);
		}

		if (action === 'Call:DownloadRecord' && actionData && actionData.url)
		{
			this.#downloadRecord(actionData.url);
		}
	}

	#makeCall(actionData): void
	{
		if (!Type.isStringFilled(actionData.phone))
		{
			return;
		}

		const params = {
			ENTITY_TYPE_NAME: BX.CrmEntityType.resolveName(actionData.entityTypeId),
			ENTITY_ID: actionData.entityId,
			AUTO_FOLD: true
		};

		if (actionData.ownerTypeId !== actionData.entityTypeId || actionData.ownerId !== actionData.entityId)
		{
			params.BINDINGS = {
				OWNER_TYPE_NAME: BX.CrmEntityType.resolveName(actionData.ownerTypeId),
				OWNER_ID: actionData.ownerId
			}
		}

		if (actionData.activityId > 0)
		{
			params.SRC_ACTIVITY_ID = actionData.activityId;
		}

		window.top['BXIM'].phoneTo(actionData.phone, params);
	}

	#scheduleCall(activityId: Number, scheduleDate: String): void
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

	#openTranscript(callId): void
	{
		if (BX.Voximplant && BX.Voximplant.Transcript)
		{
			BX.Voximplant.Transcript.create({
				callId: callId
			}).show();
		}
	}

	#changePlayerState(item: ConfigurableItem, recordId: Number): void
	{
		const player = item.getLayoutContentBlockById('audio');
		if (!player)
		{
			return;
		}

		if (recordId !== player.id)
		{
			return;
		}

		if (player.state === 'play')
		{
			player.pause();
		}
		else
		{
			player.play();
		}
	}

	#downloadRecord(url: String): void
	{
		location.href = url;
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:Call');
	}
}
