import { inInterval, IncludeBoundaries } from 'booking.lib.in-interval';
import type { IncludeBoundariesValue } from 'booking.lib.in-interval';
import type { BookingModel } from 'booking.model.bookings';

type BookingTimespan = Pick<BookingModel, 'dateFromTs' | 'dateToTs'>;

function inBookingInterval(interval: [number, number]): boolean
{
	return (value: number, include: IncludeBoundariesValue) => {
		return inInterval(value, interval, { include });
	};
}

export function checkBookingIntersection(booking: BookingTimespan, other: BookingTimespan): boolean
{
	const checkInBookingInterval = inBookingInterval([
		booking.dateFromTs,
		booking.dateToTs,
	]);

	return (
		checkInBookingInterval(other.dateFromTs, IncludeBoundaries.left)
		|| checkInBookingInterval(other.dateToTs, IncludeBoundaries.right)
	);
}
