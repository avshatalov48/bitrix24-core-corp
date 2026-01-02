import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { ApiClient } from 'booking.lib.api-client';
import { mainPageService } from 'booking.provider.service.main-page-service';
import type { WaitListItemModel } from 'booking.model.wait-list';

import { WaitListDataExtractor } from './wait-list-data-extractor';
import { mapModelToDto, mapDtoToModel } from './mappers';
import type { WaitListItemDto } from './types';

class WaitListService
{
	async getById(id: number): Promise<void>
	{
		try
		{
			const data: WaitListItemDto = await new ApiClient().post('WaitListItem.get', { id });
			const extractor = new WaitListDataExtractor(data);
			await Core.getStore().dispatch(`${Model.WaitList}/upsert`, extractor.getWaitListItem());
		}
		catch (error)
		{
			console.error('WaitListService. getById error', error);
		}
	}

	async add(waitListItem: WaitListItemModel): Promise<{ success: boolean, waitListItem: ?WaitListItemModel }>
	{
		const id = waitListItem.id;
		const $store = Core.getStore();

		try
		{
			await $store.dispatch(`${Model.WaitList}/add`, waitListItem);

			const waitListItemDto = mapModelToDto(waitListItem);
			const data = await (new ApiClient()).post('WaitListItem.add', { waitListItem: waitListItemDto });
			const createdWaitListItem = mapDtoToModel(data);

			await $store.dispatch(`${Model.Interface}/setAnimationPause`, true);
			await $store.dispatch(`${Model.WaitList}/update`, {
				id,
				waitListItem: createdWaitListItem,
			});

			void mainPageService.fetchCounters();

			return {
				success: true,
				waitListItem: createdWaitListItem,
			};
		}
		catch (error)
		{
			void $store.dispatch(`${Model.WaitList}/delete`, id);

			console.error('WaitListService: add error', error);

			return {
				success: false,
			};
		}
		finally
		{
			await $store.dispatch(`${Model.Interface}/setAnimationPause`, false);
		}
	}

	async createFromBooking(bookingId: number, waitListItem: WaitListItemModel): Promise<{
		success: boolean,
		waitListItem: ?WaitListItemModel
	}>
	{
		const id = waitListItem.id;
		const $store = Core.getStore();

		try
		{
			if ($store.getters[`${Model.Interface}/isBookingCreatedFromEmbed`](bookingId))
			{
				await $store.dispatch(`${Model.Interface}/addCreatedFromEmbedWaitListItem`, bookingId);
			}

			await $store.dispatch(`${Model.WaitList}/add`, waitListItem);

			const data = await (new ApiClient()).post('WaitListItem.createFromBooking', { bookingId });
			const createdWaitListItem = mapDtoToModel(data);

			await $store.dispatch(`${Model.Interface}/setAnimationPause`, true);
			await Promise.all([
				$store.dispatch(`${Model.WaitList}/update`, {
					id,
					waitListItem: createdWaitListItem,
				}),
				$store.dispatch(`${Model.Interface}/addCreatedFromEmbedWaitListItem`, createdWaitListItem.id),
			]);

			void mainPageService.fetchCounters();

			return {
				success: true,
				waitListItem: createdWaitListItem,
			};
		}
		catch (error)
		{
			void $store.dispatch(`${Model.WaitList}/delete`, id);

			console.error('WaitListService: createFromBooking error', error);

			return {
				success: false,
			};
		}
		finally
		{
			await $store.dispatch(`${Model.Interface}/setAnimationPause`, false);
		}
	}

	async update(waitListItem: WaitListItemModel): Promise<void>
	{
		const id = waitListItem.id;
		const waitListItemBeforeUpdate = {
			...Core.getStore().getters[`${Model.WaitList}/getById`](id),
		};

		try
		{
			await Core.getStore()
				.dispatch(`${Model.WaitList}/update`, { id, waitListItem });

			const waitListItemDto = mapModelToDto(waitListItem);
			const data = await new ApiClient().post('WaitListItem.update', { waitListItem: waitListItemDto });
			const updatedWaitListItem = mapDtoToModel(data);

			await Core.getStore().dispatch(`${Model.WaitList}/update`, {
				id,
				waitListItem: updatedWaitListItem,
			});

			const clients = new WaitListDataExtractor([data]).getClients();
			await Core.getStore().dispatch(`${Model.Clients}/upsertMany`, clients);

			void mainPageService.fetchCounters();
		}
		catch (error)
		{
			void Core.getStore().dispatch(`${Model.WaitList}/update`, {
				id,
				waitListItem: waitListItemBeforeUpdate,
			});

			console.error('WaitListService: update error', error);
		}
	}

	async delete(id: number): Promise<void>
	{
		const $store = Core.getStore();
		const waitListItem = { ...$store.getters[`${Model.WaitList}/getById`](id) };

		try
		{
			await $store.dispatch(`${Model.WaitList}/delete`, id);
			await new ApiClient().post('WaitListItem.delete', { id });
			await this.#onAfterDelete(id);
		}
		catch (error)
		{
			await $store.dispatch(`${Model.WaitList}/upsert`, waitListItem);
			console.error('WaitListItemService. delete error', error);
		}
	}

	async deleteList(ids: number[]): Promise<void>
	{
		const $store = Core.getStore();
		const collection = $store.state[Model.WaitList].collection;
		const waitListItems = ids
			.map((id) => {
				return (id in collection) ? { ...collection[id] } : null;
			})
			.filter((item) => item !== null);

		try
		{
			await $store.dispatch(`${Model.WaitList}/deleteMany`, ids);
			await new ApiClient().post('WaitListItem.deleteList', { ids });
			await Promise.all(ids.map((id) => this.#onAfterDelete(id)));
		}
		catch (error)
		{
			await $store.dispatch(`${Model.WaitList}/upsertMany`, waitListItems);
			console.error('WaitListService. Delete list error', error);
		}
	}

	async #onAfterDelete(id: number): Promise<void>
	{
		const $store = Core.getStore();
		const editingWaitListItemId = $store.getters[`${Model.Interface}/editingWaitListItemId`];
		if (id === editingWaitListItemId)
		{
			await $store.dispatch(`${Model.Interface}/setEditingWaitListItemId`, 0);
		}

		await $store.dispatch(`${Model.Interface}/addDeletingWaitListItemId`, id);
	}
}

export const waitListService = new WaitListService();
