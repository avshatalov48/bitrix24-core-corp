import { Builder, Dictionary } from 'crm.integration.analytics';
import { ajax as Ajax, Loc, Type } from 'main.core';
import { sendData } from 'ui.analytics';
import { MessageBox } from 'ui.dialogs.messagebox';
import './css/feedback-popup.css';

/**
 * @memberof BX.Crm.AI.Feedback
 */
export function showSendFeedbackPopupIfFeedbackWasNeverSent(
	mergeUuid: number,
	ownerType: string | number,
	activityId: number,
	activityDirection: string,
): Promise
{
	return wasFeedbackSent(mergeUuid).then((wasSent) => {
		if (!wasSent)
		{
			return showSendFeedbackPopup(mergeUuid, ownerType, activityId, activityDirection);
		}

		// eslint-disable-next-line promise/no-return-wrap
		return Promise.resolve();
	});
}

/**
 * @memberof BX.Crm.AI.Feedback
 */
export function wasFeedbackSent(mergeUuid: number): Promise
{
	// Ajax.runAction returns BX.Promise. I think it's not okay to return it from an exported function
	return new Promise((resolve, reject) => {
		Ajax.runAction('crm.timeline.ai.wasFeedbackSent', {
			data: {
				mergeUuid,
			},
		})
			.then(({ data }) => {
				if (Type.isBoolean(data))
				{
					resolve(data);
				}
				else
				{
					resolve(false);
				}
			})
			// eslint-disable-next-line prefer-promise-reject-errors
			.catch((...args) => reject(...args))
		;
	});
}

/**
 * @memberof BX.Crm.AI.Feedback
 */
export function sendFeedback(
	mergeUuid: number,
	ownerType: string | number,
	activityId: number,
	activityDirection: string,
): void
{
	Ajax.runAction('crm.timeline.ai.sendFeedback', {
		data: {
			mergeUuid,
		},
	})
		.then(() => {
			sendData(
				Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, Dictionary.STATUS_SUCCESS)
					.setElement(Dictionary.ELEMENT_FEEDBACK_SEND)
					.setActivityDirection(activityDirection)
					.buildData(),
			);

			sendData(
				Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, Dictionary.STATUS_SUCCESS)
					.setTool(Dictionary.TOOL_CRM)
					.setCategory(Dictionary.CATEGORY_AI_OPERATIONS)
					.setElement(Dictionary.ELEMENT_FEEDBACK_SEND)
					.setActivityDirection(activityDirection)
					.buildData(),
			);
		})
		.catch(({ errors }) => console.error('Error sending feedback', errors));
}

/**
 * @memberof BX.Crm.AI.Feedback
 */
export function showSendFeedbackPopup(
	mergeUuid: number,
	ownerType: string,
	activityId: number,
	activityDirection: string,
): Promise
{
	return new Promise((resolve) => {
		const messageBox = createFeedbackMessageBox({
			onOk: () => {
				sendFeedback(mergeUuid, ownerType, activityId, activityDirection);
				messageBox.close();
				resolve();
			},
			onCancel: () => {
				messageBox.close();

				sendData(
					Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, Dictionary.STATUS_SUCCESS)
						.setElement(Dictionary.ELEMENT_FEEDBACK_REFUSED)
						.setActivityDirection(activityDirection)
						.buildData(),
				);

				sendData(
					Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, Dictionary.STATUS_SUCCESS)
						.setTool(Dictionary.TOOL_CRM)
						.setCategory(Dictionary.CATEGORY_AI_OPERATIONS)
						.setElement(Dictionary.ELEMENT_FEEDBACK_REFUSED)
						.setActivityDirection(activityDirection)
						.buildData(),
				);

				resolve();
			},
		});

		messageBox.show();
	});
}

interface FeedbackMessageBoxOpts {
	onOk: () => void,
	onCancel: () => void,
	popupOptions?: {
		targetContainer: any,
		id: string,
	}
}

/**
 * @memberof BX.Crm.AI.Feedback
 */
export function createFeedbackMessageBox(options: FeedbackMessageBoxOpts): MessageBox
{
	const message = `
		<div class="bx-crm-ai-feedback-popup-content">
			<div class="bx-crm-ai-feedback-popup-content__icon"></div>
			<div class="bx-crm-ai-feedback-popup-content__text">
				${Loc.getMessage('CRM_AI_FEEDBACK_POPUP_TEXT')}
			</div>
		</div>
	`;

	return MessageBox.create({
		title: Loc.getMessage('CRM_AI_FEEDBACK_POPUP_TITLE'),
		message,
		okCaption: Loc.getMessage('CRM_AI_FEEDBACK_POPUP_BUTTON_SHARE'),
		cancelCaption: Loc.getMessage('CRM_AI_FEEDBACK_POPUP_BUTTON_ANOTHER_TIME'),
		buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
		...options,
	});
}
