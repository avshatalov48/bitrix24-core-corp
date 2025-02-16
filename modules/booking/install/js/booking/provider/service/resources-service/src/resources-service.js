import type { AjaxResponse } from 'main.core';
import { Core } from 'booking.core';
import { ApiClient } from 'booking.lib.api-client';
import { Model } from 'booking.const';
import type { ResourceModel } from 'booking.model.resources';
import { mapModelToDto, mapDtoToModel } from './mappers';

class ResourceService
{
	async add(resource: ResourceModel): Promise<AjaxResponse>
	{
		try
		{
			const resourceDto = mapModelToDto(resource);
			const data = await (new ApiClient()).post('Resource.add', { resource: resourceDto });
			const createdResource = mapDtoToModel(data);

			Core.getStore().commit('resources/upsert', createdResource);

			if (createdResource.isMain)
			{
				await Core.getStore().dispatch(`${Model.Favorites}/add`, createdResource.id);
			}

			if (Core.getStore().getters[`${Model.Interface}/canTurnOnTrial`])
			{
				void this.#turnOnTrial();
			}

			this.#updateResourcesFromFavorites();

			return data;
		}
		catch (error)
		{
			console.error('ResourceService: add error', error);

			return error;
		}
	}

	async update(resource: ResourceModel): Promise<AjaxResponse>
	{
		const id = resource.id;
		const resourceBeforeUpdate = { ...Core.getStore().getters['resources/getById'](id) };

		try
		{
			const resourceDto = mapModelToDto(resource);
			const data = await (new ApiClient()).post('Resource.update', { resource: resourceDto });
			const updatedResource = mapDtoToModel(data);

			Core.getStore().commit('resources/upsert', updatedResource);

			if (resourceBeforeUpdate.isMain && !updatedResource.isMain)
			{
				await Core.getStore().dispatch(`${Model.Favorites}/delete`, id);
			}

			this.#updateResourcesFromFavorites();

			return data;
		}
		catch (error)
		{
			console.error('ResourceService: update error', error);

			return error;
		}
	}

	#updateResourcesFromFavorites(): void
	{
		const isFilterMode = Core.getStore().getters[`${Model.Interface}/isFilterMode`];
		if (isFilterMode)
		{
			return;
		}

		const favorites = Core.getStore().getters[`${Model.Favorites}/get`];

		void Core.getStore().dispatch(`${Model.Interface}/setResourcesIds`, favorites);
	}

	async delete(resourceId: number): Promise<void>
	{
		try
		{
			await (new ApiClient()).post('Resource.delete', { id: resourceId });

			await Promise.all([
				Core.getStore().dispatch(`${Model.Resources}/delete`, resourceId),
				Core.getStore().dispatch(`${Model.Favorites}/delete`, resourceId),
				Core.getStore().dispatch(`${Model.Interface}/deleteResourceId`, resourceId),
			]);
		}
		catch (error)
		{
			console.error('ResourceService: delete error', error);
		}
	}

	async hasBookings(resourceId: number): Promise<?boolean>
	{
		try
		{
			return (new ApiClient()).post('Resource.hasBookings', { resourceId });
		}
		catch (error)
		{
			console.error('ResourceService: hasBookings error', error);
		}

		return Promise.resolve();
	}

	async #turnOnTrial(): Promise<void>
	{
		await Promise.all([
			Core.getStore().dispatch(`${Model.Interface}/setCanTurnOnTrial`, false),
			Core.getStore().dispatch(`${Model.Interface}/setIsFeatureEnabled`, true),
		]);

		await new Promise((resolve) => setTimeout(resolve, 2000));

		await Core.getStore().dispatch(`${Model.Interface}/setIsShownTrialPopup`, true);
	}
}

export const resourceService = new ResourceService();
