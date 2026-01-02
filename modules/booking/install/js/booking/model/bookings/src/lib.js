import type { BookingModel, OverbookingMapItem } from './types';

export function dateToTsRange(dateTs: number): [number, number]
{
	const dateFrom = dateTs;
	const dateTo = new Date(dateTs).setDate(new Date(dateTs).getDate() + 1);

	return [dateFrom, dateTo];
}

function getIntersectionBookings(booking: BookingModel, resourceId: number, bookings: BookingModel[]): BookingModel[]
{
	return bookings.filter((b) => {
		return (
			b.id !== booking.id
			&& b.resourcesIds.includes(resourceId)
			&& booking.dateFromTs < b.dateToTs
			&& booking.dateToTs > b.dateFromTs
		);
	});
}

type CreateOverbookingMapItemParams = {
	booking: BookingModel,
	resourceId: number,
	intersections: BookingModel[],
	shifted?: boolean,
	overbookingMapItem?: OverbookingMapItem,
}

function createOverbookingMapItem({
	booking,
	resourceId,
	intersections,
	shifted = false,
	overbookingMapItem = null,
}: CreateOverbookingMapItemParams): OverbookingMapItem
{
	const item = {
		resourceId,
		shifted,
		intersections: intersections.map((intersection) => ({
			id: intersection.id,
			dateFromTs: intersection.dateFromTs,
			dateToTs: intersection.dateToTs,
		})),
	};

	if (overbookingMapItem === null)
	{
		return {
			id: booking.id,
			booking,
			items: [item],
		};
	}

	overbookingMapItem.items.push(item);

	return overbookingMapItem;
}

export function createOverbookingMap(resourceBookings: { [number]: BookingModel[] }): Map<number, OverbookingMapItem>
{
	let maxLeftBookingTs = 0;
	let maxRightBookingTs = 0;
	const overbookingMap: Map<number, OverbookingMapItem> = new Map();
	const resourcesIds = Object.keys(resourceBookings);

	for (const key of resourcesIds)
	{
		const resourceId = Number(key);
		const bookings = resourceBookings[resourceId]
			.sort((a, b) => a.dateFromTs - b.dateFromTs);

		maxLeftBookingTs = 0;
		maxRightBookingTs = 0;

		for (const booking of bookings)
		{
			const bookingId = booking.id;
			const intersectionBookings = getIntersectionBookings(
				booking,
				resourceId,
				bookings,
			);

			if (intersectionBookings.length === 0)
			{
				continue;
			}

			const canBeRight = booking.dateFromTs >= maxRightBookingTs;
			const canBeLeft = booking.dateFromTs >= maxLeftBookingTs;
			if (canBeLeft)
			{
				maxLeftBookingTs = booking.dateToTs;
				overbookingMap.set(
					bookingId,
					createOverbookingMapItem({
						booking,
						resourceId,
						intersections: intersectionBookings,
						shifted: false,
						overbookingMapItem: overbookingMap.get(bookingId),
					}),
				);
			}
			else if (canBeRight)
			{
				maxRightBookingTs = booking.dateToTs;
				overbookingMap.set(
					bookingId,
					createOverbookingMapItem({
						booking,
						resourceId,
						intersections: intersectionBookings,
						shifted: true,
						overbookingMapItem: overbookingMap.get(bookingId),
					}),
				);
			}
		}
	}

	return overbookingMap;
}
