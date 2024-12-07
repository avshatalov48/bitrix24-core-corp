import { Type } from 'main.core';

import { ActionParams, Base } from '../base';
import ConfigurableItem from '../../configurable-item';
import { Call } from 'crm.ai.call';

export class TranscriptResult extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'TranscriptResult:Open' && actionData)
		{
			this.#open(actionData);
		}
	}

	#open(actionData): void
	{
		if (
			!Type.isInteger(actionData.activityId)
			|| !Type.isInteger(actionData.ownerTypeId)
			|| !Type.isInteger(actionData.ownerId)
		)
		{
			return;
		}

		const transcription = new Call.Transcription({
			activityId: actionData.activityId,
			ownerTypeId: actionData.ownerTypeId,
			ownerId: actionData.ownerId,
			languageTitle: actionData.languageTitle,
		});

		transcription.open();
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'AI:Call:TranscriptResult');
	}
}
