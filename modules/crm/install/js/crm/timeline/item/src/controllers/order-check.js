import ConfigurableItem from '../configurable-item';
import {type ActionParams, Base} from './base';
import {Router} from 'crm.router';

export class OrderCheck extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;
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
					cacheable : false,
				}
			);
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'OrderCheckPrinted'
			|| item.getType() === 'OrderCheckNotPrinted'
			|| item.getType() === 'OrderCheckSent'
		);
	}
}
