import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { resourcesDateCache } from 'booking.lib.resources-date-cache';
import { ApiClient } from 'booking.lib.api-client';
import type { BookingModel } from 'booking.model.bookings';

import { MainPageDataExtractor } from './main-page-data-extractor';
import { CountersExtractor } from './counters-extractor';

class MainPageService
{
	#dateCache: number[] = [];

	clearCache(ids: number[]): void
	{
		this.#dateCache = this.#dateCache.filter((date: number) => resourcesDateCache.isDateLoaded(date, ids));
	}

	async fetchData(dateTs: number): Promise<void>
	{
		if (this.#dateCache.includes(dateTs))
		{
			return;
		}

		this.#dateCache.push(dateTs);

		await this.loadData(dateTs);
	}

	async loadData(dateTs: number): Promise<void>
	{
		try
		{
			if (Core.getStore().getters[`${Model.Interface}/isEditingBookingMode`])
			{
				await this.#requestDataForBooking(dateTs);
			}
			else
			{
				await this.#requestData(dateTs);
			}
		}
		catch (error)
		{
			console.error('BookingMainPageGetRequest: error', error);
		}
	}

	async #requestData(dateTs: number): Promise<void>
	{
		const data = await new ApiClient().get('MainPage.get', { dateTs });
		const extractor = new MainPageDataExtractor(data);

		resourcesDateCache.upsertIds(dateTs, extractor.getFavoriteIds());

		await Promise.all([
			Core.getStore().dispatch(`${Model.Favorites}/set`, extractor.getFavoriteIds()),
			Core.getStore().dispatch(`${Model.Interface}/setResourcesIds`, extractor.getFavoriteIds()),
			Core.getStore().dispatch(`${Model.Interface}/setIntersectionMode`, extractor.getIntersectionMode()),
			Core.getStore().dispatch(`${Model.Resources}/upsertMany`, extractor.getResources()),
			Core.getStore().dispatch(`${Model.ResourceTypes}/upsertMany`, extractor.getResourceTypes()),
			Core.getStore().dispatch(`${Model.Counters}/set`, extractor.getCounters()),
			Core.getStore().dispatch(`${Model.Bookings}/upsertMany`, extractor.getBookings()),
			Core.getStore().dispatch(`${Model.Clients}/upsertMany`, extractor.getClients()),
			Core.getStore().dispatch(`${Model.Clients}/setProviderModuleId`, extractor.getClientsProviderModuleId()),
			Core.getStore().dispatch(`${Model.Interface}/setIsCurrentSenderAvailable`, extractor.getIsCurrentSenderAvailable()),
		]);
	}

	async #requestDataForBooking(dateTs: number): Promise<void>
	{
		const bookingId = Core.getParams().editingBookingId;
		const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
		const resourcesIds = Core.getStore().getters[`${Model.Favorites}/get`];

		const data = await new ApiClient().get('MainPage.getForBooking', { dateTs, bookingId, timezone, resourcesIds });

		const extractor = new MainPageDataExtractor(data);

		const promises = [
			Core.getStore().dispatch(`${Model.Interface}/setIntersectionMode`, extractor.getIntersectionMode()),
			Core.getStore().dispatch(`${Model.Resources}/upsertMany`, extractor.getResources()),
			Core.getStore().dispatch(`${Model.ResourceTypes}/upsertMany`, extractor.getResourceTypes()),
			Core.getStore().dispatch(`${Model.Counters}/set`, extractor.getCounters()),
			Core.getStore().dispatch(`${Model.Bookings}/upsertMany`, extractor.getBookings()),
			Core.getStore().dispatch(`${Model.Clients}/upsertMany`, extractor.getClients()),
			Core.getStore().dispatch(`${Model.Clients}/setProviderModuleId`, extractor.getClientsProviderModuleId()),
			Core.getStore().dispatch(`${Model.Interface}/setIsCurrentSenderAvailable`, extractor.getIsCurrentSenderAvailable()),
		];

		const editingBooking = extractor.getBookings()
			.find((booking: BookingModel) => booking.id === bookingId)
		;

		if (!editingBooking && dateTs === 0)
		{
			promises.push(
				Core.getStore().dispatch(`${Model.Interface}/setEditingBookingId`, 0),
			);
		}

		let selectedDate = new Date(dateTs * 1000);
		if (editingBooking && dateTs === 0)
		{
			const dateFrom = new Date(editingBooking.dateFromTs);
			selectedDate = new Date(dateFrom.getFullYear(), dateFrom.getMonth(), dateFrom.getDate());

			promises.push(
				Core.getStore().dispatch(`${Model.Interface}/setSelectedDateTs`, selectedDate.getTime()),
			);

			this.#dateCache.push(selectedDate.getTime() / 1000);
		}

		let selectedResourcesIds = resourcesIds;
		if (editingBooking && resourcesIds.length === 0)
		{
			selectedResourcesIds = [editingBooking.resourcesIds[0]];

			promises.push(
				Core.getStore().dispatch(`${Model.Favorites}/set`, [editingBooking.resourcesIds[0]]),
				Core.getStore().dispatch(`${Model.Interface}/setResourcesIds`, [editingBooking.resourcesIds[0]]),
			);
		}

		resourcesDateCache.upsertIds(selectedDate.getTime() / 1000, selectedResourcesIds);

		await Promise.all(promises);
	}

	async fetchCounters(): Promise<void>
	{
		try
		{
			const data = await new ApiClient().get('MainPage.getCounters');

			const extractor = new CountersExtractor(data);

			await Promise.all([
				Core.getStore().dispatch(`${Model.Interface}/setTotalClients`, extractor.getTotalClients()),
				Core.getStore().dispatch(`${Model.Interface}/setTotalNewClientsToday`, extractor.getTotalNewClientsToday()),
				Core.getStore().dispatch(`${Model.Interface}/setMoneyStatistics`, extractor.getMoneyStatistics()),
				Core.getStore().dispatch(`${Model.Counters}/set`, extractor.getCounters()),
			]);
		}
		catch (error)
		{
			console.error('BookingMainPageGetCountersRequest: error', error);
		}
	}

	async activateDemo(): Promise<boolean>
	{
		try
		{
			return await new ApiClient().get('MainPage.activateDemo');
		}
		catch (error)
		{
			console.error('BookingMainPageActivateDemoRequest: error', error);
		}

		return Promise.resolve(false);
	}
}

export const mainPageService = new MainPageService();
