import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { ApiClient } from 'booking.lib.api-client';
import { bookingFilter } from 'booking.lib.booking-filter';
import { mainPageService } from 'booking.provider.service.main-page-service';
import type { BookingModel } from 'booking.model.bookings';
import type { BookingUIFilter, BookingListFilter } from 'booking.lib.booking-filter';

import { BookingDataExtractor } from './booking-data-extractor';
import { mapModelToDto, mapDtoToModel } from './mappers';
import type { BookingDto } from './types';

class BookingService
{
	#filterRequests: { [key: string]: Promise } = {};
	#lastFilterRequest: Promise;

	async add(booking: BookingModel): Promise<void>
	{
		const id = booking.id;

		try
		{
			await Core.getStore().dispatch(`${Model.Interface}/addQuickFilterIgnoredBookingId`, id);
			await Core.getStore().dispatch(`${Model.Bookings}/add`, booking);

			const bookingDto = mapModelToDto(booking);
			const data = await (new ApiClient()).post('Booking.add', { booking: bookingDto });
			const createdBooking = mapDtoToModel(data);

			await Core.getStore().dispatch(`${Model.Interface}/addQuickFilterIgnoredBookingId`, createdBooking.id);
			void Core.getStore().dispatch(`${Model.Bookings}/update`, {
				id,
				booking: createdBooking,
			});

			void mainPageService.fetchCounters();
		}
		catch (error)
		{
			void Core.getStore().dispatch(`${Model.Bookings}/delete`, id);

			console.error('BookingService: add error', error);
		}
	}

	async addList(bookings: BookingModel[]): Promise<void>
	{
		try
		{
			const bookingList = bookings.map((booking) => mapModelToDto(booking));

			const api = new ApiClient();
			const data = await api.post('Booking.addList', { bookingList });
			const createdBookings = data.map((d) => mapDtoToModel(d));

			await Core.getStore().dispatch(`${Model.Bookings}/upsertMany`, createdBookings);

			void mainPageService.fetchCounters();

			return createdBookings;
		}
		catch (error)
		{
			console.error('BookingService: add list error', error);

			return [];
		}
	}

	async update(booking: BookingModel): Promise<void>
	{
		const id = booking.id;
		const bookingBeforeUpdate = { ...Core.getStore().getters[`${Model.Bookings}/getById`](id) };

		try
		{
			if (booking.clients)
			{
				booking.primaryClient ??= booking.clients[0];
			}

			await Core.getStore().dispatch(`${Model.Bookings}/update`, { id, booking });

			const bookingDto = mapModelToDto(booking);
			const data = await (new ApiClient()).post('Booking.update', { booking: bookingDto });
			const updatedBooking = mapDtoToModel(data);

			void Core.getStore().dispatch(`${Model.Bookings}/update`, {
				id,
				booking: updatedBooking,
			});

			const clients = new BookingDataExtractor([data]).getClients();

			void Core.getStore().dispatch('clients/upsertMany', clients);

			void mainPageService.fetchCounters();
		}
		catch (error)
		{
			void Core.getStore().dispatch(`${Model.Bookings}/update`, {
				id,
				booking: bookingBeforeUpdate,
			});

			console.error('BookingService: update error', error);
		}
	}

	async delete(id: number): Promise<void>
	{
		const bookingBeforeDelete = { ...Core.getStore().getters[`${Model.Bookings}/getById`](id) };

		try
		{
			void Core.getStore().dispatch(`${Model.Bookings}/delete`, id);

			await (new ApiClient()).post('Booking.delete', { id });

			await this.#onAfterDelete(id);
		}
		catch (error)
		{
			void Core.getStore().dispatch(`${Model.Bookings}/upsert`, bookingBeforeDelete);

			console.error('BookingService: delete error', error);
		}
	}

	async deleteList(ids: number[]): Promise<void>
	{
		try
		{
			void Core.getStore().dispatch(`${Model.Bookings}/deleteMany`, ids);

			await (new ApiClient()).post('Booking.deleteList', { ids });

			await Promise.all(ids.map((id: number) => this.#onAfterDelete(id)));
		}
		catch (error)
		{
			console.error('BookingService: delete list error', error);
		}
	}

	async #onAfterDelete(id: number): Promise<void>
	{
		const editingBookingId = Core.getStore().getters[`${Model.Interface}/editingBookingId`];
		if (id === editingBookingId)
		{
			await Core.getStore().dispatch(`${Model.Interface}/setEditingBookingId`, 0);

			const selectedDateTs = Core.getStore().getters[`${Model.Interface}/selectedDateTs`];

			await mainPageService.loadData(selectedDateTs / 1000);

			const resourcesIds = Core.getStore().getters[`${Model.Interface}/resourcesIds`];

			mainPageService.clearCache(resourcesIds);
		}

		void Core.getStore().dispatch(`${Model.Interface}/addDeletingBooking`, id);
	}

	clearFilterCache(): void
	{
		this.#filterRequests = {};
	}

	async filter(fields: BookingUIFilter): Promise<void>
	{
		try
		{
			const filter = bookingFilter.prepareFilter(fields);

			const key = JSON.stringify(filter);
			this.#filterRequests[key] ??= this.#requestFilter(filter);
			this.#lastFilterRequest = this.#filterRequests[key];

			const data: BookingDto[] = await this.#filterRequests[key];

			void this.#extractFilterData({ data, key });
		}
		catch (error)
		{
			console.error('BookingService: filter error', error);
		}
	}

	async getById(id: number): Promise<void>
	{
		try
		{
			const data: BookingDto[] = await this.#requestFilter({ ID: [id] });
			const extractor = new BookingDataExtractor(data);
			await Promise.all([
				Core.getStore().dispatch(`${Model.Resources}/upsertMany`, extractor.getResources()),
				Core.getStore().dispatch(`${Model.Bookings}/upsertMany`, extractor.getBookings()),
				Core.getStore().dispatch(`${Model.Clients}/upsertMany`, extractor.getClients()),
			]);
		}
		catch (error)
		{
			console.error('BookingService: getById error', error);
		}
	}

	async #extractFilterData({ data, key }: {data: BookingDto[], key: string, date: Date}): Promise<void>
	{
		const extractor = new BookingDataExtractor(data);

		await Promise.all([
			Core.getStore().dispatch(`${Model.Resources}/insertMany`, extractor.getResources()),
			Core.getStore().dispatch(`${Model.Bookings}/insertMany`, extractor.getBookings()),
			Core.getStore().dispatch(`${Model.Clients}/insertMany`, extractor.getClients()),
		]);

		if (this.#filterRequests[key] !== this.#lastFilterRequest)
		{
			return;
		}

		void Core.getStore().dispatch(`${Model.Interface}/setFilteredBookingsIds`, extractor.getBookingsIds());
	}

	async #requestFilter(filter: BookingListFilter): Promise<BookingDto[]>
	{
		return new ApiClient().post('Booking.list', {
			filter,
			select: [
				'RESOURCES',
				'CLIENTS',
				'EXTERNAL_DATA',
				'NOTE',
			],
			withCounters: true,
			withClientData: true,
			withExternalData: true,
		});
	}
}

export const bookingService = new BookingService();
