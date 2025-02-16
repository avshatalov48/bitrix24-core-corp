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
	name: string,
	datePeriod: {
		from: {
			timestamp: number,
			timezone: string,
		},
		to: {
			timestamp: number,
			timezone: string,
		}
	},
	isConfirmed: boolean | null,
	visitStatus: string,
	rrule: string | null,
	note: string | null,
	externalData: DealDataDto,
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
