import { Loc, Type } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

import { ActionParams, Base } from './base';
import ConfigurableItem from '../configurable-item';

declare type WhatsAppParams = {
	template: Object,
	from: string,
	client: Object,
}

export class WhatsApp extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Activity:Whatsapp:Resend' && Type.isPlainObject(actionData.params))
		{
			this.#resendWhatsApp(actionData.params);
		}
	}

	#resendWhatsApp(params: WhatsAppParams): void
	{
		const menuBar = BX.Crm?.Timeline?.MenuBar?.getDefault();
		if (!menuBar)
		{
			throw new Error('"BX.Crm?.Timeline.MenuBar" component not found');
		}

		const whatsAppItem = menuBar.getItemById('whatsapp');
		if (!whatsAppItem)
		{
			throw new Error('"BX.Crm.Timeline.MenuBar.WhatsApp" component not found');
		}

		const goToEditor = (): void => {
			menuBar.scrollIntoView();
			menuBar.setActiveItemById('whatsapp');
			whatsAppItem.tryToResend(params.template, params.from, params.client);
		};

		const templateId = params.template?.ORIGINAL_ID;
		const filledPlaceholders = params.template?.FILLED_PLACEHOLDERS ?? [];

		const currentTemplateId = whatsAppItem.getTemplate()?.ORIGINAL_ID;
		const currentFilledPlaceholders = whatsAppItem.getTemplate()?.FILLED_PLACEHOLDERS ?? [];

		if (
			Type.isNumber(templateId) && templateId > 0
			&& Type.isNumber(currentTemplateId) && currentTemplateId > 0
			&& (
				templateId !== currentTemplateId
				|| JSON.stringify(filledPlaceholders) !== JSON.stringify(currentFilledPlaceholders)
			)
		)
		{
			MessageBox.show({
				modal: true,
				title: Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_WHATSAPP_RESEND_CONFIRM_DIALOG_TITLE'),
				message: Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_WHATSAPP_RESEND_CONFIRM_DIALOG_MESSAGE'),
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
		return (item.getType() === 'Activity:Whatsapp');
	}
}
