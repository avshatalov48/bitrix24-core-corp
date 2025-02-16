import { BookingMappers } from 'booking.provider.service.booking-service';
import { ClientMappers } from 'booking.provider.service.client-service';
import { ResourceMappers } from 'booking.provider.service.resources-service';

import type { BookingModel } from 'booking.model.bookings';
import type { BookingDto } from 'booking.provider.service.booking-service';
import type { ClientModel } from 'booking.model.clients';
import type { ClientDto } from 'booking.provider.service.client-service';
import type { ResourceModel } from 'booking.model.resources';
import type { ResourceDto } from 'booking.provider.service.resources-service';

import { ResourceDialogResponse } from './types';

export class ResourceDialogDataExtractor
{
	#response: ResourceDialogResponse;

	constructor(response: ResourceDialogResponse)
	{
		this.#response = response;
	}

	getBookings(): BookingModel[]
	{
		return this.#response.bookings.map((booking: BookingDto): BookingModel => {
			return BookingMappers.mapDtoToModel(booking);
		});
	}

	getClients(): ClientModel[]
	{
		return this.#response.bookings
			.flatMap(({ clients }) => clients.map((client: ClientDto): ClientModel => {
				return ClientMappers.mapDtoToModel(client);
			}))
		;
	}

	getResources(): ResourceModel[]
	{
		return this.#response.resources.map((resource: ResourceDto): ResourceModel => {
			return ResourceMappers.mapDtoToModel(resource);
		});
	}
}
