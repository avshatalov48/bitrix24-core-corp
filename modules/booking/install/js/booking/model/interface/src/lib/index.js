import type { OverbookingMapItem } from 'booking.model.bookings';
import type { Occupancy } from '../types';

type OverbookingMap = Map<number, OverbookingMapItem>;

export function getOverbookingOccupancy(overbookingMap: OverbookingMap, resources: Set<number>): Occupancy[]
{
	const occupancyList: Occupancy[] = [];
	const hashSet: Set<string> = new Set();

	for (const [, overbooking] of overbookingMap)
	{
		const { dateFromTs, dateToTs, resourcesIds } = overbooking.booking;
		if (resourcesIds.every((id) => !resources.has(id)))
		{
			continue;
		}

		overbooking.items
			.filter(({ resourceId }) => resources.has(resourceId))
			.forEach(({ resourceId, intersections }) => {
				intersections.forEach((intersection) => {
					const occupancy: Occupancy = {
						fromTs: dateFromTs >= intersection.dateFromTs ? dateFromTs : intersection.dateFromTs,
						toTs: dateToTs <= intersection.dateToTs ? dateToTs : intersection.dateToTs,
						resourcesIds: [resourceId],
					};
					const occupancyHash = `${occupancy.fromTs}-${occupancy.toTs}-${occupancy.resourcesIds.join('-')}`;
					if (!hashSet.has(occupancyHash))
					{
						hashSet.add(occupancyHash);
						occupancyList.push(occupancy);
					}
				});
			});
	}

	return occupancyList;
}
