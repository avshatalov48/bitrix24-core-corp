import { ajax as Ajax, Loc, Type } from 'main.core';
import { sendData } from 'ui.analytics';
import { MessageBox } from 'ui.dialogs.messagebox';
import './css/feedback-popup.css';

/**
 * @memberof BX.Crm.AI.Feedback
 */
export function showSendFeedbackPopupIfFeedbackWasNeverSent(
	mergeUuid: number,
	ownerType: string,
	crmMode: string,
	callId: string,
): Promise
{
	return wasFeedbackSent(mergeUuid).then((wasSent) => {
		if (!wasSent)
		{
			return showSendFeedbackPopup(mergeUuid, ownerType, crmMode, callId);
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

export function sendFeedback(mergeUuid: number, ownerType: string, crmMode: string, callId: string): void
{
	Ajax.runAction('crm.timeline.ai.sendFeedback', {
		data: {
			mergeUuid,
		},
	})
		.then(() => {
			if (
				Type.isStringFilled(ownerType)
				&& Type.isStringFilled(crmMode)
				&& Type.isStringFilled(callId)
			)
			{
				sendData({
					event: 'call_parsing',
					tool: 'AI',
					category: 'crm_operations',
					type: 'manual',
					c_section: 'crm',
					c_element: 'feedback_send',
					c_sub_section: ownerType,
					p1: crmMode,
					p2: callId,
					status: 'success',
				});
			}
		})
		.catch(({ errors }) => console.error('Error sending feedback', errors));
}

/**
 * @memberof BX.Crm.AI.Feedback
 */
export function showSendFeedbackPopup(mergeUuid: number, ownerType: string, crmMode: string, callId: string): Promise
{
	return new Promise((resolve) => {
		const messageBox = createFeedbackMessageBox({
			onOk: () => {
				sendFeedback(mergeUuid, ownerType, crmMode, callId);
				messageBox.close();
				resolve();
			},
			onCancel: () => {
				messageBox.close();

				sendData({
					event: 'call_parsing',
					tool: 'AI',
					category: 'crm_operations',
					type: 'manual',
					c_section: 'crm',
					c_element: 'feedback_refused',
					c_sub_section: ownerType,
					p1: crmMode,
					p2: callId,
					status: 'success',
				});

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
