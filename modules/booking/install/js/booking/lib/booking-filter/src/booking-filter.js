import { Type } from 'main.core';
import { Model } from 'booking.const';
import { Core } from 'booking.core';
import type { BookingUIFilter, BookingListFilter } from './types';

class BookingFilter
{
	prepareFilter(fields: BookingUIFilter, withinMonth: boolean = false): BookingListFilter
	{
		const dateTs = Core.getStore().getters[`${Model.Interface}/${withinMonth ? 'viewDateTs' : 'selectedDateTs'}`];
		const date = new Date(dateTs);

		const filter = {
			WITHIN: {
				DATE_FROM: date.getTime() / 1000,
				DATE_TO: withinMonth
					? date.setMonth(date.getMonth() + 1) / 1000
					: date.setDate(date.getDate() + 1) / 1000,
			},
		};

		if (Type.isArrayFilled(fields.CREATED_BY))
		{
			filter.CREATED_BY = fields.CREATED_BY.map((id: string) => Number(id));
		}

		if (Type.isArrayFilled(fields.CONTACT))
		{
			filter.CRM_CONTACT_ID = fields.CONTACT.map((id: string) => Number(id));
		}

		if (Type.isArrayFilled(fields.COMPANY))
		{
			filter.CRM_COMPANY_ID = fields.COMPANY.map((id: string) => Number(id));
		}

		if (Type.isArrayFilled(fields.RESOURCE))
		{
			filter.RESOURCE_ID = fields.RESOURCE.map((id: string) => Number(id));
		}

		if (fields.CONFIRMED)
		{
			filter.IS_CONFIRMED = { 'Y': 1, 'N': 0 }[fields.CONFIRMED];
		}

		if (fields.DELAYED)
		{
			filter.IS_DELAYED = { 'Y': 1, 'N': 0 }[fields.DELAYED];
		}

		return filter;
	}
}

export const bookingFilter = new BookingFilter();
