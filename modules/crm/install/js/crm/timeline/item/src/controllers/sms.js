import { Loc, Type } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

import { ActionParams, Base } from './base';
import ConfigurableItem from '../configurable-item';

declare type SmsParams = {
	text: string,
	senderId: string,
	from: string,
	client: Object,
}

export class Sms extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Activity:Sms:Resend' && Type.isPlainObject(actionData.params))
		{
			this.#resendSms(actionData.params);
		}
	}

	#resendSms(params: SmsParams): void
	{
		const menuBar = BX.Crm?.Timeline?.MenuBar?.getDefault();
		if (!menuBar)
		{
			throw new Error('"BX.Crm?.Timeline.MenuBar" component not found');
		}

		const smsItem = menuBar.getItemById('sms');
		if (!smsItem)
		{
			throw new Error('"BX.Crm.Timeline.MenuBar.Sms" component not found');
		}

		const goToEditor = (): void => {
			menuBar.scrollIntoView();
			menuBar.setActiveItemById('sms');
			smsItem.tryToResend(params.senderId, params.from, params.client, params.text);
		};
		const { text, templateId } = smsItem.getSendData();
		if (Type.isStringFilled(text) || templateId !== null)
		{
			MessageBox.show({
				modal: true,
				title: Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_SMS_RESEND_CONFIRM_DIALOG_TITLE'),
				message: Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_SMS_RESEND_CONFIRM_DIALOG_MESSAGE'),
				buttons: MessageBoxButtons.OK_CANCEL,
				okCaption: Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_SMS_RESEND_CONFIRM_DIALOG_OK_BTN'),
				onOk: (messageBox) => {
					messageBox.close();
					goToEditor();
				},
				onCancel: (messageBox) => messageBox.close(),
			});
		}
		else
		{
			goToEditor();
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:Sms');
	}
}
