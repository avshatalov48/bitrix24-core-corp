import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { ResourceTypeMappers } from 'booking.provider.service.resources-type-service';
import type { ResourceTypeDto } from 'booking.provider.service.resources-type-service';

import { BasePullHandler } from './base-pull-handler';

export class ResourceTypePullHandler extends BasePullHandler
{
	getMap(): { [command: string]: Function }
	{
		return {
			resourceTypeAdded: this.#handleResourceTypeAdded.bind(this),
		};
	}

	#handleResourceTypeAdded(params: { resourceType: ResourceTypeDto }): void
	{
		const resourceTypeDto = params.resourceType;
		const resourceType = ResourceTypeMappers.mapDtoToModel(resourceTypeDto);

		void Core.getStore().dispatch(`${Model.ResourceTypes}/upsert`, resourceType);
	}
}
