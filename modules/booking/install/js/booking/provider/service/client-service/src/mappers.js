import { Type } from 'main.core';
import type { ClientModel } from 'booking.model.clients';
import type { ClientDto } from './types';

export function mapDtoToModel(clientDto: ClientDto): ClientModel | null
{
	if (!Type.isArrayFilled(Object.values(clientDto.data)))
	{
		return null;
	}

	return {
		id: clientDto.id,
		name: clientDto.data.name,
		image: clientDto.data.image,
		type: clientDto.type,
		phones: clientDto.data.phones,
		emails: clientDto.data.emails,
		isReturning: clientDto.isReturning,
	};
}
