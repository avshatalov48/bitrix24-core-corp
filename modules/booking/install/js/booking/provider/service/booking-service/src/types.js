import type { ClientDto } from 'booking.provider.service.client-service';
import type { ResourceDto } from 'booking.provider.service.resources-service';

export type BookingDto = {
	id: number | null,
	updatedAt: number,
	resources: ResourceDto[],
	primaryClient: ClientDto,
	clients: ClientDto[],
	counter: number,
	counters: Array,
	createdAt: number,
	name: string,
	datePeriod: DatePeriodDto,
	isConfirmed: boolean | null,
	visitStatus: string,
	rrule: string | null,
	note: string | null,
	externalData: DealDataDto,
	messages: MessageDto[],
};

export type DealDataDto = {
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

export type BookingFromWaitListItemDto = {
	waitListItemId: number,
	datePeriod: DatePeriodDto,
	resources: ResourceDto[],
};

type DatePeriodDto = {
	from: DatePeriodItem,
	to: DatePeriodItem,
};

type DatePeriodItem = {
	timestamp: number,
	timezone: string,
};

type MessageDto = {
	id: number,
	bookingId: number,
	notificationType: string,
};
