import type { BookingModel } from 'booking.model.bookings';
import type { Occupancy } from 'booking.model.interface';

type GetOverbookingFreeSpaceProps = {
	booking: BookingModel,
	colliding: Occupancy[],
	selectedDateTs: number,
	draggedBookingResourcesIds: number[],
};

export function getOverbookingFreeSpace({
	booking,
	colliding,
	selectedDateTs,
	draggedBookingResourcesIds,
}: GetOverbookingFreeSpaceProps): { fromTs: number, toTs: number }
{
	const minTs = new Date(selectedDateTs).setHours(0, 0, 0, 0);
	const maxTs = new Date(selectedDateTs).setHours(24, 0, 0, 0);
	const freeSpace = {
		fromTs: minTs,
		toTs: maxTs,
	};

	if (draggedBookingResourcesIds.length > 1)
	{
		const bookingColliding = colliding.find(({ fromTs }) => fromTs === booking.dateFromTs);
		if (bookingColliding && draggedBookingResourcesIds.every((id) => bookingColliding.resourcesIds.includes(id)))
		{
			return null;
		}
	}

	for (const { fromTs, toTs } of colliding)
	{
		if (toTs <= booking.dateFromTs)
		{
			freeSpace.fromTs = Math.max(freeSpace.fromTs, toTs);
		}

		if (booking.dateToTs <= fromTs)
		{
			freeSpace.toTs = Math.min(freeSpace.toTs, fromTs);
		}
	}

	if (freeSpace.fromTs === minTs && freeSpace.toTs === maxTs)
	{
		return null;
	}

	return freeSpace;
}

export function findTimeForDroppedBooking(freeSpace: Occupancy, booking: BookingModel, droppedBooking: BookingModel): {
	dateFromTs: number,
	dateToTs: number
}
{
	const duration = droppedBooking.dateToTs - droppedBooking.dateFromTs;
	if (booking.dateFromTs >= freeSpace.fromTs && booking.dateFromTs + duration <= freeSpace.toTs)
	{
		return {
			dateFromTs: booking.dateFromTs,
			dateToTs: booking.dateFromTs + duration,
		};
	}

	if (booking.dateToTs - duration >= freeSpace.fromTs && booking.dateToTs <= freeSpace.toTs)
	{
		return {
			dateFromTs: booking.dateToTs - duration,
			dateToTs: booking.dateToTs,
		};
	}

	return {
		dateFromTs: freeSpace.fromTs,
		dateToTs: freeSpace.fromTs + duration,
	};
}
