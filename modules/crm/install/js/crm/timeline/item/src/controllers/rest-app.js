import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import { ActionType } from "../action";

export class RestApp extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;
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
		const appId = params.restAppId;
		delete params.restAppId;
		if (BX.rest && BX.rest.AppLayout)
		{
			BX.rest.AppLayout.openApplication(appId, params);
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:ConfigurableRestApp');
	}
}
