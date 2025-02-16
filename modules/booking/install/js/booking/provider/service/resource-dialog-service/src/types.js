import type { BookingDto } from 'booking.provider.service.booking-service';
import type { ResourceDto } from 'booking.provider.service.resources-service';

export type ResourceDialogResponse = {
	bookings: BookingDto[],
	resources: ResourceDto[],
};
