import { ajax, Loc } from 'main.core';
import { UI } from 'ui.notification';
import ConfigurableItem from '../configurable-item';
import { type ActionParams, Base } from './base';
import { Router } from 'crm.router';

export class OrderCheck extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'OrderCheck:OpenCheck' && actionData && actionData.checkUrl)
		{
			Router.openSlider(
				actionData.checkUrl,
				{
					width: 500,
					cacheable: false,
				},
			);
		}
		else if (action === 'OrderCheck:ReprintCheck' && actionData && actionData.checkId)
		{
			ajax.runAction('crm.ordercheck.reprint', {
				data: {
					checkId: actionData.checkId,
				},
			}).catch((response) => {
				UI.Notification.Center.notify({
					content: response.errors[0].message,
					autoHideDelay: 5000,
				});
			});
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'OrderCheckPrinted'
			|| item.getType() === 'OrderCheckNotPrinted'
			|| item.getType() === 'OrderCheckSent'
			|| item.getType() === 'OrderCheckPrinting'
		);
	}
}
