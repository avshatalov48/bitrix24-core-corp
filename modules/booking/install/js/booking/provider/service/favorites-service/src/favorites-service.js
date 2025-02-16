import { Model } from 'booking.const';
import { Type } from 'main.core';
import { Core } from 'booking.core';
import { ApiClient } from 'booking.lib.api-client';

class FavoritesService
{
	async set(favoritesIds: number[]): Promise<void>
	{
		const currentIds = Core.getStore().getters['favorites/get'];
		const favoritesAdded = favoritesIds.filter((it) => !currentIds.includes(it));
		const favoritesDeleted = currentIds.filter((it) => !favoritesIds.includes(it));

		void Core.getStore().dispatch(`${Model.Favorites}/set`, favoritesIds);

		if (Type.isArrayFilled(favoritesAdded))
		{
			await favoritesService.add(favoritesAdded);
		}

		if (Type.isArrayFilled(favoritesDeleted))
		{
			await favoritesService.delete(favoritesDeleted);
		}
	}

	async add(favoritesIds: number[]): Promise<void>
	{
		if (!Type.isArrayFilled(favoritesIds))
		{
			return;
		}

		try
		{
			await (new ApiClient()).post('Favorites.add', { resourcesIds: favoritesIds });
		}
		catch (error)
		{
			console.error('FavoritesService: add error', error);
		}
	}

	async delete(favoritesIds: number[]): Promise<void>
	{
		if (!Type.isArrayFilled(favoritesIds))
		{
			return;
		}

		try
		{
			await (new ApiClient()).post('Favorites.delete', { resourcesIds: favoritesIds });
		}
		catch (error)
		{
			console.error('FavoritesService: delete error', error);
		}
	}
}

export const favoritesService = new FavoritesService();
