import { Type } from 'main.core';
import { Base } from './base';
import ConfigurableItem from '../configurable-item';
import { ActionType } from '../action';

export class RestApp extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;
		if (!ActionType.isJsEvent(actionType))
		{
			return;
		}

		if (action === 'Activity:ConfigurableRestApp:OpenApp')
		{
			this.#openRestAppSlider(actionData);
		}
	}

	#openRestAppSlider(params: Object): void
	{
		const openAppParams = { ...params };
		const appId = openAppParams.restAppId;
		delete openAppParams.restAppId;

		if (BX.rest && BX.rest.AppLayout)
		{
			if (Type.isStringFilled(openAppParams.bx24_label))
			{
				openAppParams.bx24_label = JSON.parse(openAppParams.bx24_label);
			}
			BX.rest.AppLayout.openApplication(appId, openAppParams);
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:ConfigurableRestApp');
	}
}
