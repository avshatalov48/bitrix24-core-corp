import { Type } from 'main.core';

import { ActionParams, Base } from '../base';
import ConfigurableItem from '../../configurable-item';
import { Call } from 'crm.ai.call';

export class TranscriptSummaryResult extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'TranscriptSummaryResult:Open' && actionData)
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

		const summary = new Call.Summary({
			activityId: actionData.activityId,
			ownerTypeId: actionData.ownerTypeId,
			ownerId: actionData.ownerId,
			languageTitle: actionData.languageTitle,
		});

		summary.open();
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'AI:Call:TranscriptSummaryResult');
	}
}
