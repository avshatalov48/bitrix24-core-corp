import type { DealDataDto } from 'booking.provider.service.booking-service';
import type { ClientDto } from 'booking.provider.service.client-service';

export type WaitListItemDto = {
	id: number | null,
	createdBy: number,
	createdAt: number,
	updatedAt: number,
	clients: ClientDto[],
	note: string | null,
	externalData: DealDataDto,
};
