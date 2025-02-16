import { Type } from 'main.core';

import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { resourcesDateCache } from 'booking.lib.resources-date-cache';
import { ApiClient } from 'booking.lib.api-client';
import type { ResourceDto } from 'booking.provider.service.resources-service';

import { ResourceDialogDataExtractor } from './resource-dialog-data-extractor';
import { MainResourcesExtractor } from './main-resources-extractor';
import type { ResourceDialogResponse } from './types';

class ResourceDialogService
{
	#queryCache = [];
	#loadByIdsPromises = {};
	#mainResourcesCache: Promise | null = null;

	async loadByIds(idsToLoad: number[], dateTs: number): Promise<void>
	{
		try
		{
			this.#loadByIdsPromises[dateTs] ??= {};

			const requestedIds = Object.keys(this.#loadByIdsPromises[dateTs])
				.flatMap((key) => key.split(','))
				.map((id) => Number(id))
			;

			const requestedIdsSet = new Set(requestedIds);

			const ids = idsToLoad.filter((id) => !requestedIdsSet.has(id));
			if (!Type.isArrayFilled(ids))
			{
				await Promise.all(Object.values(this.#loadByIdsPromises[dateTs]));

				return;
			}

			const idsKey = ids.join(',');

			this.#loadByIdsPromises[dateTs][idsKey] = this.#requestLoadByIds(ids, dateTs);

			const data = await this.#loadByIdsPromises[dateTs][idsKey];

			await this.#upsertResponseData(data, dateTs);
		}
		catch (error)
		{
			console.error('ResourceDialogLoadByIdsRequest: error', error);
		}
	}

	async #requestLoadByIds(ids: number[], dateTs: number): Promise<void>
	{
		return new ApiClient().post('ResourceDialog.loadByIds', { ids, dateTs });
	}

	async fillDialog(dateTs: number): Promise<void>
	{
		try
		{
			const data = await new ApiClient().post('ResourceDialog.fillDialog', { dateTs });

			await this.#upsertResponseData(data, dateTs);
		}
		catch (error)
		{
			console.error('ResourceDialogFillDialogRequest: error', error);
		}
	}

	async doSearch(query: string, dateTs: number): Promise<void>
	{
		if (!Type.isStringFilled(query))
		{
			return;
		}

		if (this.#isQueryLoaded(query))
		{
			return;
		}

		this.#queryCache.push(query);

		try
		{
			const data = await new ApiClient().post('ResourceDialog.doSearch', { query, dateTs });

			await this.#upsertResponseData(data, dateTs);
		}
		catch (error)
		{
			console.error('ResourceDialogDoSearchRequest: error', error);
		}
	}

	async #upsertResponseData(data: ResourceDialogResponse, dateTs: number): Promise<void>
	{
		const extractor = new ResourceDialogDataExtractor(data);

		resourcesDateCache.upsertIds(dateTs, extractor.getResources().map((it) => it.id));

		await Promise.all([
			Core.getStore().dispatch('bookings/upsertMany', extractor.getBookings()),
			Core.getStore().dispatch('clients/upsertMany', extractor.getClients()),
			Core.getStore().dispatch('resources/upsertMany', extractor.getResources()),
		]);
	}

	#isQueryLoaded(query: string): boolean
	{
		return this.#queryCache.some((it) => query.startsWith(it));
	}

	async getMainResources()
	{
		try
		{
			if (Type.isNull(this.#mainResourcesCache))
			{
				this.#mainResourcesCache = this.#requestGetMainResources();
			}
			const data: ResourceDto[] = await this.#mainResourcesCache;
			const extractor = new MainResourcesExtractor(data);

			const ids = extractor.getMainResourceIds();
			await Core.getStore().dispatch(`${Model.MainResources}/setMainResources`, ids);
		}
		catch (error)
		{
			console.error('ResourceDialogGetMainResources: error', error);
		}
	}

	#requestGetMainResources(): Promise<ResourceDto[]>
	{
		const api = new ApiClient();

		return api.post('ResourceDialog.getMainResources', {});
	}

	clearMainResourcesCache(): void
	{
		this.#mainResourcesCache = null;
	}
}

export const resourceDialogService = new ResourceDialogService();
