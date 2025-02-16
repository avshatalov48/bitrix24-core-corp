import { ClientData } from 'booking.model.clients';

export type BookingsState = {
	collection: { [bookingId: string]: BookingModel },
};

export type BookingModel = {
	id: number,
	updatedAt: number,
	resourcesIds: number[],
	primaryClient: ClientData,
	clients: ClientData[],
	counter: number,
	counters: [
		{
			type: 'booking_unconfirmed',
			value: 0 | 1,
		},
		{
			type: 'booking_delayed',
			value: 0 | 1,
		},
	],
	name: string,
	dateFromTs: number,
	dateToTs: number,
	timezoneFrom: string,
	timezoneTo: string,
	rrule: string,
	isConfirmed: boolean,
	visitStatus: string,
	note: string | null,
	externalData: DealData[],
};

export type DealData = {
	moduleId: string,
	entityTypeId: string,
	value: string,
	data: {
		createdTimestamp: number,
		currencyId: string,
		opportunity: number,
		formattedOpportunity: string,
	},
};
