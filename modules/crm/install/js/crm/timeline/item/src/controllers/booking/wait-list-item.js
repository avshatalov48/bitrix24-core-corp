import { ActionParams, Base } from '../base';
import ConfigurableItem from 'crm.timeline.item';
import { showCyclePopup } from './show-cycle-popup';

export class WaitListItem extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === `${item.getType()}:ShowWaitListItem`)
		{
			const url = `/booking/?editingWaitListItemId=${actionData.id}`;
			BX.SidePanel.Instance.open(url, {
				customLeftBoundary: 0,
			});
		}

		if (action === `${item.getType()}:ShowCyclePopup`)
		{
			showCyclePopup(actionData.status);
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return item.getType() === 'Activity:WaitListItem';
	}
}
