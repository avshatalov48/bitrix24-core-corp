import { Type } from 'main.core';
import type { WaitListItemModel } from 'booking.model.wait-list';
import type { WaitListItemDto } from './types';

export function mapModelToDto(waitListItem: WaitListItemModel): WaitListItemDto
{
	return {
		id: waitListItem.id,
		createdBy: waitListItem.createdBy,
		createdAt: waitListItem.createdAt / 1000,
		updatedAt: waitListItem.updatedAt / 1000,
		clients: waitListItem.clients,
		note: waitListItem.note,
		externalData: waitListItem.externalData,
	};
}

export function mapDtoToModel(waitListItemDto: WaitListItemDto): WaitListItemModel
{
	const clients = waitListItemDto.clients.filter((client) => Type.isArrayFilled(Object.values(client.data)));

	return {
		id: waitListItemDto.id,
		createdBy: waitListItemDto.createdBy,
		createdAt: waitListItemDto.createdAt * 1000,
		updatedAt: waitListItemDto.updatedAt * 1000,
		clients,
		note: waitListItemDto.note,
		externalData: waitListItemDto.externalData,
	};
}
