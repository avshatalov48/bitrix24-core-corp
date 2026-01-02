import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { ClientMappers } from 'booking.provider.service.client-service';
import { mainPageService } from 'booking.provider.service.main-page-service';
import { WaitListMappers } from 'booking.provider.service.wait-list-service';
import type { ClientModel } from 'booking.model.clients';
import type { ClientDto } from 'booking.provider.service.client-service';
import type { WaitListItemDto } from 'booking.provider.service.wait-list-service';

import { BasePullHandler } from './base-pull-handler';

export class WaitListItemPullHandler extends BasePullHandler
{
	#delayTimeout: number | null = null;

	constructor(props)
	{
		super(props);

		this.handleWaitListItemAdded = this.#handleWaitListItemAdded.bind(this);
		this.handleWaitListItemDeleted = this.#handleWaitListItemDeleted.bind(this);
		this.updateCounters = this.#updateCounters.bind(this);
	}

	getMap(): { [command: string]: Function }
	{
		return {
			waitListItemAdded: this.handleWaitListItemAdded,
			waitListItemDeleted: this.handleWaitListItemDeleted,
		};
	}

	getDelayedMap(): { [command: string]: Function }
	{
		return {
			waitListItemAdded: this.updateCounters,
			waitListItemDeleted: this.updateCounters,
		};
	}

	#handleWaitListItemAdded(params: { waitListItem: WaitListItemDto }): void
	{
		const waitListItemDto = params.waitListItem;

		const waitListItem = WaitListMappers.mapDtoToModel(waitListItemDto);
		const clients = waitListItemDto.clients.map((clientDto: ClientDto): ClientModel => {
			return ClientMappers.mapDtoToModel(clientDto);
		});

		const $store = Core.getStore();
		void Promise.all([
			$store.dispatch(`${Model.WaitList}/upsert`, waitListItem),
			$store.dispatch(`${Model.Clients}/upsertMany`, clients),
		]);
	}

	#handleWaitListItemDeleted(params: { id: number }): void
	{
		const $store = Core.getStore();
		void $store.dispatch(`${Model.WaitList}/delete`, params.id);
		void $store.commit(`${Model.Interface}/addDeletingWaitListItemId`, params.id);
	}

	async #updateCounters(): Promise<void>
	{
		if (this.#delayTimeout)
		{
			return;
		}

		this.#delayTimeout = setTimeout(async () => {
			try
			{
				await mainPageService.fetchCounters();
			}
			finally
			{
				this.#delayTimeout = null;
			}
		}, 0);
	}
}
