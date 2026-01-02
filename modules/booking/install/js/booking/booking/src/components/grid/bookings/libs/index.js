import { inInterval } from 'booking.lib.in-interval';
import type {
	BookingModel,
	BookingModelUi,
	BookingUiGroup,
	OverbookingMapItem,
} from 'booking.model.bookings';
import { MinUiBookingDurationMs } from '../model';

function getBookingUiSlot({ dateFromTs, dateToTs }: BookingModel): [number, number]
{
	const duration = dateToTs - dateFromTs;

	return [
		dateFromTs,
		duration < MinUiBookingDurationMs ? dateFromTs + MinUiBookingDurationMs : dateToTs,
	];
}

export function createBookingModelUi(
	resourceId: number,
	booking: BookingModel,
	overbookingMapItem: ?OverbookingMapItem,
): BookingModelUi
{
	return {
		...booking,
		resourcesIds: [resourceId],
		uiSlot: getBookingUiSlot(booking),
		overbooking: overbookingMapItem?.items?.some((item) => {
			return item.resourceId === resourceId && item.shifted;
		}),
	};
}

type Bookings = Array<BookingModel | BookingModelUi>;

export function splitBookingsByResourceId(bookings: Bookings): Map<number, Bookings>
{
	const resourceBookingsMap: Map<number, Bookings> = new Map();
	for (const booking of bookings)
	{
		const resourceId = booking.resourcesIds?.[0] || 0;
		let resourceBookings: Bookings = [];
		if (resourceBookingsMap.has(resourceId))
		{
			resourceBookings = resourceBookingsMap.get(resourceId) || [];
		}

		resourceBookings.push(booking);
		resourceBookingsMap.set(resourceId, resourceBookings);
	}

	return resourceBookingsMap;
}

export function groupBookingUis(bookings: BookingModelUi[]): BookingUiGroup[]
{
	const groups: BookingUiGroup[] = [];
	if (bookings.length === 0)
	{
		return groups;
	}

	let group: BookingUiGroup = {
		slot: [0, 0],
		bookingIds: [],
	};

	bookings
		.filter((booking) => !booking.overbooking)
		.map((booking) => {
			return {
				id: booking.id,
				uiSlot: booking.uiSlot,
			};
		})
		.sort((a, b) => a.uiSlot[0] - b.uiSlot[0])
		.forEach((booking) => {
			if (group.bookingIds.length === 0)
			{
				group.slot = [booking.uiSlot[0], booking.uiSlot[1]];
			}
			else if (inInterval(booking.uiSlot[0], group.slot))
			{
				group.slot[1] = booking.uiSlot[1];
			}
			else
			{
				groups.push(group);
				group = {
					slot: [booking.uiSlot[0], booking.uiSlot[1]],
					bookingIds: [],
				};
			}

			group.bookingIds.push(booking.id);
		});
	groups.push(group);

	return groups;
}

export function getResourceBookingUiGroups(
	resourceBookingsMap: Map<number, BookingModelUi[]>,
): Map<number, BookingUiGroup[]>
{
	const resourceBookingUiGroups: Map<number, BookingUiGroup[]> = new Map();

	if (resourceBookingsMap instanceof Map)
	{
		[...resourceBookingsMap.keys()]
			.forEach((resourceId) => {
				const bookingGroups = groupBookingUis(
					resourceBookingsMap.get(resourceId) || [],
				);
				resourceBookingUiGroups.set(resourceId, bookingGroups);
			});
	}

	return resourceBookingUiGroups;
}
