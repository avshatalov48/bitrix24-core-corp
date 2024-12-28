import { ActionParams, Base } from './base';
import ConfigurableItem from 'crm.timeline.item';

export class Booking extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Activity:Booking:ShowBooking')
		{
			const url = `/booking/?editingBookingId=${actionData.id}`;
			BX.SidePanel.Instance.open(url, {
				customLeftBoundary: 0,
			});
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return item.getType() === 'Activity:Booking';
	}
}
