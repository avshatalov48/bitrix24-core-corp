import { ajax, Runtime, Text, Type } from 'main.core';
import ConfigurableItem from '../../configurable-item';

import { ActionAnimationCallbacks, ActionParams, Base } from '../base';

export class EntityFieldsFillingResult extends Base
{
	async onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData, animationCallbacks } = actionParams;

		if (actionType !== 'jsEvent' || !actionData)
		{
			return;
		}

		switch (action)
		{
			case 'EntityFieldsFillingResult:OpenAiFormFill':
				this.#openAiFormFillAction(actionData);
				break;
			case 'EntityFieldsFillingResult:OpenSendFeedbackPopup':
				this.#openSendFeedbackPopup(actionData, animationCallbacks);
				break;
			default:
		}
	}

	async #openAiFormFillAction(actionData: Object): void
	{
		const operationStatus = await this.#fetchOperationStatus(actionData.mergeUuid);
		switch (operationStatus)
		{
			case 'APPLIED':
				this.#openAiDoneSlider();
				break;
			case 'CONFLICT':
				this.#openAiFormFill(actionData);
				break;
			default:
				throw new Error(`Invalid operation status: ${operationStatus}`);
		}
	}

	#openAiFormFill(actionData: Object): void
	{
		const mergeUuid = parseInt(actionData.mergeUuid, 10);
		if (!Type.isInteger(mergeUuid) || mergeUuid <= 0)
		{
			return;
		}

		top.BX.Runtime.loadExtension('crm.ai.form-fill')
			.then((exports) => {
				const { createAiFormFillApplicationInsideSlider } = exports;

				createAiFormFillApplicationInsideSlider({ ...actionData, mergeUuid });
			})
			.catch(() => {
				throw new Error('Cant load createAiFormFillApplicationInsideSlider extension');
			});
	}

	#openAiDoneSlider(): void
	{
		top.BX.Runtime.loadExtension('crm.ai.done')
			.then((exports) => {
				const { Done } = exports;
				(new Done()).start();
			})
			.catch(() => {
				throw new Error('Cant load crm.ai.done extension');
			});
	}

	async #fetchOperationStatus(mergeId: number): Promise<void>
	{
		const response = await ajax.runAction('crm.timeline.ai.fieldsFillingStatus', {
			data: { mergeId },
		});

		if (response.status !== 'success')
		{
			return null;
		}

		return response?.data?.operationStatus;
	}

	#openSendFeedbackPopup(actionData, animationCallbacks: ?ActionAnimationCallbacks): void
	{
		const mergeUuid: number = parseInt(actionData.mergeUuid, 10);
		if (!Type.isInteger(mergeUuid) || mergeUuid <= 0)
		{
			return;
		}
		const activityId: number = Text.toInteger(actionData.activityId) > 0 ? Text.toInteger(actionData.activityId) : 0;

		animationCallbacks?.onStart?.();

		Runtime.loadExtension('crm.ai.feedback')
			.then((exports) => {
				const { showSendFeedbackPopup } = exports;

				/** @see BX.Crm.AI.Feedback.showSendFeedbackPopup */
				showSendFeedbackPopup(mergeUuid, actionData.ownerTypeId, activityId, actionData.activityDirection);
			})
			.catch(() => {
				console.error('Cant load showSendFeedbackPopup extension');
			}).finally(() => animationCallbacks?.onStop?.());
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'AI:Call:EntityFieldsFillingResult');
	}
}
