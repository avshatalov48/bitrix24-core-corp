import { Call as CallScoringResultDialog } from 'crm.ai.call';
import { Loc, Runtime, Type } from 'main.core';
import { Button } from '../../components/layout/button';
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
		const btn: Button = item.getLayoutFooterButtonById('editPromptButton')?.getUiButton();

		BX.UI.Hint.popupParameters = {
			closeByEsc: true,
			autoHide: true,
		};

		Runtime.debounce(
			() => {
				BX.UI.Hint.show(
					btn?.getContainer(),
					Loc.getMessage('CRM_TIMELINE_ITEM_CALL_SCORING_EDIT_PROMPT_HINT'),
					true,
				);
			},
			150,
			this,
		)();

		// @todo: not implemented yet
		/*
		if (!Type.isInteger(actionData.assessmentSettingId))
		{
			return;
		}

		if (SidePanel.Instance)
		{
			const path = `/crm/copilot-call-assessment/details/${actionData.assessmentSettingId}/`;

			SidePanel.Instance.open(path, {
				width: 700,
				allowChangeHistory: false,
			});
		}
		*/
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'AI:Call:CallScoringResult');
	}
}
