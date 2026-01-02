import type { ClientData } from 'booking.model.bookings';
import type { DealData } from 'booking.model.clients';

export type { DealData };

export type WaitListState = {
	collection: { [bookingId: string]: WaitListItemModel },
};

export type WaitListItemModel = {
	id: number,
	createdBy: number,
	createdAt: number,
	updatedAt: number,
	clients: ClientData[],
	note: string | null,
	externalData: DealData[],
};

export type UpdatePayload = {
	id: number | string,
	waitListItem: WaitListItemModel,
};
