import { Core } from 'booking.core';
import { CrmEntity, Model } from 'booking.const';
import { waitListService } from 'booking.provider.service.wait-list-service';
import type { DealData } from 'booking.model.booking';
import type { WaitListItemModel } from 'booking.model.wait-list';

import { DealHelper } from './deal-helper';
import type { CreateCrmDealLoadParams } from './types';

export class WaitListDealHelper extends DealHelper
{
	#waitListItemId: number;

	constructor(waitListItemId: number)
	{
		super();
		this.#waitListItemId = waitListItemId;
	}

	hasDeal(): boolean
	{
		return Boolean(this.#deal);
	}

	get #deal(): DealData | null
	{
		return this.#waitListItem?.externalData?.find((data) => data.entityTypeId === CrmEntity.Deal) || null;
	}

	get #waitListItem(): WaitListItemModel
	{
		return Core.getStore().getters[`${Model.WaitList}/getById`](this.#waitListItemId);
	}

	openDeal(): void
	{
		super.openDealSidePanel({
			deal: this.#deal,
			onClose: async (deal: DealData | null): void => {
				if (deal?.value)
				{
					await waitListService.getById(this.#waitListItemId);
				}
			},
		});
	}

	createDeal(): void
	{
		const itemIdQueryParamName = 'waitListItemId';

		super.createCrmDeal({
			itemIdQueryParamName,
			itemId: this.#waitListItemId,
			clients: this.#waitListItem?.clients || [],
			onLoad: async (data: CreateCrmDealLoadParams): Promise<void> => {
				if (!data.isDeal || this.#waitListItemId !== data.itemIdFromQuery)
				{
					return;
				}

				await this.saveDeal(data.dealData);
			},
			onClose: async (): Promise<void> => {
				if (this.#deal?.value)
				{
					await this.saveDeal(this.#deal);
				}
			},
		});
	}

	async saveDeal(dealData: DealData | null): Promise<void>
	{
		const externalData = dealData ? [dealData] : [];

		await waitListService.update({
			id: this.#waitListItemId,
			externalData,
		});
	}
}
