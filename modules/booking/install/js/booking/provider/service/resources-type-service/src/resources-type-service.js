import { Core } from 'booking.core';
import { ApiClient } from 'booking.lib.api-client';
import { ResourceTypeModel } from 'booking.model.resource-types';
import { mapModelToDto, mapDtoToModel } from './mappers';
import type { ResourceTypeDto } from './types';

class ResourceTypeService
{
	async add(resourceType: ResourceTypeModel): Promise<ResourceTypeModel>
	{
		let createdResourceType = null;

		try
		{
			const resourceDto: ResourceTypeDto = mapModelToDto(resourceType);
			const data = await (new ApiClient()).post(
				'ResourceType.add',
				{ resourceType: resourceDto },
			);
			createdResourceType = mapDtoToModel(data);

			void Core.getStore().dispatch('resourceTypes/upsert', createdResourceType);
		}
		catch (error)
		{
			console.error('ResourceTypeService: add error', error);
		}

		return createdResourceType;
	}

	async update(resourceType: ResourceTypeModel): Promise<void>
	{
		try
		{
			const resourceDto: ResourceTypeDto = mapModelToDto(resourceType);
			const data = await (new ApiClient()).post(
				'ResourceType.update',
				{ resourceType: resourceDto },
			);

			void Core.getStore().dispatch('resourceTypes/upsert', mapDtoToModel(data));
		}
		catch (error)
		{
			console.error('ResourceTypeService: update error', error);
		}
	}

	async delete(resourceTypeId: number): Promise<void>
	{
		return Promise.resolve();
	}
}

export const resourceTypeService = new ResourceTypeService();
