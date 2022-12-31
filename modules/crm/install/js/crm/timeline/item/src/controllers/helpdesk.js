import {Base} from './base';
import ConfigurableItem from '../configurable-item';

export class Helpdesk extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: Object): void
	{
		const { action, actionData } = actionParams;

		if (action === 'Helpdesk:Open' && actionData && actionData.articleCode)
		{
			this.#openHelpdesk(actionData.articleCode);
		}
	}

	#openHelpdesk(articleCode): void
	{
		if (top.BX && top.BX.Helper)
		{
			top.BX.Helper.show(`redirect=detail&code=${articleCode}`);
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return true;
	}
}