import { BusySlot } from 'booking.const';
import type { BookingModel, OverbookingMap } from 'booking.model.bookings';

import type { BusySlotDto, Range } from '../types';

type GetIntersectionBusySlotsProps = {
	busyRanges: Range[],
	resourceId: number,
	selectedDateTs: number,
	intersectingBookings: BookingModel[],
	intersectingResourcesIds: number[],
	overbookingMap: OverbookingMap,
}

export function getIntersectionBusySlots({
	busyRanges,
	resourceId,
	selectedDateTs,
	intersectingBookings,
	intersectingResourcesIds,
	overbookingMap,
}: GetIntersectionBusySlotsProps): BusySlotDto[]
{
	const intersectionBusySlots = [];

	for (const busyRange of busyRanges)
	{
		let fromTs = new Date(selectedDateTs).setMinutes(busyRange.from);
		const toTs = new Date(selectedDateTs).setMinutes(busyRange.to);

		const booking = intersectingBookings.find((intersectingBooking) => intersectingBooking.id === busyRange.id);

		const intersectingResourceId = booking
			? booking.resourcesIds.find((it) => intersectingResourcesIds.includes(it))
			: 0;

		const overbookingIntersections = getOverbookingIntersections(
			resourceId,
			busyRange.id,
			overbookingMap,
		);

		const overbookingIntersectionBusySlots = overbookingIntersections
			.map((overbookingIntersection) => {
				const overbookingFromTs = fromTs >= overbookingIntersection.dateFromTs
					? fromTs
					: overbookingIntersection.dateFromTs;
				const overbookingToTs = toTs <= overbookingIntersection.dateToTs
					? toTs
					: overbookingIntersection.dateToTs;

				if (overbookingFromTs === overbookingToTs)
				{
					return null;
				}

				return {
					id: `${resourceId}-${overbookingFromTs}-${overbookingToTs}`,
					fromTs: overbookingFromTs,
					toTs: overbookingToTs,
					resourceId,
					intersectingResourceId,
					type: BusySlot.IntersectionOverbooking,
				};
			})
			.filter((item) => item !== null);

		overbookingIntersectionBusySlots.forEach((overbookingIntersectionBusySlot) => {
			if (fromTs <= overbookingIntersectionBusySlot.fromTs)
			{
				intersectionBusySlots.push({
					id: `${resourceId}-${fromTs}-${overbookingIntersectionBusySlot.fromTs}`,
					fromTs,
					toTs: overbookingIntersectionBusySlot.fromTs,
					resourceId,
					intersectingResourceId,
					type: BusySlot.Intersection,
				});
				fromTs = overbookingIntersectionBusySlot.toTs;
			}
		});

		intersectionBusySlots.push(...overbookingIntersectionBusySlots);

		if (fromTs !== toTs)
		{
			intersectionBusySlots.push({
				id: `${resourceId}-${fromTs}-${toTs}`,
				fromTs,
				toTs,
				resourceId,
				intersectingResourceId,
				type: BusySlot.Intersection,
			});
		}
	}

	return intersectionBusySlots;
}

function getOverbookingIntersections(resourceId, bookingId, overbookingMap): Array<{
	dateFromTs: number,
	dateToTs: number,
	id: number
}>
{
	const overbooking = overbookingMap.get(bookingId);
	if (!overbooking)
	{
		return [];
	}

	return overbooking.items
		.filter((item) => item.resourceId !== resourceId)
		.flatMap((item) => item.intersections);
}
