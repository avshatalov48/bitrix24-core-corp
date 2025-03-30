import { Uri } from 'main.core';
import { SidePanel } from 'main.sidepanel';

import { Core } from 'booking.core';
import { CrmEntity, Model, Module } from 'booking.const';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel, DealData } from 'booking.model.bookings';
import type { ClientData } from 'booking.model.clients';

export class DealHelper
{
	#bookingId: number;

	constructor(bookingId: number)
	{
		this.#bookingId = bookingId;
	}

	hasDeal(): boolean
	{
		return Boolean(this.#deal);
	}

	openDeal(): void
	{
		SidePanel.Instance.open(`/crm/deal/details/${this.#deal.value}/`, {
			events: {
				onClose: () => {
					if (this.#deal?.value)
					{
						void bookingService.getById(this.#bookingId);
					}
				},
			},
		});
	}

	createDeal(): void
	{
		const bookingIdParamName = 'bookingId';

		const createDealUrl = new Uri('/crm/deal/details/0/');
		createDealUrl.setQueryParam(bookingIdParamName, this.#bookingId);

		(this.#booking.clients ?? []).forEach((client: ClientData) => {
			const paramName = {
				[CrmEntity.Contact]: 'contact_id',
				[CrmEntity.Company]: 'company_id',
			}[client.type.code];

			createDealUrl.setQueryParam(paramName, client.id);
		});

		SidePanel.Instance.open(createDealUrl.toString(), {
			events: {
				onLoad: ({ slider }) => {
					slider.getWindow().BX.Event.EventEmitter.subscribe('onCrmEntityCreate', (event) => {
						const [data] = event.getData();

						const isDeal = data.entityTypeName === CrmEntity.Deal;
						const bookingId = Number(new Uri(data.sliderUrl).getQueryParam(bookingIdParamName));
						if (!isDeal || bookingId !== this.#bookingId)
						{
							return;
						}

						const dealData = this.mapEntityInfoToDeal(data.entityInfo);

						this.saveDeal(dealData);
					});
				},
				onClose: () => {
					if (this.#deal?.value)
					{
						this.saveDeal(this.#deal);
					}
				},
			},
		});
	}

	mapEntityInfoToDeal(info: Object): DealData
	{
		return {
			moduleId: Module.Crm,
			entityTypeId: info.typeName,
			value: info.id,
			data: [],
		};
	}

	saveDeal(dealData: DealData | null): void
	{
		const externalData = dealData ? [dealData] : [];

		void bookingService.update({
			id: this.#bookingId,
			externalData,
		});
	}

	get #deal(): DealData | null
	{
		return this.#booking.externalData?.find((data) => data.entityTypeId === CrmEntity.Deal) ?? null;
	}

	get #booking(): BookingModel
	{
		return Core.getStore().getters[`${Model.Bookings}/getById`](this.#bookingId);
	}
}
