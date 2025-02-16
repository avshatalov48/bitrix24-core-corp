import { Type } from 'main.core';

import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { ApiClient } from 'booking.lib.api-client';
import { bookingFilter } from 'booking.lib.booking-filter';
import type { BookingListFilter, BookingUIFilter } from 'booking.lib.booking-filter';

import { CalendarGetBookingsDatesResponse } from './types';

export class CalendarService
{
	#filterMarksRequests: { [key: string]: Promise<CalendarGetBookingsDatesResponse> } = {};
	#lastFilterMarksRequest: Promise<CalendarGetBookingsDatesResponse>;
	#freeMarksRequests: { [key: string]: Promise } = {};
	#lastFreeMarksRequest: Promise;
	#counterMarksRequests: { [key: string]: Promise } = {};

	clearCache(timestamp: number, resourceId: number): void
	{
		Object.keys(this.#filterMarksRequests).forEach((key) => {
			const { dateTs, sortedResources } = JSON.parse(key);
			if (timestamp === dateTs)
			{
				delete this.#filterMarksRequests[key];
			}

			for (const ids of sortedResources)
			{
				if (ids.includes(resourceId))
				{
					delete this.#filterMarksRequests[key];

					break;
				}
			}
		});
	}

	clearFilterCache(): void
	{
		this.#filterMarksRequests = {};
	}

	async loadMarks(dateTs: number, resources: number[][]): Promise<void>
	{
		try
		{
			if (!Type.isArrayFilled(resources))
			{
				return;
			}

			const sortedResources = resources
				.map((ids: number[]) => {
					return ids.sort((a, b) => a - b);
				})
				.sort((a, b) => a[0] - b[0])
			;

			const key = JSON.stringify({ dateTs, sortedResources });
			this.#freeMarksRequests[key] ??= this.#requestLoadMarks(dateTs, resources);
			this.#lastFreeMarksRequest = this.#freeMarksRequests[key];

			const freeMarks = await this.#freeMarksRequests[key];

			if (this.#freeMarksRequests[key] !== this.#lastFreeMarksRequest)
			{
				return;
			}

			await Core.getStore().dispatch(`${Model.Interface}/setFreeMarks`, freeMarks);
		}
		catch (error)
		{
			console.error('BookingService: loadMarks error', error);
		}
	}

	async loadFilterMarks(fields: BookingUIFilter): Promise<void>
	{
		try
		{
			const filter = bookingFilter.prepareFilter(fields, true);

			const key = JSON.stringify(filter);
			this.#filterMarksRequests[key] ??= this.#requestFilterMarks(filter);
			this.#lastFilterMarksRequest = this.#filterMarksRequests[key];

			const { foundDates, foundDatesWithCounters } = await this.#filterMarksRequests[key];

			if (this.#filterMarksRequests[key] !== this.#lastFilterMarksRequest)
			{
				return;
			}

			await Promise.all([
				Core.getStore().dispatch(`${Model.Interface}/setFilteredMarks`, foundDates),
				Core.getStore().dispatch(`${Model.Interface}/setCounterMarks`, foundDatesWithCounters),
			]);
		}
		catch (error)
		{
			console.error('BookingService: loadFilterMarks error', error);
		}
	}

	async loadCounterMarks(dateTs: number, force = false): Promise<void>
	{
		try
		{
			const key = dateTs.toString();

			if (force)
			{
				this.#counterMarksRequests[key] = this.#requestCounterMarks(dateTs);
			}
			else
			{
				this.#counterMarksRequests[key] ??= this.#requestCounterMarks(dateTs);
			}

			const counterMarks = await this.#counterMarksRequests[key];

			await Core.getStore().dispatch(`${Model.Interface}/setCounterMarks`, counterMarks);
		}
		catch (error)
		{
			console.error('CalendarService: loadCounterMarks error', error);
		}
	}

	async #requestLoadMarks(dateTs: number, resources: number[][]): Promise<string[]>
	{
		const now = new Date();
		const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
		const minTs = today.getTime() + this.#offset;

		const dateFromTs = Math.max(minTs, dateTs) / 1000;
		const dateToTs = new Date(dateTs).setMonth(new Date(dateTs).getMonth() + 1) / 1000;

		if (dateToTs < minTs / 1000)
		{
			return [];
		}

		const { freeDates } = await new ApiClient().post('Calendar.getResourceOccupation', {
			timezone: this.#timezone,
			dateFromTs,
			dateToTs,
			resources,
		});

		return freeDates;
	}

	#requestFilterMarks(filter: BookingListFilter): Promise<CalendarGetBookingsDatesResponse>
	{
		return new ApiClient().post('Calendar.getBookingsDates', {
			timezone: this.#timezone,
			dateFromTs: filter.WITHIN.DATE_FROM,
			dateToTs: filter.WITHIN.DATE_TO,
			filter,
		});
	}

	async #requestCounterMarks(dateTs: number): Promise<string[]>
	{
		const dateFromTs = dateTs / 1000;
		const dateToTs = new Date(dateTs).setMonth(new Date(dateTs).getMonth() + 1) / 1000;

		const { foundDatesWithCounters } = await new ApiClient().post('Calendar.getBookingsDates', {
			timezone: this.#timezone,
			dateFromTs,
			dateToTs,
			filter: {
				HAS_COUNTERS_USER_ID: 1,
			},
		});

		return foundDatesWithCounters;
	}

	get #offset(): string
	{
		return Core.getStore().getters[`${Model.Interface}/offset`];
	}

	get #timezone(): string
	{
		return Core.getStore().getters[`${Model.Interface}/timezone`];
	}
}

export const calendarService = new CalendarService();
