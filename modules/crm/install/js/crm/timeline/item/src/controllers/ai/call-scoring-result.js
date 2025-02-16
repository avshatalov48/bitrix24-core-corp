import { Call as CallScoringResultDialog } from 'crm.ai.call';
import { Router } from 'crm.router';
import { Type } from 'main.core';
import ConfigurableItem from '../../configurable-item';

import { ActionParams, Base } from '../base';

import 'ui.hint';

export class CallScoringResult extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'CallScoringResult:Open' && actionData)
		{
			this.#open(actionData);
		}

		if (action === 'CallScoringResult:EditPrompt')
		{
			this.#editPrompt(item, actionData);
		}
	}

	#open(actionData: Object): void
	{
		if (
			!Type.isInteger(actionData.activityId)
			|| !Type.isInteger(actionData.ownerTypeId)
			|| !Type.isInteger(actionData.ownerId)
		)
		{
			return;
		}

		const callQualityDlg = new CallScoringResultDialog.CallQuality({
			activityId: actionData.activityId,
			activityCreated: actionData.activityCreated ?? null,
			ownerTypeId: actionData.ownerTypeId,
			ownerId: actionData.ownerId,
			clientDetailUrl: actionData.clientDetailUrl ?? null,
			clientFullName: actionData.clientFullName ?? null,
			userPhotoUrl: actionData.userPhotoUrl ?? null,
			jobId: actionData.jobId ?? null,
		});

		callQualityDlg.open();
	}

	#editPrompt(item: ConfigurableItem, actionData: Object): void
	{
		if (!Type.isInteger(actionData.assessmentSettingId))
		{
			return;
		}

		Router.openSlider(
			`/crm/copilot-call-assessment/details/${actionData.assessmentSettingId}/`,
			{
				width: 700,
				cacheable: false,
			},
		);
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'AI:Call:CallScoringResult');
	}
}
