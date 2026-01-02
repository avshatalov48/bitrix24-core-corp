import { BookingFilter } from './booking-filter';
import type { BookingListFilter, BookingUIFilter } from './types';

class BookingDateCountFilter extends BookingFilter
{
	prepareUndatedFilter(fields: BookingUIFilter, withinMonth: boolean = false): BookingListFilter
	{
		const filter = super.prepareFilter(fields, withinMonth);

		delete filter.WITHIN;

		return filter;
	}

	prepareFutureOnlyFilter(fields: BookingUIFilter, withinMonth: boolean = false): BookingListFilter
	{
		const filter = super.prepareFilter(fields, withinMonth);

		filter.WITHIN = {
			DATE_FROM: Math.trunc(Date.now() / 1000),
		};

		return filter;
	}

	prepareFutureFilter(fields: BookingUIFilter, withinMonth = false): BookingListFilter
	{
		const filter = super.prepareFilter(fields, withinMonth);
		const dateFrom = filter.WITHIN.DATE_FROM;
		const today = Math.trunc(Date.now() / 1000);

		if (dateFrom < today)
		{
			filter.WITHIN.DATE_FROM = today;
		}

		return filter;
	}
}

export const bookingDateCountFilter = new BookingDateCountFilter();
