import { ClientMappers } from 'booking.provider.service.client-service';
import { ResourceMappers } from 'booking.provider.service.resources-service';
import type { BookingModel } from 'booking.model.bookings';
import type { ClientModel } from 'booking.model.clients';
import type { ClientDto } from 'booking.provider.service.client-service';
import type { ResourceModel } from 'booking.model.resources';
import type { ResourceDto } from 'booking.provider.service.resources-service';

import { mapDtoToModel } from './mappers';
import type { BookingDto } from './types';

export class BookingDataExtractor
{
	#response: BookingDto[];

	constructor(response: BookingDto[])
	{
		this.#response = response;
	}

	getBookings(): BookingModel[]
	{
		return this.#response.map((bookingDto: BookingDto): BookingModel => mapDtoToModel(bookingDto));
	}

	getBookingsIds(): number[]
	{
		return this.#response.map(({ id }) => id);
	}

	getClients(): ClientModel[]
	{
		return this.#response
			.flatMap(({ clients }) => clients)
			.map((clientDto: ClientDto): ClientModel => {
				return ClientMappers.mapDtoToModel(clientDto);
			})
		;
	}

	getResources(): ResourceModel[]
	{
		return this.#response
			.flatMap(({ resources }) => resources)
			.map((resourceDto: ResourceDto): ResourceModel => {
				return ResourceMappers.mapDtoToModel(resourceDto);
			})
		;
	}
}
