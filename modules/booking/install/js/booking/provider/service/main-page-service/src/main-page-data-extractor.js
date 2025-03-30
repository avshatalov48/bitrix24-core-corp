import { CrmEntity } from 'booking.const';
import { BookingMappers } from 'booking.provider.service.booking-service';
import { ClientMappers } from 'booking.provider.service.client-service';
import { ResourceMappers } from 'booking.provider.service.resources-service';
import { ResourceTypeMappers } from 'booking.provider.service.resources-type-service';

import type { BookingModel } from 'booking.model.bookings';
import type { BookingDto } from 'booking.provider.service.booking-service';
import type { CountersModel } from 'booking.model.counters';
import type { ClientModel } from 'booking.model.clients';
import type { ClientDto } from 'booking.provider.service.client-service';
import type { ResourceModel } from 'booking.model.resources';
import type { ResourceDto } from 'booking.provider.service.resources-service';
import type { ResourceTypeModel } from 'booking.model.resource-types';
import type { ResourceTypeDto } from 'booking.provider.service.resources-type-service';

import type { MainPageGetResponse } from './types';

export class MainPageDataExtractor
{
	#response: MainPageGetResponse;

	constructor(response: MainPageGetResponse)
	{
		this.#response = response;
	}

	getFavoriteIds(): number[]
	{
		return this.#response.favorites.resources.map((resource: ResourceDto) => resource.id);
	}

	getBookings(): BookingModel[]
	{
		return this.#response.bookings.map((booking: BookingDto): BookingModel => {
			return BookingMappers.mapDtoToModel(booking);
		});
	}

	getClientsProviderModuleId(): string
	{
		return this.#response.clients.providerModuleId;
	}

	getClients(): ClientModel[]
	{
		return [
			...this.#extractClients(CrmEntity.Contact),
			...this.#extractClients(CrmEntity.Company),
			...this.#extractClientsFromBookings(),
		];
	}

	#extractClients(code: string): ClientModel[]
	{
		const module = this.#response.clients.providerModuleId;
		if (!module)
		{
			return [];
		}

		return Object.values(this.#response.clients.recent[code]).map((client): ClientModel => ({
			...client,
			type: { module, code },
		}));
	}

	#extractClientsFromBookings(): ClientModel[]
	{
		return this.#response.bookings
			.flatMap(({ clients }) => clients.map((client: ClientDto): ClientModel => {
				return ClientMappers.mapDtoToModel(client);
			}));
	}

	getCounters(): CountersModel
	{
		return this.#response.counters;
	}

	getResources(): ResourceModel[]
	{
		const favoriteResources = this.#response.favorites?.resources ?? [];
		const bookingResources = this.#response.bookings.flatMap(({ resources }) => resources);

		const result = {};
		[...favoriteResources, ...bookingResources].forEach((resourceDto: ResourceDto) => {
			result[resourceDto.id] ??= ResourceMappers.mapDtoToModel(resourceDto);
		});

		return Object.values(result);
	}

	getResourceTypes(): ResourceTypeModel[]
	{
		return this.#response.resourceTypes.map((resourceTypeDto: ResourceTypeDto): ResourceTypeModel => {
			return ResourceTypeMappers.mapDtoToModel(resourceTypeDto);
		});
	}

	getIntersectionMode(): boolean
	{
		return this.#response.isIntersectionForAll;
	}

	getIsCurrentSenderAvailable(): boolean
	{
		return this.#response.isCurrentSenderAvailable;
	}
}
