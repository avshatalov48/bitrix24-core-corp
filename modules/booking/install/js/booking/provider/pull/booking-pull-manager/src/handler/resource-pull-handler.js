import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { ResourceMappers } from 'booking.provider.service.resources-service';
import type { ResourceDto } from 'booking.provider.service.resources-service';

import { BasePullHandler } from './base-pull-handler';

export class ResourcePullHandler extends BasePullHandler
{
	getMap(): { [command: string]: Function }
	{
		return {
			resourceAdded: this.#handleResourceAdded.bind(this),
			resourceUpdated: this.#handleResourceUpdated.bind(this),
			resourceDeleted: this.#handleResourceDeleted.bind(this),
		};
	}

	async #handleResourceAdded(params: { resource: ResourceDto }): Promise<void>
	{
		const resourceDto = params.resource;
		const resource = ResourceMappers.mapDtoToModel(resourceDto);

		await Core.getStore().dispatch(`${Model.Resources}/upsert`, resource);

		if (resource.isMain)
		{
			await Core.getStore().dispatch(`${Model.Favorites}/addMany`, [resource.id]);
		}

		const isFilterMode = Core.getStore().getters[`${Model.Interface}/isFilterMode`];
		if (isFilterMode)
		{
			return;
		}

		const favorites = Core.getStore().getters[`${Model.Favorites}/get`];

		void Core.getStore().dispatch(`${Model.Interface}/setResourcesIds`, favorites);
	}

	#handleResourceUpdated(params: { resource: ResourceDto }): void
	{
		const resourceDto = params.resource;
		const resource = ResourceMappers.mapDtoToModel(resourceDto);

		void Core.getStore().dispatch(`${Model.Resources}/upsert`, resource);
	}

	#handleResourceDeleted(params: { id: number }): void
	{
		void Promise.all([
			Core.getStore().dispatch(`${Model.Resources}/delete`, params.id),
			Core.getStore().dispatch(`${Model.Favorites}/delete`, params.id),
			Core.getStore().dispatch(`${Model.Interface}/deleteResourceId`, params.id),
		]);
	}
}
