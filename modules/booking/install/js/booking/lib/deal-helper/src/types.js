import type { DealData } from 'booking.model.bookings';
import type { ClientData } from 'booking.model.clients';

export type CreateCrmDealParams = {
	itemIdQueryParamName: string,
	itemId: number,
	queryParams?: { [key: string]: string },
	clients: ClientData[],
	onLoad: (data: CreateCrmDealLoadParams) => void,
	onClose: () => void,
}

export type CreateCrmDealLoadParams = {
	isDeal: boolean,
	isCanceled: boolean,
	itemIdFromQuery: number,
	dealData: DealData,
}
