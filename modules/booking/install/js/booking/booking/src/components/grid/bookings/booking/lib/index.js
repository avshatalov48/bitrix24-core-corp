export { getOverbookingFreeSpace, findTimeForDroppedBooking } from './overbooking-free-space';

import type { BookingId } from 'booking.model.bookings';
import { OverlappingBookings } from '../types';
import { BookingWidth } from '../const';

export function countBookingWidth(overlappingBookings: OverlappingBookings): number
{
	const overlappingBookingsCount = overlappingBookings.length > 0 ? overlappingBookings.length : 1;

	return BookingWidth / overlappingBookingsCount;
}

type CountBookingLeftOffsetProps = {
	bookingId: BookingId,
	bookingWidth: number,
	overlappingBookings: OverlappingBookings,
}

export function countBookingLeftOffset({
	bookingId,
	bookingWidth,
	overlappingBookings,
}: CountBookingLeftOffsetProps): number
{
	let index = overlappingBookings.indexOf(bookingId);
	if (index === -1)
	{
		index = 0;
	}

	return bookingWidth * index;
}
