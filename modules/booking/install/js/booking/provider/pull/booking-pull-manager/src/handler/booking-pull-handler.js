import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { BookingMappers } from 'booking.provider.service.booking-service';
import { ClientMappers } from 'booking.provider.service.client-service';
import { ResourceMappers } from 'booking.provider.service.resources-service';
import { mainPageService } from 'booking.provider.service.main-page-service';
import type { BookingDto } from 'booking.provider.service.booking-service';
import type { ClientDto } from 'booking.provider.service.client-service';
import type { ClientModel } from 'booking.model.clients';
import type { ResourceDto } from 'booking.provider.service.resources-service';

import { BasePullHandler } from './base-pull-handler';

export class BookingPullHandler extends BasePullHandler
{
	constructor(props)
	{
		super(props);

		this.handleBookingAdded = this.#handleBookingAdded.bind(this);
		this.handleBookingDeleted = this.#handleBookingDeleted.bind(this);
		this.updateCounters = this.#updateCounters.bind(this);
	}

	getMap(): { [command: string]: Function }
	{
		return {
			bookingAdded: this.handleBookingAdded,
			bookingUpdated: this.handleBookingAdded,
			bookingDeleted: this.handleBookingDeleted,
		};
	}

	getDelayedMap(): { [command: string]: Function }
	{
		return {
			bookingAdded: this.updateCounters,
			bookingUpdated: this.updateCounters,
			bookingDeleted: this.updateCounters,
		};
	}

	#handleBookingAdded(params: { booking: BookingDto }): void
	{
		const bookingDto = params.booking;

		const booking = BookingMappers.mapDtoToModel(bookingDto);
		const resources = bookingDto.resources.map((resourceDto: ResourceDto) => {
			return ResourceMappers.mapDtoToModel(resourceDto);
		});
		const clients = bookingDto.clients.map((clientDto: ClientDto): ClientModel => {
			return ClientMappers.mapDtoToModel(clientDto);
		});

		void Promise.all([
			Core.getStore().dispatch('resources/upsertMany', resources),
			Core.getStore().dispatch('bookings/upsert', booking),
			Core.getStore().dispatch('clients/upsertMany', clients),
		]);
	}

	#handleBookingDeleted(params: { id: number }): void
	{
		void Core.getStore().dispatch(`${Model.Bookings}/delete`, params.id);
		void Core.getStore().dispatch(`${Model.Interface}/addDeletingBooking`, params.id);
	}

	async #updateCounters(): Promise<void>
	{
		await mainPageService.fetchCounters();
	}
}
