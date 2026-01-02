import { Event } from 'main.core';

import { Core } from 'booking.core';
import { EventName, Model } from 'booking.const';
import { ApiClient } from 'booking.lib.api-client';
import { bookingFilter } from 'booking.lib.booking-filter';
import { deepToRaw } from 'booking.lib.deep-to-raw';
import { mainPageService } from 'booking.provider.service.main-page-service';
import type { BookingModel } from 'booking.model.bookings';
import type { BookingUIFilter, BookingListFilter } from 'booking.lib.booking-filter';

import { BookingDataExtractor } from './booking-data-extractor';
import { mapModelToDto, mapDtoToModel, mapModelToCreateFromWaitListItemDto } from './mappers';
import type { BookingDto } from './types';

class BookingService
{
	#filterRequests: { [key: string]: Promise } = {};
	#lastFilterRequest: Promise;

	async add(booking: BookingModel): Promise<{ success: boolean, booking: ?BookingModel }>
	{
		const id = booking.id;
		const $store = Core.getStore();

		try
		{
			await $store.dispatch(`${Model.Interface}/addCreatedFromEmbedBooking`, id);
			await $store.dispatch(`${Model.Filter}/addQuickFilterIgnoredBookingId`, id);
			await $store.dispatch(`${Model.Bookings}/add`, booking);

			const bookingDto = mapModelToDto(booking);
			const data = await (new ApiClient()).post('Booking.add', { booking: bookingDto });
			const createdBooking = mapDtoToModel(data);

			const clients = new BookingDataExtractor([data]).getClients();
			void $store.dispatch(`${Model.Clients}/upsertMany`, clients);

			await $store.dispatch(`${Model.Interface}/setAnimationPause`, true);
			await $store.dispatch(`${Model.Interface}/addCreatedFromEmbedBooking`, createdBooking.id);
			await $store.dispatch(`${Model.Filter}/addQuickFilterIgnoredBookingId`, createdBooking.id);
			await $store.dispatch(`${Model.Bookings}/update`, {
				id,
				booking: createdBooking,
			});

			Event.EventEmitter.emit(EventName.CreateBookings, {
				bookings: [createdBooking],
			});

			void mainPageService.fetchCounters();

			return {
				success: true,
				booking: createdBooking,
			};
		}
		catch (error)
		{
			void $store.dispatch(`${Model.Bookings}/delete`, id);

			console.error('BookingService: add error', error);

			return { success: false };
		}
		finally
		{
			await $store.dispatch(`${Model.Interface}/setAnimationPause`, false);
		}
	}

	async addList(bookings: BookingModel[]): Promise<void>
	{
		const $store = Core.getStore();

		try
		{
			await $store.dispatch(`${Model.Interface}/addCreatedFromEmbedBooking`, bookings.map(({ id }) => id));
			const bookingList = bookings.map((booking) => mapModelToDto(booking));

			const api = new ApiClient();
			const data = await api.post('Booking.addList', { bookingList });
			const createdBookings = data.map((d) => mapDtoToModel(d));

			await Promise.all([
				$store.dispatch(`${Model.Interface}/addCreatedFromEmbedBooking`, createdBookings.map(({ id }) => id)),
				$store.dispatch(`${Model.Bookings}/upsertMany`, createdBookings),
			]);

			Event.EventEmitter.emit(EventName.CreateBookings, {
				bookings: createdBookings,
			});

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
				// eslint-disable-next-line no-param-reassign
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

			void Core.getStore().dispatch(`${Model.Clients}/upsertMany`, clients);

			Event.EventEmitter.emit(EventName.UpdateBooking, {
				oldBooking: deepToRaw(bookingBeforeUpdate),
				newBooking: deepToRaw(updatedBooking),
			});

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

			Event.EventEmitter.emit(EventName.DeleteBooking, {
				booking: deepToRaw(bookingBeforeDelete),
			});

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

	async createFromWaitListItem(waitListItemId: number, booking: BookingModel): Promise<{
		success: boolean,
		booking?: BookingModel
	}>
	{
		const $store = Core.getStore();
		const id = booking.id;
		const waitListItem = { ...$store.getters[`${Model.WaitList}/getById`](waitListItemId) };

		try
		{
			if ($store.getters[`${Model.Interface}/isWaitListItemCreatedFromEmbed`](waitListItemId))
			{
				await $store.dispatch(`${Model.Interface}/addCreatedFromEmbedBooking`, id);
			}

			await $store.dispatch(`${Model.WaitList}/delete`, waitListItemId);
			await $store.dispatch(`${Model.Bookings}/add`, booking);

			const createFromWaitListItemDto = mapModelToCreateFromWaitListItemDto(
				waitListItemId,
				booking,
			);
			const data = await (new ApiClient()).post('Booking.createFromWaitListItem', createFromWaitListItemDto);
			const createdBooking = mapDtoToModel(data);

			await $store.dispatch(`${Model.Interface}/setAnimationPause`, true);
			const clients = new BookingDataExtractor([data]).getClients();
			await Promise.all([
				$store.dispatch(`${Model.Clients}/upsertMany`, clients),
				$store.dispatch(`${Model.Filter}/addQuickFilterIgnoredBookingId`, createdBooking.id),
				$store.dispatch(`${Model.Bookings}/update`, {
					id,
					booking: createdBooking,
				}),
				$store.dispatch(`${Model.Interface}/addCreatedFromEmbedBooking`, createdBooking.id),
			]);

			Event.EventEmitter.emit(EventName.CreateBookings, {
				bookings: [createdBooking],
			});

			void mainPageService.fetchCounters();

			return {
				success: true,
				booking: createdBooking,
			};
		}
		catch (error)
		{
			await $store.dispatch(`${Model.Bookings}/delete`, id);
			await $store.dispatch(`${Model.WaitList}/upsert`, waitListItem);

			console.error('BookingService: add from wait list item error', error);

			return {
				success: false,
			};
		}
		finally
		{
			await $store.dispatch(`${Model.Interface}/setAnimationPause`, false);
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

	async #extractFilterData({ data, key }: { data: BookingDto[], key: string, date: Date }): Promise<void>
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

		void Core.getStore().dispatch(`${Model.Filter}/setFilteredBookingsIds`, extractor.getBookingsIds());
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
