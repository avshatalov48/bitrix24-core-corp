import type { WaitListItemModel } from 'booking.model.wait-list';

export type { WaitListItemModel };

export type WaitListGroup = {
	title: string,
	items: WaitListItemModel[],
}
