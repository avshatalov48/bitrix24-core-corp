import { BookingId, BookingModel } from 'booking.model.bookings';

export type BookingUiDuration = {
	id: number,
	fromTs: number,
	toTs: number,
}

export type BookingModelUi = BookingModel & {
	overbooking: boolean;
	resourcesIds: [number],
	uiSlot: [number, number],
}

export type BookingUiGroup = {
	slot: [number, number],
	bookingIds: OverlappingBookings,
}

export type OverlappingBookings = BookingId[];
