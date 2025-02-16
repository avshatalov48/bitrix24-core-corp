import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { mainPageService } from 'booking.provider.service.main-page-service';
import { favoritesService } from 'booking.provider.service.favorites-service';

export async function hideResources(ids: number[]): Promise<void>
{
	const store = Core.getStore();

	await store.dispatch(`${Model.Interface}/setResourcesIds`, ids);

	mainPageService.clearCache(ids);

	if (store.getters[`${Model.Interface}/isEditingBookingMode`])
	{
		await store.dispatch(`${Model.Favorites}/set`, ids);

		return;
	}

	await favoritesService.set(ids);
}
