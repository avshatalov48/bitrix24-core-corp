import type { BookingListFilter } from 'booking.lib.booking-filter';
import type { CalendarGetBookingsDatesResponse } from '../types';

export async function* requestNextBookingDatesGenerator(
	startDateTs: number,
	limitDateTs: number,
	filter: BookingListFilter,
	request: (filter: BookingListFilter) => Promise<CalendarGetBookingsDatesResponse>,
): AsyncGenerator<CalendarGetBookingsDatesResponse, void>
{
	const direction = startDateTs < limitDateTs ? 1 : -1;
	const getWithin = getWithinBuilder(3, direction, limitDateTs);
	let { fromDate, toDate } = getWithin(new Date(startDateTs));

	const isLimit = (toDateTs: number): boolean => {
		return direction === 1 ? toDateTs > limitDateTs : toDateTs < limitDateTs;
	};

	while (!isLimit(fromDate.getTime()))
	{
		const data = await request({
			...filter,
			WITHIN: {
				DATE_FROM: fromDate.getTime() / 1000,
				DATE_TO: toDate.getTime() / 1000,
			},
		});

		const nextDates = getWithin(direction === 1 ? toDate : fromDate);
		fromDate = nextDates.fromDate;
		toDate = nextDates.toDate;

		if (data.foundDates.length > 0)
		{
			yield data;
		}
	}
}

function getWithinBuilder(duration: number, direction: 1 | -1, limitDateTs: number): (Date) => {
	fromDate: Date,
	toDate: Date
}
{
	let intervalDuration = duration;

	return function(firstDate: Date): { fromDate: Date, toDate: Date } {
		intervalDuration++;

		const secondDate = new Date(firstDate);
		secondDate.setMonth(secondDate.getMonth() + (intervalDuration + 1) * direction);
		secondDate.setDate(0);

		if (direction === 1)
		{
			return {
				fromDate: firstDate,
				toDate: secondDate.getDate() > limitDateTs ? new Date(limitDateTs) : secondDate,
			};
		}

		const fromDate = secondDate.getTime() < limitDateTs ? new Date(limitDateTs) : secondDate;
		const toDate = firstDate;

		if (toDate.getTime() === fromDate.getTime())
		{
			toDate.setDate(toDate.getDate() + 1);
		}

		return {
			fromDate,
			toDate,
		};
	};
}
