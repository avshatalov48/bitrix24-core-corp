import { ClientMappers } from 'booking.provider.service.client-service';
import type { ClientDto } from 'booking.provider.service.client-service';
import type { ClientModel } from 'booking.model.clients';
import type { WaitListItemModel } from 'booking.model.wait-list';

import { mapDtoToModel } from './mappers';
import type { WaitListItemDto } from './types';

export class WaitListDataExtractor
{
	#response: WaitListItemDto[];

	constructor(response: WaitListItemDto[])
	{
		this.#response = response;
	}

	getClients(): ClientModel[]
	{
		return this.#response
			.flatMap(({ clients }) => clients)
			.map((clientDto: ClientDto): ClientModel => {
				return ClientMappers.mapDtoToModel(clientDto);
			});
	}

	getWaitListItem(): WaitListItemModel
	{
		return mapDtoToModel(this.#response);
	}
}
